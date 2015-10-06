<?php

namespace Depage\XmlDb\Tests;

class DocumentHistoryTest extends DatabaseTestCase
{
    // {{{ variables
    protected $xmlDb;
    protected $doc;
    protected $history;
    protected $version37 = array(
        'last_saved_at' => '2015-06-26 12:07:37',
        'user_id' => '1',
        'published' => '0',
        'hash' => '9b8911ed83d9ccd6669ae95272fb1777f901c2f2',
    );
    protected $version38 = array(
        'last_saved_at' => '2015-06-26 12:07:38',
        'user_id' => '1',
        'published' => '1',
        'hash' => 'a02ccf643388c40161a22d5f82d6581d149e9222',
    );

    protected $xml37 = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1" db:lastchangeUid=""><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>';
    protected $xml38 = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1" db:lastchangeUid=""><root db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><node db:id="12"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></root></dpg:pages>';

    protected $ignoreAttributes = array(
        'db:id',
        'db:lastchangeUid',
    );

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

    // {{{ addTestDoc
    protected function addTestDoc($xml)
    {
        $newDoc = new \DomDocument();
        $newDoc->loadXml($xml);
        $this->doc->save($newDoc);

        return $newDoc;
    }
    // }}}

    // {{{ testGetVersions
    public function testGetVersions()
    {
        $expected = array(
            strtotime('2015-06-26 12:07:37') => $this->version37,
            strtotime('2015-06-26 12:07:38') => $this->version38,
        );

        $this->assertEquals($expected, $this->history->getVersions());
    }
    // }}}
    // {{{ testGetVersionsPublished
    public function testGetVersionsPublished()
    {
        $published = array(
            strtotime('2015-06-26 12:07:38') => $this->version38,
        );

        $this->assertEquals($published, $this->history->getVersions(true));
    }
    // }}}
    // {{{ testGetVersionsUnpublished
    public function testGetVersionsUnpublished()
    {
        $unpublished = array(
            strtotime('2015-06-26 12:07:37') => $this->version37,
        );

        $this->assertEquals($unpublished, $this->history->getVersions(false));
    }
    // }}}
    // {{{ testGetVersionsMaxResultsOne
    public function testGetVersionsMaxResultsOne()
    {
        $expected = array(
            strtotime('2015-06-26 12:07:38') => $this->version38,
        );

        $this->assertEquals($expected, $this->history->getVersions(null, 1));
    }
    // }}}
    // {{{ testGetVersionsMaxResultsTen
    public function testGetVersionsMaxResultsTen()
    {
        $expected = array(
            strtotime('2015-06-26 12:07:37') => $this->version37,
            strtotime('2015-06-26 12:07:38') => $this->version38,
        );

        $this->assertEquals($expected, $this->history->getVersions(null, 10));
    }
    // }}}

    // {{{ testGetLatestVersion
    public function testGetLatestVersion()
    {
        $this->assertEquals($this->version38, $this->history->getLatestVersion());

        $newXml = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database"></root>';
        $this->addTestDoc($newXml);

        $this->setForeignKeyChecks(false);
        $timestamp = $this->history->save(1, true);
        $this->setForeignKeyChecks(true);

        $latestVersion = $this->history->getLatestVersion();
        $newTimestamp = strtotime($latestVersion['last_saved_at']);
        $this->assertXmlStringEqualsXmlStringIgnoreAttributes($newXml, $this->history->getXml($newTimestamp)->saveXml(), $this->ignoreAttributes);
    }
    // }}}

    // {{{ testGetXml
    public function testGetXml()
    {
        $doc = $this->history->getXml(strtotime('2015-06-26 12:07:37'));

        $this->assertXmlStringEqualsXmlString($this->xml37, $doc->saveXml());
    }
    // }}}
    // {{{ testGetXmlNoIdAttr
    public function testGetXmlNoIdAttr()
    {
        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:lastchangeUid=""><pg:page name="Home" multilang="true" file_type="html" db:dataid="3"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6">bla bla bla </pg:page></pg:page></dpg:pages>';

        $doc = $this->history->getXml(strtotime('2015-06-26 12:07:37'), false);

        $this->assertXmlStringEqualsXmlString($expected, $doc->saveXml());
    }
    // }}}
    // {{{ testGetXmlFail
    public function testGetXmlFail()
    {
        $this->assertFalse($this->history->getXml(42));
    }
    // }}}

