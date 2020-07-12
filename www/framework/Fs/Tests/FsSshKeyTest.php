<?php

namespace Depage\Fs\Tests;

use Depage\Fs\Tests\TestClasses\FsSshTestClass;
use Depage\Fs\Exceptions\FsException;

class FsSshKeyTest extends FsSshTest
{
    // {{{ createTestObject
    public function createTestObject($override = array())
    {
        $params = [
            'path' => '/home/testuser/Temp',
            'scheme' => 'ssh2.sftp',
            'host' => $GLOBALS['REMOTE_HOST'],
            'port' => '22',
            'user' => $GLOBALS['REMOTE_USER'],
            'pass' => $GLOBALS['SSH_KEYPASS'],
            'privateKeyFile' => __DIR__ . '/' . $GLOBALS['PRIVATE_RSA_KEY'],
            'publicKeyFile' => __DIR__ . '/' . $GLOBALS['PUBLIC_RSA_KEY'],
            'fingerprint' => $GLOBALS['SSH_FINGERPRINT'],
        ];

        $newParams = array_merge($params, $override);

        return new FsSshTestClass($newParams);
    }
    // }}}
    // {{{ createTestObjectWithoutKeys
    public function createTestObjectWithoutKeys($override = array())
    {
        $params = array(
            'path' => '/home/testuser/Temp',
            'scheme' => 'ssh2.sftp',
            'host' => $GLOBALS['REMOTE_HOST'],
            'port' => '22',
            'user' => $GLOBALS['REMOTE_USER'],
            'pass' => $GLOBALS['SSH_KEYPASS'],
            'fingerprint' => $GLOBALS['SSH_FINGERPRINT'],
        );

        $newParams = array_merge($params, $override);

        return new \Depage\Fs\FsSsh($newParams);
    }
    // }}}

    // {{{ testInaccessiblePrivateKeyFile
    public function testInaccessiblePrivateKeyFile()
    {
        $this->expectException(FsException::class);
        $this->expectExceptionMessage("SSH key file not accessible: \"filedoesntexist\".");

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
            'privateKey' => file_get_contents(__DIR__ . '/' . $GLOBALS['PRIVATE_RSA_KEY']),
            'publicKeyFile' => __DIR__ . '/' . $GLOBALS['PUBLIC_RSA_KEY'],
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
            'privateKeyFile' => __DIR__ . '/' . $GLOBALS['PRIVATE_RSA_KEY'],
            'publicKey' => file_get_contents(__DIR__ . '/' . $GLOBALS['PUBLIC_RSA_KEY']),
        );

