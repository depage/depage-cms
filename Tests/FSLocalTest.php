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
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
