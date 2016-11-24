<?php

namespace Depage\Fs\Tests;

class TestRemote extends TestBase
{
    // {{{ sshConnection
    protected function sshConnection()
    {
        if (!isset($GLOBALS['SSH_CONNECTION'])) {
            $GLOBALS['SSH_CONNECTION'] = ssh2_connect($GLOBALS['REMOTE_HOST'], 22);
            ssh2_auth_password($GLOBALS['SSH_CONNECTION'], $GLOBALS['REMOTE_USER'], $GLOBALS['REMOTE_PASS']);
        }

        return $GLOBALS['SSH_CONNECTION'];
    }
    // }}}
    // {{{ sshExec
    protected function sshExec($cmd)
    {
        $stream = ssh2_exec($this->sshConnection(), $cmd);
        stream_set_blocking($stream, true);
        $streamResult = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);

        return stream_get_contents($streamResult);
    }
    // }}}

    // {{{ mkdirRemote
    protected function mkdirRemote($path, $mode = 0777, $recursive = true)
    {
        $remotePath = '/home/testuser/Temp/' . $path;
        $parents = ($recursive) ? '-p ' : '';
        $decMode = decoct($mode);
        $command = 'mkdir ' . $parents . '-m ' . $decMode . ' ' . $remotePath;
        $this->sshExec($command);

        $this->assertTrue($this->isDir($remotePath));
    }
    // }}}
    // {{{ touchRemote
    protected function touchRemote($path, $mode = 0777)
    {
        $remotePath = '/home/testuser/Temp/' . $path;
        $this->sshExec('touch ' . $remotePath);
        $decMode = decoct($mode);
        $this->sshExec('chmod ' . $decMode . ' ' . $remotePath);

        $this->assertTrue($this->isFile($remotePath));
    }
    // }}}

    // {{{ createRemoteTestDir
    public function createRemoteTestDir()
    {
        $dir = '/home/testuser/Temp';

        if ($this->isDir($dir)) {
            $this->deleteRemoteTestDir();
            if ($this->isDir($dir)) {
                $this->fail('Test directory not clean: ' . $dir);
            }
        }

        $this->sshExec('mkdir -m 777 ' . $dir);
        $this->assertTrue($this->isDir($dir));

        return $dir;
    }
    // }}}
    // {{{ deleteRemoteTestDir
    public function deleteRemoteTestDir()
    {
        $this->sshExec('rm -r /home/testuser/Temp');
    }
    // }}}
    // {{{ createRemoteTestFile
    public function createRemoteTestFile($path, $contents = 'testString')
    {
        $remotePath = '/home/testuser/Temp/' . $path;

        $this->sshExec("echo -n \"$contents\" > $remotePath");
        $this->assertTrue($this->isFile($remotePath));
        $this->assertTrue($this->confirmRemoteTestFile($path, $contents));
    }
    // }}}
    // {{{ confirmRemoteTestFile
    protected function confirmRemoteTestFile($path, $contents = 'testString')
    {
        $remotePath = '/home/testuser/Temp/' . $path;
        $file = $this->sshExec("cat $remotePath");

        return $file === $contents;
    }
    // }}}

    // {{{ isDir
    protected function isDir($path)
    {
        $result = $this->sshExec('if [ -d "' . $path . '" ]; then echo 1; else echo 0; fi');

        return (bool) trim($result);
    }
    // }}}
    // {{{ isFile
    protected function isFile($path)
    {
        $result = $this->sshExec('if [ -f "' . $path . '" ]; then echo 1; else echo 0; fi');

        return (bool) trim($result);
    }
    // }}}
    // {{{ sha1File
    protected function sha1File($path)
    {
        $remotePath = '/home/testuser/Temp/' . $path;

        $resultArray = explode(' ', $this->sshExec("sha1sum $remotePath"));

        return $resultArray[0];
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
