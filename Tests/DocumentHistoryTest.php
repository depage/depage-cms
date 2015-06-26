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
            strtotime('2015-06-26 12:07:37') => array(
                'last_saved_at' => '2015-06-26 12:07:37',
                'user_id' => '1',
                'published' => '0',
                'hash' => 'ba4e7ab543319b169e4b86eaeead19079fea5acb'
            ),
            strtotime('2015-06-26 12:07:38') => array(
                'last_saved_at' => '2015-06-26 12:07:38',
                'user_id' => '1',
                'published' => '1',
                'hash' => '2bc9284487aa7441400f8e363e8b3065993432fb'
            )
        );

        $this->assertEquals($expected, $this->history->getVersions());
    }
    // }}}
    // {{{ testGetVersionsPublished
    public function testGetVersionsPublished()
    {
        $published = array(
            strtotime('2015-06-26 12:07:38') => array(
                'last_saved_at' => '2015-06-26 12:07:38',
                'user_id' => '1',
                'published' => '1',
                'hash' => '2bc9284487aa7441400f8e363e8b3065993432fb'
            )
        );

        $this->assertEquals($published, $this->history->getVersions(true));
    }
    // }}}
    // {{{ testGetVersionsUnpublished
    public function testGetVersionsUnpublished()
    {
        $unpublished = array(
            strtotime('2015-06-26 12:07:37') => array(
                'last_saved_at' => '2015-06-26 12:07:37',
                'user_id' => '1',
                'published' => '0',
                'hash' => 'ba4e7ab543319b169e4b86eaeead19079fea5acb'
            )
        );

        $this->assertEquals($unpublished, $this->history->getVersions(false));
    }
    // }}}
    // {{{ testGetVersionsMaxResultsOne
    public function testGetVersionsMaxResultsOne()
    {
        $expected = array(
            strtotime('2015-06-26 12:07:38') => array(
                'last_saved_at' => '2015-06-26 12:07:38',
                'user_id' => '1',
                'published' => '1',
                'hash' => '2bc9284487aa7441400f8e363e8b3065993432fb'
            )
        );

        $this->assertEquals($expected, $this->history->getVersions(null, 1));
    }
    // }}}
    // {{{ testGetVersionsMaxResultsTen
    public function testGetVersionsMaxResultsTen()
    {
        $expected = array(
            strtotime('2015-06-26 12:07:37') => array(
                'last_saved_at' => '2015-06-26 12:07:37',
                'user_id' => '1',
                'published' => '0',
                'hash' => 'ba4e7ab543319b169e4b86eaeead19079fea5acb'
            ),
            strtotime('2015-06-26 12:07:38') => array(
                'last_saved_at' => '2015-06-26 12:07:38',
                'user_id' => '1',
                'published' => '1',
                'hash' => '2bc9284487aa7441400f8e363e8b3065993432fb'
            )
        );

        $this->assertEquals($expected, $this->history->getVersions(null, 10));
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
