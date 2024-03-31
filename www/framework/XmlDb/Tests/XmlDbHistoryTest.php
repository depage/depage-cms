<?php

namespace Depage\XmlDb\Tests;

class XmlDbHistoryTest extends XmlDbTestCase
{
    // {{{ variables
    protected $xmlDbHistory;
    protected $cache;
    protected $testXmlDocument = '<?xml version="1.0"?>' . "\n" . '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="ver2" db:docid="3" db:id="4" db:lastchange="2016-02-03 16:02:00" db:lastchangeUid=""><pg:page name="Home3" db:id="5"><pg:page name="P3.1" db:id="6">bla bla blub <pg:page name="P3.1.2" db:id="7"/></pg:page><pg:page name="P3.2" db:id="8"/></pg:page></dpg:pages>';
    protected $testXmlDocumentNoIdAttr = '<?xml version="1.0"?>' . "\n" . '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="ver2" db:docid="3" db:lastchange="2016-02-03 16:02:00" db:lastchangeUid=""><pg:page name="Home3"><pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page><pg:page name="P3.2"/></pg:page></dpg:pages>';
    // }}}
    // {{{ setUp
    protected function setUp():void
    {
        parent::setUp();

        $this->cache = \Depage\Cache\Cache::factory('xmlDb', ['disposition' => 'uncached']);

        $this->xmlDbHistory = new \Depage\XmlDb\XmlDbHistory($this->pdo->prefix . '_proj_test', $this->pdo, $this->cache);
    }
    // }}}

    // {{{ testDocExistsByIhd
    public function testDocExistsById()
    {
        $this->assertSame(3, $this->xmlDbHistory->docExists(3));
    }
    // }}}
    // {{{ testDocExistsByIdFail
    public function testDocExistsByIdFail()
    {
        $this->assertFalse($this->xmlDbHistory->docExists(2));
    }
    // }}}
    // {{{ testDocExistsByName
    public function testDocExistsByName()
    {
        $this->assertEquals(3, $this->xmlDbHistory->docExists('pages'));
    }
    // }}}
    // {{{ testDocExistsByNameFail
    public function testDocExistsByNameFail()
    {
        $this->assertFalse($this->xmlDbHistory->docExists('noDocByThisName'));
    }
    // }}}

    // {{{ testGetDocXmlById
    public function testGetDocXmlById()
    {
        $this->assertXmlStringEqualsXmlString($this->testXmlDocument, $this->xmlDbHistory->getDocXml(3));
    }
    // }}}
    // {{{ testGetDocXmlByIdNoIdAttr
    public function testGetDocXmlByIdNoIdAttr()
    {
        $this->assertXmlStringEqualsXmlString($this->testXmlDocumentNoIdAttr, $this->xmlDbHistory->getDocXml(3, false));
    }
    // }}}
    // {{{ testGetDocXmlByIdFail
    public function testGetDocXmlByIdFail()
    {
        $this->assertFalse($this->xmlDbHistory->getDocXml(2));
    }
    // }}}
    // {{{ testGetDocXmlByName
    public function testGetDocXmlByName()
    {
        $this->assertXmlStringEqualsXmlString($this->testXmlDocument, $this->xmlDbHistory->getDocXml('pages'));
    }
    // }}}
    // {{{ testGetDocXmlByNameNoIdAttr
    public function testGetDocXmlByNameNoIdAttr()
    {
        $this->assertXmlStringEqualsXmlString($this->testXmlDocumentNoIdAttr, $this->xmlDbHistory->getDocXml('pages', false));
    }
    // }}}
    // {{{ testGetDocXmlByNameFail
    public function testGetDocXmlByNameFail()
    {
        $this->assertFalse($this->xmlDbHistory->getDocXml('noDocByThisName'));
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
