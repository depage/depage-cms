<?php

namespace Depage\Fs;

require_once(__DIR__ . '/PublicSshKeyTest.php');

class PrivateSshKeyTest extends PublicSshKeyTest
{
    // {{{ setUp
    public function setUp()
    {
        $this->keyPath = __DIR__ . '/../' . $GLOBALS['SSH_PRIVATE_KEY'];
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
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
