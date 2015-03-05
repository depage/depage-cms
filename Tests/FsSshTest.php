<?php

class FsSshTest extends TestRemote
{
    // {{{ createTestClass
    public function createTestClass($override = array())
    {
        $params = array(
            'path' => $GLOBALS['REMOTE_DIR'] . 'Temp',
            'scheme' => 'ssh2.sftp',
            'host' => $GLOBALS['REMOTE_HOST'],
            'port' => '22',
            'user' => $GLOBALS['REMOTE_USER'],
            'pass' => $GLOBALS['REMOTE_PASS'],
            'fingerprint' => $GLOBALS['SSH_FINGERPRINT'],
        );

        $newParams = array_merge($params, $override);

        return new FsSshTestClass($newParams);
    }
    // }}}

    // {{{ testGetFingerprint
    public function testGetFingerprint()
    {
        $caseInsensitiveCompare = strcasecmp($GLOBALS['SSH_FINGERPRINT'], $this->fs->getFingerprint());
        $this->assertSame(0, $caseInsensitiveCompare);
    }
    // }}}
    // {{{ testDefaultPort
    public function testDefaultPort()
    {
        $params = array(
            'path' => $GLOBALS['REMOTE_DIR'] . 'Temp',
            'scheme' => 'ssh2.sftp',
            'host' => $GLOBALS['REMOTE_HOST'],
            'user' => $GLOBALS['REMOTE_USER'],
            'pass' => $GLOBALS['REMOTE_PASS'],
            'fingerprint' => $GLOBALS['SSH_FINGERPRINT'],
        );

        $fs = new FsSshTestClass($params);
        $this->assertTrue($fs->test());
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
