<?php

require_once(__DIR__ . '/../Fs.php');
require_once(__DIR__ . '/../FsFile.php');
require_once(__DIR__ . '/../FsSsh.php');
require_once(__DIR__ . '/../Exceptions/FsException.php');
require_once(__DIR__ . '/TestBase.php');

// {{{ FsTestClass
class FsTestClass extends Depage\Fs\Fs
{
    public function parseUrl($url) {
        return parent::parseUrl($url);
    }
    public function cleanUrl($url) {
        return parent::cleanUrl($url);
    }
}
// }}}
// {{{ FsFileTestClass
class FsFileTestClass extends Depage\Fs\FsFile
{
    public function parseUrl($url) {
        return parent::parseUrl($url);
    }
    public function cleanUrl($url) {
        return parent::cleanUrl($url);
    }
}
// }}}

// {{{ getMode
function getMode($path) {
    return substr(sprintf('%o', fileperms($path)), -4);
}
// }}}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
