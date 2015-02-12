<?php

class TestRemote extends TestBase
{
    // {{{ createRemoteTestDir
    public function createRemoteTestDir()
    {
        $dir = $GLOBALS['FTP_DIR'] . '/Temp';

        if (file_exists($dir)) {
            $this->rmr($dir);
            if (file_exists($dir)) {
                $this->fail('Remote test directory not clean: ' . $dir);
            }
        }

        mkdir($dir, 0777);
        chmod($dir, 0777);
        $this->assertTrue(is_dir($dir));

        return $dir;
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
