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
        $this->fs = $this->createTestObject();
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
        } elseif (is_file($path)) {
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
    protected function createTestFile($path, $contents = 'testString')
    {
        $testFile = fopen($path, 'w');
        fwrite($testFile, $contents);
        fclose($testFile);

        $this->assertTrue($this->confirmTestFile($path, $contents));
    }
    // }}}
    // {{{ confirmTestFile
    protected function confirmTestFile($path, $contents = 'testString')
    {
        $file = file($path);

        return $file === array($contents);
    }
    // }}}
    // {{{ confirmRemoteTestFile
    protected function confirmRemoteTestFile($path, $contents = 'testString')
    {
        return $this->confirmTestFile($this->remoteDir . '/' . $path, $contents);
    }
    // }}}
    // {{{ isDir
    protected function isDir($path)
    {
        return is_dir($path);
    }
    // }}}
    // {{{ isFile
    protected function isFile($path)
    {
        return is_file($path);
    }
    // }}}

    // {{{ testLs
    public function testLs()
    {
        $this->mkdirRemote('testDir');
        $this->mkdirRemote('testAnotherDir');
        $this->touchRemote('testFile');
        $this->touchRemote('testAnotherFile');

        $lsReturn = $this->fs->ls('*');
        $expected = array(
            'testAnotherDir',
            'testAnotherFile',
            'testDir',
            'testFile',
        );

        $this->assertEquals($expected, $lsReturn);
    }
    // }}}
    // {{{ testLsDir
    public function testLsDir()
    {
        $this->mkdirRemote('testDir');
        $this->mkdirRemote('testAnotherDir');
        $this->touchRemote('testFile');
        $this->touchRemote('testAnotherFile');

        $lsDirReturn = $this->fs->lsDir('*');
        $expected = array(
            'testAnotherDir',
            'testDir',
        );

        $this->assertEquals($expected, $lsDirReturn);
    }
    // }}}
    // {{{ testLsFiles
    public function testLsFiles()
    {
        $this->mkdirRemote('testDir');
        $this->mkdirRemote('testAnotherDir');
        $this->touchRemote('testFile');
        $this->touchRemote('testAnotherFile');

        $lsFilesReturn = $this->fs->lsFiles('*');
        $expected = array(
            'testAnotherFile',
            'testFile',
        );

        $this->assertEquals($expected, $lsFilesReturn);
    }
    // }}}
    // {{{ testLsHidden
    public function testLsHidden()
    {
        $this->mkdirRemote('testDir');
        $this->mkdirRemote('.testHiddenDir');
        $this->touchRemote('testFile');
        $this->touchRemote('.testHiddenFile');

        $lsReturn = $this->fs->ls('*');
        $expected = array(
            'testDir',
            'testFile',
        );

        $this->assertEquals($expected, $lsReturn);

        $params = array('hidden' => true);
        $hiddenFs = $this->createTestObject($params);
        $lsReturn = $hiddenFs->ls('*');

        $expected = array(
            '.testHiddenDir',
            '.testHiddenFile',
            'testDir',
            'testFile',
        );

        $this->assertEquals($expected, $lsReturn);
    }
    // }}}
    // {{{ testLsRecursive
    public function testLsRecursive()
    {
        $this->mkdirRemote('testDir/abc/abc/abc');
        $this->mkdirRemote('testDir/abc/abcd/abcd');
        $this->mkdirRemote('testDir/abc/abcde/abcde');
        $this->mkdirRemote('testDir/abcd/abcde/abcde');
        $this->touchRemote('testDir/abcFile');
        $this->touchRemote('testDir/abc/abcFile');
        $this->touchRemote('testDir/abc/abcd/abcFile');
        $this->touchRemote('testDir/abcd/abcde/abcde/abcFile');

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
        $this->mkdirRemote('testDir');
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
        $this->assertFalse($this->isDir($this->remoteDir . '/testDir'));
        $this->fs->mkdir('testDir');
        $this->assertTrue($this->isDir($this->remoteDir . '/testDir'));
    }
    // }}}
    // {{{ testMkdirRecursive
    public function testMkdirRecursive()
    {
        $this->assertFalse($this->isDir($this->remoteDir . 'testDir'));
        $this->assertFalse($this->isDir($this->remoteDir . 'testDir/testSubDir'));

        $this->fs->mkdir('testDir/testSubDir');

        $this->assertTrue($this->isDir($this->remoteDir . '/testDir/testSubDir'));
    }
    // }}}
    // {{{ testMkdirFail
    /**
     * @expectedException           Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage    Error while creating directory "testDir/testSubDir".
     */
    public function testMkdirFail()
    {
        $this->assertFalse($this->isDir($this->remoteDir . 'testDir'));
        $this->assertFalse($this->isDir($this->remoteDir . 'testDir/testSubDir'));

        $this->fs->mkdir('testDir/testSubDir', 0777, false);

        $this->assertFalse($this->isDir($this->remoteDir . '/testDir/testSubDir'));
    }
    // }}}
    // {{{ testRm
    public function testRm()
    {
        $this->mkdirRemote('testDir/testSubDir');
        $this->createRemoteTestFile('testDir/testFile');
        $this->createRemoteTestFile('testDir/testSubDir/testFile');

        $this->fs->rm('testDir');
        $this->assertFalse($this->isDir($this->remoteDir . '/testDir'));
        $this->assertFalse($this->isFile($this->remoteDir . '/testDir'));
    }
    // }}}
    // {{{ testRmDoesntExist
    /**
     * @expectedException           Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage    doesn't exist.
     */
    public function testRmDoesntExist()
    {
        $this->fs->rm('filedoesntexist');
    }
    // }}}
    // {{{ testRmCurrent
    /**
     * @expectedException           Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage    Cannot delete current or parent directory
     */
    public function testRmCurrent()
    {
        $this->mkdirRemote('testDir');

        $this->fs->cd('testDir');
        $this->fs->rm('../testDir');
    }
    // }}}
    // {{{ testRmParentDirOfCurrent
    /**
     * @expectedException           Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage    Cannot delete current or parent directory
     */
    public function testRmParentDirOfCurrent()
    {
        $this->mkdirRemote('testDir/testSubDir');

        $pwd = $this->fs->pwd();
        $this->fs->cd('testDir/testSubDir');
        $this->fs->rm($pwd . '/testDir');
    }
    // }}}

    // {{{ testMv
    public function testMv()
    {
        $this->createRemoteTestFile('testFile');
        $this->assertFalse($this->isFile($this->remoteDir . '/testFile2'));

        $this->fs->mv('testFile', 'testFile2');
        $this->assertFalse($this->isFile($this->remoteDir . '/testFile'));
        $this->assertTrue($this->confirmRemoteTestFile('testFile2'));
    }
    // }}}
    // {{{ testMvOverwrite
    public function testMvOverwrite()
    {
        $this->createRemoteTestFile('testFile', 'before');
        $this->createRemoteTestFile('testFile2', 'after');

        $this->fs->mv('testFile2', 'testFile');
        $this->assertTrue($this->confirmRemoteTestFile('testFile', 'after'));
    }
    // }}}
    // {{{ testMvIntoDirectory
    public function testMvIntoDirectory()
    {
        $this->createRemoteTestFile('testFile');
        $this->mkdirRemote('testDir');

        $this->fs->mv('testFile', 'testDir');
        $this->assertTrue($this->confirmRemoteTestFile('testDir/testFile'));
    }
    // }}}
    // {{{ testMvSourceDoesntExist
    /**
     * @expectedException           Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage    source doesn't exist
     */
    public function testMvSourceDoesntExist()
    {
        $this->mkdirRemote('testDir');
        $this->assertFalse($this->isFile($this->remoteDir . '/testFile'));

        $this->fs->mv('testFile', 'testDir/testFile');
    }
    // }}}

    // {{{ testGet
    public function testGet()
    {
        $this->createRemoteTestFile('testFile');

        $this->fs->get('testFile');
        $this->assertTrue($this->confirmTestFile('testFile'));
    }
    // }}}
    // {{{ testGetNamed
    public function testGetNamed()
    {
        $this->createRemoteTestFile('testFile');

        $this->fs->get('testFile', 'testFile2');
        $this->assertTrue($this->confirmTestFile('testFile2'));
    }
    // }}}
    // {{{ testGetOverwrite
    public function testGetOverwrite()
    {
        $this->createRemoteTestFile('testFile', 'after');
        $this->createTestFile('testFile2', 'before');

        $this->fs->get('testFile', 'testFile2');
        $this->assertTrue($this->confirmTestFile('testFile2', 'after'));
    }
    // }}}
    // {{{ testPut
    public function testPut()
    {
        $this->createTestFile('testFile');

        $this->assertFalse($this->isFile($this->remoteDir . '/testFile2'));
        $this->fs->put('testFile', 'testFile2');
        $this->assertTrue($this->confirmTestFile('testFile'));
        $this->assertTrue($this->confirmRemoteTestFile('testFile2'));
    }
    // }}}
    // {{{ testPutOverwrite
    public function testPutOverwrite()
    {
        $this->createRemoteTestFile('testFile', 'before');
        $this->createTestFile('testFile2', 'after');

        $this->fs->put('testFile2', 'testFile');
        $this->assertTrue($this->confirmRemoteTestFile('testFile', 'after'));
    }
    // }}}

    // {{{ testExists
    public function testExists()
    {
        $this->mkdirRemote('testDir');

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
        $this->createRemoteTestFile('testFile');

        $this->assertEquals('testString', $this->fs->getString('testFile'));
    }
    // }}}
    // {{{ testPutString
    public function testPutString()
    {
        $this->fs->putString('testFile', 'testString');

        $this->assertTrue($this->confirmRemoteTestFile('testFile'));
    }
    // }}}
    // {{{ testPutStringOverwrite
    public function testPutStringOverwrite()
    {
        $this->createRemoteTestFile('testFile', 'before');
        $this->fs->putString('testFile', 'after');

        $this->assertTrue($this->confirmRemoteTestFile('testFile', 'after'));
    }
    // }}}

    // {{{ testTest
    public function testTest()
    {
        $this->assertTrue($this->fs->test());
        $this->deleteRemoteTestDir();
        $this->assertFalse($this->fs->test($error));
        $this->assertContains('file_put_contents', $error);
    }
    // }}}

    // {{{ testLateConnectInvalidDirectory
    public function testLateConnectInvalidDirectory()
    {
        $params = array('path' => 'directorydoesnotexist');
        $this->createTestObject($params);
    }
    // }}}
    // {{{ testLateConnectInvalidDirectoryFail
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage Invalid path: "directorydoesnotexist"
     */
    public function testLateConnectInvalidDirectoryFail()
    {
        $params = array('path' => 'directorydoesnotexist');
        $fs = $this->createTestObject($params);
        $fs->ls('*');
    }
    // }}}

    // {{{ testEmptyParamsPath
    public function testEmptyParamsPath()
    {
        $params = array();
        $fs = $this->createTestObject($params);
        $fs->ls('*');

        $params = array('path' => '');
        $fs = $this->createTestObject($params);
        $fs->ls('*');

        $params = array('path' => '.');
        $fs = $this->createTestObject($params);
        $fs->ls('*');

        $params = array('path' => './');
        $fs = $this->createTestObject($params);
        $fs->ls('*');
    }
    // }}}
}
