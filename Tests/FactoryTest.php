<?php

use Depage\Fs\Fs;

class FactoryTest extends PHPUnit_Framework_TestCase
{

    // {{{ getScheme
    public function getScheme($fs)
    {
        $reflector = new ReflectionClass($fs);
        $urlProperty = $reflector->getProperty('url');
        $urlProperty->setAccessible(true);
        $url = $urlProperty->getValue($fs);

        return $url['scheme'];
    }
    // }}}

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
            $this->assertEquals('file', $this->getScheme($fs));
        }
    }
    // }}}
    // {{{ testFsFtp
    public function testFsFtp()
    {
        $fs = Fs::factory('ftp://user@host/path/to/file');

        $this->assertInstanceOf('Depage\Fs\FsFtp', $fs);
        $this->assertEquals('ftp', $this->getScheme($fs));
    }
    // }}}
    // {{{ testFsFtps
    public function testFsFtps()
    {
        $fs = Fs::factory('ftps://user@host/path/to/file');

        $this->assertInstanceOf('Depage\Fs\FsFtp', $fs);
        $this->assertEquals('ftps', $this->getScheme($fs));
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
            $this->assertEquals('ssh2.sftp', $this->getScheme($fs));
        }
    }
    // }}}
    // {{{ testFsCustom
    public function testFsCustom()
    {
        $fs = Fs::factory('madeupscheme://user@host/path/to/file');

        $this->assertInstanceOf('Depage\Fs\Fs', $fs);
        $this->assertEquals('madeupscheme', $this->getScheme($fs));
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
