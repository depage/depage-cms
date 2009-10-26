<?php
/**
 * @file    lib_tasks.php
 *
 * Background Tasks Library
 *
 * This file defines an interface for performing, logging,
 * and viewing different asks, which ae executed in a 
 * second or third php process. this process may also be
 * executed at a lower priority than normal tasks. This library
 * needs access to the cli-version of php.
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 *
 * $Id: lib_tasks.php,v 1.31 2004/11/12 19:45:31 jonas Exp $
 */

// {{{ define and require
if (!function_exists('die_error')) require_once('lib_global.php');
require_once('lib_auth.php');
require_once('lib_tpl.php');
require_once('lib_pocket_server.php');
require_once('Mail.php');
// }}}

/**
 * provides access to start and control background tasks.
 */
class bgTasks_control {
    // {{{ constructor
    /**
     * constructor, sets the database tables, in which the task lists
     * are saved. it also sets a pocketClient object to send informations
     * about actual performing tasks.
     *
     * @public
     *
     * @param    $taskTable (string) db table with task informations
     * @param    $threadTable (string) db table with thread informations
     */
    function bgTasks_control($taskTable, $threadTable) {
        global $conf;
        
        $this->taskTable = $taskTable;
        $this->threadTable = $threadTable;
        $this->timeOut = 1;
        $this->sendProgressTimeOut = 1;
        $this->pocket_client = new PocketClient('127.0.0.1', $conf->pocket_port);
        $this->tasks = array();
    }
    // }}}
    // {{{ control_msg()
    /**
     * logs and/or prints out a message to console
     *
     * @public
     *
     * @param    $message (string) message to log/print
     */
    function control_msg($message) {
        global $conf, $log;
        
        $log->add_entry($message, 'task');
        if (php_sapi_name() == 'cli') {
            echo('[' . $conf->dateUTC($conf->date_format_UTC) . '] ' . $message . "\n");
        }
    }
    // }}}
    // {{{ get_task_control()
    /**
     * gets a task object from db by id
     *
     * @public
     *
     * @param    $id (int) id of task
     */
    function get_task_control($id) {
        global $conf;
        
        if (!isset($this->tasks[$id])) {
            $this->tasks[$id] = new bgTasks_task($conf->db_table_tasks, $conf->db_table_tasks_threads);
            $this->tasks[$id]->load_by_id($id);
            $this->tasks[$id]->started_time = getmicrotime() - 10;
        }
        return $this->tasks[$id];
    }
    // }}}
    // {{{ handle_tasks()
    /**
     * starts planned tasks, controls active tasks, and sends informations about 
     * it to the clients and cleans up finished tasks. this function will be 
     * called from pocketServer in every listening loop. and through this server
     * its sends out the informations about current tasks.
     *
     * @public
     *
     * @param    $pocketServerObj (ref) reference to running pocketServer object.
     */
    function handle_tasks($pocketServerObj) {
        global $log;

        $active_tasks = $this->get_active_tasks();
        $planned_tasks = $this->get_planned_tasks();
        $finished_tasks = $this->get_finished_tasks();
        if (count($finished_tasks) > 0) {
            for ($i = 0; $i < count($finished_tasks); $i++) {
                $task = $this->get_task_control($finished_tasks[$i]['id']);
                $this->control_msg('finished task "' . $finished_tasks[$i]['name'] . '" with status "' . $task->get_status() . '"');
                $this->send_status($pocketServerObj, $finished_tasks[$i], true);
                $task->remove();
            }
        }
        
        for ($i = 0; $i < count($planned_tasks); $i++) {
            $is_active = false;
            if ($planned_tasks[$i]['depends_on'] != '') {
                for ($j = 0; $j < count($active_tasks); $j++) {
                    if ($planned_tasks[$i]['depends_on'] == $active_tasks[$j]['depends_on']) {
                        $is_active = true;
                        break;
                    }
                }
            }
            if (!$is_active) {
                $this->do_task($planned_tasks[$i]['id']);
                $this->control_msg('starting task "' . $planned_tasks[$i]['name'] . '"');
                
                $active_tasks[] = $planned_tasks[$i];
            }
        }
        
        if (count($active_tasks) > 0) {
            for ($i = 0; $i < count($active_tasks); $i++) {
                $this->send_status($pocketServerObj, $active_tasks[$i]);
            }
            return false;
        } else {
            return true;
        }
    }
    // }}}
    // {{{ send_status()
    /**
     * send status about current tasks through pocketServer to clients
     *
     * @public
     *
     * @param    $pocketServerObj (ref) reference to running pocketServer object
     * @param    $actualtask (array) array with informations about current task
     * @param    $finished (bool) true if tasks has been finished, false otherwise
     *
     * @return    $task (object) task object
     */
    function send_status($pocketServerObj, $actualtask, $finished = false) {
        global $conf, $project;
        
        if (strlen($actualtask['depends_on']) == 0) {
            $depender = '';
        } else {
            $depender = substr($actualtask['depends_on'], 0, strpos($actualtask['depends_on'], ' '));
            $depends_on = substr($actualtask['depends_on'], strpos($actualtask['depends_on'], '[') + 1, -1);
        }
        $task = $this->get_task_control($actualtask['id']);
        
        $progress = $task->get_progress();
        if ($finished) {
            $progress["percent"] = 100;
        }
        $runningTasksXML = "<task name=\"" . htmlspecialchars($actualtask["name"]) . "\" "
            . "id=\"" . $actualtask["id"] . "\" "
            . "progress_percent=\"" . $progress["percent"] . "\" "
            . "time_from_start=\"" . $progress["time_from_start"] . "\" "
            . "time_at_all=\"" . $progress["time_at_all"] . "\" "
            . "time_until_end=\"" . $progress["time_until_end"] . "\" "
            . "description=\"" . htmlspecialchars($progress["description"]) . "\" "
            . "/>";
        
        
        
        $func = new ttRpcFunc('set_active_tasks_status', array('status' => $runningTasksXML));
        $message = $func->create_msg_func();
        
        if ($depender == '') {
            if (is_object($pocketServerObj)) {
                $pocketServerObj->msgHandler->funcObj->send_message_to_clients(array('serverObj' => $pocketServerObj, 'message' => $message));
            }
        } else if ($depender == 'project') {
            $users = $project->user->get_loggedin_nonpocket();
            foreach ($users as $act_sid => $act_project) {
                if ($depends_on == $act_project) {
                    $project->user->add_update($act_sid, $message);
                }
            }

            if (is_object($pocketServerObj)) {
                $pocketServerObj->msgHandler->funcObj->send_message_to_clients(array('serverObj' => $pocketServerObj, 'message' => $message, 'project' => $depends_on));
            }
        } else if ($depender == 'user') {
            
        }
        
        return $task;
    }
    // }}}
    // {{{ get_num_tasks()
    /**
     * gets number of planned and active tasks
     *
     * @public
     * 
     * @param    num (int) number of tasks
     */
    function get_num_tasks() {
        $result = db_query(
            "SELECT COUNT(*) AS num 
            FROM $this->taskTable"
        );
        $row = mysql_fetch_assoc($result);
        //mysql_free_result($result);
        
        return $row['num'];
    }
    // }}}
    // {{{ get_tasks()
    /**
     * gets all tasks
     *
     * @public
     *
     * @return    $ids (array) ids and other information of planned tasks
     */
    function get_tasks() {
        $ids = array();
        
        $result = db_query(
            "SELECT name, id, depends_on 
            FROM $this->taskTable"
        );
        if ($result) {
            $num = mysql_num_rows($result);
            for ($i = 0; $i < $num; $i++) {
                $row = mysql_fetch_assoc($result);
                $ids[] = $row;
            }
        }
        //mysql_free_result($result);
        
        return $ids;
    }
    // }}}
    // {{{ get_planned_tasks()
    /**
     * gets planned tasks, which should be performed now
     *
     * @public
     *
     * @return    $ids (array) ids and other information of planned tasks
     */
    function get_planned_tasks() {
        $ids = array();
        
        $result = db_query(
            "SELECT name, id, depends_on 
            FROM $this->taskTable 
            WHERE status='planned' AND start_time < NOW()"
        );
        if ($result) {
            $num = mysql_num_rows($result);
            for ($i = 0; $i < $num; $i++) {
                $row = mysql_fetch_assoc($result);
                $ids[] = $row;
            }
        }
        //mysql_free_result($result);
        
        return $ids;
    }
    // }}}
    // {{{ get_active_tasks()
    /**
     * gets active tasks
     *
     * @public
     *
     * @return    $ids (array) ids an other information of active tasks
     */
    function get_active_tasks() {
        $ids = array();
        
        $result = db_query(
            "SELECT name, id, depends_on 
            FROM $this->taskTable 
            WHERE status='active' AND start_time < NOW()"
        );
        if ($result) {
            $num = mysql_num_rows($result);
            for ($i = 0; $i < $num; $i++) {
                $row = mysql_fetch_assoc($result);
                $ids[] = $row;
            }
        }
        //mysql_free_result($result);
        
        return $ids;
    }
    // }}}
    // {{{ get_finished_tasks()
    /**
     * gets finished tasks
     *
     * @public
     *
     * @return    $ids (array) ids and other information of finished tasks
     */
    function get_finished_tasks() {
        $ids = array();
        
        $result = db_query(
            "SELECT name, id, depends_on 
            FROM $this->taskTable 
            WHERE status='finished' OR status='error'"
        );
        if ($result) {
            $num = mysql_num_rows($result);
            for ($i = 0; $i < $num; $i++) {
                $row = mysql_fetch_assoc($result);
                $ids[] = $row;
            }
        }
        //mysql_free_result($result);
        
        return $ids;
    }
    // }}}
    // {{{ get_aborted_tasks()
    /**
     * gets aborted tasks
     *
     * @public
     * 
     * @todo    implement this function and the functions to abort
     *            and finish task during processing.
     */
    function get_aborted_tasks() {
        
    }
    // }}}
    // {{{ do_task()
    /**
     * starts task handling script (task_do.php) to perform a task
     * in background.
     *
     * @public
     *
     * @param    $id (int) id of task to perform
     */
    function do_task($id) {
        global $conf;    
        
        $task = $this->get_task_control($id);
        $task->set_status("wait_for_start");
                    
        $conf->execInBackground($conf->path_server_root . $conf->path_base . "framework/", "task_do.php", $id, true);
    }
    // }}}
}

