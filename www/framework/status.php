<?php
/**
 * @file    status.php
 *
 * status viewer
 *
 *
 * copyright (c) 2002-2007 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

    define("IS_IN_CONTOOL", true);
        
    require_once('lib/lib_global.php');
    require_once('lib_html.php');
    require_once('lib_auth.php');
    require_once('lib_files.php');
    require_once('lib_tpl_xslt.php');
    require_once('lib_pocket_server.php');
    require_once('lib_tasks.php');
        
    $settings = $conf->getScheme($conf->interface_scheme);
    $lang = $conf->getTexts($conf->interface_language, '', false);
    $lang_keys = array();
    foreach ($lang as $key => $text) {
        $lang_keys[] = "%$key%";
    }
    
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <title><?php echo(str_replace(array("%app_name%", "%app_version%"), array($conf->app_name, $conf->app_version), $lang["inhtml_status_title"])); ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <meta http-equiv="Refresh" content="3 ;URL=status.php">
        <script language="JavaScript" type="text/JavaScript">
        <!--
        //-->
        </script>
        <?php htmlout::echoStyleSheet(); ?>
    </head>
    <body bgcolor="<?php echo($settings['color_face']); ?>">            
        <h1>Logged in users</h1>
        <?php
            $user = $project->user->get_loggedin_users();

            if (count($user) > 0) {
                echo("<ul>");
                foreach($user as $u) {
                    echo("<li>");
                    echo("<h3><a href=\"mailto:$u->email\">$u->name_full</a> [$u->name]</h3>");
                    echo("<p>is logged into '<b>$u->project</b>' from '$u->ip:$u->port'</p>");
                    echo("<p>last update: $u->last_update</p>");
                    echo("</li>");
                }
                echo("</ul>");
            } else {
                echo("<p>none</p>");
            }
        ?>

        <h1>Tasks</h1>
        <?php
            $task_control = new bgTasks_control($conf->db_table_tasks, $conf->db_table_tasks_threads);

            $tasks = $task_control->get_tasks();
            if (count($tasks) > 0) {
                echo("<ul>");
                foreach($tasks as $t) {
                    $tt = $task_control->get_task_control($t['id']);
                    $t_status = $tt->get_status();
                    $t_desc = $tt->get_description();
                    $t_progress = $tt->get_progress();

                    echo("<li>");
                    echo("<h3>{$t['id']}. {$t['name']} &mdash; {$t['depends_on']}</h3>");
                    echo("<p style=\"padding-bottom: 10px\">Status: <b>$t_status</b></p>");
                    echo("<div style=\"float: left; border: 1px solid #000000; width: 202px; height: 15px; margin-left: 10px; margin-right: 10px;\">");
                    echo("<div style=\"background: #ff9900; width: " . ($t_progress['percent'] * 2) . "px; height: 15px;\">");
                    echo("</div></div>");
                    echo("<p style=\"padding-top: 1px\">" . $t_progress['percent'] . "% finishing in " . $t_progress['time_until_end'] . "min</p>");
                    echo("<p style=\"padding-top: 10px\">" . str_replace($lang_keys, $lang, $t_progress['description']) . "<br></p>");
                    echo("</li>");
                }
                echo("</ul>");
            } else {
                echo("<p>none</p>");
            }
        ?>
    </body>
</html>
<?php

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