        $fs = $this->createTestObjectWithoutKeys($params);
        $this->assertTrue($fs->test());
    }
    // }}}

    // {{{ testConnectInvalidPrivateKeyString
    public function testConnectInvalidPrivateKeyString()
    {
        $this->expectException(FsException::class);
        $this->expectExceptionMessage("Invalid SSH private key format (PEM format required).");

        $params = array(
            'tmp' => '/tmp',
            'privateKey' => 'iamnotaprivatesshkey' ,
            'publicKeyFile' => __DIR__ . '/' . $GLOBALS['PUBLIC_RSA_KEY'],
        );

        $fs = $this->createTestObjectWithoutKeys($params);
        $fs->ls('*');
    }
    // }}}
    // {{{ testConnectInvalidPublicKeyString
    public function testConnectInvalidPublicKeyString()
    {
        $this->expectException(FsException::class);
        $this->expectExceptionMessage("ssh2_auth_pubkey_file(): Authentication failed for testuser using public key: Invalid public key data");

        $params = array(
            'tmp' => '/tmp',
            'privateKeyFile' => __DIR__ . '/' . $GLOBALS['PRIVATE_RSA_KEY'],
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
            'privateKey' => file_get_contents(__DIR__ . '/' . $GLOBALS['PRIVATE_RSA_KEY']),
            'publicKey' => file_get_contents(__DIR__ . '/' . $GLOBALS['PUBLIC_RSA_KEY']),
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
            'privateKeyFile' => __DIR__ . '/' . $GLOBALS['PRIVATE_RSA_KEY'],
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
            'privateKey' => file_get_contents(__DIR__ . '/' . $GLOBALS['PRIVATE_RSA_KEY']),
        );

        $fs = $this->createTestObjectWithoutKeys($params);
        $this->assertTrue($fs->test());
    }
    // }}}

    // {{{ testIsValidKeyCombination
    public function testIsValidKeyCombination()
    {
        $perms = array(
            array('privateKeyFile' => 0, 'publicKeyFile' => 0, 'tmp' => 0, 'privateKey' => 0, 'publicKey' => 0, 'pass' => 0),
            array('privateKeyFile' => 0, 'publicKeyFile' => 0, 'tmp' => 0, 'privateKey' => 0, 'publicKey' => 1, 'pass' => 0),
            array('privateKeyFile' => 0, 'publicKeyFile' => 0, 'tmp' => 0, 'privateKey' => 1, 'publicKey' => 0, 'pass' => 0),
            array('privateKeyFile' => 0, 'publicKeyFile' => 0, 'tmp' => 0, 'privateKey' => 1, 'publicKey' => 1, 'pass' => 0),
            array('privateKeyFile' => 0, 'publicKeyFile' => 0, 'tmp' => 1, 'privateKey' => 0, 'publicKey' => 0, 'pass' => 0),
            array('privateKeyFile' => 0, 'publicKeyFile' => 0, 'tmp' => 1, 'privateKey' => 0, 'publicKey' => 1, 'pass' => 0),
            array('privateKeyFile' => 0, 'publicKeyFile' => 0, 'tmp' => 1, 'privateKey' => 1, 'publicKey' => 0, 'pass' => 1),
            array('privateKeyFile' => 0, 'publicKeyFile' => 0, 'tmp' => 1, 'privateKey' => 1, 'publicKey' => 1, 'pass' => 1),
            array('privateKeyFile' => 0, 'publicKeyFile' => 1, 'tmp' => 0, 'privateKey' => 0, 'publicKey' => 0, 'pass' => 0),
            array('privateKeyFile' => 0, 'publicKeyFile' => 1, 'tmp' => 0, 'privateKey' => 0, 'publicKey' => 1, 'pass' => 0),
            array('privateKeyFile' => 0, 'publicKeyFile' => 1, 'tmp' => 0, 'privateKey' => 1, 'publicKey' => 0, 'pass' => 0),
            array('privateKeyFile' => 0, 'publicKeyFile' => 1, 'tmp' => 0, 'privateKey' => 1, 'publicKey' => 1, 'pass' => 0),
            array('privateKeyFile' => 0, 'publicKeyFile' => 1, 'tmp' => 1, 'privateKey' => 0, 'publicKey' => 0, 'pass' => 0),
            array('privateKeyFile' => 0, 'publicKeyFile' => 1, 'tmp' => 1, 'privateKey' => 0, 'publicKey' => 1, 'pass' => 0),
            array('privateKeyFile' => 0, 'publicKeyFile' => 1, 'tmp' => 1, 'privateKey' => 1, 'publicKey' => 0, 'pass' => 1),
            array('privateKeyFile' => 0, 'publicKeyFile' => 1, 'tmp' => 1, 'privateKey' => 1, 'publicKey' => 1, 'pass' => 1),
            array('privateKeyFile' => 1, 'publicKeyFile' => 0, 'tmp' => 0, 'privateKey' => 0, 'publicKey' => 0, 'pass' => 0),
            array('privateKeyFile' => 1, 'publicKeyFile' => 0, 'tmp' => 0, 'privateKey' => 0, 'publicKey' => 1, 'pass' => 0),
            array('privateKeyFile' => 1, 'publicKeyFile' => 0, 'tmp' => 0, 'privateKey' => 1, 'publicKey' => 0, 'pass' => 0),
            array('privateKeyFile' => 1, 'publicKeyFile' => 0, 'tmp' => 0, 'privateKey' => 1, 'publicKey' => 1, 'pass' => 0),
            array('privateKeyFile' => 1, 'publicKeyFile' => 0, 'tmp' => 1, 'privateKey' => 0, 'publicKey' => 0, 'pass' => 1),
            array('privateKeyFile' => 1, 'publicKeyFile' => 0, 'tmp' => 1, 'privateKey' => 0, 'publicKey' => 1, 'pass' => 1),
            array('privateKeyFile' => 1, 'publicKeyFile' => 0, 'tmp' => 1, 'privateKey' => 1, 'publicKey' => 0, 'pass' => 1),
            array('privateKeyFile' => 1, 'publicKeyFile' => 0, 'tmp' => 1, 'privateKey' => 1, 'publicKey' => 1, 'pass' => 1),
            array('privateKeyFile' => 1, 'publicKeyFile' => 1, 'tmp' => 0, 'privateKey' => 0, 'publicKey' => 0, 'pass' => 1),
            array('privateKeyFile' => 1, 'publicKeyFile' => 1, 'tmp' => 0, 'privateKey' => 0, 'publicKey' => 1, 'pass' => 1),
            array('privateKeyFile' => 1, 'publicKeyFile' => 1, 'tmp' => 0, 'privateKey' => 1, 'publicKey' => 0, 'pass' => 1),
            array('privateKeyFile' => 1, 'publicKeyFile' => 1, 'tmp' => 0, 'privateKey' => 1, 'publicKey' => 1, 'pass' => 1),
            array('privateKeyFile' => 1, 'publicKeyFile' => 1, 'tmp' => 1, 'privateKey' => 0, 'publicKey' => 0, 'pass' => 1),
            array('privateKeyFile' => 1, 'publicKeyFile' => 1, 'tmp' => 1, 'privateKey' => 0, 'publicKey' => 1, 'pass' => 1),
            array('privateKeyFile' => 1, 'publicKeyFile' => 1, 'tmp' => 1, 'privateKey' => 1, 'publicKey' => 0, 'pass' => 1),
            array('privateKeyFile' => 1, 'publicKeyFile' => 1, 'tmp' => 1, 'privateKey' => 1, 'publicKey' => 1, 'pass' => 1),
        );

        foreach($perms as $perm) {
            extract($perm);
            $fs = new FsSshTestClass();
            $fs->privateKeyFile = $privateKeyFile;
            $fs->publicKeyFile = $publicKeyFile;
            $fs->privateKey = $privateKey;
            $fs->publicKey = $publicKey;
            $fs->tmp = $tmp;

            $this->assertEquals($pass, $fs->isValidKeyCombination());
        }
    }
    // }}}
    // {{{ testInvalidKeyCombination
    public function testInvalidKeyCombination()
    {
        $this->expectException(FsException::class);
        $this->expectExceptionMessage("Invalid SSH key combination.");

        $params = array(
            'publicKey' => $GLOBALS['PUBLIC_RSA_KEY'],
        );

        $fs = $this->createTestObjectWithoutKeys($params);
        $fs->ls('*');
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
