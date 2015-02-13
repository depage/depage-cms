<?php

class TestBase extends PHPUnit_Framework_TestCase
{
    // {{{ setUp
    public function setUp()
    {
        $this->testRootDir = __DIR__;
        $this->localDir = $this->createLocalTestDir();
        $this->remoteDir = $this->createRemoteTestDir();
        chdir($this->localDir);
        $this->fs = $this->createTestClass();
    }
    // }}}
    // {{{ tearDown
    public function tearDown()
    {
        $this->deleteRemoteTestDir();
        $this->deleteLocalTestDir();
        chdir($this->testRootDir);
    }
    // }}}
    // {{{ rmr
    protected function rmr($path)
    {
        if (is_dir($path)) {
            $scanDir = array_diff(scandir($path), array('.', '..'));

            foreach ($scanDir as $nested) {
                $this->rmr($path . '/' . $nested);
            }
            rmdir($path);
        } else if (is_file($path)) {
            unlink($path);
        }
    }
    // }}}

    // {{{ createTestDir
    public function createTestDir($path)
    {
        $dir = $path . '/Temp';

        if (file_exists($dir)) {
            $this->rmr($dir);
            if (file_exists($dir)) {
                $this->fail('Test directory not clean: ' . $dir);
            }
        }

        mkdir($dir, 0777);
        chmod($dir, 0777);
        $this->assertTrue(is_dir($dir));

        return $dir;
    }
    // }}}
    // {{{ createLocalTestDir
    public function createLocalTestDir()
    {
        return $this->createTestDir($this->testRootDir);
    }
    // }}}
    // {{{ deleteLocalTestDir
    public function deleteLocalTestDir()
    {
        $this->rmr($this->localDir);
    }
    // }}}

    // {{{ createTestFile
    protected function createTestFile($path)
    {
        $testFile = fopen($path, 'w');
        fwrite($testFile, 'testString');
        fclose($testFile);
    }
    // }}}
    // {{{ confirmTestFile
    protected function confirmTestFile($path)
    {
        $contents = file($path);
        return $contents == array('testString');
    }
    // }}}

