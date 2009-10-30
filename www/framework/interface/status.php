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
        if ($project->backup_save($_REQUEST['project'])) {
            echo("<h2>backup saved</h2>");
        } else {
            echo("<h2>backup not saved</h2>");
        }
    } elseif ($_REQUEST['type'] == "lastchanged_pages") {
        $pages = $project->get_lastchanged_pages($_REQUEST['project']);
        $languages = $project->get_languages($_REQUEST['project']);
        $languages = array_keys($languages);
        $lang = $languages[0];

        foreach ($pages as $page) {
            echo("<li>");
            echo("<a href=\"../../projects/{$_REQUEST['project']}/preview/html/cached/{$lang}{$page['url']}\">");
                    echo("{$page['url']}");
                    echo("<span class=\"date\">" . date("d.m.y H:m", $page['dt']) . "</span>");
                echo("</a>");
            echo("</li>");
        }
    }

    if (!$conf->pocket_use) {
        //init objects
        $task_control = new bgTasks_control($conf->db_table_tasks, $conf->db_table_tasks_threads);
        $pocket_server = "";
        register_shutdown_function(array($task_control, "handle_tasks"), array($pocket_server));
    }
?>
