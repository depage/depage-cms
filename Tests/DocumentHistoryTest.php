<?php

namespace Depage\XmlDb\Tests;

class DocumentHistoryTest extends DatabaseTestCase
{
    // {{{ variables
    protected $xmlDb;
    protected $doc;
    protected $history;
    protected $ver1 = array(
        'last_saved_at' => '2016-02-03 16:01:00',
        'user_id' => '1',
        'published' => '0',
        'hash' => '91c9ab72534f336e7f7d7759060508f36333bff4',
    );
    protected $ver2 = array(
        'last_saved_at' => '2016-02-03 16:02:00',
        'user_id' => '1',
        'published' => '1',
        'hash' => 'b8d61df8cd5d29de11231a347d9c31a6f523a4f8',
    );
    protected $ver3 = array(
        'last_saved_at' => '2016-02-03 16:03:00',
        'user_id' => '1',
        'published' => '0',
        'hash' => '12a17a2601f621bbe05ffa5c599ed6abed59a072',
    );

    protected $xml1 = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="ver1" db:docid="6" db:id="27" db:lastchange="2016-02-03 16:02:00" db:lastchangeUid=""><pg:page name="Home6" db:id="28"><pg:page name="P6.1" db:id="29">bla bla blub <pg:page name="P6.1.2" db:id="30"/></pg:page><pg:page name="P6.2" db:id="31"/></pg:page></dpg:pages>';
    protected $xml2 = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="ver2" db:docid="6" db:id="27" db:lastchange="2016-02-03 16:02:00" db:lastchangeUid=""><pg:page name="Home6" db:id="28"><pg:page name="P6.1" db:id="29">bla bla blub <pg:page name="P6.1.2" db:id="30"/></pg:page><pg:page name="P6.2" db:id="31"/></pg:page></dpg:pages>';

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

