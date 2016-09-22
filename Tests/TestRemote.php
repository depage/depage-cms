<?php

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
        $remotePath = $this->remoteDir . '/' . $path;

        mkdir($remotePath, 0777, $recursive);
        $this->assertTrue(is_dir($remotePath));
    }
    // }}}
    // {{{ touchRemote
    protected function touchRemote($path, $mode = 0777)
    {
        $remotePath = $this->remoteDir . '/' . $path;
        touch($remotePath);
        $this->assertTrue(is_file($remotePath));
    }
    // }}}

    // {{{ createRemoteTestDir
    public function createRemoteTestDir()
    {
        $dir = __DIR__ . '/docker/home/Temp';

        if (is_dir($dir)) {
            $this->deleteRemoteTestDir();
            if (is_dir($dir)) {
                $this->fail('Test directory not clean: ' . $dir);
            }
        }

        mkdir($dir, 0777);
        $this->assertTrue(is_dir($dir));

        return $dir;
    }
    // }}}
    // {{{ deleteRemoteTestDir
    public function deleteRemoteTestDir()
    {
        $this->rmr(__DIR__ . '/docker/home/Temp');
    }
    // }}}
    // {{{ createRemoteTestFile
    public function createRemoteTestFile($path, $content = null)
    {
        $this->createTestFile($this->remoteDir . '/' . $path, $content);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
