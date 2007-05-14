shutting server down...
<?php
    define("IS_IN_CONTOOL", true);
    
    require_once("lib/lib_global.php");
    
    $conf->set_tt_env("pocket_server_running", -1);
/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
