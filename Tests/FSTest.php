<?php

use Depage\FS\FS;

class FSTest extends PHPUnit_Framework_TestCase
{
    // {{{ setUp
    public function setUp()
    {
        $this->testDirectory = getcwd();
        $this->rmr('Temp');
        mkdir('Temp');
        chdir('Temp');

        $this->fs = new FS('');
    }
    // }}}
    // {{{ tearDown
    public function tearDown()
    {
        chdir($this->testDirectory);
        $this->rmr('Temp');
    }
    // }}}
    // {{{ rmr
    protected function rmr($path)
    {
        if (is_dir($path)) {
            foreach (glob($path . '/*') as $nested) {
                $this->rmr($nested);
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

    // {{{ testLs
    public function testLs()
    {
        mkdir('testDir/testSubDir', 0777, true);
        mkdir('testDir/testAnotherSubDir', 0777, true);
        touch('testDir/testFile');
        touch('testDir/testAnotherFile');

        $lsReturn = $this->fs->ls('testDir');
        $expected = array(
            'testAnotherFile',
            'testAnotherSubDir',
            'testFile',
            'testSubDir',
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

        $lsDirReturn    = $this->fs->lsDir('testDir');
        $expected       = array(
            'testAnotherSubDir',
            'testSubDir',
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

        $lsFilesReturn  = $this->fs->lsFiles('testDir');
        $expected       = array(
            'testAnotherFile',
            'testFile',
        );

        $this->assertEquals($expected, $lsFilesReturn);
    }
    // }}}
    // {{{ testGlob
    public function testGlob()
    {
        mkdir('testDir/abc/abc/abc', 0777, true);
        mkdir('testDir/abc/abcd/abcd', 0777, true);
        mkdir('testDir/abc/abcde/abcde', 0777, true);
        mkdir('testDir/abcd/abcde/abcde', 0777, true);
        touch('testDir/abcFile');
        touch('testDir/abc/abcFile');
        touch('testDir/abc/abcd/abcFile');
        touch('testDir/abcd/abcde/abcde/abcFile');

        $globReturn = $this->fs->glob('testDir');
        $this->assertEquals(array('testDir'), $globReturn);

        $globReturn = $this->fs->glob('*');
        $this->assertEquals(array('testDir'), $globReturn);

        $globReturn = $this->fs->glob('testDir/ab*');
        $expected = array(
            'testDir/abc',
            'testDir/abcd',
            'testDir/abcFile',
        );
        $this->assertEquals($expected, $globReturn);

        $globReturn = $this->fs->glob('testDir/ab*d/*');
        $expected = array(
            'testDir/abcd/abcde',
        );
        $this->assertEquals($expected, $globReturn);

        $globReturn = $this->fs->glob('testDir/ab?');
        $expected = array(
            'testDir/abc',
        );
        $this->assertEquals($expected, $globReturn);

        $globReturn = $this->fs->glob('*/*/*/*');
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
        $pwd        = $this->fs->pwd();
        mkdir('testDir');
        $cdReturn   = $this->fs->cd('testDir');
        $newPwd     = $this->fs->pwd();

        $this->assertTrue($cdReturn);
        $this->assertEquals($pwd . 'testDir/', $newPwd);
    }
    // }}}
    // {{{ testCdIntoWrapperUrl
    public function testCdIntoWrapperUrl()
    {
        $pwd        = $this->fs->pwd();
        mkdir('testDir');
        $cdReturn   = $this->fs->cd('file://' . getcwd() . '/testDir');
        $newPwd     = $this->fs->pwd();

        $this->assertTrue($cdReturn);
        $this->assertEquals($pwd . 'testDir/', $newPwd);
    }
    // }}}
    // {{{ testCdOutOfBaseDir
    /**
     * @expectedException Depage\FS\Exceptions\FSException
     * @expectedExceptionMessage Cannot leave base directory
     */
    public function testCdOutOfBaseDir()
    {
        $basePwd = getcwd();
        $pwd = preg_replace(';Temp$;', '', $basePwd);
        $this->assertEquals($pwd . 'Temp', $basePwd);

        $cdReturn = $this->fs->cd($pwd);
    }
    // }}}
    // {{{ testCdOutOfBaseDirRelative
    /**
     * @expectedException           Depage\FS\Exceptions\FSException
     * @expectedExceptionMessage    Cannot leave base directory
     */
    public function testCdOutOfBaseDirRelative()
    {
        $cdReturn = $this->fs->cd('..');
    }
    // }}}
    // {{{ testCdFail
    /**
     * @expectedException           Depage\FS\Exceptions\FSException
     * @expectedExceptionMessage    Directory not accessible
     */
    public function testCdFail()
    {
        $pwd        = $this->fs->pwd();
        mkdir('testDir', 400);
        $cdReturn   = $this->fs->cd('testDir');
    }
    // }}}
    // {{{ testMkdir
    public function testMkdir()
    {
        $this->assertFalse(file_exists('testDir'));
        $this->assertFalse(file_exists('testDir/testDir'));

        $mkdirReturn = $this->fs->mkdir('testDir/testDir');

        $this->assertTrue(file_exists('testDir/testDir'));
    }
    // }}}
    // {{{ testRm
    public function testRm()
    {
        // create test nodes
        mkdir('testDir/testSubDir/testAnotherSubDir', 0777, true);
        touch('testDir/testFile');
        touch('testDir/testSubDir/testFile');
        $this->assertTrue(file_exists('testDir/testSubDir/testAnotherSubDir'));
        $this->assertTrue(file_exists('testDir/testSubDir/testFile'));
        $this->assertTrue(file_exists('testDir/testFile'));

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
     * @expectedException Depage\FS\Exceptions\FSException
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

        $this->fs->put('testDir/testSubDir/testFile', 'testDir/testFile');
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
        $this->fs->putString('testFile', 'testString');

        $this->assertTrue($this->confirmTestFile('testFile'));
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
