<?php

use Depage\FS\FSLocal;

class FSLocalTest extends PHPUnit_Framework_TestCase
{
    // {{{ setUp
    public function setUp()
    {
        $this->fs               = new FSLocal();
        $this->testDirectory    = getcwd();

        $this->rmr('Temp');
        mkdir('Temp');
        chdir('Temp');
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
        $lsReturn = $this->fs->ls('Fixtures');
        $expected = array(
            'dirs'  => array(),
            'files' => array(),
        );

        $this->assertEquals($expected, $lsReturn);
    }
    // }}}
    // {{{ testLsDir
    public function testDir()
    {
        $lsDirReturn    = $this->fs->lsDir('Fixtures');
        $expected       = array();

        $this->assertEquals($expected, $lsDirReturn);
    }
    // }}}
    // {{{ testLsiFiles
    public function testLsFiles()
    {
        $lsFilesReturn  = $this->fs->lsFiles('Fixtures');
        $expected       = array();

        $this->assertEquals($expected, $lsFilesReturn);
    }
    // }}}
    // {{{ testCd
    public function testCd()
    {
        $pwd        = getcwd();
        $cdReturn   = $this->fs->cd('..');
        $newPwd     = getcwd();

        $this->assertEquals(true, $cdReturn);
        $this->assertEquals($pwd, $newPwd . DIRECTORY_SEPARATOR . 'Temp');
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
    // {{{ testChmod
    public function testChmod()
    {
        function getMode($path) {
            return substr(sprintf('%o', fileperms($path)), -4);
        }

        $this->fs = new FSLocal(array('chmod' => 0640));

        // create test nodes
        mkdir('testDir');
        touch('testFile');
        $this->assertTrue(file_exists('testDir'));
        $this->assertTrue(file_exists('testFile'));

        // set mode (~fixture)
        chmod('testDir', 0660);
        chmod('testFile', 0660);
        $this->assertEquals('0660', getMode('testDir'));
        $this->assertEquals('0660', getMode('testFile'));

        // test mode set in constructor parameters
        $this->fs->chmod('testDir');
        $this->fs->chmod('testFile');
        $this->assertEquals('0750', getMode('testDir'));
        $this->assertEquals('0640', getMode('testFile'));

        // test custom mode
        $this->fs->chmod('testDir', 0600);
        $this->fs->chmod('testFile', 0600);
        $this->assertEquals('0600', getMode('testDir'));
        $this->assertEquals('0600', getMode('testFile'));
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
