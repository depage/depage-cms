<?php

class TestRemote extends TestBase
{
    // {{{ createRemoteTestDir
    public function createRemoteTestDir()
    {
        $this->rmr($GLOBALS['FTP_DIR'] . '/Temp');
        mkdir($GLOBALS['FTP_DIR'] . '/Temp', 0777);
        chmod($GLOBALS['FTP_DIR'] . '/Temp', 0777);
        // @todo verify

        return $GLOBALS['FTP_DIR'] . '/Temp';
    }
    // }}}
    // {{{ deleteRemoteTestDir
    public function deleteRemoteTestDir()
    {
        if (!empty($this->nodes)) {
            $script = '';
            foreach(array_reverse($this->nodes) as $node) {
                if ($node[0] == 'dir') {
                    $script .= "rmdir Temp/" . $node[1] . "\n";
                } elseif ($node[0] == 'file') {
                    $script .= "delete Temp/" . $node[1] . "\n";
                }
            }
            $this->runFtpScript($script);
        }
        chdir($GLOBALS['FTP_DIR']);
        $this->rmr('Temp');
    }
    // }}}
    // {{{ createRemoteTestFile
    public function createRemoteTestFile($path)
    {
        $this->createTestFile('testFile.tmp');
        $this->nodes[] = array('file', $path);
        $this->runFtpScript("cd Temp\nput testFile.tmp $path\n");
        $this->rmr('testFile.tmp');
    }
    // }}}

    // {{{ runFtpScript
    public function runFtpScript($script)
    {
        $script =   "ftp -n " . $GLOBALS['FTP_HOST'] . " <<END_OF_SESSION\n" .
                    "user " . $GLOBALS['FTP_USER'] . " " . $GLOBALS['FTP_PASS'] . "\n" .
                    $script . "END_OF_SESSION";

        exec($script);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
