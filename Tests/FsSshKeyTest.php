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
            'privateKeyFile' => __DIR__ . '/../' . $GLOBALS['SSH_PRIVATE_KEY'],
            'publicKeyFile' => __DIR__ . '/../' . $GLOBALS['SSH_PUBLIC_KEY'],
            'fingerprint' => $GLOBALS['SSH_FINGERPRINT'],
        );

        $newParams = array_merge($params, $override);

        return new FsSshTestClass($newParams);
    }
    // }}}

    // {{{ testInaccessiblePrivateKeyFile
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage SSH key file not accessible: "filedoesntexist".
     */
    public function testInaccessiblePrivateKeyFile()
    {
        $params = array(
            'privateKeyFile' => 'filedoesntexist',
        );

        $fs = $this->createTestClass($params);
        $fs->ls('*');
    }
    // }}}
    // {{{ testInaccessiblePublicKeyFile
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage SSH key file not accessible: "filedoesntexist".
     */
    public function testInaccessiblePublicKeyFile()
    {
        $params = array(
            'publicKeyFile' => 'filedoesntexist',
        );

        $fs = $this->createTestClass($params);
        $fs->ls('*');
    }
    // }}}
    // {{{ testGeneratePublicKey
    public function testGeneratePublicKey()
    {
        $params = array(
            'path' => $GLOBALS['REMOTE_DIR'] . 'Temp',
            'scheme' => 'ssh2.sftp',
            'host' => $GLOBALS['REMOTE_HOST'],
            'port' => '22',
            'user' => $GLOBALS['REMOTE_USER'],
            'pass' => $GLOBALS['SSH_KEYPASS'],
            'tmp' => '/tmp',
            'privateKeyFile' => __DIR__ . '/../' . $GLOBALS['SSH_PRIVATE_KEY'],
            'fingerprint' => $GLOBALS['SSH_FINGERPRINT'],
        );

        $fs =  new FsSshTestClass($params);
        $fs->ls('*');
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
