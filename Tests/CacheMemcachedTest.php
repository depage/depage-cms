<?php

/**
 * Blackbox tests for all extensions, compares imagesizes/filesizes
 **/
class CacheMemcachedTest extends CacheFileTest
{
    // {{{ setUp()
    /**
     * setup function
     **/
    public function setUp()
    {
        $this->clean();

        $this->cache = \Depage\Cache\Cache::factory("test", array(
            'disposition' => "memcached",
            'cachepath' => "cache",
        ));
    }
    // }}}
    // {{{ tearDown()
    /**
     * setup function
     **/
    public function tearDown()
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
        $memc = new \Memcached();
        $memc->addServer("localhost", "11211");
        $memc->flush();
    }
    // }}}
}

/* vim:set ft=php sts=4 fdm=marker et : */
