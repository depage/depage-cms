<?php
/*
 * $Id: test.php,v 1.52 2004/11/12 19:45:31 jonas Exp $
 */
    // {{{ define and includes
    define("IS_IN_CONTOOL", true);
    
    require_once("lib/lib_global.php");
    require_once("lib_auth.php");
    require_once("lib_tpl.php");
    require_once("lib_project.php");
    require_once("lib_pocket_server.php");
    require_once("lib_tasks.php");
    require_once("lib_files.php");
    require_once("Archive/tar.php");
    // }}}
    
    var_dump($conf);

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
