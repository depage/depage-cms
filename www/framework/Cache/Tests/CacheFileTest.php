<?php

/**
 * Blackbox tests for all extensions, compares imagesizes/filesizes
 **/
class CacheFileTest extends \PHPUnit\Framework\TestCase
{
    protected $cache;

    // {{{ setUp()
    /**
     * setup function
     **/
    public function setUp():void
    {
        $this->clean();

        $this->cache = Depage\Cache\Cache::factory("test", array(
            'disposition' => "file",
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
        // @todo implement cross plattform better clean
        exec("rm -r cache/* 2> /dev/null");
    }
    // }}}

    // {{{ testSetGetSimpleString()
    /**
     * Tests basic getter and setter
     **/
    public function testSetGetSimpleString()
    {
        $var = "This is a test content";
        $key = "test";

        $this->cache->set($key, $var);

        $this->assertEquals($var, $this->cache->get($key));
    }
    // }}}
    // {{{ testSetGetSimpleNumber()
    /**
     * Tests basic getter and setter
     **/
    public function testSetGetSimpleNumber()
    {
        $var = 2;
        $key = "test";

        $this->cache->set($key, $var);

        $this->assertEquals($var, $this->cache->get($key));
    }
    // }}}
    // {{{ testSetGetObject()
    /**
     * Tests basic getter and setter with object content
     **/
    public function testSetGetObject()
    {
        $var = new \StdClass();
        $var->attr1 = "Test attribute";
        $var->attr2 = 2;

        $key = "test";

        $this->cache->set($key, $var);

        $this->assertEquals($var, $this->cache->get($key));
    }
    // }}}
    // {{{ testSetGetXmlString()
    /**
     * Tests basic getter and setter with xml string content
     **/
    public function testSetGetXmlString()
    {
        $var = "<?xml version=\"1.0\">\n<root>text content</root>";
        $key = "test.xml";

        $this->cache->set($key, $var);

        $this->assertEquals($var, $this->cache->get($key));
    }
    // }}}
    // {{{ testSetGetJsonString()
    /**
     * Tests basic getter and setter with json string content
     **/
    public function testSetGetJsonString()
    {
        $var = '{"a":1,"b":2,"c":3,"d":4,"e":5}';
        $key = "test.json";

        $this->cache->set($key, $var);

        $this->assertEquals($var, $this->cache->get($key));
    }
    // }}}

    // {{{ testExists()
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

        $this->assertTrue($this->cache->exist($key1));
        $this->assertFalse($this->cache->exist($key2));
    }
    // }}}

    // {{{ testDelete()
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
    // {{{ testNonExistant()
    /**
     * Tests basic return value for unset keys
     **/
    public function testNonExistant()
    {
        $this->assertFalse($this->cache->get("key"));
    }
    // }}}

    // {{{ testSetGetNamespace()
    /**
     * Tests basic getter and setter
     **/
    public function testSetGetNamespace()
    {
        $var = "This is a test content";
        $key = "test/sub/sub1";

        $this->cache->set($key, $var);

        $this->assertEquals($var, $this->cache->get($key));
    }
    // }}}
    // {{{ testDeleteNamespace()
    /**
     * Tests basic getter and setter
     **/
    public function testDeleteNamespace()
    {
        $var = "This is a test content";
        $key1 = "test/sub/sub1";
        $key2 = "test/sub/sub2";
        $key3 = "test/sub/subsub/sub3";
        $key4 = "test/val";

        $this->cache->set($key1, $var);
        $this->cache->set($key2, $var);
        $this->cache->set($key3, $var);
        $this->cache->set($key4, $var);

        $this->cache->delete("test/sub/");

        // all inside the sub namespace should be deleted
        $this->assertFalse($this->cache->get($key1));
        $this->assertFalse($this->cache->get($key2));
        $this->assertFalse($this->cache->get($key3));

        // things in the test namespace should still be set
        $this->assertEquals($var, $this->cache->get($key4));
    }
    // }}}

    // {{{ testSerializedCacheObject()
    /**
     * Test serialization of Cache Object
     **/
    public function testSerializedCacheObject()
    {
        $var = "This is a test content";
        $key = "test";

        $serialized = serialize($this->cache);
        $newCache = unserialize($serialized);

        $newCache->set($key, $var);

        $this->assertEquals($var, $newCache->get($key));
    }
    // }}}

    // {{{ testClear()
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
