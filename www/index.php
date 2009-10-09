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

    if ($_GET['logout']) {
        if ($_COOKIE[session_name()] != "") {
            $project->user->auth_http();
            $project->user->logout();

            die_error("Thank you for using depage::cms.");
        }
    } else {
        $project->user->auth_http();
    }

    $html = new html();

    $html->head();
    $html->preview_frame();
    $html->end();
