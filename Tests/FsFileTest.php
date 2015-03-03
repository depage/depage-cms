<?php

class FsFileTest extends TestBase
{
    // {{{ createTestClass
    public function createTestClass($override = array())
    {
        $params = array('scheme' => 'file');
        $newParams = array_merge($params, $override);

        return new FsFileTestClass($newParams);
    }
    // }}}

    // {{{ mkdirRemote
    protected function mkdirRemote($path, $mode = 0777, $recursive = false)
    {
        $remotePath = $this->remoteDir . '/' . $path;
        mkdir($remotePath, $mode, $recursive);
        chmod($remotePath, $mode);
    }
    // }}}
    // {{{ touchRemote
    protected function touchRemote($path, $mode = 0777)
    {
        $remotePath = $this->remoteDir . '/' . $path;
        touch($remotePath, $mode);
        chmod($remotePath, $mode);
    }
    // }}}

    // {{{ createRemoteTestDir
    public function createRemoteTestDir()
    {
        return $this->localDir;
    }
    // }}}
    // {{{ deleteRemoteTestDir
    public function deleteRemoteTestDir()
    {
        $this->rmr($this->localDir);
    }
    // }}}
    // {{{ createRemoteTestFile
    public function createRemoteTestFile($path, $content = null)
    {
        $this->createTestFile($path, $content);
    }
    // }}}

    // {{{ testCdIntoWrapperUrl
    public function testCdIntoWrapperUrl()
    {
        $pwd = $this->fs->pwd();
        mkdir($this->remoteDir . '/testDir');
        $this->fs->cd('file://' . $this->remoteDir . '/testDir');
        $newPwd = $this->fs->pwd();

        $this->assertEquals($pwd . 'testDir/', $newPwd);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
