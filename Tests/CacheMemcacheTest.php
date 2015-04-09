<?php

namespace Depage\Cache\Cache\Tests;

use Depage\Cache\Cache;

/**
 * Blackbox tests for all extensions, compares imagesizes/filesizes
 **/
class CacheMemcacheTest extends CacheFileTest
{
    // {{{ setUp()
    /**
     * setup function 
     **/
    public function setUp()
    {
        $this->clean();

        $this->cache = \Depage\Cache\Cache::factory("test", array(
            'disposition' => "memcache",
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
        $memc = new \Memcache();
        $memc->addServer("localhost", "11211");
        $memc->flush();
    }
    // }}}
}

/* vim:set ft=php sts=4 fdm=marker et : */
