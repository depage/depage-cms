<?php

namespace Depage\Fs\Tests;

use Depage\Fs\FsFtp;
use Depage\Fs\Streams\FtpCurl;

class FsFtpTest extends OperationsTestCase
{
    // {{{ constructor
    public function __construct()
    {
        parent::__construct();

        $this->cert = $this->root . '/' . $GLOBALS['CA_CERT'];
    }
    // }}}

    // {{{ setUp
    public function setUp()
    {
        FtpCurl::disconnect();
        parent::setUp();
    }
    // }}}

    // {{{ createDst
    public function createDst()
    {
        return new HelperFsRemote('/Temp');
    }
    // }}}
    // {{{ createTestObject
    public function createTestObject($override = array())
    {
        $params = array(
            'path' => '/Temp',
            'scheme' => 'ftp',
            'host' => $GLOBALS['REMOTE_HOST'],
            'port' => 21,
            'user' => $GLOBALS['REMOTE_USER'],
            'pass' => $GLOBALS['REMOTE_PASS'],
            'caCert' => $this->cert,
        );

        $newParams = array_merge($params, $override);

        return new FsFtp($newParams);
    }
    // }}}

    // {{{ testDefaultPort
    public function testDefaultPort()
    {
        $params = array(
            'path' => '/Temp',
            'scheme' => 'ftp',
            'host' => $GLOBALS['REMOTE_HOST'],
            'user' => $GLOBALS['REMOTE_USER'],
            'pass' => $GLOBALS['REMOTE_PASS'],
            'caCert' => $this->cert,
        );

        $fs = new FsFtp($params);
        $this->assertTrue($fs->test());
    }
    // }}}
    // {{{ testSslFail
    public function testSslFail()
    {
        $params = array(
            'path' => '/Temp',
            'scheme' => 'ftp',
            'host' => $GLOBALS['REMOTE_HOST'],
            'user' => $GLOBALS['REMOTE_USER'],
            'pass' => $GLOBALS['REMOTE_PASS'],
        );

        $fs = new FsFtp($params);

        $this->assertFalse($fs->test($error));
        $this->assertSame('SSL certificate problem: unable to get local issuer certificate', $error);
    }
    // }}}
    // {{{ testTest
    /**
     * override, sending data to server actually happens at stream_flush
     * so Fs::test doesn't get a write specific error
     */
    public function testTest()
    {
        $this->assertTrue($this->fs->test());
        $this->assertTrue($this->dst->tearDown());
        $this->assertFalse($this->fs->test($error));
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
