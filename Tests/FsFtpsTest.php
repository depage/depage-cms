<?php

class FsFtpsTest extends FsFtpTest
{
    // {{{ createTestClass
    public function createTestClass($override = array())
    {
        $params = array(
                'path' => '/Temp',
                'scheme' => 'ftps',
                'host' => $GLOBALS['FTP_HOST'],
                'user' => $GLOBALS['FTP_USER'],
                'pass' => $GLOBALS['FTP_PASS'],
        );

        $newParams = array_merge($params, $override);

        return new FsTestClass($newParams);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