/**
 * handles or creates a task
 */
class bgTasks_task {
    // {{{ constructor
    /**
     * constructor, sets needed options
     *
     * @public
     *
     * @param    $taskTable (string) name of task table
     * @param    $threadTable (string) name of thread table
     * @param    $timelimit (int) limit in second, after which a task script
     *            should be aborted, if not finished.
     */
    function bgTasks_task($taskTable, $threadTable, $timelimit = 2000) {
        $this->taskTable = $taskTable;
        $this->threadTable = $threadTable;
        $this->timelimit = $timelimit;
        $this->threadDuration = 0.001;
        
        $this->msgHandler = new ttRpcMsgHandler();
        
        $this->id = NULL;
        $this->name = NULL;
        $this->depends_on = NULL;
        $this->status = NULL;
        $this->lang = NULL;
        $this->start_by = NULL;
        $this->start_time = NULL;
        $this->active_thread = NULL;
        
        $this->aborted = false;
    }
    // }}}
    // {{{ control_msg()
    /**
     * sends control message to log and to console,
     * if started from console
     *
     * @public
     *
     * @param    $message (string) message to log
     */
    function control_msg($message) {
        global $conf, $log;
        
        $log->add_entry($message, 'task');
        if (php_sapi_name() == 'cli') {
            echo("[" . $conf->dateUTC($conf->date_format_UTC) . "] " . $message . "\n");
        }
    }
    // }}}
    // {{{ create
    /**
     * creates a new background task
     *
     * @public
     *
     * @param    $name (string) name of tasks
     * @param    $depends_on (string) name of group of tasks. tasks with the
     *            same dependency name will not execute at the same time:
     * @param    $start_by (int) id of user, who creates the task
     * @param    $start_time (int) unix timestamp, at what date and time the task
     *            should start
     * @param    $func_init_vars (rpcfuncobject) this function will be called
     *            by every execution script, that handles the task.
     *            so you can define global variables and obbjects in it.
     */
    function create($name, $depends_on = '', $start_by = NULL, $start_time = NULL, $func_init_vars = '') {
        global $conf;
        
        if ($start_time == NULL) {
            $start_time = date('Y-m-d H:i:s');
        } else {
            $start_time = date('Y-m-d H:i:s', $start_time);
        }
        if ($func_init_vars != '') {
            $func_init_vars = $this->msgHandler->create_msg($func_init_vars);
        }
        db_query(
            "INSERT INTO $this->taskTable 
            SET name='" . mysql_real_escape_string($name) . "', depends_on='" . mysql_real_escape_string($depends_on) . "', func_init_vars='" . mysql_real_escape_string($func_init_vars) . "', status='planned', lang='$conf->interface_language', start_by='$start_by', start_time='$start_time'"
        );
        $this->id = mysql_insert_id();
        $this->name = $name;
        $this->depends_on = $depends_on;
        $this->status = 'planned';
        $this->lang = $conf->interface_language;
        $this->start_by = $start_by;
        $this->start_time = $start_time;
        $this->started_time = NULL;
        
        $this->control_msg("added new task \"$name\" to start at $start_time");
    }
    // }}}
    // {{{ load_by_id()
    /**
     * loads a task from db by its task-id, and fills actual
     * object with it.
     *
     * @public
     *
     * @param    $id (int) task-id
     */
    function load_by_id($id) {
        if (substr($id, 0, 1) == "'") {
            $id = substr($id, 1, -1);
        }
        $result = db_query(
            "SELECT * 
            FROM $this->taskTable 
            WHERE id='$id'"
        );
        if ($result && mysql_num_rows($result) == 1) {
            $row = mysql_fetch_assoc($result);
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->depends_on = $row['depends_on'];
            $this->func_init_vars = $row['func_init_vars'];
            $this->lang = $row['lang'];
            $this->status = $row['status'];
            $this->start_by = $row['start_by'];
            $this->start_time = $row['start_time'];
        }
        //mysql_free_result($result);
    }
    // }}}
    // {{{ add_thread()
    /**
     * adds a thread to the current task
     *
     * @public
     *
     * @param    $func (array) list of rpcfuncobjects, that the thread
     *            should execute.
     */
    function add_thread($funcs) {
        $thread = new bgTasks_thread($this);
        $thread->create($funcs);
    }
    // }}}
    // {{{ _do_threads()
    /**
     * executes a thread
     *
     * @private
     *
     * @param    $funcObj (rpcfuncobject) rpc function object that handles 
     *            the function execution.
     */
    function _do_threads($funcObj) {
        $this->msgHandler->funcObj = $funcObj;
        $this->set_status('active');
        
        ignore_user_abort(true);
        
        //init variables
        $funcs = $this->msgHandler->parse_msg($this->func_init_vars);
        for ($i = 0; $i < count($funcs); $i++) {
            $funcs[$i]->add_args(array('task' => $this));
            $funcs[$i]->call();
        }
        
        //start threads
        $this->active_thread = new bgTasks_thread($this);
        $this->active_thread->load_next();
        while ($this->active_thread->funcs !== NULL && !$this->aborted) {
            $this->active_thread->do_start();
            $this->stop_for_resume();
        }
    }
    // }}}
    // {{{ do_start()
    /**
     * starts a task
     *
     * @public
     *
     * @param    $funcObj (rpcfuncobject) rpc function object that handles 
     *            the function execution.
     */
    function do_start($funcObj) {
        if ($this->id !== NULL) {
            $this->_do_threads($funcObj);
        }
    }
    // }}}
    // {{{ do_resume()
    /**
     * resumes task execution
     *
     * @public
     *
     * @param    $funcObj (rpcfuncobject) rpc function object that handles 
     *            the function execution.
     */
    function do_resume($funcObj) {
        if ($this->id !== NULL) {
            $this->_do_threads($funcObj);
        }
    }
    // }}}
    // {{{ stop()
    /**
     * stops a task
     *
     * @public
     */
    function stop() {
        $this->set_status("wait_for_resume");
        $this->aborted = true;
    }
    // }}}
    // {{{ stop_or_resume()
    /**
     * stops a task, and starts the next script for
     * continuing task execution
     *
     * @public
     */
    function stop_for_resume() {
        global $conf;
        
        $this->set_status("wait_for_resume");
        $conf->execInBackground($conf->path_server_root . $conf->path_base . "framework/", "task_do.php", $this->id, true);
        $this->aborted = true;
    }
    // }}}
    // {{{ stop_for_question()
    /**
     * stops a task for interacting wit the user, which
     * starts the task.
     *
     * @public
     *
     * todo        implement this function
     */
    function stop_for_question() {
        
    }
    // }}}
    // {{{ stop_error()
    /**
     * stops the task, because an unrecoverable error occurred
     *
     * @public
     */
    function stop_error() {
        global $conf;
        
        $this->set_status("error");
        $this->aborted = true;
        die();
    }
    // }}}
    // {{{ handle_error()
    /**
     * handles errors, that occurred during bg-task-handling.
     * it logs the error. if user, who has started the task, is
     * logged in, he gets an message box with the error message.
     * other
     */
    function handle_error($errno, $errmsg, $filename, $linenum, $vars) {
        global $conf, $log;
        
        $hide_errors = array(
            "ftp_connect",
            "ftp_login",
            "ftp_rmdir",
            "ftp_mkdir",
            "ftp_delete",
            "ftp_chdir",
            "ftp_rename",
            "ftp_put",
        );
        
        $errtype = array (
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_NOTICE => 'Notice',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
        ); 
        if ($errno < E_USER_ERROR) {
            $errstr = "error in task \"$this->name\" [{$errtype[$errno]}]: $errmsg in $filename at line $linenum";
        } else {
            $lang = $conf->getTexts($this->lang, 'error');
            $lang_str = array();
            $lang_keys = array_keys($lang);
            for ($i = 0; $i < count($lang_keys); $i++) {
                $lang_str[$i] = str_replace("&lt;br&gt;", "\n", $lang[$lang_keys[$i]]);
                $lang_keys[$i] = '%' . $lang_keys[$i] . '%';
            }
            
            $errmsg = str_replace($lang_keys, $lang_str, $errmsg);
            $errstr = "error in task \"$this->name\" [{$errtype[$errno]}]: $errmsg";
        }
        
        //log error
        if ($errno != E_WARNING || !in_array(substr($errmsg, 0, strpos($errmsg, "()")), $hide_errors)) {
            $log->add_entry($errstr, "task");
        }
        //inform user if error is critical
        if ($errno == E_USER_ERROR || $errno == E_ERROR) {
            $result = db_query(
                "SELECT sessions_win.sid AS sid, sessions_win.wid AS wid
                FROM $conf->db_table_sessions AS sessions, $conf->db_table_sessions_win AS sessions_win, $conf->db_table_user AS user 
                WHERE user.id='$this->start_by' and sessions.userid = user.id and sessions.sid = sessions_win.sid"
            );
            if ($result && mysql_num_rows($result) > 0) {
                //user is logged in
                $row = mysql_fetch_assoc($result);
                $pocket_client = new PocketClient('127.0.0.1', $conf->pocket_port);
                if ($pocket_client->connect()) {
                    $pocket_client->send_to_client(new ttRpcFunc('error_alert', array(
                        'name' => $this->name,
                        'id' => $this->id,
                        'depends_on' => $this->depends_on,
                        'status' => $this->status,
                        'start_time' => $this->start_time,
                        'error_no' => $errno,
                        'error_msg' => htmlspecialchars($errmsg),
                        'php_filename' => $filename,
                        'php_linenum' => $linenum,
                    )), $row['sid'], $row['wid']);
                }
            } else {
                //user have to get mail
                $result = db_query(
                    "SELECT name_full, email
                    FROM $conf->db_table_user
                    WHERE id='$this->start_by'"
                );
                if ($result && mysql_num_rows($result) > 0) {
                    $row = mysql_fetch_assoc($result);
                    PEAR::setErrorHandling(PEAR_ERROR_PRINT );
                    $mailSender = Mail::factory($conf->mail_interface, array(
                        'host' => $conf->mail_smtp_host,
                        'port' => $conf->mail_smtp_port,
                        'auth' => $conf->mail_smtp_auth,
                        'username' => $conf->mail_smtp_user,
                        'password' => $conf->mail_smtp_path,
                    ));
                    $issent = $mailSender->send(
                        array(
                            $row['name_full'] . ' <' . $row['email'] . '>'
                        ),
                        array(
                            'Subject' => '[' . $conf->app_name . ' ' . $conf->app_version . '] error during task "' . $this->name . '"',
                            'From' => $conf->app_name . ' taskHandler <' . $conf->mail_sender_adress . '>',
                            'To' => $row['name_full'] . ' <' . $row['email'] . '>',
                        ),
                        $errmsg
                    );
                    if (!$issent) {
                        $log->add_entry($issent->toString(), "task");
                    }
                }
            }
            $this->stop_error();
        }
    }
    // }}}
    // {{{ remove()
    /**
     * ----------------------------------------------
     */
    function remove() {
        if ($this->id != NULL) {
            db_query(
                "DELETE 
                FROM $this->taskTable 
                WHERE id=$this->id"
            );
            db_query(
                "DELETE 
                FROM $this->threadTable 
                WHERE id=$this->id"
            );
        }

        $this->id = NULL;
        $this->name = NULL;
        $this->status = NULL;
        $this->start_time = NULL;
        $this->active_thread = NULL;
    }
    // }}}
    // {{{ set_status()
    /**
     * ----------------------------------------------
     */
    function set_status($newStatus) {
        db_query(
            "UPDATE $this->taskTable 
            SET status='" . mysql_real_escape_string($newStatus) . "' 
            WHERE id=$this->id"
        );
    }
    // }}}
    // {{{ get_status()
    /**
     * ----------------------------------------------
     */
    function get_status() {
        $result = db_query(
            "SELECT status 
            FROM $this->taskTable 
            WHERE id=$this->id"
        );
        if ($result && mysql_num_rows($result) == 1) {
            $row = mysql_fetch_assoc($result);
            return $row['status'];
        }
        //mysql_free_result($result);
    }
    // }}}
    // {{{ set_description()
    /**
     * ----------------------------------------------
     */
    function set_description($newDesc) {
        db_query(
            "UPDATE $this->taskTable 
            SET status_description='" . mysql_real_escape_string($newDesc) . "' 
            WHERE id=$this->id"
        );
    }
    // }}}
    // {{{ get_description()
    /**
     * ----------------------------------------------
     */
    function get_description() {
        $result = db_query(
            "SELECT status_description 
            FROM $this->taskTable 
            WHERE id=$this->id"
        );
        if ($result && mysql_num_rows($result) == 1) {
            $row = mysql_fetch_assoc($result);
            
            return $row['status_description'];
        }
        //mysql_free_result($result);
    }
    // }}}
    // {{{ get_progress()
    /**
     * ----------------------------------------------
     */
    function get_progress() {
        $value = array();
        
        $result = db_query(
            "SELECT COUNT(*) AS num, SUM(status) AS ready 
            FROM $this->threadTable 
            WHERE id=$this->id GROUP BY id"
        );
        if ($result && mysql_num_rows($result) == 1) {
            $row = mysql_fetch_assoc($result);
            
            settype($row['ready'], 'float');
            if ($row['num'] > 0) {
                $value['percent'] = $row['ready'] / $row['num'];
            } else {
                $value['percent'] = 0;
            }
            
            $value['time_from_start'] = getmicrotime() - $this->started_time;
            $value['time_at_all'] = $value['percent'] > 0 ? ($value['time_from_start'] / $value['percent']): 0;
            $value['time_until_end'] = ($value['time_at_all'] - $value['time_from_start']);
            
            $value['time_from_start'] = round($value['time_from_start']);
            $value['time_at_all'] = round($value['time_at_all']);
            $value['time_until_end'] = round($value['time_until_end']);
            $value['percent'] = round($value['percent'] * 99);
            $value['description'] = $this->get_description();
        }
        //mysql_free_result($result);
        
        return $value;
    }
    // }}}
}

