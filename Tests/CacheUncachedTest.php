<?php

/**
 * Blackbox tests for all extensions, compares imagesizes/filesizes
 **/
class CacheUncachedTest extends \PHPUnit\Framework\TestCase
{
    // {{{ setUp
    /**
     * setup function
     **/
    public function setUp():void
    {
        $this->cache = \Depage\Cache\Cache::factory("test", array(
            'disposition' => 'uncached',
        ));
    }
    // }}}

    // {{{ testSetGet
    /**
     * Tests basic getter and setter
     **/
    public function testSetGetSimpleString()
    {
        $var = "This is a test content";
        $key = "test";

        $this->cache->set($key, $var);

        $this->assertFalse($this->cache->get($key));
    }
    // }}}

    // {{{ testExists
    /**
     * Tests basic exists test
     **/
    public function testExists()
    {
        $var = "This is a test content";
        $key1 = "test1";
        $key2 = "test2";

        $this->cache->set($key1, $var);
        $this->cache->set($key2, $var);

        $this->cache->delete($key2, $var);

        $this->assertFalse($this->cache->exist($key1));
        $this->assertFalse($this->cache->exist($key2));
    }
    // }}}

    // {{{ testDelete
    /**
     * Tests basic return value for unset keys
     **/
    public function testDelete()
    {
        $key = "key";
        $content = "This is the content";

        $this->cache->set($key, $content);
        $this->cache->delete($key);

        $this->assertFalse($this->cache->get($key));
    }
    // }}}
    // {{{ testNonExistant
    /**
     * Tests basic return value for unset keys
     **/
    public function testNonExistant()
    {
        $this->assertFalse($this->cache->get("key"));
    }
    // }}}

    // {{{ testClear
    /**
     * Tests clear function
     **/
    public function testClear()
    {
        $key = "key";
        $content = "This is the content";

        $this->cache->set($key, $content);
        $this->cache->clear();

        $this->assertFalse($this->cache->get($key));
    }
    // }}}
}

/* vim:set ft=php sts=4 fdm=marker et : */
