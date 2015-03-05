<?php

class FsFtpTest extends TestRemote
{
    // {{{ createTestClass
    public function createTestClass($override = array())
    {
        $params = array(
            'path' => '/Temp',
            'scheme' => 'ftp',
            'host' => $GLOBALS['REMOTE_HOST'],
            'port' => 21,
            'user' => $GLOBALS['REMOTE_USER'],
            'pass' => $GLOBALS['REMOTE_PASS'],
        );

        $newParams = array_merge($params, $override);

        return new FsFtpTestClass($newParams);
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
        );

        $fs = new FsFtpTestClass($params);
        $this->assertTrue($fs->test());
    }
    // }}}

    // {{{ testLateConnectInvalidDirectoryFail
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage No such file or directory
     */
    public function testLateConnectInvalidDirectoryFail()
    {
        return parent::testLateConnectInvalidDirectoryFail();
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
