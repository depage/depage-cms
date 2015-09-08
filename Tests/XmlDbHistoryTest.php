<?php

namespace Depage\XmlDb\Tests;

class XmlDbHistoryTest extends DatabaseTestCase
{
    // {{{ variables
    protected $xmlDbHistory;
    protected $testXmlDocument = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1" db:lastchange="2015-06-26 14:07:37" db:lastchangeUid=""><root db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><node db:id="12"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></root></dpg:pages>';
    // }}}
    // {{{ setUp
    protected function setUp()
    {
        parent::setUp();

        $this->xmlDbHistory = new \Depage\XmlDb\XmlDbHistory($this->pdo->prefix . '_proj_test', $this->pdo);
    }
    // }}}

    // {{{ testDocExistsById
    public function testDocExistsById()
    {
        $this->assertSame(1, $this->xmlDbHistory->docExists(1));
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
        $this->assertSame(1, $this->xmlDbHistory->docExists('pages'));
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
        $this->assertXmlStringEqualsXmlString($this->testXmlDocument, $this->xmlDbHistory->getDocXml(1));
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
    // {{{ testGetDocXmlByNameFail
    public function testGetDocXmlByNameFail()
    {
        $this->assertFalse($this->xmlDbHistory->getDocXml('noDocByThisName'));
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
