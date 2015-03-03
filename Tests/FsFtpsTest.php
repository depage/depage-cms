<?php

class FsFtpsTest extends FsFtpTest
{
    // {{{ createTestClass
    public function createTestClass($override = array())
    {
        $params = array(
            'path' => '/Temp',
            'scheme' => 'ftps',
            'host' => $GLOBALS['REMOTE_HOST'],
            'user' => $GLOBALS['REMOTE_USER'],
            'pass' => $GLOBALS['REMOTE_PASS'],
        );

        $newParams = array_merge($params, $override);

        return new FsFtpTestClass($newParams);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
