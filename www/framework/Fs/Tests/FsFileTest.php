<?php

namespace Depage\Fs\Tests;

use Depage\Fs\Tests\TestClasses\FsFileTestClass;

class FsFileTest extends OperationsTestCase
{
    // {{{ setUp
    public function setUp()
    {
        $this->assertTrue($this->src->setUp());
        $this->assertTrue(chdir($this->src->getRoot()));

        $this->fs = $this->createTestObject();
    }
    // }}}
    // {{{ tearDown
    public function tearDown()
    {
        $this->assertTrue($this->src->tearDown());

        $this->assertTrue(chdir($this->root));
    }
    // }}}

    // {{{ createDst
    public function createDst()
    {
        return $this->src;
    }
    // }}}
    // {{{ createTestObject
    protected function createTestObject($override = array())
    {
        $params = [
            'scheme' => 'file',
        ];

        $newParams = array_merge($params, $override);

        return new FsFileTestClass($newParams);
    }
    // }}}

    // {{{ testGet
    public function testGet()
    {
        // file-scheme: create subdirectory so we don't overwrite the 'local' file
        $this->mkdirDst('testDir');
        $this->createFileDst('testDir/testFile');

        $this->fs->cd('testDir');
        $this->fs->get('testFile');
        $this->assertTrue($this->src->checkFile('testFile'));
    }
    // }}}
    // {{{ testCdIntoWrapperUrl
    public function testCdIntoWrapperUrl()
    {
        $pwd = $this->fs->pwd();
        $this->mkdirDst('testDir');

        $this->fs->cd('file://' . $this->dst->getRoot() . '/testDir');
        $newPwd = $this->fs->pwd();

        $this->assertEquals($pwd . 'testDir/', $newPwd);
    }
    // }}}
    // {{{ testMkdirFail
    /**
     * @expectedException           Depage\Fs\Exceptions\FsException
     * @expectedExceptionMessage    mkdir(): No such file or directory
     */
    public function testMkdirFail()
    {
        return parent::testMkdirFail();
    }
    // }}}
    // {{{ testCleanUrlFile
    public function testCleanUrlFile()
    {
        $fs = $this->createTestObject();
        $fs->lateConnect();
        $cwd = getcwd();

        $this->assertEquals('file://' . $cwd . '/path/to/file', $fs->cleanUrl('file://' . $cwd . '/path/to/file'));
        $this->assertEquals('file://' . $cwd . '/path/to/file', $fs->cleanUrl('path/to/file'));
        $this->assertEquals('file://' . $cwd . '/path/to/file', $fs->cleanUrl($cwd . '/path/to/file'));
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
