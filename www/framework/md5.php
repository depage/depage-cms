<?php
    define('IS_IN_CONTOOL', true);

    require_once('lib/lib_global.php');
    require_once("lib_auth.php");

    $user = new ttUser();

    $name = $_GET['name'];
    $pass = $_GET['pass'];

    $A1 = md5($name . ':' . $user->realm . ':' . $pass);
    
    echo("hash: " . $A1);
?>
