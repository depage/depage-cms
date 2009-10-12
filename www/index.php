<?php
/**
 * @file    index.php
 *
 * index file
 *
 *
 * copyright (c) 2002-2008 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
 
/**
 * @mainpage
 *
 * Welcome to unepexted experiences!
 *
 * @li @ref todo "Todo List"
 * @li @ref bug "Bug List"
 */
 
    define('IS_IN_CONTOOL', true);

    require_once('framework/lib/lib_global.php');
    require_once('lib_auth.php');
    require_once('lib_html.php');
    require_once('lib_files.php');
    require_once('lib_tpl_xslt.php');
    require_once('lib_pocket_server.php');
    require_once('lib_tasks.php');

    $html = new html();

    if ($_GET['logout']) {
        if ($_COOKIE[session_name()] != "") {
            $project->user->auth_http();
            $project->user->logout();

            $html->head();
            $html->message($html->lang["inhtml_logout_headline"], str_replace("%app_name%", $conf->app_name, $html->lang["inhtml_logout_text"]), "<p class=\"bottom right\">" . $html->lang["inhtml_logout_relogin"] . "</p>");
        }
    } else {
        $project->user->auth_http();

        $html->head();
        $html->preview_frame();
    }
    $html->end();
