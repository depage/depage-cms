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
 * copyright (c) 2002-2008 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 *
 * $Id: lib_html.php,v 1.7 2004/05/26 14:49:05 jonas Exp $
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
    function head() {
        global $conf;
        ?>
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
            
            <script language="JavaScript" type="text/JavaScript" src="<?php echo("{$conf->path_base}/framework/interface/interface.js");?>"></script>
            <link rel="stylesheet" type="text/css" href="<?php echo("{$conf->path_base}/framework/interface/interface.css");?>">
            <?php htmlout::echoStyleSheet(); ?>
        </head>
        <?php
    }
    /* }}} */
    /* {{{ preview_frame */
    function preview_frame() {
        ?>
        <frameset rows="30,100%,*" frameborder="0" border="0"  framespacing="0" onUnload="close_edit()">
            <frame id="toolbarFrame" name="toolbar" src="framework/interface/toolbar.php" scrolling="no" noresize frameborder="0" border="0" framespacing="0">
            <frame id="contentFrame" name="content" src="framework/interface/home.php" scrolling="auto" noresize frameborder="0" border="0" framespacing="0" onLoad="set_preview_title()">
        </frameset>
        <?php
    }
    /* }}} */
    /* {{{ close_edit */
    function close_edit() {
        ?>
            <script type="text/javascript">top.close_edit();</script>
        <?php
    }
    /* }}} */
    /* {{{ project_listing */
    function project_listing() {
        global $conf;
        global $project;
        global $user;
        global $log;

        $projects = $project->get_projects();

        echo("<h1>Projekte</h1>");
        echo("<ul>");
        foreach ($projects as $name => $id) {
            echo("<li>
                <a href=\"javascript:top.open_edit('$name')\">$name</a> 
                <a href=\"javascript:top.open_edit('$name')\">edit</a>
                <a href=\"{$conf->path_base}/projects/$name/preview/html/cached/\">preview</a>
            </li>");
        }
        echo("</ul>");
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
            $logfile = $conf->path_server_root . $conf->path_base . "/logs/depage.log";
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
}
/**
 * Defines functions, which may be called without initializing an
 * instance of htmlout. 
 */
class htmlout {
    /**
     * outputs unified stylesheet for html content
     *
     * @public
     */
    function echoStyleSheet() {
        ?>
            <style type="text/css">
                <!--
                * {
                    font-family : Verdana, Tahoma, Verdana, Arial, Geneva, sans-serif;
                    font-size : 12px;
                    text-decoration : none;
                    margin-top : 0px;
                    margin-bottom : 0px;
                }
                .head {
                    font-weight : bold;
                    line-height : 15px;
                    color : #000000;
                    margin-top : 7px;
                    margin-bottom : 10px;
                }
                .normal {
                    line-height : 15px;
                    color : #000000;
                    margin-bottom : 10px;
                }
                h1 {
                    padding-top: 10px;
                }
                ul {
                    list-style: none;
                    padding-left: 10px;
                    text-indent: 0px;
                    padding-bottom: 10px;
                }
                li {
                    padding-top: 5px;
                    padding-bottom: 5px;
                }
                a {
                    color: #882200;
                }
                -->
            </style>
        <?php    
    }

    /**
     * outputs a transparent spacer image
     *
     * @public
     *
     * @param    $width (int) width of spacer, optional, default is 1
     * @param    $height (int) height of spacer, optional, default is 1
     */
    function echoNullImg($width = 1, $height = 1) {
        global $conf;
        
        echo("<img src=\"{$conf->path_base}/framework/interface/pics/null.gif\" width=\"$width\" height=\"$height\">");
    }

    /**
     * outputs a message in a centered box
     *
     * @public
     *
     * @param    $head (string) title of message
     * @param    $text (string) message text
     */
    function echoMsg($head, $text) {
        ?>
            <table width="100%" height="150">
                <tr>
                    <td align="center">
                        <table border="0" cellspacing="0" cellpadding="0">
                          <tr> 
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                            <td rowspan="5" width="10" height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(10); ?></td>
                            <td height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td rowspan="5" width="10" height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(10); ?></td>
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                          </tr>
                          <tr> 
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                          </tr>
                          <tr> 
                            <td width="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td width="500" height="50" align="left" valign="middle" bgcolor="#D4D0C8">
                                <p class="head"><?php echo($head) ?></p>
                                <p class="normal"><?php echo($text) ?></p>
                            </td>
                            <td width="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                          </tr>
                          <tr> 
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                          </tr>
                          <tr> 
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                            <td height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                          </tr>
                        </table>
                    </td>
                </tr>
            </table>
        <?php
    }
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
