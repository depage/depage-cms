<?php

class FsSshTest extends TestRemote
{
    // {{{ createTestClass
    public function createTestClass($override = array())
    {
        $params = array(
            'path' => '/home/ftptest/Temp',
            'scheme' => 'ssh2.sftp',
            'host' => $GLOBALS['FTP_HOST'],
            'user' => $GLOBALS['FTP_USER'],
            'pass' => $GLOBALS['FTP_PASS'],
            'port' => '22',
        );

        $newParams = array_merge($params, $override);

        return new FsSshTestClass($newParams);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
