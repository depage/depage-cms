<?php

namespace Depage\Fs\Tests;

use Depage\Fs\Tests\TestClasses\PrivateSshKeyTestClass;
use Depage\Fs\Exceptions\FsException;

class PrivateSshKeyTest extends PublicSshKeyTest
{
    // {{{ setUp
    public function setUp():void
    {
        $this->keyPath = __DIR__ . '/' . $GLOBALS['PRIVATE_RSA_KEY'];
        $this->publicKeyPath = __DIR__ . '/' . $GLOBALS['PUBLIC_RSA_KEY'];
        $this->testKey = file_get_contents($this->keyPath);
    }
    // }}}
    // {{{ generateTestObject
    /**
     * factory hook, override to also test class children
     */
    public function generateTestObject($data, $tmpDir = false)
    {
        return new PrivateSshKeyTestClass($data, $tmpDir);
    }
    // }}}

    // {{{ testExtractPublicKey
    public function testExtractPublicKey()
    {
        $key = $this->generateTestObject($this->keyPath);

        // ...comparing ssh keys is hard
        $public = $key->extractPublicKey('/tmp');
        $extractedKeyString = trim(file_get_contents($public));

        $publicKeyString = file_get_contents($this->publicKeyPath);
        preg_match('/ssh-rsa \S*/', $publicKeyString, $match);
        $publicKeyStringTrimmed = trim($match[0]);

        $this->assertEquals($publicKeyStringTrimmed, $extractedKeyString);
    }
    // }}}
    // {{{ testExtractPublicKeyDsa
    public function testExtractPublicKeyDsa()
    {
        $this->expectException(FsException::class);
        $this->expectExceptionMessage("Currently public key generation is only supported for RSA keys.");

        $key = $this->generateTestObject(__DIR__ . '/' . $GLOBALS['PRIVATE_DSA_KEY']);

        $public = $key->extractPublicKey('/tmp');
    }
    // }}}
    // {{{ testInvalidKey
    public function testInvalidKey()
    {
        $this->expectException(FsException::class);
        $this->expectExceptionMessage("Invalid SSH private key format (PEM format required).");

        $this->generateTestObject($this->publicKeyPath);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
