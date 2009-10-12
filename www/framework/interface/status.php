<?php
/**
 * @file    status.php
 *
 * index file
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
 
    define('IS_IN_CONTOOL', true);

    require_once('../lib/lib_global.php');
    require_once('lib_auth.php');
    require_once('lib_html.php');
    require_once('lib_tpl_xslt.php');
    require_once('lib_pocket_server.php');
    require_once('lib_tasks.php');

    $project->user->auth_http();

    $html = new html();

    if ($_REQUEST['type'] == "tasks") {
        $html->task_status();
    } elseif ($_REQUEST['type'] == "publish") {
        $project->publish($_REQUEST['project']);
    }

    if (!$conf->pocket_use) {
        //init objects
        $task_control = new bgTasks_control($conf->db_table_tasks, $conf->db_table_tasks_threads);
        $pocket_server = "";
        register_shutdown_function(array($task_control, "handle_tasks"), array($pocket_server));
    }
?>
