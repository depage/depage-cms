<?php

class FsFtpTest extends PHPUnit_Framework_TestCase
{
    // {{{ setUp
    public function setUp()
    {
        chdir($GLOBALS['FTP_DIR']);
        $this->rmr('Temp');
        mkdir('Temp');
        chmod('Temp', 0777);
        chdir('Temp');

        $params = array(
            'path' => '/Temp',
            'scheme' => 'ftp',
            'host' => $GLOBALS['FTP_HOST'],
            'user' => $GLOBALS['FTP_USER'],
            'pass' => $GLOBALS['FTP_PASS'],
        );

        $this->fs = new FsTestClass($params);
    }
    // }}}
    // {{{ tearDown
    public function tearDown()
    {
        if (!empty($this->nodes)) {
            $script =   "ftp -n " . $GLOBALS['FTP_HOST'] . " <<END_OF_SESSION\n" .
                        "user " . $GLOBALS['FTP_USER'] . " " . $GLOBALS['FTP_PASS'] . "\n";

            foreach(array_reverse($this->nodes) as $node) {
                if ($node[0] == 'dir') {
                    $script .= "rmdir Temp/" . $node[1] . "\n";
                } elseif ($node[0] == 'file') {
                    $script .= "delete Temp/" . $node[1] . "\n";
                }
            }
            $script .= "END_OF_SESSION\n";
            exec($script);
        }

        chdir($GLOBALS['FTP_DIR']);
        $this->rmr('Temp');
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
    // {{{ invokeMkdir
    protected function invokeMkdir($path)
    {
        // @todo explode recursive paths
        $this->nodes[] = array('dir', $path);
        $this->fs->mkdir($path);
    }
    // }}}
    // {{{ invokePut
    protected function invokePut($local, $remotePath)
    {
        $this->nodes[] = array('file', $remotePath);
        $this->fs->put($local, $remotePath);
    }
    // }}}
    // {{{ invokePutString
    protected function invokePutString($remotePath, $string)
    {
        $this->nodes[] = array('file', $remotePath);
        $this->fs->putString($remotePath, $string);
    }
    // }}}

    // {{{ testLs
    public function testLs()
    {
        mkdir('testDir/testSubDir', 0777, true);
        mkdir('testDir/testAnotherSubDir', 0777, true);
        touch('testDir/testFile');
        touch('testDir/testAnotherFile');

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
        mkdir('testDir/testSubDir', 0777, true);
        mkdir('testDir/testAnotherSubDir', 0777, true);
        touch('testDir/testFile');
        touch('testDir/testAnotherFile');

        $lsDirReturn    = $this->fs->lsDir('testDir/*');
        $expected       = array(
            'testDir/testAnotherSubDir',
            'testDir/testSubDir',
        );

        $this->assertEquals($expected, $lsDirReturn);
    }
    // }}}
    // {{{ testLsFiles
    public function testLsFiles()
    {
        mkdir('testDir/testSubDir', 0777, true);
        mkdir('testDir/testAnotherSubDir', 0777, true);
        touch('testDir/testFile');
        touch('testDir/testAnotherFile');

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
        mkdir('testDir/testSubDir', 0777, true);
        mkdir('testDir/.testSubDirHidden', 0777, true);
        touch('testDir/testFile');
        touch('testDir/.testFileHidden');

        $lsReturn = $this->fs->ls('testDir/*');
        $expected = array(
            'testDir/testFile',
            'testDir/testSubDir',
        );

        $this->assertEquals($expected, $lsReturn);

        $params = array(
            'path' => '/Temp',
            'scheme' => 'ftp',
            'hidden' => true,
            'host' => $GLOBALS['FTP_HOST'],
            'user' => $GLOBALS['FTP_USER'],
            'pass' => $GLOBALS['FTP_PASS'],
        );

        $hiddenFs = new FsTestClass($params);
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
        mkdir('testDir/abc/abc/abc', 0777, true);
        mkdir('testDir/abc/abcd/abcd', 0777, true);
        mkdir('testDir/abc/abcde/abcde', 0777, true);
        mkdir('testDir/abcd/abcde/abcde', 0777, true);
        touch('testDir/abcFile');
        touch('testDir/abc/abcFile');
        touch('testDir/abc/abcd/abcFile');
        touch('testDir/abcd/abcde/abcde/abcFile');

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
        mkdir('testDir');
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
        $pwd = preg_replace(';Temp$;', '', $basePwd);
        $this->assertEquals($pwd . 'Temp', $basePwd);

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
        $pwd = $this->fs->pwd();
        mkdir('testDir', 400);
        $this->fs->cd('testDir');
    }
    // }}}
    // {{{ testMkdir
    public function testMkdir()
    {
        $this->assertFalse(file_exists('testDir'));
        $this->assertFalse(file_exists('testDir/testDir'));

        $mkdirReturn = $this->invokeMkdir('testDir');
        $mkdirReturn = $this->invokeMkdir('testDir/testDir');

        $this->assertTrue(file_exists('testDir/testDir'));
    }
    // }}}
    // {{{ testRm
    public function testRm()
    {
        // create test nodes
        $this->invokeMkdir('testDir');
        $this->invokeMkdir('testDir/testSubDir');
        $this->invokeMkdir('testDir/testSubDir/testAnotherSubDir');
        $this->invokePutString('testDir/testFile', '');
        $this->invokePutString('testDir/testSubDir/testFile', '');
        $this->assertTrue(file_exists('testDir/testSubDir/testAnotherSubDir'));

        $this->fs->rm('testDir');
        $this->assertFalse(file_exists('testDir'));
    }
    // }}}

    // {{{ testMv
    public function testMv()
    {
        // create test nodes
        mkdir('testDir/testSubDir/testAnotherSubDir', 0777, true);
        $this->createTestFile('testDir/testFile');
        $this->assertTrue($this->confirmTestFile('testDir/testFile'));
        $this->assertTrue(file_exists('testDir/testSubDir/testAnotherSubDir'));
        $this->assertFalse(file_exists('testDir/testSubDir/testFile'));

        $this->fs->mv('testDir/testFile', 'testDir/testSubDir/testFile');
        $this->assertFalse(file_exists('testDir/testFile'));
        $this->assertTrue($this->confirmTestFile('testDir/testSubDir/testFile'));
    }
    // }}}
    // {{{ testMvSourceDoesntExist
    /**
     * @expectedException Depage\Fs\Exceptions\FsException
     */
    public function testMvSourceDoesntExist()
    {
        // create test nodes
        mkdir('testDir/testSubDir/testAnotherSubDir', 0777, true);
        $this->assertFalse(file_exists('testDir/testFile'));

        $this->fs->mv('testDir/testFile', 'testDir/testSubDir/testFile');
    }
    // }}}

    // {{{ testGet
    public function testGet()
    {
        // create test nodes
        mkdir('testDir/testSubDir/testAnotherSubDir', 0777, true);
        $this->createTestFile('testDir/testFile');
        $this->assertTrue($this->confirmTestFile('testDir/testFile'));
        $this->assertTrue(file_exists('testDir/testSubDir/testAnotherSubDir'));
        $this->assertFalse(file_exists('testDir/testSubDir/testFile'));

        $this->fs->get('testDir/testFile', 'testDir/testSubDir/testFile');
        $this->assertTrue($this->confirmTestFile('testDir/testFile'));
        $this->assertTrue($this->confirmTestFile('testDir/testSubDir/testFile'));
    }
    // }}}
    // {{{ testPut
    public function testPut()
    {
        // create test nodes
        mkdir('testDir/testSubDir/testAnotherSubDir', 0777, true);
        $this->createTestFile('testDir/testSubDir/testFile');
        $this->assertTrue($this->confirmTestFile('testDir/testSubDir/testFile'));
        $this->assertTrue(file_exists('testDir/testSubDir/testAnotherSubDir'));
        $this->assertFalse(file_exists('testDir/testFile'));

        $this->invokePut('testDir/testSubDir/testFile', 'testDir/testFile');
        $this->assertTrue($this->confirmTestFile('testDir/testFile'));
        $this->assertTrue($this->confirmTestFile('testDir/testSubDir/testFile'));
    }
    // }}}

    // {{{ testExists
    public function testExists()
    {
        mkdir('testDir');

        $this->assertTrue($this->fs->exists('testDir'));
        $this->assertFalse($this->fs->exists('i_dont_exist'));
    }
    // }}}
    // {{{ testFileInfo
    public function testFileInfo()
    {
        $this->createTestFile('testFile');
        $fileInfo = $this->fs->fileInfo('testFile');

        $this->assertTrue(is_a($fileInfo, 'SplFileInfo'));
        $this->assertTrue($fileInfo->isFile());
    }
    // }}}

    // {{{ testGetString
    public function testGetString()
    {
        $this->createTestFile('testFile');

        $this->assertEquals('testString', $this->fs->getString('testFile'));
    }
    // }}}
    // {{{ testPutString
    public function testPutString()
    {
        $this->invokePutString('testFile', 'testString');

        $this->assertTrue($this->confirmTestFile('testFile'));
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
