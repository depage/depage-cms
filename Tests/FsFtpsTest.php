<?php

namespace Depage\Fs\Tests;

use Depage\Fs\FsFtp;

class FsFtpsTest extends FsFtpTest
{
    // {{{ createTestObject
    public function createTestObject($override = array())
    {
        $params = array(
            'path' => '/Temp',
            'scheme' => 'ftps',
            'host' => $GLOBALS['REMOTE_HOST'],
            'port' => 21,
            'user' => $GLOBALS['REMOTE_USER'],
            'pass' => $GLOBALS['REMOTE_PASS'],
        );

        $newParams = array_merge($params, $override);

        return new FsFtp($newParams);
    }
    // }}}
    // {{{ testDefaultPort
    public function testDefaultPort()
    {
        $params = array(
            'path' => '/Temp',
            'scheme' => 'ftps',
            'host' => $GLOBALS['REMOTE_HOST'],
            'user' => $GLOBALS['REMOTE_USER'],
            'pass' => $GLOBALS['REMOTE_PASS'],
        );

        $fs = new Depage\Fs\FsFtp($params);
        $this->assertTrue($fs->test());
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
