<?php

require_once __DIR__ . "/CacheFileTest.php";

/**
 * Blackbox tests for all extensions, compares imagesizes/filesizes
 **/
class CacheRedisTest extends CacheFileTest
{
    // {{{ setUp()
    /**
     * setup function
     **/
    public function setUp():void
    {
        $this->clean();

        $this->cache = \Depage\Cache\Cache::factory("test", array(
            'disposition' => "redis",
            'cachepath' => "cache",
        ));
    }
    // }}}
    // {{{ tearDown()
    /**
     * setup function
     **/
    public function tearDown():void
    {
        $this->clean();
    }
    // }}}
    // {{{ clean()
    /**
     * clean cache directory
     **/
    public function clean()
    {
        $redis = new \Redis();
        $redis->connect("127.0.0.1", 6379);
        $redis->flushAll();
    }
    // }}}
}

/* vim:set ft=php sts=4 fdm=marker et : */
