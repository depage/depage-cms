<?php

namespace Depage\Cache\Cache\Tests;

require_once("bootstrap.php");

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
            'disposition' => "memory",
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
    }
    // }}}
}

/* vim:set ft=php sts=4 fdm=marker et : */