        $this->doc = new DocumentTestClass($this->xmlDb, 6);
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
            strtotime('2016-02-03 16:01:00') => $this->ver1,
            strtotime('2016-02-03 16:02:00') => $this->ver2,
            strtotime('2016-02-03 16:03:00') => $this->ver3,
        );

        $this->assertEquals($expected, $this->history->getVersions());
    }
    // }}}
    // {{{ testGetVersionsPublished
    public function testGetVersionsPublished()
    {
        $published = array(
            strtotime('2016-02-03 16:02:00') => $this->ver2,
        );

        $this->assertEquals($published, $this->history->getVersions(true));
    }
    // }}}
    // {{{ testGetVersionsUnpublished
    public function testGetVersionsUnpublished()
    {
        $unpublished = array(
            strtotime('2016-02-03 16:01:00') => $this->ver1,
            strtotime('2016-02-03 16:03:00') => $this->ver3,
        );

        $this->assertEquals($unpublished, $this->history->getVersions(false));
    }
    // }}}
    // {{{ testGetVersionsMaxResultsOne
    public function testGetVersionsMaxResultsOne()
    {
        $expected = array(
            strtotime('2016-02-03 16:03:00') => $this->ver3,
        );

        $this->assertEquals($expected, $this->history->getVersions(null, 1));
    }
    // }}}
    // {{{ testGetVersionsMaxResultsTen
    public function testGetVersionsMaxResultsTen()
    {
        $expected = array(
            strtotime('2016-02-03 16:01:00') => $this->ver1,
            strtotime('2016-02-03 16:02:00') => $this->ver2,
            strtotime('2016-02-03 16:03:00') => $this->ver3,
        );

        $this->assertEquals($expected, $this->history->getVersions(null, 10));
    }
    // }}}

    // {{{ testGetLatestVersion
    public function testGetLatestVersion()
    {
        $this->assertEquals($this->ver3, $this->history->getLatestVersion());

        $newXml = '<root/>';
        $expected = '<root xmlns:db="http://cms.depagecms.net/ns/database" db:docid="6" db:id="27"/>';
        $this->addTestDoc($newXml);

        $this->setForeignKeyChecks(false);
        $timestamp = $this->history->save(6, true);
        $this->setForeignKeyChecks(true);

        $latestVersion = $this->history->getLatestVersion();
        $newTimestamp = strtotime($latestVersion['last_saved_at']);
        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->history->getXml($newTimestamp));
    }
    // }}}

    // {{{ testGetXml
    public function testGetXml()
    {
        $doc = $this->history->getXml(strtotime('2016-02-03 16:02:00'));

        $this->assertInstanceOf('\Depage\Xml\Document', $doc);
        $this->assertXmlStringEqualsXmlString($this->xml2, $doc);
    }
    // }}}
    // {{{ testGetXmlNoIdAttr
    public function testGetXmlNoIdAttr()
    {
        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="ver2" db:docid="6" db:lastchange="2016-02-03 16:02:00" db:lastchangeUid=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/></pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $doc = $this->history->getXml(strtotime('2016-02-03 16:02:00'), false);

        $this->assertXmlStringEqualsXmlString($expected, $doc);
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
        $this->assertXmlStringEqualsXmlString($this->xml2, $this->history->getLastPublishedXml());
    }
    // }}}

    // {{{ testSave
    public function testSave()
    {
        $newXml = '<root/>';
        $this->addTestDoc($newXml);

        $this->setForeignKeyChecks(false);
        $timestamp = $this->history->save(6, true);
        $this->setForeignKeyChecks(true);

        $historyTable = $this->getConnection()->createQueryTable('xmldb_proj_test_history', 'SELECT * FROM xmldb_proj_test_history');
        $expected = '<root xmlns:db="http://cms.depagecms.net/ns/database" db:id="27" db:lastchangeUid=""></root>';

        $rows = $historyTable->getRowCount();
        $lastRowNumber = $rows - 1;
        $this->assertXmlStringEqualsXmlString($expected, $historyTable->getValue($lastRowNumber,'xml'));
    }
    // }}}
    // {{{ testSaveUserId
    public function testSaveUserId()
    {
        $newXml = '<root xmlns:db="http://cms.depagecms.net/ns/database"></root>';
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
        $newXml = '<root xmlns:db="http://cms.depagecms.net/ns/database"></root>';
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
        $newXml = '<root/>';
        $this->addTestDoc($newXml);

        $this->setForeignKeyChecks(false);
        $timestamp = $this->history->save(6, false);
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
        $afterTimestamp = $this->history->save(6, true);
        $this->setForeignKeyChecks(true);

        $afterDate = date('Y-m-d H:i:s', $afterTimestamp);

        $this->assertEquals($beforeDate, $afterDate);
    }
    // }}}

    // {{{ testRestore
    public function testRestore()
    {
        $this->assertXmlStringEqualsXmlString($this->xml2, $this->history->getLastPublishedXml());

        $result = $this->history->restore(strtotime('2016-02-03 16:01:00'));

        $ignore = array('db:lastchange');
        $this->assertXmlStringEqualsXmlStringIgnoreAttributes($this->xml1, $this->doc->getXml(), $ignore);
        $this->assertXmlStringEqualsXmlStringIgnoreAttributes($result, $this->doc->getXml(), $ignore);
    }
    // }}}

    // {{{ testDelete
    public function testDelete()
    {
        $before = array(
            strtotime('2016-02-03 16:01:00') => $this->ver1,
            strtotime('2016-02-03 16:02:00') => $this->ver2,
            strtotime('2016-02-03 16:03:00') => $this->ver3,
        );
        $this->assertEquals($before, $this->history->getVersions());

        $result = $this->history->delete(strtotime('2016-02-03 16:01:00'));
        $this->assertEquals(true, $result);
        $after = array(
            strtotime('2016-02-03 16:02:00') => $this->ver2,
            strtotime('2016-02-03 16:03:00') => $this->ver3,
        );
        $this->assertEquals($after, $this->history->getVersions());
    }
    // }}}
    // {{{ testDeleteFail
    public function testDeleteFail()
    {
        $expected = array(
            strtotime('2016-02-03 16:01:00') => $this->ver1,
            strtotime('2016-02-03 16:02:00') => $this->ver2,
            strtotime('2016-02-03 16:03:00') => $this->ver3,
        );
        $this->assertEquals($expected, $this->history->getVersions());

        $result = $this->history->delete(strtotime('1985-10-26 09:00:00'));

        $this->assertEquals(false, $result);
        $this->assertEquals($expected, $this->history->getVersions());
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
