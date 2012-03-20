<?php
/**
 * @file    framework/task/task_runner.php
 *
 * depage cms task runner module
 *
 *
 * copyright (c) 2011 Lion Vollnhals [lion.vollnhals@googlemail.com]
 *
 * @author    Lion Vollnhals [lion.vollnhals@googlemail.com]
 */

namespace depage\task;


require_once("framework/depage/depage.php");

require_once(__DIR__ . "/task.php");
require_once(__DIR__ . "/execute_in_background.php");


/*
 * task runner design:
 * 
 * task_runner#task reads task with respective id from table *_tasks .
 * task is locked by writing a file lock.
 * individual subtasks are read from table *_subtasks .
 * each subtask and task has a status that is updated after running it.
 * if a subtask fails then its status is set to "failed: {$error_message}",
 * the corresponding task is also set to "failed".
 * 
 * general errors are signaled by throwing exceptions. for example when
 * there is no such task or when the task is already running.
 * 
 * logging is done to logs/depage_task_task_runner.log .
 * 
 * php's max execution timeout is anticipated, the script is automatically
 * restarted and the task will be resumed.
 * dependent subtasks will also be redone.
 * if possible the script will be re-executed by php CLI, otherwise the
 * current request will be repeated.
 */

/* TODO:
 webserver timeouts may happen before php max execution timeout:
 "Your web server can have other timeout configurations that
 may also interrupt PHP execution. Apache has a Timeout directive
 and IIS has a CGI timeout function. Both default to 300 seconds.
 See your web server documentation for specific details."

*/


class task_runner extends \depage_ui {
    // {{{ constructor
    public function __construct($options = NULL, $cli = false) {
        parent::__construct($options);

        // overwrite config with real values. TODO: find a better way to do that
        if ($cli) {
            $conf = new \config();
            $conf->readConfig(__DIR__ . "/../../conf/dpconf.php");
            $this->options = $conf->getFromDefaults($this->defaults);
            // create log
            $this->init();
        }
        
        // get database instance
        $this->pdo = new \db_pdo (
            $this->options->db->dsn, // dsn
            $this->options->db->user, // user
            $this->options->db->password, // password
            array(
                'prefix' => $this->options->db->prefix, // database prefix
            )
        );

        $this->prefix = $this->pdo->prefix;
        
        $this->force_login = false; // TODO: !$cli;
        if ($this->force_login) {
            // get auth object
            $this->auth = \auth::factory(
                $this->pdo, // db_pdo 
                $this->options->auth->realm, // auth realm
                DEPAGE_BASE, // domain
                $this->options->auth->method // method
            );
        }
    }
    // }}}
    
    // {{{ execute
    public function execute($task_id) {
        if ($this->force_login)
            $this->auth->enforce();

        $this->task = new \depage\task\task((int)$task_id, $this->prefix, $this->pdo);
        $this->abnormal_exit = true;
        register_shutdown_function(array($this, "at_shutdown"));
        
        if ($this->task->lock()) {
            try {
                $this->log->log("starting task {$task_id} ({$this->task->task_name})");

                while ($subtask = $this->task->get_next_subtask()) {
                    $subtask_name = "{$subtask->id} ({$subtask->name})";
                    $this->log->log("    starting subtask $subtask_name");

                    $status = $this->task->run_subtask($subtask);
                    if ($status === false) {
                        throw new \Exception("Parse Error");
                    }
                    
                    $this->log->log("    finished subtask $subtask_name");
                    $this->task->set_subtask_status($subtask, "done");
                }

                $this->log->log("finished task {$task_id} ({$this->task->task_name})");
                $this->task->set_task_status("done");
            } catch (\Exception $e) {
                $this->task->set_subtask_status($subtask, "failed: " . $e->getMessage());
                $this->task->set_task_status("failed");
                $this->log->log("ERROR: " . $e->getMessage());
            }
            
            $this->task->unlock();
        } else {
            $this->log->log("task {$this->task->task_name} is already running");
        }

        $this->abnormal_exit = false;        
    }
    // }}}

    // {{{ at_shutdown
    public function at_shutdown() {
        if ($this->abnormal_exit) {
            $this->log->log("abnormal exit: restarting ...");

            // release last held lock, so that new task can grab it
            $this->task->unlock();

            execute_in_background(__DIR__ . "/../../", "framework/task/" . basename(__FILE__), $this->task->task_id);
        } else {
            $this->log->log("normal exit");
        }
    }
    // }}}
}


// run task if called from cli
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cli') {
    $task_runner = new task_runner(NULL, true);
    $task_runner->execute($argv[1]);
}

/* TODO:
 *  - how to handle errors?
 *  - restart failed task!? wird erstmal nicht gebraucht!
 *  - was ist wenn ein einzelner Subtaks Zeitlimit überschreitet?
 */
/* vim:set ft=php sw=4 sts=4 fdm=marker : */
