<?php

require_once(__DIR__ . '/FsSshTest.php');

class FsSshKeyTest extends FsSshTest
{
    // {{{ createTestObject
    public function createTestObject($override = array())
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

        return new Depage\Fs\FsSsh($newParams);
    }
    // }}}
    // {{{ createTestObjectWithoutKeys
    public function createTestObjectWithoutKeys($override = array())
    {
        $params = array(
            'path' => $GLOBALS['REMOTE_DIR'] . 'Temp',
            'scheme' => 'ssh2.sftp',
            'host' => $GLOBALS['REMOTE_HOST'],
            'port' => '22',
            'user' => $GLOBALS['REMOTE_USER'],
            'pass' => $GLOBALS['SSH_KEYPASS'],
            'fingerprint' => $GLOBALS['SSH_FINGERPRINT'],
        );

        $newParams = array_merge($params, $override);

        return new Depage\Fs\FsSsh($newParams);
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

        $fs = $this->createTestObject($params);
        $fs->ls('*');
    }
    // }}}
    // {{{ testConnectPrivateKeyString
    public function testConnectPrivateKeyString()
    {
        $params = array(
            'tmp' => '/tmp',
            'privateKey' => file_get_contents(__DIR__ . '/../' . $GLOBALS['SSH_PRIVATE_KEY']),
            'publicKeyFile' => __DIR__ . '/../' . $GLOBALS['SSH_PUBLIC_KEY'],
        );

        $fs = $this->createTestObjectWithoutKeys($params);
        $this->assertTrue($fs->test());
    }
    // }}}
    // {{{ testConnectPublicKeyString
    public function testConnectPublicKeyString()
    {
        $params = array(
            'tmp' => '/tmp',
            'privateKeyFile' => __DIR__ . '/../' . $GLOBALS['SSH_PRIVATE_KEY'],
            'publicKey' => file_get_contents(__DIR__ . '/../' . $GLOBALS['SSH_PUBLIC_KEY']),
        );

        $fs = $this->createTestObjectWithoutKeys($params);
        $this->assertTrue($fs->test());
    }
    // }}}
    // {{{ testConnectInvalidPrivateKeyString
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage Invalid SSH private key format (PEM format required).
     */
    public function testConnectInvalidPrivateKeyString()
    {
        $params = array(
            'tmp' => '/tmp',
            'privateKey' => 'iamnotaprivatesshkey' ,
            'publicKeyFile' => __DIR__ . '/../' . $GLOBALS['SSH_PUBLIC_KEY'],
        );

        $fs = $this->createTestObjectWithoutKeys($params);
        $fs->ls('*');
    }
    // }}}
    // {{{ testConnectInvalidPublicKeyString
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage ssh2_auth_pubkey_file(): Authentication failed for testuser using public key: Invalid public key data
     */
    public function testConnectInvalidPublicKeyString()
    {
        $params = array(
            'tmp' => '/tmp',
            'privateKeyFile' => __DIR__ . '/../' . $GLOBALS['SSH_PRIVATE_KEY'],
            'publicKey' => 'iamnotapublicsshkey',
        );

        $fs = $this->createTestObjectWithoutKeys($params);
        $fs->ls('*');
    }
    // }}}
    // {{{ testConnectKeyPairStrings
    public function testConnectKeyPairStrings()
    {
        $params = array(
            'tmp' => '/tmp',
            'privateKey' => file_get_contents(__DIR__ . '/../' . $GLOBALS['SSH_PRIVATE_KEY']),
            'publicKey' => file_get_contents(__DIR__ . '/../' . $GLOBALS['SSH_PUBLIC_KEY']),
        );

        $fs = $this->createTestObjectWithoutKeys($params);
        $this->assertTrue($fs->test());
    }
    // }}}
    // {{{ testConnectGeneratePublicKeyFromPrivateKeyFile
    public function testConnectGeneratePublicKeyFromPrivateKeyFile()
    {
        $params = array(
            'tmp' => '/tmp',
            'privateKeyFile' => __DIR__ . '/../' . $GLOBALS['SSH_PRIVATE_KEY'],
        );

        $fs = $this->createTestObjectWithoutKeys($params);
        $this->assertTrue($fs->test());
    }
    // }}}
    // {{{ testConnectGeneratePublicKeyFromPrivateKeyString
    public function testConnectGeneratePublicKeyFromPrivateKeyString()
    {
        $params = array(
            'tmp' => '/tmp',
            'privateKey' => file_get_contents(__DIR__ . '/../' . $GLOBALS['SSH_PRIVATE_KEY']),
        );

        $fs = $this->createTestObjectWithoutKeys($params);
        $this->assertTrue($fs->test());
    }
    // }}}
    // {{{ testInvalidKeyCombination
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage Invalid SSH key combination.
     */
    public function testInvalidKeyCombination()
    {
        $params = array(
            'publicKey' => $GLOBALS['SSH_PUBLIC_KEY'],
        );

        $fs = $this->createTestObjectWithoutKeys($params);
        $fs->ls('*');
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
