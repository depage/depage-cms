<?php

namespace Depage\Fs\Tests;

use Depage\Fs\FsSsh;

class FsSshTest extends OperationsTestCase
{
    // {{{ createDst
    protected function createDst()
    {
        return new HelperFsRemote('/Temp');
    }
    // }}}
    // {{{ createTestObject
    public function createTestObject($override = array())
    {
        $params = array(
            'path' => '/home/testuser/Temp',
            'scheme' => 'ssh2.sftp',
            'host' => $GLOBALS['REMOTE_HOST'],
            'port' => 22,
            'user' => $GLOBALS['REMOTE_USER'],
            'pass' => $GLOBALS['REMOTE_PASS'],
            'fingerprint' => $GLOBALS['SSH_FINGERPRINT'],
        );

        $newParams = array_merge($params, $override);

        return new FsSsh($newParams);
    }
    // }}}

    // {{{ testGetFingerprint
    public function testGetFingerprint()
    {
        $caseInsensitiveCompare = strcasecmp($GLOBALS['SSH_FINGERPRINT'], $this->fs->getFingerprint());
        $this->assertSame(0, $caseInsensitiveCompare);
    }
    // }}}
    // {{{ testWrongFingerprint
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage SSH RSA Fingerprints don't match.
     */
    public function testWrongFingerprint()
    {
        $fs = $this->createTestObject(array('fingerprint' => 'wrongfingerprint'));
        $fs->ls('*');
    }
    // }}}
    // {{{ testDefaultPort
    public function testDefaultPort()
    {
        $params = array(
            'path' => '/home/testuser/Temp',
            'scheme' => 'ssh2.sftp',
            'host' => $GLOBALS['REMOTE_HOST'],
            'user' => $GLOBALS['REMOTE_USER'],
            'pass' => $GLOBALS['REMOTE_PASS'],
            'fingerprint' => $GLOBALS['SSH_FINGERPRINT'],
        );

        $fs = new FsSsh($params);
        $this->assertTrue($fs->test());
    }
    // }}}
    // {{{ testLateConnectInvalidDirectoryFail
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage Unable to open ssh2.sftp://
     * @todo ambiguous error message
     */
    public function testLateConnectInvalidDirectoryFail()
    {
        return parent::testLateConnectInvalidDirectoryFail();
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
