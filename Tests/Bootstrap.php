<?php

require_once('../FS.php');
require_once('../Exceptions/FSException.php');

function getMode($path) {
    return substr(sprintf('%o', fileperms($path)), -4);
}


/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
