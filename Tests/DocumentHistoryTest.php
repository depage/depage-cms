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

    // {{{ testGetXml
    public function testGetXml()
    {
        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1" db:lastchange="0000-00-00 00:00:00" db:lastchangeUid=""><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>';

        $doc = $this->history->getXml(strtotime('2015-06-26 12:07:37'));

        $this->assertXmlStringEqualsXmlString($expected, $doc->saveXml());
    }
    // }}}

    // {{{ testGetLastPublishedXml
    public function testGetLastPublishedXml()
    {
        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1" db:lastchange="2015-06-26 14:07:37" db:lastchangeUid=""><root db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><node db:id="12"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></root></dpg:pages>';

        $this->assertXmlStringEqualsXmlString($expected, $this->history->getLastPublishedXml()->saveXml());
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
