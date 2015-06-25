<?php

namespace Depage\XmlDb\Tests;

class DocumentHistoryTest extends DatabaseTestCase
{
    // {{{ variables
    protected $xmlDb;
    protected $doc;
    protected $history;
    // }}}
    // {{{ setUp
    protected function setUp()
    {
        parent::setUp();

        // get cache instance
        $this->cache = \Depage\Cache\Cache::factory('xmlDb', array('disposition' => 'uncached'));

        // get xmldb instance
        $this->xmlDb = new \Depage\XmlDb\XmlDb($this->pdo->prefix . '_proj_test', $this->pdo, $this->cache, array(
            'root',
            'child',
        ));

        $this->doc = new DocumentTestClass($this->xmlDb, 1);
        $this->history = $this->doc->getHistory();
    }
    // }}}

    // {{{ testGetVersions
    public function testGetVersions()
    {
        $expected = array(
            -62169987600 => array(
                'last_saved_at' => '0000-00-00 00:00:00',
                'user_id' => '1',
                'published' => '0',
                'hash' => 'ba4e7ab543319b169e4b86eaeead19079fea5acb'
            )
        );

        $this->assertEquals($expected, $this->history->getVersions());
    }
    // }}}
    // {{{ testGetVersionsPublished
    public function testGetVersionsPublished()
    {
        $unpublished = array(
            -62169987600 => array(
                'last_saved_at' => '0000-00-00 00:00:00',
                'user_id' => '1',
                'published' => '0',
                'hash' => 'ba4e7ab543319b169e4b86eaeead19079fea5acb'
            )
        );

        $this->assertEquals(array(), $this->history->getVersions(true));
        $this->assertEquals($unpublished, $this->history->getVersions(false));
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
