<?php

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
        );

        $newParams = array_merge($params, $override);

        return new Depage\Fs\FsFtp($newParams);
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

        $fs = new Depage\Fs\FsFtp($params);
        $this->assertTrue($fs->test());
    }
    // }}}

    // {{{ testLateConnectInvalidDirectoryFail
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @todo figure out "connect() failed: Connection refused" error
     */
    public function testLateConnectInvalidDirectoryFail()
    {
        return parent::testLateConnectInvalidDirectoryFail();
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
