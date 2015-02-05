<?php

class FsFtpTest extends TestBase
{
    // {{{ createTestClasses
    public function createTestClass($override = array())
    {
        $params = array(
                'path' => '/Temp',
                'scheme' => 'ftp',
                'host' => $GLOBALS['FTP_HOST'],
                'user' => $GLOBALS['FTP_USER'],
                'pass' => $GLOBALS['FTP_PASS'],
        );

        $newParams = array_merge($params, $override);

        return new FsTestClass($newParams);
    }
    // }}}
    // {{{ createRemoteTestDir
    public function createRemoteTestDir()
    {
        $this->rmr($GLOBALS['FTP_DIR'] . '/Temp');
        mkdir($GLOBALS['FTP_DIR'] . '/Temp');
        // @todo verify

        return $GLOBALS['FTP_DIR'] . '/Temp';
    }
    // }}}
    // {{{ deleteRemoteTestDir
    public function deleteRemoteTestDir()
    {
        if (!empty($this->nodes)) {
            $script =   "ftp -n " . $GLOBALS['FTP_HOST'] . " <<END_OF_SESSION\n" .
                        "user " . $GLOBALS['FTP_USER'] . " " . $GLOBALS['FTP_PASS'] . "\n";

            foreach(array_reverse($this->nodes) as $node) {
                if ($node[0] == 'dir') {
                    $script .= "rmdir Temp/" . $node[1] . "\n";
                } elseif ($node[0] == 'file') {
                    $script .= "delete Temp/" . $node[1] . "\n";
                }
            }
            $script .= "END_OF_SESSION\n";
            exec($script);
        }

        chdir($GLOBALS['FTP_DIR']);
        $this->rmr('Temp');
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
