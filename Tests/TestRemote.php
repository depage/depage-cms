<?php

class TestRemote extends TestBase
{
    // {{{ sshConnection
    public function sshConnection()
    {
        if (!isset($GLOBALS['SSH_CONNECTION'])) {
            $GLOBALS['SSH_CONNECTION'] = ssh2_connect($GLOBALS['REMOTE_HOST'], 22);
            ssh2_auth_password($GLOBALS['SSH_CONNECTION'], $GLOBALS['REMOTE_USER'], $GLOBALS['REMOTE_PASS']);
        }

        return $GLOBALS['SSH_CONNECTION'];
    }
    // }}}

    // {{{ createRemoteTestDir
    public function createRemoteTestDir()
    {
        return $this->createTestDir($GLOBALS['REMOTE_DIR']);
    }
    // }}}
    // {{{ deleteRemoteTestDir
    public function deleteRemoteTestDir()
    {
        if (!empty($this->nodes)) {
            foreach(array_reverse($this->nodes) as $node) {
                $path = $GLOBALS['REMOTE_DIR'] . 'Temp/' . $node[1];

                if ($node[0] == 'dir') {
                    ssh2_exec($this->sshConnection(), 'rmdir ' . $path);
                } elseif ($node[0] == 'file') {
                    ssh2_exec($this->sshConnection(), 'rm ' . $path);
                }
            }
        }
        $this->rmr($GLOBALS['REMOTE_DIR'] . '/Temp');
    }
    // }}}
    // {{{ createRemoteTestFile
    public function createRemoteTestFile($path)
    {
        $this->nodes[] = array('file', $path);
        ssh2_exec($this->sshConnection(), 'printf "testString" > ' . $GLOBALS['REMOTE_DIR'] . 'Temp/' . $path);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
