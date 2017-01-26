<?php

namespace Depage\Fs\Tests;

use Depage\Fs\Fs;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    // {{{ testFsFile
    public function testFsFile()
    {
        $cases = array(
            '',
            'file://',
            'file:///path/to/file',
            'path/to/file',
        );

        foreach ($cases as $case) {
            $fs = Fs::factory($case);

            $this->assertInstanceOf('Depage\Fs\FsFile', $fs);
        }
    }
    // }}}
    // {{{ testFsFtp
    public function testFsFtp()
    {
        $fs = Fs::factory('ftp://user@host/path/to/file');

        $this->assertInstanceOf('Depage\Fs\FsFtp', $fs);
    }
    // }}}
    // {{{ testFsFtps
    public function testFsFtps()
    {
        $fs = Fs::factory('ftps://user@host/path/to/file');

        $this->assertInstanceOf('Depage\Fs\FsFtp', $fs);
    }
    // }}}
    // {{{ testFsSsh
    public function testFsSsh()
    {
        $cases = array(
            'ssh://user@host/path/to/file',
            'sftp://user@host/path/to/file',
            'ssh2.sftp://user@host/path/to/file',
        );

        foreach ($cases as $case) {
            $fs = Fs::factory($case);

            $this->assertInstanceOf('Depage\Fs\FsSsh', $fs);
        }
    }
    // }}}
    // {{{ testFsCustom
    public function testFsCustom()
    {
        $fs = Fs::factory('madeupscheme://user@host/path/to/file');

        $this->assertInstanceOf('Depage\Fs\Fs', $fs);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
