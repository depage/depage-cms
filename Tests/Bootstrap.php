<?php

require_once(__DIR__ . '/../Fs.php');
require_once(__DIR__ . '/../FsFile.php');
require_once(__DIR__ . '/../FsFtp.php');
require_once(__DIR__ . '/../FsSsh.php');
require_once(__DIR__ . '/../PublicSshKey.php');
require_once(__DIR__ . '/../PrivateSshKey.php');
require_once(__DIR__ . '/../Exceptions/FsException.php');
require_once(__DIR__ . '/TestBase.php');
require_once(__DIR__ . '/TestRemote.php');

// {{{ invoke
function invoke($fs, $methodName, $args = null)
{
    $reflector = new ReflectionClass($fs);
    $reflectionMethod = $reflector->getMethod($methodName);
    $reflectionMethod->setAccessible(true);
    $result = null;

    if ($args === null) {
        $result = $reflectionMethod->invoke($fs);
    } else {
        $result = $reflectionMethod->invokeArgs($fs, $args);
    }

    return $result;
}
// }}}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
