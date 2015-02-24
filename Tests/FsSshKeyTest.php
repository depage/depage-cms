<?php

class FsSshKeyTest extends TestRemote
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
            'pass' => $GLOBALS['SSH_KEYPASS'],
            'key' => __DIR__ . '/../' . $GLOBALS['SSH_KEY'],
            'fingerprint' => $GLOBALS['SSH_FINGERPRINT'],
        );

        $newParams = array_merge($params, $override);

        return new FsSshTestClass($newParams);
    }
    // }}}

    // {{{ testInaccessiblePrivateKey
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage Cannot read SSH private key file "filedoesntexist".
     */
    public function testInaccessiblePrivateKey()
    {
        $params = array(
            'key' => 'filedoesntexist',
        );

        $fs = $this->createTestClass($params);
        $fs->ls('*');
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