    // {{{ testLs
    public function testLs()
    {
        mkdir($this->remoteDir . '/testDir/testSubDir', 0777, true);
        mkdir($this->remoteDir . '/testDir/testAnotherSubDir', 0777, true);
        touch($this->remoteDir . '/testDir/testFile');
        touch($this->remoteDir . '/testDir/testAnotherFile');

        $lsReturn = $this->fs->ls('testDir/*');
        $expected = array(
            'testDir/testAnotherFile',
            'testDir/testAnotherSubDir',
            'testDir/testFile',
            'testDir/testSubDir',
        );

        $this->assertEquals($expected, $lsReturn);
    }
    // }}}
    // {{{ testLsDir
    public function testLsDir()
    {
        mkdir($this->remoteDir . '/testDir/testSubDir', 0777, true);
        mkdir($this->remoteDir . '/testDir/testAnotherSubDir', 0777, true);
        touch($this->remoteDir . '/testDir/testFile');
        touch($this->remoteDir . '/testDir/testAnotherFile');

        $lsDirReturn = $this->fs->lsDir('testDir/*');
        $expected = array(
            'testDir/testAnotherSubDir',
            'testDir/testSubDir',
        );

        $this->assertEquals($expected, $lsDirReturn);
    }
    // }}}
    // {{{ testLsFiles
    public function testLsFiles()
    {
        mkdir($this->remoteDir . '/testDir/testSubDir', 0777, true);
        mkdir($this->remoteDir . '/testDir/testAnotherSubDir', 0777, true);
        touch($this->remoteDir . '/testDir/testFile');
        touch($this->remoteDir . '/testDir/testAnotherFile');

        $lsFilesReturn  = $this->fs->lsFiles('testDir/*');
        $expected       = array(
            'testDir/testAnotherFile',
            'testDir/testFile',
        );

        $this->assertEquals($expected, $lsFilesReturn);
    }
    // }}}
    // {{{ testLsHidden
    public function testLsHidden()
    {
        mkdir($this->remoteDir . '/testDir/testSubDir', 0777, true);
        mkdir($this->remoteDir . '/testDir/.testSubDirHidden', 0777, true);
        touch($this->remoteDir . '/testDir/testFile');
        touch($this->remoteDir . '/testDir/.testFileHidden');

        $lsReturn = $this->fs->ls('testDir/*');
        $expected = array(
            'testDir/testFile',
            'testDir/testSubDir',
        );

        $this->assertEquals($expected, $lsReturn);

        $params = array('hidden' => true);
        $hiddenFs = $this->createTestClass($params);
        $lsReturn = $hiddenFs->ls('testDir/*');

        $expected = array(
            'testDir/.testFileHidden',
            'testDir/.testSubDirHidden',
            'testDir/testFile',
            'testDir/testSubDir',
        );

        $this->assertEquals($expected, $lsReturn);
    }
    // }}}
    // {{{ testLsRecursive
    public function testLsRecursive()
    {
        mkdir($this->remoteDir . '/testDir/abc/abc/abc', 0777, true);
        mkdir($this->remoteDir . '/testDir/abc/abcd/abcd', 0777, true);
        mkdir($this->remoteDir . '/testDir/abc/abcde/abcde', 0777, true);
        mkdir($this->remoteDir . '/testDir/abcd/abcde/abcde', 0777, true);
        touch($this->remoteDir . '/testDir/abcFile');
        touch($this->remoteDir . '/testDir/abc/abcFile');
        touch($this->remoteDir . '/testDir/abc/abcd/abcFile');
        touch($this->remoteDir . '/testDir/abcd/abcde/abcde/abcFile');

        $globReturn = $this->fs->ls('testDir');
        $this->assertEquals(array('testDir'), $globReturn);

        $globReturn = $this->fs->ls('*');
        $this->assertEquals(array('testDir'), $globReturn);

        $globReturn = $this->fs->ls('testDir/ab*');
        $expected = array(
            'testDir/abc',
            'testDir/abcd',
            'testDir/abcFile',
        );
        $this->assertEquals($expected, $globReturn);

        $globReturn = $this->fs->ls('testDir/ab*d/*');
        $expected = array(
            'testDir/abcd/abcde',
        );
        $this->assertEquals($expected, $globReturn);

        $globReturn = $this->fs->ls('testDir/ab?');
        $expected = array(
            'testDir/abc',
        );
        $this->assertEquals($expected, $globReturn);

        $globReturn = $this->fs->ls('*/*/*/*');
        $expected = array(
            'testDir/abc/abc/abc',
            'testDir/abc/abcd/abcd',
            'testDir/abc/abcd/abcFile',
            'testDir/abc/abcde/abcde',
            'testDir/abcd/abcde/abcde',
        );
        $this->assertEquals($expected, $globReturn);
    }
    // }}}
    // {{{ testCd
    public function testCd()
    {
        $pwd = $this->fs->pwd();
        mkdir($this->remoteDir . '/testDir');
        $this->fs->cd('testDir');
        $newPwd = $this->fs->pwd();

        $this->assertEquals($pwd . 'testDir/', $newPwd);
    }
    // }}}
    // {{{ testCdOutOfBaseDir
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage Cannot leave base directory
     */
    public function testCdOutOfBaseDir()
    {
        $basePwd = $this->fs->pwd();
        $pwd = preg_replace(';Temp/$;', '', $basePwd);
        $this->assertEquals($pwd . 'Temp/', $basePwd);

        $this->fs->cd($pwd);
    }
    // }}}
    // {{{ testCdOutOfBaseDirRelative
    /**
     * @expectedException           Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage    Cannot leave base directory
     */
    public function testCdOutOfBaseDirRelative()
    {
        $this->fs->cd('..');
    }
    // }}}
    // {{{ testCdFail
    /**
     * @expectedException           Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage    Directory not accessible
     */
    public function testCdFail()
    {
        $this->fs->cd('dirDoesntExist');
    }
    // }}}
    // {{{ testMkdir
    public function testMkdir()
    {
        $this->assertFalse(file_exists('testDir'));
        $this->assertFalse(file_exists('testDir/testDir'));

        $mkdirReturn = $this->fs->mkdir('testDir');
        $mkdirReturn = $this->fs->mkdir('testDir/testDir');

        $this->assertTrue(file_exists($this->remoteDir . '/testDir/testDir'));
    }
    // }}}
    // {{{ testRm
    public function testRm()
    {
        // create test nodes
        $this->fs->mkdir('testDir');
        $this->fs->mkdir('testDir/testSubDir');
        $this->fs->mkdir('testDir/testSubDir/testAnotherSubDir');
        $this->fs->putString('testDir/testFile', '');
        $this->fs->putString('testDir/testSubDir/testFile', '');
        $this->assertTrue(file_exists($this->remoteDir . '/testDir/testSubDir/testAnotherSubDir'));
        $this->assertTrue(file_exists($this->remoteDir . '/testDir/testSubDir/testFile'));
        $this->assertTrue(file_exists($this->remoteDir . '/testDir/testFile'));

        $this->fs->rm('testDir');
        $this->assertFalse(file_exists('testDir'));
    }
    // }}}

