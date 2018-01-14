<?php

namespace Depage\Fs\Tests\TestClasses;

class FsTestClass extends \Depage\Fs\Fs
{
    public static function schemeAlias($alias = '')
    {
        return parent::schemeAlias($alias);
    }
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
    public function extractFileName($path)
    {
        return parent::extractFileName($path);
    }
    public function cleanPath($path)
    {
        return parent::cleanPath($path);
    }
    public function preCommandHook()
    {
        return parent::preCommandHook();
    }
    public function postCommandHook()
    {
        return parent::postCommandHook();
    }
    public function file_put_contents($filename, $data, $flags = 0, $context = null)
    {
        if ($data === 'writeFail!') {
            return false;
        } else {
            return parent::file_put_contents($filename, $data, $flags, $context);
        }
    }
}