    // {{{ testGetLastPublishedXml
    public function testGetLastPublishedXml()
    {
        $this->assertXmlStringEqualsXmlString($this->xml38, $this->history->getLastPublishedXml()->saveXml());
    }
    // }}}

    // {{{ testSave
    public function testSave()
    {
        $newXml = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database"></root>';
        $this->addTestDoc($newXml);

        $this->setForeignKeyChecks(false);
        $timestamp = $this->history->save(1, true);
        $this->setForeignKeyChecks(true);

        $this->assertXmlStringEqualsXmlStringIgnoreAttributes($newXml, $this->history->getXml($timestamp)->saveXml(), $this->ignoreAttributes);
    }
    // }}}
    // {{{ testSaveUserId
    public function testSaveUserId()
    {
        $newXml = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database"></root>';
        $this->addTestDoc($newXml);

        $this->setForeignKeyChecks(false);
        $timestamp = $this->history->save(42);
        $this->setForeignKeyChecks(true);

        $versions = $this->history->getVersions();
        $this->assertEquals(42, $versions[$timestamp]['user_id']);
    }
    // }}}
    // {{{ testSavePublished
    public function testSavePublished()
    {
        $newXml = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database"></root>';
        $this->addTestDoc($newXml);

        $this->setForeignKeyChecks(false);
        $timestamp = $this->history->save(1, true);
        $this->setForeignKeyChecks(true);

        $versions = $this->history->getVersions();
        $this->assertEquals(1, $versions[$timestamp]['published']);
    }
    // }}}
    // {{{ testSaveUnpublished
    public function testSaveUnpublished()
    {
        $newXml = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database"></root>';
        $this->addTestDoc($newXml);

        $this->setForeignKeyChecks(false);
        $timestamp = $this->history->save(1, false);
        $this->setForeignKeyChecks(true);

        $versions = $this->history->getVersions();
        $this->assertEquals(0, $versions[$timestamp]['published']);
    }
    // }}}
    // {{{ testSaveDuplicate
    public function testSaveDuplicate()
    {
        $latestVersion = $this->history->getLatestVersion();
        $beforeDate = $latestVersion['last_saved_at'];

        $this->setForeignKeyChecks(false);
        $afterTimestamp = $this->history->save(1, true);
        $this->setForeignKeyChecks(true);

        $afterDate = date('Y-m-d H:i:s', $afterTimestamp);

        $this->assertEquals($beforeDate, $afterDate);
    }
    // }}}

    // {{{ testRestore
    public function testRestore()
    {
        $this->assertXmlStringEqualsXmlString($this->xml38, $this->history->getLastPublishedXml()->saveXml());

        $result = $this->history->restore(strtotime('2015-06-26 12:07:37'));

        $ignore = array('db:lastchange');
        $this->assertXmlStringEqualsXmlStringIgnoreAttributes($this->xml37, $this->doc->getXml()->saveXml(), $ignore);
        $this->assertXmlStringEqualsXmlStringIgnoreAttributes($result->saveXml(), $this->doc->getXml()->saveXml(), $ignore);
    }
    // }}}

    // {{{ testDelete
    public function testDelete()
    {
        $before = array(
            strtotime('2015-06-26 12:07:37') => $this->version37,
            strtotime('2015-06-26 12:07:38') => $this->version38,
        );
        $this->assertEquals($before, $this->history->getVersions());

        $result = $this->history->delete(strtotime('2015-06-26 12:07:37'));
        $this->assertEquals(true, $result);
        $after = array(
            strtotime('2015-06-26 12:07:38') => $this->version38,
        );
        $this->assertEquals($after, $this->history->getVersions());
    }
    // }}}
    // {{{ testDeleteFail
    public function testDeleteFail()
    {
        $expected = array(
            strtotime('2015-06-26 12:07:37') => $this->version37,
            strtotime('2015-06-26 12:07:38') => $this->version38,
        );
        $this->assertEquals($expected, $this->history->getVersions());

        $result = $this->history->delete(strtotime('2015-06-26 12:07:57'));

        $this->assertEquals(false, $result);
        $this->assertEquals($expected, $this->history->getVersions());
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
