<?php
/**
 * G E R O N I M O
 * P O C K E T - S E R V E R
 *
 * php-script:
 * (c)2003 jonas [jonas.info@gmx.net]
 */

define('IS_IN_CONTOOL', true);

require_once('lib/lib_global.php');
require_once('lib_auth.php');
require_once('lib_pocket_server.php');
require_once('lib_tasks.php');

/**
 * ----------------------------------------------
 */
class rpc_pocketConnect_functions extends rpc_pocketConnect_default_functions {
    /**
     * ----------------------------------------------
     */ 
    function get_active_tasks($arg) {
        global $conf;
        
        $taskControl = new bgTasks_control($conf->db_table_tasks, $conf->db_table_tasks_threads);
        $active = $taskControl->get_active_tasks();
        
        $runningTasksXML = '';
        for ($i = 0; $i < count($active); $i++) {
            if (strlen($active[$i]['depends_on']) == 0) {
                $depender = '';
            } else {
                $depender = substr($active[$i]['depends_on'], 0, strpos($active[$i]['depends_on'], ' '));
                $depends_on = substr($active[$i]['depends_on'], strpos($active[$i]['depends_on'], '[') + 1, -1);
            }
            if ($depender == '' || ($depender == 'project' && $depends_on == $arg['project']) || ($depender == 'user' && $depends_on == $arg['sid'])) {
                $task = new bgTasks_task($conf->db_table_tasks, $conf->db_table_tasks_threads);
                $task->load_by_id($active[$i]['id']);
                $progress = $task->get_progress();
                $runningTasksXML .= "<task name=\"" . htmlspecialchars($active[$i]["name"]) . "\" "
                    . "id=\"{$active[$i]['id']}\" "
                    . "progress_percent=\"{$progress['percent']}\" "
                    . "time_from_start=\"{$progress['time_from_start']}\" "
                    . "time_at_all=\"{$progress['time_at_all']}\" "
                    . "time_until_end=\"{$progress['time_until_end']}\" "
                    . "description=\"{$progress['description']}\" "
                    . "/>";
            }
        }
        
        $func = new ttRpcFunc('return_active_tasks', array('running' => $runningTasksXML));
        
        //$arg['connectionObj']->sendMessage($arg['serverObj']->msgHandler->create_msg($func));
        return count($active) == 0;
    }
}

/**
 * ----------------------------------------------
 */ 
//init objects
$msgHandler = new ttRpcMsgHandler(new rpc_pocketConnect_functions());
$task_control = new bgTasks_control($conf->db_table_tasks, $conf->db_table_tasks_threads);
$pocket_server = new PocketServer($conf->pocket_addr, $conf->pocket_port, $conf->pocket_buffersize, $conf->pocket_max_unused_time, $msgHandler);

//start pocket_server
$pocket_server->Init();
$pocket_server->startListen(array(&$task_control, 'handle_tasks'));

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
