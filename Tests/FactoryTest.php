<?php

use Depage\Fs\Fs;
use Depage\Fs\FsTestClass;

class FactoryTest extends PHPUnit_Framework_TestCase
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

    // {{{ testSchemeAlias
    public function testSchemeAlias()
    {
        $fs = new FsTestClass();
        $this->assertEquals(array('class' => 'file', 'scheme' => 'file'),       $fs->schemeAlias());
        $this->assertEquals(array('class' => 'file', 'scheme' => 'file'),       $fs->schemeAlias(''));
        $this->assertEquals(array('class' => 'file', 'scheme' => 'file'),       $fs->schemeAlias('file'));
        $this->assertEquals(array('class' => 'ftp', 'scheme' => 'ftp'),         $fs->schemeAlias('ftp'));
        $this->assertEquals(array('class' => 'ftp', 'scheme' => 'ftps'),        $fs->schemeAlias('ftps'));
        $this->assertEquals(array('class' => 'ssh', 'scheme' => 'ssh2.sftp'),   $fs->schemeAlias('ssh2.sftp'));
        $this->assertEquals(array('class' => 'ssh', 'scheme' => 'ssh2.sftp'),   $fs->schemeAlias('ssh'));
        $this->assertEquals(array('class' => 'ssh', 'scheme' => 'ssh2.sftp'),   $fs->schemeAlias('sftp'));
        $this->assertEquals(array('class' => '', 'scheme' => 'madeupscheme'),   $fs->schemeAlias('madeupscheme'));
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