/**
 * ----------------------------------------------
 */
class bgTasks_thread {
    // {{{ constructor
    /**
     * ----------------------------------------------
     */
    function bgTasks_thread($taskObj) {
        $this->taskObj = $taskObj;
        $this->funcs = NULL;
    }
    // }}}
    // {{{ create()
    function create($funcs) {
        if (!is_array($funcs)) {
            $funcs = array($funcs);
        }
        db_query(
            "INSERT INTO " . $this->taskObj->threadTable . " 
            SET id=" . $this->taskObj->id . ", func='" . mysql_real_escape_string($this->taskObj->msgHandler->create_msg($funcs)) . "'"
        );
    }
    // }}}
    // {{{ load_next()
    function load_next() {
        $result = db_query(
            "SELECT threads.id_thread AS id_thread, threads.func AS func 
            FROM " . $this->taskObj->taskTable . " AS tasks, " . $this->taskObj->threadTable . " AS threads 
            WHERE threads.status=0 AND threads.id=" . $this->taskObj->id . " AND threads.id=tasks.id AND tasks.status='active' 
            ORDER BY threads.id_thread 
            LIMIT 1"
        );
        if ($result && mysql_num_rows($result) == 1) {
            $row = mysql_fetch_assoc($result);
            $this->funcs = $this->taskObj->msgHandler->parse_msg($row['func']);
            $this->id_thread = $row['id_thread'];
        } else {
            $this->taskObj->set_status('finished');
            $this->funcs = NULL;
        }
        //mysql_free_result($result);
    }
    // }}}
    // {{{ do_start()
    function do_start() {
        global $log;

        for ($i = 0; $i < count($this->funcs); $i++) {
            set_time_limit($this->taskObj->timelimit);
            
            $this->funcs[$i]->add_args(array('task' => $this->taskObj));
            // log every called function
            //$log->add_entry($this->funcs[$i]->name . "(" . implode(",", $this->funcs[$i]->args) . ")");
            $log->add_memory_usage($this->funcs[$i]->name);
            $this->funcs[$i]->call();
            db_query(
                "UPDATE " . $this->taskObj->threadTable . " 
                SET status=" . (($i + 1) / count($this->funcs)) . " 
                WHERE id=" . $this->taskObj->id . " AND id_thread=$this->id_thread"
            );
        }
    }
    // }}}
}

/**
 * ----------------------------------------------
 * user defined error handling function 
 */
// {{{ taskErrorHandler()
function taskErrorHandler($errno, $errmsg, $filename, $linenum, $vars) { 
    global $task;
    
    if ($errno != E_NOTICE) {
        $task->handle_error($errno, $errmsg, $filename, $linenum, $vars);
    }
}
// }}}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
