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

    // {{{ testSchemeAlias
    public function testSchemeAlias()
    {
        $fs = new Fs();
        $this->assertEquals(array('class' => 'file', 'scheme' => 'file'),       invoke($fs, 'schemeAlias', array()));
        $this->assertEquals(array('class' => 'file', 'scheme' => 'file'),       invoke($fs, 'schemeAlias', array('')));
        $this->assertEquals(array('class' => 'file', 'scheme' => 'file'),       invoke($fs, 'schemeAlias', array('file')));
        $this->assertEquals(array('class' => 'ftp', 'scheme' => 'ftp'),         invoke($fs, 'schemeAlias', array('ftp')));
        $this->assertEquals(array('class' => 'ftp', 'scheme' => 'ftps'),        invoke($fs, 'schemeAlias', array('ftps')));
        $this->assertEquals(array('class' => 'ssh', 'scheme' => 'ssh2.sftp'),   invoke($fs, 'schemeAlias', array('ssh2.sftp')));
        $this->assertEquals(array('class' => 'ssh', 'scheme' => 'ssh2.sftp'),   invoke($fs, 'schemeAlias', array('ssh')));
        $this->assertEquals(array('class' => 'ssh', 'scheme' => 'ssh2.sftp'),   invoke($fs, 'schemeAlias', array('sftp')));
        $this->assertEquals(array('class' => '', 'scheme' => 'madeupscheme'),   invoke($fs, 'schemeAlias', array('madeupscheme')));
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
