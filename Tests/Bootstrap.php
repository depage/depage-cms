<?php

require_once(__DIR__ . '/../vendor/autoload.php');

// {{{ mock built-in functions
/*
function openssl_pkey_get_private($data)
{
    if ($data === 'writeFail!') {
        return $data;
    } else {
        return \openssl_pkey_get_private($data);
    }
}
*/
// }}}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
