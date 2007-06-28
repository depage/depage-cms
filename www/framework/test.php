<?php
/*
 * 
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

    $xml_proc = tpl_engine::factory('xslt', $param);
    $project_name = "RLM Trier";
    $transformed = $xml_proc->generate_page_css($project_name, "html", "screen");

    echo("<html><body><pre>");
    echo($transformed['value']);
    echo("</pre></body></html>");

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
