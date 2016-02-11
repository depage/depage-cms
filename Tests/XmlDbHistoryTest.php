<?php

namespace Depage\XmlDb\Tests;

class XmlDbHistoryTest extends DatabaseTestCase
{
    // {{{ variables
    protected $xmlDbHistory;
    protected $testXmlDocument = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="ver2" db:id="27" db:lastchangeUid=""><pg:page name="Home6" db:id="28"><pg:page name="P6.1" db:id="29">bla bla blub <pg:page name="P6.1.2" db:id="30"/></pg:page><pg:page name="P6.2" db:id="31"/></pg:page></dpg:pages>';
    protected $testXmlDocumentNoIdAttr = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="ver2" db:lastchangeUid=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/></pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';
    // }}}
    // {{{ setUp
    protected function setUp()
    {
        parent::setUp();

        $this->cache = \Depage\Cache\Cache::factory('xmlDb', array('disposition' => 'uncached'));

        $this->xmlDbHistory = new \Depage\XmlDb\XmlDbHistory($this->pdo->prefix . '_proj_test', $this->pdo, $this->cache);
    }
    // }}}

    // {{{ testDocExistsById
    public function testDocExistsById()
    {
        $this->assertSame(6, $this->xmlDbHistory->docExists(6));
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
        $this->assertEquals(6, $this->xmlDbHistory->docExists('pages4'));
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
        $this->assertXmlStringEqualsXmlString($this->testXmlDocument, $this->xmlDbHistory->getDocXml(6));
    }
    // }}}
    // {{{ testGetDocXmlByIdNoIdAttr
    public function testGetDocXmlByIdNoIdAttr()
    {
        $this->assertXmlStringEqualsXmlString($this->testXmlDocumentNoIdAttr, $this->xmlDbHistory->getDocXml(6, false));
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
        $this->assertXmlStringEqualsXmlString($this->testXmlDocument, $this->xmlDbHistory->getDocXml('pages4'));
    }
    // }}}
    // {{{ testGetDocXmlByNameNoIdAttr
    public function testGetDocXmlByNameNoIdAttr()
    {
        $this->assertXmlStringEqualsXmlString($this->testXmlDocumentNoIdAttr, $this->xmlDbHistory->getDocXml('pages4', false));
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
