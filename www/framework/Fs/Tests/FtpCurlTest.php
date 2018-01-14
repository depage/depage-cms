<?php

namespace Depage\Fs\Tests;

use Depage\Fs\Tests\TestClasses\FtpCurlTestClass;

class FtpCurlTest extends \PHPUnit_Framework_TestCase
{
    // {{{ constructor
    public function __construct()
    {
        $this->root = __DIR__;
        $this->cert = $this->root . '/' . $GLOBALS['CA_CERT'];
        $this->src = new HelperFsLocal($this->root . '/Temp');
        $this->dst = new HelperFsRemote('/Temp');

        $this->url = 'ftp://' .
            $GLOBALS['REMOTE_USER'] . ':' .
            $GLOBALS['REMOTE_PASS'] . '@' .
            $GLOBALS['REMOTE_HOST'] . '/Temp/';
    }
    // }}}

    // {{{ setUp
    public function setUp()
    {
        FtpCurlTestClass::registerStream('ftp', ['caCert' => $this->cert]);

        $this->assertTrue($this->src->setUp());
        $this->assertTrue($this->dst->setUp());

        $this->assertTrue(chdir($this->src->getRoot()));
    }
    // }}}
    // {{{ tearDown
    public function tearDown()
    {
        FtpCurlTestClass::disconnect();

        $this->assertTrue($this->src->tearDown());
        $this->assertTrue($this->dst->tearDown());

        $this->assertTrue(chdir($this->root));
        $this->assertTrue(stream_wrapper_restore('ftp'));
    }
    // }}}

    // {{{ testScandir
    public function testScandir()
    {
        $this->assertSame(['.', '..'], scandir($this->url));
    }
    // }}}
    // {{{ testScandirFile
    public function testScandirFile()
    {
        $this->assertTrue($this->dst->createFile('a'));

        $this->assertSame(['.', '..', 'a'], scandir($this->url));
    }
    // }}}
    // {{{ testScandirDir
    public function testScandirDir()
    {
        $this->assertTrue($this->dst->mkdir('a'));

        $this->assertSame(['.', '..', 'a'], scandir($this->url));
    }
    // }}}
    // {{{ testScandirFileDir
    public function testScandirDirFile()
    {
        $this->assertTrue($this->dst->createFile('a'));
        $this->assertTrue($this->dst->mkdir('b'));

        $this->assertSame(['.', '..', 'a', 'b'], scandir($this->url));
    }
    // }}}
    // {{{ testScandirFileSortDescending
    public function testScandirFileSortDescending()
    {
        $this->assertTrue($this->dst->createFile('a'));
        $this->assertTrue($this->dst->createFile('b'));
        $this->assertTrue($this->dst->createFile('c'));

        $this->assertSame(['c', 'b', 'a', '..', '.'], scandir($this->url, SCANDIR_SORT_DESCENDING));
    }
    // }}}

    // {{{ testFileGetContents
    public function testFileGetContents()
    {
        $this->assertTrue($this->dst->createFile('a'));

        $this->assertSame('testString', file_get_contents($this->url . 'a'));
    }
    // }}}
    // {{{ testFile
    public function testFile()
    {
        $this->assertTrue($this->dst->createFile('a'));

        $this->assertSame(['testString'], file($this->url . 'a'));
    }
    // }}}

    // {{{ testStat
    public function testStat()
    {
        $this->assertTrue($this->dst->createFile('a'));
        $this->assertTrue($this->dst->touch('a', 0777, 499137660));

        stat($this->url . '/a');
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
