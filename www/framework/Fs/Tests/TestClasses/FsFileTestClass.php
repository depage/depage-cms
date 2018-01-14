<?php

namespace Depage\Fs\Tests\TestClasses;

use Depage\Fs\FsFile;

class FsFileTestClass extends FsFile
{
    public function lateConnect()
    {
        return parent::lateConnect();
    }
    public static function parseUrl($url)
    {
        return parent::parseUrl($url);
    }
    public function cleanUrl($url, $showPass = true)
    {
        return parent::cleanUrl($url, $showPass);
    }
}
