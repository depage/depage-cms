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
        return ssh2_exec($this->sshConnection(), $cmd);
    }
    // }}}

    // {{{ mkdirRemote
    protected function mkdirRemote($path, $mode = 0777, $recursive = false)
    {
        $parents = ($recursive) ? '-p ' : '';
        $remotePath = $this->remoteDir . '/' . $path;
        $decMode = decoct($mode);
        $command = 'mkdir ' . $parents . '-m ' . $decMode . ' ' . $remotePath;

        $this->sshExec($command);
        $this->assertTrue(is_dir($remotePath));
    }
    // }}}
    // {{{ touchRemote
    protected function touchRemote($path, $mode = 0777)
    {
        $remotePath = $this->remoteDir . '/' . $path;
        $this->sshExec('touch ' . $remotePath);
        $this->assertTrue(is_file($remotePath));
    }
    // }}}

    // {{{ createRemoteTestDir
    public function createRemoteTestDir()
    {
        $dir = $GLOBALS['REMOTE_DIR'] . 'Temp';

        if (file_exists($dir)) {
            $this->deleteRemoteTestDir();
            if (file_exists($dir)) {
                $this->fail('Test directory not clean: ' . $dir);
            }
        }

        $this->sshExec('mkdir -m 777 ' . $dir);
        $this->assertTrue(is_dir($dir));

        return $dir;
    }
    // }}}
    // {{{ deleteRemoteTestDir
    public function deleteRemoteTestDir()
    {
        $this->sshExec('rm -r ' . $GLOBALS['REMOTE_DIR'] . 'Temp');
    }
    // }}}
    // {{{ createRemoteTestFile
    public function createRemoteTestFile($path, $content = null)
    {
        $content = ($content === null) ? 'testString' : $content;
        $this->sshExec('printf "' . $content . '" > ' . $this->remoteDir . '/' . $path);
        $this->confirmRemoteTestFile($path, $content);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
