<?php

require_once(__DIR__ . '/../Fs.php');
require_once(__DIR__ . '/../FsFile.php');
require_once(__DIR__ . '/../FsSsh.php');
require_once(__DIR__ . '/../PublicSshKey.php');
require_once(__DIR__ . '/../PrivateSshKey.php');
require_once(__DIR__ . '/../Exceptions/FsException.php');
require_once(__DIR__ . '/TestBase.php');
require_once(__DIR__ . '/TestRemote.php');

// {{{ FsTestClass
class FsTestClass extends Depage\Fs\Fs
{
    public function lateConnect() {
        return parent::lateConnect();
    }

    public static function parseUrl($url) {
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
    public function lateConnect() {
        return parent::lateConnect();
    }
    public static function parseUrl($url) {
        return parent::parseUrl($url);
    }
    public function cleanUrl($url) {
        return parent::cleanUrl($url);
    }
}
// }}}
// {{{ FsSshTestClass
class FsSshTestClass extends Depage\Fs\FsSsh
{
    public static function parseUrl($url) {
        return parent::parseUrl($url);
    }
    public function cleanUrl($url) {
        return parent::cleanUrl($url);
    }
}
// }}}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
