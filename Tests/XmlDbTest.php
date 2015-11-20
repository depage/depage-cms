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

    // {{{ testGetDocByNodeId
    public function testGetDocByNodeId()
    {
        $this->assertEquals(1, $this->xmldb->getDocByNodeId(1)->getDocId());
        $this->assertEquals(3, $this->xmldb->getDocByNodeId(5)->getDocId());
        $this->assertEquals(2, $this->xmldb->getDocByNodeId(4)->getDocId());
    }
    // }}}
    // {{{ testGetDocByNodeIdNonExistent
    public function testGetDocByNodeIdNonExistent()
    {
        $this->assertFalse($this->xmldb->getDocByNodeId(42));
    }
    // }}}

    // {{{ testGetDocXml
    public function testGetDocXml()
    {
        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1" db:lastchange="0000-00-00 00:00:00" db:lastchangeUid=""><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlString($expected, $this->xmldb->getDocXml('pages'));
        $this->assertXmlStringEqualsXmlString($expected, $this->xmldb->getDocXml(1));
    }
    // }}}
    // {{{ testGetDocXmlFail
    public function testGetDocXmlFail()
    {
        $xml = $this->xmldb->getDocXml('idontexist');

        $this->assertFalse($xml);
    }
    // }}}

    // {{{ testCreateDoc
    public function testCreateDoc()
    {
        $doc = $this->xmldb->createDoc();

        $this->assertInstanceOf('Depage\XmlDb\Document', $doc);
        $this->assertEquals(4, $doc->getDocId());
    }
    // }}}
    // {{{ testCreateDocSpecific
    public function testCreateDocSpecific()
    {
        $doc = $this->xmldb->createDoc('Depage\XmlDb\XmlDocTypes\Base', 'newDoc');

        $this->assertInstanceOf('Depage\XmlDb\Document', $doc);
        $this->assertEquals('newDoc', $doc->getDocInfo()->name);
        $this->assertEquals(4, $doc->getDocId());
    }
    // }}}
    // {{{ testCreateDocInvalidName
    /**
     * @expectedException Depage\XmlDb\XmlDbException
     * @expectedExceptionMessage Invalid or duplicate document name: ""
     */
    public function testCreateDocInvalidName()
    {
        $doc = $this->xmldb->createDoc('Depage\XmlDb\XmlDocTypes\Base', false);
    }
    // }}}

    // {{{ testDuplicateDoc
    public function testDuplicateDoc()
    {
        $doc = $this->xmldb->duplicateDoc('pages', 'newPages');

        $this->assertInstanceOf('Depage\XmlDb\Document', $doc);
        $this->assertEquals(4, $doc->getDocId());
        $this->assertEquals('newPages', $doc->getDocInfo()->name);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($this->xmldb->getDoc('pages')->getXml(false), $doc->getXml(false));
    }
    // }}}
    // {{{ testDuplicateDocFail
    public function testDuplicateDocFail()
    {
        $doc = $this->xmldb->duplicateDoc('idontexist', 'copy');

        $this->assertFalse($doc);
    }
    // }}}

    // {{{ testRemoveDoc
    public function testRemoveDoc()
    {
        $idsBefore = array(
            1 => '1',
            'pages' => '1',
        );

        $idsAfter = array();

        $this->assertArrayHasKey('pages', $this->xmldb->getDocuments('pages'));

        $this->xmldb->docExists('pages'); // load id into cache
        $this->assertEquals($idsBefore, $this->xmldb->doc_ids);

        $return = $this->xmldb->removeDoc('pages');
        $this->assertTrue($return);

        $this->assertArrayNotHasKey('pages', $this->xmldb->getDocuments('pages'));
        $this->assertEquals($idsAfter, $this->xmldb->doc_ids);
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

    // {{{ testclearTables
    public function testClearTables()
    {
        // @todo foreign key constraints
        $this->insertDummyDataIntoTable('xmldb_proj_test_xmlnodetypes');
        $this->insertDummyDataIntoTable('xmldb_proj_test_xmldocs');

        $this->xmldb->clearTables();

        $this->assertTableEmpty('xmldb_proj_test_xmlnodetypes');
        $this->assertTableEmpty('xmldb_proj_test_xmldocs');

        // make sure it'll work on empty tables
        $this->xmldb->clearTables();

        $this->assertTableEmpty('xmldb_proj_test_xmlnodetypes');
        $this->assertTableEmpty('xmldb_proj_test_xmldocs');
    }
    // }}}

    // {{{ testCreateDocExisting
    /**
     * @expectedException Depage\XmlDb\XmlDbException
     * @expectedExceptionMessage Invalid or duplicate document name: "pages"
     */
    public function testCreateDocExisting()
    {
        $this->xmldb->createDoc('Depage\XmlDb\XmlDocTypes\Base', 'pages');
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
