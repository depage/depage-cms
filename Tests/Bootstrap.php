<?php

require_once('../Fs.php');
require_once('../Exceptions/FsException.php');

// {{{ FsTestClass
class FsTestClass extends depage\Fs\Fs
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
