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
            'private' => __DIR__ . '/../' . $GLOBALS['SSH_PRIVATE_KEY'],
            'public' => __DIR__ . '/../' . $GLOBALS['SSH_PUBLIC_KEY'],
            'fingerprint' => $GLOBALS['SSH_FINGERPRINT'],
        );

        $newParams = array_merge($params, $override);

        return new FsSshTestClass($newParams);
    }
    // }}}

    // {{{ testInaccessiblePrivateKey
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage Invalid SSH key "filedoesntexist".
     */
    public function testInaccessiblePrivateKey()
    {
        $params = array(
            'private' => 'filedoesntexist',
        );

        $fs = $this->createTestClass($params);
        $fs->ls('*');
    }
    // }}}
    // {{{ testInaccessiblePublicKey
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage Invalid SSH key "filedoesntexist".
     */
    public function testInaccessiblePublicKey()
    {
        $params = array(
            'public' => 'filedoesntexist',
        );

        $fs = $this->createTestClass($params);
        $fs->ls('*');
    }
    // }}}
    // {{{ testGeneratePublicKey
    public function testGeneratePublicKey()
    {
        $params = array(
            'tmp' => '/tmp',
        );

        $fs = $this->createTestClass($params);
        $fs->ls('*');
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
