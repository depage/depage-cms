<?php

use Depage\FS\FS;

class FSLocalTest extends PHPUnit_Framework_TestCase
{
    // {{{ setUp
    public function setUp()
    {
        $this->fs = FS::factory('Local');
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
        $cdReturn   = $this->fs->cd('Fixtures');
        $newPwd     = getcwd();

        $this->assertEquals(true, $cdReturn);
        $this->assertEquals($pwd . DIRECTORY_SEPARATOR . 'Fixtures', $newPwd);
    }
    // }}}
    // {{{ testMkdir
    public function testMkdir()
    {
        $this->assertFalse(file_exists('Temp/testDir'));
        $this->assertFalse(file_exists('Temp/testDir/testDir'));

        $mkdirReturn = $this->fs->mkdir('Temp/testDir/testDir');

        $this->assertTrue(file_exists('Temp/testDir/testDir'));

        // @todo add to setup
        rmdir('Temp/testDir/testDir');
        rmdir('Temp/testDir');
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
