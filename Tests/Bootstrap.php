<?php

require_once('../FS.php');
require_once('../Exceptions/FSException.php');

// {{{ FSTestClass
class FSTestClass extends depage\FS\FS
{
    public function parseUrl($url) {
        return parent::parseUrl($url);
    }
}
// }}}

// {{{ getMode
function getMode($path) {
    return substr(sprintf('%o', fileperms($path)), -4);
}
// }}}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
