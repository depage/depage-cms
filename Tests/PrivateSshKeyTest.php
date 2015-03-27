<?php

namespace Depage\Fs;

require_once(__DIR__ . '/PublicSshKeyTest.php');

class PrivateSshKeyTest extends PublicSshKeyTest
{
    // {{{ setUp
    public function setUp()
    {
        $this->keyPath = __DIR__ . '/../' . $GLOBALS['SSH_PRIVATE_KEY'];
        $this->publicKeyPath = __DIR__ . '/../' . $GLOBALS['SSH_PUBLIC_KEY'];
        $this->testKey = file_get_contents($this->keyPath);
    }
    // }}}
    // {{{ generateTestObject
    /**
     * factory hook, override to also test class children
     */
    public function generateTestObject($data, $tmpDir = false)
    {
        return new PrivateSshKey($data, $tmpDir);
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
    // {{{ testInvalidKey
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage Invalid SSH private key format (PEM format required).
     */
    public function testInvalidKey()
    {
        $this->generateTestObject($this->publicKeyPath);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
