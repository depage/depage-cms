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
        echo($html->task_status());
    } elseif ($_REQUEST['type'] == "users") {
        echo($html->user_status());
    } elseif ($_REQUEST['type'] == "publish") {
        $project->publish($_REQUEST['project']);
    } elseif ($_REQUEST['type'] == "backup_save") {
        if ($savename = $project->backup_save($_REQUEST['project'])) {
            echo("<h1>backup saved</h1>");
            echo("<p>$savename</p>");
        } else {
            echo("<h1>backup not saved</h1>");
        }
    } elseif ($_REQUEST['type'] == "backup_restore") {
        if ($project->backup_restore($_REQUEST['project'], $_REQUEST['file'])) {
            echo("<h1>backup restored</h1>");
            echo("<p>{$_REQUEST['project']} from {$_REQUEST['file']}</p>");
        } else {
            echo("<h1>backup not restored</h1>");
        }
    } elseif ($_REQUEST['type'] == "backup_files") {
        echo($html->backup_files($_REQUEST['project']));
    } elseif ($_REQUEST['type'] == "lastchanged_pages") {
        echo($html->lastchanged_pages($_REQUEST['project']));
    }

    if (!$conf->pocket_use) {
        //init objects
        $task_control = new bgTasks_control($conf->db_table_tasks, $conf->db_table_tasks_threads);
        $pocket_server = "";
        register_shutdown_function(array($task_control, "handle_tasks"), array($pocket_server));
    }
?>
