<?php

class XmlDbTest extends Depage\XmlDb\Tests\DatabaseTestCase
{
    protected $xmldb;
    // {{{ setUp
    protected function setUp()
    {
        parent::setUp();

        // get cache instance
        $cache = Depage\Cache\Cache::factory('xmldb', array('disposition' => 'uncached'));

        // get xmldb instance
        $this->xmldb = new Depage\XmlDb\XmlDb($this->pdo->prefix . "_proj_test", $this->pdo, $cache, array(
            'root',
            'child',
        ));
    }
    // }}}

    // {{{ testGetDocumentsByName
    public function testGetDocumentsByName()
    {
        $docs = $this->xmldb->getDocuments('pages');
        $pagesDoc = $docs['pages'];

        $this->assertEquals(array('pages'), array_keys($docs));
        $this->assertInstanceOf('Depage\XmlDb\Document', $pagesDoc);
        $this->assertEquals('pages', $pagesDoc->getDocInfo()->name);
    }
    // }}}
    // {{{ testGetDocuments
    public function testGetDocuments()
    {
        $docs = $this->xmldb->getDocuments();
        $expectedNames = array(
            'pages',
            'tpl_newnodes',
            'tpl_templates',
        );

        $this->assertEquals($expectedNames, array_keys($docs));

        foreach ($expectedNames as $expectedName) {
            $expectedDoc = $docs[$expectedName];
            $this->assertInstanceOf('Depage\XmlDb\Document', $expectedDoc);
            $this->assertEquals($expectedName, $expectedDoc->getDocInfo()->name);
        }
    }
    // }}}
    // {{{ testDocExists
    public function testDocExists()
    {
        $this->assertFalse($this->xmldb->docExists("non existent document"));
        $this->assertFalse($this->xmldb->docExists(100));
        $this->assertEquals(1, $this->xmldb->docExists("pages"));
        $this->assertEquals(1, $this->xmldb->docExists(1));
    }
    // }}}

    // {{{ testGetDoc
    public function testGetDoc()
    {
        $xmlStr = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" db:id="1" db:lastchange="0000-00-00 00:00:00" db:lastchangeUid="" db:name=""><pg:page file_type="html" multilang="true" name="Home" db:dataid="3" db:id="2"><pg:page file_type="html" multilang="true" name="Subpage" db:dataid="4" db:id="6"/><pg:page file_type="html" multilang="true" name="Subpage 2" db:dataid="5" db:id="7"/><pg:folder file_type="html" multilang="true" name="Subpage" db:dataid="7" db:id="9"/>bla bla blub <pg:page file_type="html" multilang="true" name="bla blub" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>';

        $searches = array(1, '1', 'pages');

        foreach ($searches as $search) {
            $this->assertXmlStringEqualsXmlString($xmlStr, $this->xmldb->getDoc($search)->getXml());
        }
    }
    // }}}
    // {{{ testGetDocNonExistent
    public function testGetDocNonExistent()
    {
        $xml = $this->xmldb->getDoc("non existing document");
        $this->assertFalse($xml);

        $xml = $this->xmldb->getDoc(100);
        $this->assertFalse($xml);
    }
    // }}}

    // {{{ testRemoveDoc
    public function testRemoveDoc()
    {
        $return = $this->xmldb->removeDoc('pages');

        $this->assertTrue($return);
        $this->assertArrayNotHasKey('pages', $this->xmldb->getDocuments('pages'));
    }
    // }}}
    // {{{ testRemoveDocUnavailable
    public function testRemoveDocUnavailable()
    {
        $return = $this->xmldb->removeDoc('non existent document');

        $this->assertFalse($return);
        $this->assertArrayNotHasKey('non existent document', $this->xmldb->getDocuments('non existent document'));
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
