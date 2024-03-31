<?php

namespace Depage\XmlDb\Tests;

class DocumentHistoryTest extends XmlDbTestCase
{
    // {{{ variables
    protected $xmlDb;
    protected $doc;
    protected $history;
    protected $cache;
    protected $ver1;
    protected $ver2;
    protected $ver3;

    protected $xml1 = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="ver1" db:docid="3" db:id="4" db:lastchange="2016-02-03 16:02:00" db:lastchangeUid=""><pg:page name="Home3" db:id="5"><pg:page name="P3.1" db:id="6">bla bla blub <pg:page name="P3.1.2" db:id="7"/></pg:page><pg:page name="P3.2" db:id="8"/></pg:page></dpg:pages>';
    protected $xml2 = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="ver2" db:docid="3" db:id="4" db:lastchange="2016-02-03 16:02:00" db:lastchangeUid=""><pg:page name="Home3" db:id="5"><pg:page name="P3.1" db:id="6">bla bla blub <pg:page name="P3.1.2" db:id="7"/></pg:page><pg:page name="P3.2" db:id="8"/></pg:page></dpg:pages>';

    protected $ignoreAttributes = [
        'db:id',
        'db:lastchangeUid',
    ];

    // }}}
    // {{{ setUp
    protected function setUp():void
    {
        parent::setUp();

        // get cache instance
        $this->cache = \Depage\Cache\Cache::factory('xmlDb', ['disposition' => 'uncached']);

        // get xmlDb instance
        $this->xmlDb = new \Depage\XmlDb\XmlDb($this->pdo->prefix . '_proj_test', $this->pdo, $this->cache, [
            'root',
            'child',
        ]);

        $this->doc = new DocumentTestClass($this->xmlDb, 3);
        $this->history = $this->doc->getHistory();
        $this->ver1 = (object)[
            'lastsaved' => new \DateTime('2016-02-03 16:01:00'),
            'firstsaved' => new \DateTime('2016-02-03 16:01:00'),
            'userId' => 1,
            'published' => 0,
            'hash' => '5ceae27386aa1518d346c3129ef9c2d530c18769',
        ];
        $this->ver2 = (object)[
            'lastsaved' => new \DateTime('2016-02-03 16:02:00'),
            'firstsaved' => new \DateTime('2016-02-03 16:02:00'),
            'userId' => 1,
            'published' => 1,
            'hash' => 'f80107795f6da964ce7e3ccf472b42931ea0884eb15dd40d0bc718d71ba94bf5',
        ];
        $this->ver3 = (object)[
            'lastsaved' => new \DateTime('2016-02-03 16:03:00'),
            'firstsaved' => new \DateTime('2016-02-03 16:03:00'),
            'userId' => 1,
            'published' => 0,
            'hash' => 'c8780f81274114f9f97771cd2e1428d2c39c2961',
        ];
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
        $expected = [
            strtotime('2016-02-03 16:01:00') => $this->ver1,
            strtotime('2016-02-03 16:02:00') => $this->ver2,
            strtotime('2016-02-03 16:03:00') => $this->ver3,
        ];

        $this->assertEquals($expected, $this->history->getVersions());
    }
    // }}}
    // {{{ testGetVersionsPublished
    public function testGetVersionsPublished()
    {
        $published = [
            strtotime('2016-02-03 16:02:00') => $this->ver2,
        ];

        $this->assertEquals($published, $this->history->getVersions(true));
    }
    // }}}
    // {{{ testGetVersionsUnpublished
    public function testGetVersionsUnpublished()
    {
        $unpublished = [
            strtotime('2016-02-03 16:01:00') => $this->ver1,
            strtotime('2016-02-03 16:03:00') => $this->ver3,
        ];

        $this->assertEquals($unpublished, $this->history->getVersions(false));
    }
    // }}}
    // {{{ testGetVersionsMaxResultsOne
    public function testGetVersionsMaxResultsOne()
    {
        $expected = [
            strtotime('2016-02-03 16:03:00') => $this->ver3,
        ];

        $this->assertEquals($expected, $this->history->getVersions(null, 1));
    }
    // }}}
    // {{{ testGetVersionsMaxResultsTen
    public function testGetVersionsMaxResultsTen()
    {
        $expected = [
            strtotime('2016-02-03 16:01:00') => $this->ver1,
            strtotime('2016-02-03 16:02:00') => $this->ver2,
            strtotime('2016-02-03 16:03:00') => $this->ver3,
        ];

        $this->assertEquals($expected, $this->history->getVersions(null, 10));
    }
    // }}}

