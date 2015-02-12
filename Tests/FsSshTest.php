<?php

class FsSshTest extends TestRemote
{
    // {{{ createTestClass
    public function createTestClass($override = array())
    {
        $params = array(
            'path' => $GLOBALS['REMOTE_DIR'] . 'Temp',
            'scheme' => 'ssh2.sftp',
            'host' => $GLOBALS['REMOTE_HOST'],
            'user' => $GLOBALS['REMOTE_USER'],
            'pass' => $GLOBALS['REMOTE_PASS'],
            'port' => '22',
        );

        $newParams = array_merge($params, $override);

        return new FsSshTestClass($newParams);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
