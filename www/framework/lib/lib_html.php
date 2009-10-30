<?php
/**
 * @file    lib_html.php
 *
 * HTML Output Library
 *
 * This file provides functions, which generates the HTML output
 * including styles and message-boxes
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

if (!function_exists('die_error')) require_once('lib_global.php');

class html {
    /* {{{ constructor */
    function html() {
        global $conf;

        $this->settings = $conf->getScheme($conf->interface_scheme);
        $this->lang = $conf->getTexts($conf->interface_language, '', false);
        $lang_keys = array();
        foreach ($this->lang as $key => $text) {
            $this->lang_keys[] = "%$key%";
        }
    }
    /* }}} */

    /* {{{ head */
    function head($extra_content = "") {
        global $conf;
        ?><!DOCTYPE html>
<html>
        <head>
            <title><?php echo(str_replace(array("%app_name%", "%app_version%"), array($conf->app_name, $conf->app_version), $this->lang["inhtml_main_title"])); ?></title>
            <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
            <?php
                if ($_GET['autorefresh'] == 'true') {
            ?>
                <meta http-equiv="Refresh" content="3 ;URL=status.php?autorefresh=true">
            <?php
                }
            ?>
            
            <script language="JavaScript" type="text/JavaScript" src="<?php echo("{$conf->path_base}framework/interface/jquery-1.3.2.min.js");?>"></script>
            <script language="JavaScript" type="text/JavaScript" src="<?php echo("{$conf->path_base}framework/interface/jquery.cookie.min.js");?>"></script>
            <script language="JavaScript" type="text/JavaScript" src="<?php echo("{$conf->path_base}framework/interface/interface.js");?>"></script>
            <link rel="stylesheet" type="text/css" href="<?php echo("{$conf->path_base}framework/interface/interface.css");?>">
            <?php 
                echo($extra_content);
                $this->head_stylesheet(); 
            ?>
            <link rel="shortcut icon" type="image/vnd.microsoft.icon" href="<?php echo("{$conf->path_base}framework/interface/pics/favicon.ico");?>">
            <link rel="icon" type="image/png" href="<?php echo("{$conf->path_base}framework/interface/pics/favicon.png");?>">
        </head>
        <?php
    }
    /* }}} */
    /* {{{ head_stylesheet */
    function head_stylesheet() {
        global $conf;

        $settings = $conf->getScheme($conf->interface_scheme);
        ?>
            <style type="text/css">
                <!--
                .head {
                    color: <?php echo($settings['color_font']); ?>;
                }
                .normal,
                h2 a {
                    color: <?php echo($settings['color_font']); ?>;
                }
                a,
                h2 a:hover {
                    color: <?php echo($settings['color_active2']); ?>;
                }
                body {
                    background: <?php echo($settings['color_background']); ?>
                }
                .centered_box .content {
                    background: <?php echo($settings['color_face']); ?>
                }
                .toolbar a:hover {
                    background: <?php echo($settings['color_face']); ?>;
                }
                .projectlisting a:hover {
                    background: <?php echo($settings['color_background']); ?>;
                }
                .projectlisting div.details {
                    background: <?php echo($settings['color_background']); ?>;
                }
                .projectlisting div.details a {
                    color: <?php echo($settings['color_font']); ?>;
                }
                .projectlisting div.details a:hover {
                    color: <?php echo($settings['color_active2']); ?>;
                    background: <?php echo($settings['color_face']); ?>;
                }
                .projectlisting div.details ul.lastchanged_pages li .date {
                    color: <?php echo($settings['color_inactive']); ?>;
                }
                .projectlisting div.details ul.lastchanged_pages li:hover .date {
                    color: <?php echo($settings['color_font']); ?>;
                }
                .dlg {
                    background: <?php echo($settings['color_tooltipMsg_face']); ?>;
                    border: 1px solid <?php echo($settings['color_component_line']); ?>;
                    color: <?php echo($settings['color_tooltip_font']); ?>;
                }
                .dlg .question {
                    background: url(<?php echo($this->icon_path("question"))?>);
                }
                .dlg .yes {
                    background: url(<?php echo($this->icon_path("yes"))?>);
                }
                .dlg .no {
                    background: url(<?php echo($this->icon_path("no"))?>);
                }
                .centered_box small  {
                    color: #666666;
                }
                .centered_box .icon {
                    color: <?php echo($settings['color_face']); ?>;
                }
                .centered_box .icon {
                    color: <?php echo($settings['color_font']); ?>;
                }
                -->
            </style>
        <?php    
    }
    /* }}} */
    /* {{{ end */
    function end() {
        echo("</html>");
    }
    /* }}} */

    /* {{{ box */
    function box($content, $icon = "", $class = "") {
        $h = "";

        $h .= "<div id=\"box_$icon\" class=\"centered_box $class\"><div class=\"content\">";
            if ($icon != "") {
                $h .= "<div class=\"icon\">";
                    $h .= $this->icon($icon);
                $h .= "</div>";
            }
            $h .= $content;
        $h .= "</div></div>";

        return $h;
    }
    /* }}} */

    /* {{{ message */
    function message($head, $text = "", $extra = "") {
        $h = "";
        if (!is_array($text)) {
            $text = array($text);
        }
        if (!is_array($extra)) {
            $extra = array($extra);
        }

        $h .= "<h1>" . $head . "</h1>";
        foreach ($text as $t) {
            $h .= "<p>" . $t . "</p>";
        }
        foreach ($extra as $t) {
            $h .= $t;
        }

        echo($this->box($h, "", "first"));
    }
    /* }}} */
    /* {{{ preview_frame */
    function preview_frame() {
        ?>
        <frameset rows="30,100%,*" frameborder="1" border="1"  framespacing="0" bordercolor="#000000" onUnload="close_edit()">
            <frame id="toolbarFrame" name="toolbarFrame" src="framework/interface/toolbar.php" scrolling="no" noresize frameborder="1" border="1" framespacing="0">
            <frame id="contentFrame" name="content" src="framework/interface/home.php" scrolling="auto" noresize frameborder="0" border="0" framespacing="0" onload="set_preview_title()">
        </frameset>
        <?php
    }
    /* }}} */
    /* {{{ close_edit */
    function close_edit() {
        $h = "<script type=\"text/javascript\">top.close_edit();</script>";

        return $h;
    }
    /* }}} */

    /* {{{ project_listing */
    function project_listing() {
        global $conf;
        global $project;
        global $user;
        global $log;

        $h = "";

        $projects = $project->get_projects();

        $h .= "<ul class=\"projectlisting\">";
        foreach ($projects as $name => $id) {
            $h .= "<li data-project=\"$name\">";
            // add main controls
                $h .= "<a class=\"details_control arrow\"></a>";
            $h .= "
                <h2><a href=\"#edit('$name','')\" class=\"details_control\">$name</a></h2>
                <p>
                    <a href=\"#edit('$name')\" class=\"edit\"><span>" . $this->icon("edit") . "&nbsp;</span>" . $this->lang['inhtml_projects_edit'] . "</a>
                    <a href=\"{$conf->path_base}projects/$name/preview/html/cached/\"><span>" . $this->icon("preview") . "&nbsp;</span>" . $this->lang['inhtml_projects_preview'] . "</a> ";
                    if ($project->user->get_level_by_sid() <= 3) {
                        $h .= "<a href=\"#publish('$name')\" class=\"publish\">" . $this->lang['inhtml_projects_publish'] . "</a>";
                    }
            $h .= "</p>";
            // add details
            $h .= "<div class=\"details\">";
                $h .= "<h3>" . $this->lang['inhtml_lastchanged_pages'] . "</h3>";
                $h .= "<ul class=\"lastchanged_pages\">";
                $h .= "</ul>";
                if ($project->user->get_level_by_sid() <= 2) {
                    $h .= "<h3>" . $this->lang['inhtml_extra_functions'] . "</h3>";
                    $h .= "<ul>";
                            $h .= "<li><a href=\"#backup_save('$name')\" class=\"backup_save\">" . $this->lang['inhtml_projects_backup_save'] . "</a>";
                            //$h .= "<a href=\"#backup_restore('$name')\" class=\"backup_restore\">" . $this->lang['inhtml_projects_backup_restore'] . "</a></li>";
                    $h .= "</ul>";
                }
            $h .= "</div>";
            $h .= "</li>";
        }
        $h .= "</ul>";

        return $this->box($h, "projects", "first");
    }
    /* }}} */
    /* {{{ copyright_footer */
    function copyright_footer() {
        global $conf;

        $h = "";

        $h .= "<p><small>" . $conf->app_copyright . "</small></p>";
        $h .= "<p><small>" . $conf->app_license . "</small></p>";

        $h = $this->box($h, "", "noback");

        return $h;
    }
    /* }}} */
    /* {{{ task_status */
    function task_status() {
        global $conf;
        global $project;

        $h = "";

        $task_control = new bgTasks_control($conf->db_table_tasks, $conf->db_table_tasks_threads);

        $tasks = $task_control->get_tasks();
        if (count($tasks) > 0) {
            $lang = $this->lang;

            $lang_keys = array();
            foreach ($lang as $key => $text) {
                $lang_keys[] = "%$key%";
            }

            $h .= "<ul class=\"tasks\">";
            foreach($tasks as $t) {
                $tt = $task_control->get_task_control($t['id']);
                $t_status = $tt->get_status();
                $t_desc = $tt->get_description();
                $t_progress = $tt->get_progress();

                $h .= "<li>";
                    $h .= "<h3>{$t['name']} &mdash; {$t['depends_on']}</h3>";
                    $h .= "<div class=\"progress\">";
                        $h .= "<div style=\"width: " . ($t_progress['percent']) . "%;\">";
                    $h .= "</div></div>";
                    //$h .= "<p class=\"desc\">" . str_replace(array("%percent%", "%time_until_end%"), array($t_progress['percent'], $t_progress['time_until_end']), $this->lang['task_publish_progress']) . "</p>");
                    $h .= "<p class=\"desc\">" . $t_progress['percent'] . "%</p>";
                    $h .= "<p style=\"clear: both;\">" . str_replace($lang_keys, $lang, $t_progress['description']) . "</p>";
                    if ($project->user->get_level_by_sid() <= 2) {
                        $h .= "<p>Status: <b>$t_status</b></p>";
                    }
                $h .= "</li>";
            }
            $h .= "</ul>";
        }

        return $h;
    }
    /* }}} */
    /* {{{ user_status */
    function user_status() {
        global $conf;
        global $project;

        $h = "";

        $users = $project->user->get_loggedin_users();
        if (count($users) > 0) {
            $h .= "<ul class=\"users\">";
            foreach($users as $u) {
                $h .= "<li>";
                $h .= "<h3><a href=\"mailto:$u->email\">$u->name_full</a> ($u->name) </h3>";
                if ($u->project != "") {
                    $h .= "<p>is editing '$u->project'</p>";
                }
                //$h .= "<p>last update: $u->last_update</p>";
                $h .= "</li>";
            }
            $h .= "</ul>";
        }

        return $h;
    }
    /* }}} */
    /* {{{ status */
    function status() {
        global $conf;
        global $project;

        ?>
        <!-- {{{ Users -->
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
        <!-- }}} -->
        <!-- {{{ Tasks -->
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
        <!-- }}} -->
        <!-- {{{ Pocket-Server -->
        <h1>Pocket Server</h1>
        <?php
            $running = $conf->get_tt_env('pocket_server_running');
            if ($running == 0) {
                echo("<p>stopped</p>");
            } elseif ($running == 1) {
                echo("<p>running</p>");
            } elseif ($running == -1) {
                echo("<p>stopping</p>");
            } elseif ($running == -2) {
                echo("<p>forcing to stop</p>");
            } else {
                echo("<p>unknown</p>");
            }
        ?>
        <!-- }}} -->
        <!-- {{{ Log -->
        <h1>Log</h1>
        <?php
            $loglines = array();
            clearstatcache();
            $logfile = $conf->path_server_root . $conf->path_base . "logs/depage.log";
            $fp = fopen($logfile, "r");
            $i = 0;
            if ($fp) {
                fseek($fp, -5000, SEEK_END);
                while (!feof($fp)) {
                    $buffer = fgets($fp);
                    if ($i > 0) {
                        $loglines[] = $buffer;
                    }
                    $i++;
                }
                fclose($fp);
                for ($i = count($loglines) - 20; $i < count($loglines); $i++) {
                    echo("<p>{$loglines[$i]}</p>");
                }
            }
        ?>
        <!-- }}} -->
        <?php
    }
    /* }}} */
    /* {{{ toolbar */
    function toolbar() {
        global $conf;
        global $project;
        
        echo("<div class=\"toolbar\">
            <h2><a>depage::cms</a></h2>
            <p>
                <a id=\"button_home\" href=\"javascript:top.go_home()\">" . $this->lang['inhtml_toolbar_home'] . "</a>
                <a id=\"button_reload\" href=\"javascript:top.content.location.reload()\" style=\"display:none\"><span>" . $this->icon("reload") . "&nbsp;</span>" . $this->lang['inhtml_toolbar_reload'] . "</a>
                <a id=\"button_edit\" href=\"javascript:top.edit_page()\" style=\"display:none\"><span>" . $this->icon("edit") . "&nbsp;</span>" . $this->lang['inhtml_toolbar_edit'] . "</a>
            </p>
            <p class=\"right\">
                <a id=\"button_logout\" href=\"javascript:top.logout()\">" . $this->lang['inhtml_toolbar_logout'] . "</a>
            </p>
        </div>");
    }
    /* }}} */

    /* {{{ icon */
    function icon($name, $alt = "") {
        return "<img src=\"" . $this->icon_path($name) . "\" alt=\"" . htmlentities($alt) . "\">";
    }
    /* }}} */
    /* {{{ icon_path */
    function icon_path($name) {
        global $conf;

        return "{$conf->path_base}framework/interface/pics/icon_{$name}.gif";
    }
    /* }}} */
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
