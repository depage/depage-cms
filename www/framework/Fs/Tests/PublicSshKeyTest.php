<?php

namespace Depage\Fs\Tests;

use Depage\Fs\Tests\TestClasses\PublicSshKeyTestClass;
use PHPUnit\Framework\TestCase;
use Depage\Fs\Exceptions\FsException;

class PublicSshKeyTest extends TestCase
{
    // {{{ setUp
    public function setUp():void
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
    public function testKeyFileExistsFail()
    {
        $this->expectException(FsException::class);
        $this->expectExceptionMessage("SSH key file not accessible: \"filedoesntexist\".");

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
    public function testCreateKeyFileAndCleanFail()
    {
        $this->expectException(FsException::class);
        $this->expectExceptionMessage("Cannot delete temporary key file");

        $key = $this->generateTestObject($this->testKey, '/tmp');
        $this->assertTrue(is_file($key->__toString()));

        unlink($key->__toString());

        $key->clean();
    }
    // }}}
    // {{{ testCreateKeyFileTmpDirNotWritable
    public function testCreateKeyFileTmpDirNotWritable()
    {
        $this->expectException(FsException::class);
        $this->expectExceptionMessage("Cannot write to temporary key file directory \"tmpdirdoesntexist\".");

        $key = $this->generateTestObject($this->testKey, 'tmpdirdoesntexist');
    }
    // }}}
    // {{{ testCreateKeyFileWriteError
    public function testCreateKeyFileWriteError()
    {
        $this->expectException(FsException::class);
        $this->expectExceptionMessage("Cannot create temporary key file");

        $key = $this->generateTestObject('writeFail!', '/tmp');
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
