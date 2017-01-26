<?php

namespace Depage\Fs\Tests;

use Depage\Fs\Tests\TestClasses\PublicSshKeyTestClass;

class PublicSshKeyTest extends \PHPUnit_Framework_TestCase
{
    // {{{ setUp
    public function setUp()
    {
        $this->keyPath = __DIR__ . '/' . $GLOBALS['PUBLIC_RSA_KEY'];
        $this->testKey = file_get_contents($this->keyPath);
    }
    // }}}
    // {{{ generateTestObject
    /**
     * factory hook, override to also test class children
     */
    public function generateTestObject($data, $tmpDir = false)
    {
        return new PublicSshKeyTestClass($data, $tmpDir);
    }
    // }}}

    // {{{ testKeyFileExists
    public function testKeyFileExists()
    {
        $key = $this->generateTestObject($this->keyPath);
        $this->assertEquals($this->keyPath, $key->__toString());
    }
    // }}}
    // {{{ testKeyFileExistsFail
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage SSH key file not accessible: "filedoesntexist".
     */
    public function testKeyFileExistsFail()
    {
        $key = $this->generateTestObject('filedoesntexist');
    }
    // }}}
    // {{{ testCreateKeyFile
    public function testCreateKeyFile()
    {
        $key = $this->generateTestObject($this->testKey, '/tmp');

        $this->assertTrue(is_file($key->__toString()));
        $this->assertEquals($this->testKey, file_get_contents($key->__toString()));
    }
    // }}}
    // {{{ testCreateKeyFileAndClean
    public function testCreateKeyFileAndClean()
    {
        $key = $this->generateTestObject($this->testKey, '/tmp');

        $this->assertTrue(is_file($key->__toString()));

        $key->clean();
        $this->assertFalse(file_exists($key->__toString()));
    }
    // }}}
    // {{{ testCreateKeyFileAndCleanFail
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage Cannot delete temporary key file
     */
    public function testCreateKeyFileAndCleanFail()
    {
        $key = $this->generateTestObject($this->testKey, '/tmp');
        $this->assertTrue(is_file($key->__toString()));

        unlink($key->__toString());

        $key->clean();
    }
    // }}}
    // {{{ testCreateKeyFileTmpDirNotWritable
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage Cannot write to temporary key file directory "tmpdirdoesntexist".
     */
    public function testCreateKeyFileTmpDirNotWritable()
    {
        $key = $this->generateTestObject($this->testKey, 'tmpdirdoesntexist');
    }
    // }}}
    // {{{ testCreateKeyFileWriteError
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage Cannot create temporary key file
     */
    public function testCreateKeyFileWriteError()
    {
        $key = $this->generateTestObject('writeFail!', '/tmp');
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