    // {{{ testMv
    public function testMv()
    {
        $this->createRemoteTestFile('testFile');
        $this->assertTrue($this->confirmTestFile($this->remoteDir . '/testFile'));
        $this->assertFalse(file_exists($this->remoteDir . '/testFile2'));

        $this->fs->mv('testFile', 'testFile2');
        $this->assertFalse(file_exists($this->remoteDir . '/testFile'));
        $this->assertTrue($this->confirmTestFile($this->remoteDir . '/testFile2'));
    }
    // }}}
    // {{{ testMvSourceDoesntExist
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     */
    public function testMvSourceDoesntExist()
    {
        // create test nodes
        mkdir($this->remoteDir . '/testDir/testSubDir/testAnotherSubDir', 0777, true);
        $this->assertFalse(file_exists('testDir/testFile'));

        $this->fs->mv('testDir/testFile', 'testDir/testSubDir/testFile');
    }
    // }}}

    // {{{ testGet
    public function testGet()
    {
        // create test node
        $this->createRemoteTestFile('testFile');

        $this->fs->get('testFile', 'testFile2');
        $this->assertTrue($this->confirmTestFile('testFile2'));
    }
    // }}}
    // {{{ testPut
    public function testPut()
    {
        // create test nodes
        $this->createTestFile('testFile');
        $this->assertTrue(file_exists('testFile'));
        $this->assertFalse(file_exists($this->remoteDir . '/testFile2'));

        $this->fs->put('testFile', 'testFile2');
        $this->assertTrue($this->confirmTestFile('testFile'));
        $this->assertTrue($this->confirmTestFile($this->remoteDir . '/testFile2'));
    }
    // }}}

    // {{{ testExists
    public function testExists()
    {
        mkdir($this->remoteDir . '/testDir');

        $this->assertTrue($this->fs->exists('testDir'));
        $this->assertFalse($this->fs->exists('i_dont_exist'));
    }
    // }}}
    // {{{ testFileInfo
    public function testFileInfo()
    {
        $this->createRemoteTestFile('testFile');
        $fileInfo = $this->fs->fileInfo('testFile');

        $this->assertTrue(is_a($fileInfo, 'SplFileInfo'));
        $this->assertTrue($fileInfo->isFile());
    }
    // }}}

    // {{{ testGetString
    public function testGetString()
    {
        $this->createTestFile($this->remoteDir . '/testFile');

        $this->assertEquals('testString', $this->fs->getString('testFile'));
    }
    // }}}
    // {{{ testPutString
    public function testPutString()
    {
        $this->fs->putString('testFile', 'testString');

        $this->assertTrue($this->confirmTestFile($this->remoteDir . '/testFile'));
    }
    // }}}

    // {{{ testTest
    public function testTest()
    {
        $this->assertTrue($this->fs->test());
        rmdir($this->remoteDir);
        $this->assertFalse($this->fs->test());
    }
    // }}}
}