    // {{{ testGetLatestVersion
    public function testGetLatestVersion()
    {
        $this->assertEquals($this->ver2, $this->history->getLatestVersion());

        $newXml = '<root/>';
        $expected = '<root xmlns:db="http://cms.depagecms.net/ns/database" db:docid="3" db:id="37"/>';
        $this->addTestDoc($newXml);

        $this->setForeignKeyChecks(false);
        $timestamp = $this->history->save(42, true);
        $this->setForeignKeyChecks(true);

        $latestVersion = $this->history->getLatestVersion();
        $newTimestamp = $latestVersion->lastsaved->getTimestamp();
        $this->assertXmlStringEqualsXmlStringIgnoreAllDbAttributes($expected, $this->history->getXml($newTimestamp));
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
        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="ver2" db:docid="3" db:lastchange="2016-02-03 16:02:00" db:lastchangeUid=""><pg:page name="Home3"><pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page><pg:page name="P3.2"/></pg:page></dpg:pages>';

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

        $oldRowNum = count($this->history->getVersions());

        $this->setForeignKeyChecks(false);
        $timestamp = $this->history->save(42, true);
        $this->setForeignKeyChecks(true);

        $expected = '<root xmlns:db="http://cms.depagecms.net/ns/database" db:id="4" db:lastchangeUid=""></root>';

        $newRowNum = count($this->history->getVersions());
        $this->assertEquals($oldRowNum + 1, $newRowNum);
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
        $this->assertEquals(42, $versions[$timestamp]->userId);
    }
    // }}}
    // {{{ testSavePublished
    public function testSavePublished()
    {
        $newXml = '<root xmlns:db="http://cms.depagecms.net/ns/database"></root>';
        $this->addTestDoc($newXml);

        $this->setForeignKeyChecks(false);
        $timestamp = $this->history->save(42, true);
        $this->setForeignKeyChecks(true);

        $versions = $this->history->getVersions();
        $this->assertEquals(1, $versions[$timestamp]->published);
    }
    // }}}
    // {{{ testSaveUnpublished
    public function testSaveUnpublished()
    {
        $newXml = '<root/>';
        $this->addTestDoc($newXml);

        $this->setForeignKeyChecks(false);
        $timestamp = $this->history->save(42, false);
        $this->setForeignKeyChecks(true);

        $versions = $this->history->getVersions();
        $this->assertEquals(0, $versions[$timestamp]->published);
    }
    // }}}
    // {{{ testSaveDuplicate
    public function testSaveDuplicate()
    {
        $latestVersion = $this->history->getLatestVersion();
        $date1 = $latestVersion->firstsaved;

        $this->setForeignKeyChecks(false);
        $afterTimestamp = $this->history->save(42, true);
        $this->setForeignKeyChecks(true);

        $latestVersion = $this->history->getLatestVersion();
        $date2 = $latestVersion->firstsaved;

        $this->assertEquals($date1, $date2);
    }
    // }}}

    // {{{ testRestore
    public function testRestore()
    {
        $this->assertXmlStringEqualsXmlString($this->xml2, $this->history->getLastPublishedXml());

        $result = $this->history->restore(strtotime('2016-02-03 16:01:00'));

        $ignore = ['db:lastchange'];
        $this->assertXmlStringEqualsXmlStringIgnoreAttributes($this->xml1, $this->doc->getXml(), $ignore);
        $this->assertXmlStringEqualsXmlStringIgnoreAttributes($result, $this->doc->getXml(), $ignore);
    }
    // }}}

    // {{{ testDelete
    public function testDelete()
    {
        $before = [
            strtotime('2016-02-03 16:01:00') => $this->ver1,
            strtotime('2016-02-03 16:02:00') => $this->ver2,
            strtotime('2016-02-03 16:03:00') => $this->ver3,
        ];
        $this->assertEquals($before, $this->history->getVersions());

        $result = $this->history->delete(strtotime('2016-02-03 16:01:00'));
        $this->assertEquals(true, $result);
        $after = [
            strtotime('2016-02-03 16:02:00') => $this->ver2,
            strtotime('2016-02-03 16:03:00') => $this->ver3,
        ];
        $this->assertEquals($after, $this->history->getVersions());
    }
    // }}}
    // {{{ testDeleteFail
    public function testDeleteFail()
    {
        $expected = [
            strtotime('2016-02-03 16:01:00') => $this->ver1,
            strtotime('2016-02-03 16:02:00') => $this->ver2,
            strtotime('2016-02-03 16:03:00') => $this->ver3,
        ];
        $this->assertEquals($expected, $this->history->getVersions());

        $result = $this->history->delete(strtotime('1985-10-26 09:00:00'));

        $this->assertEquals(false, $result);
        $this->assertEquals($expected, $this->history->getVersions());
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
