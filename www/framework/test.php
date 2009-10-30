<?php
/*
 * 
 */
    // {{{ define and includes
    define('IS_IN_CONTOOL', true);

    require_once('lib/lib_global.php');
    require_once('lib_auth.php');
    require_once('lib_project.php');
    // }}}
    
    $project->user->auth_http();

    $projects = $project->get_projects();

    foreach ($projects as $name => $id) {
        echo("saving '$name'\n");
        $project->backup_save($name);
    }

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
