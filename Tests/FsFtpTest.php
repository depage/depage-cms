<?php

namespace Depage\Fs\Tests;

use Depage\Fs\FsFtp;

class FsFtpTest extends TestRemote
{
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
            'caCert' => $GLOBALS['CA_CERT'],
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
            'caCert' => $GLOBALS['CA_CERT'],
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

    // {{{ testLateConnectInvalidDirectoryFail
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * 
     * override,
     * ftp stream wrappers give weird error messages
     */
    public function testLateConnectInvalidDirectoryFail()
    {
        return parent::testLateConnectInvalidDirectoryFail();
    }
    // }}}

    // {{{ testTest
    public function testTest()
    {
        $this->assertTrue($this->fs->test());
        $this->deleteRemoteTestDir();
        $this->assertFalse($this->fs->test($error));
        // @todo fix error messeage in ftp stream wrapper
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
