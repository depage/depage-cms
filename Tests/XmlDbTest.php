<?php

namespace Depage\XmlDb\Tests;

class XmlDbTest extends DatabaseTestCase
{
    protected $xmlDb;
    protected $xmlPages4 = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="" db:lastchange="2016-02-03 16:09:05" db:lastchangeUid=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/></pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';

    // {{{ setUp
    protected function setUp()
    {
        parent::setUp();

        $cache = \Depage\Cache\Cache::factory('xmlDb', array('disposition' => 'uncached'));
        $this->xmlDb = new XmlDbTestClass($this->pdo->prefix . '_proj_test', $this->pdo, $cache, array(
            'root',
            'child',
        ));
    }
    // }}}

    // {{{ testGetDocumentsByName
    public function testGetDocumentsByName()
    {
        $docs = $this->xmlDb->getDocuments('pages');
        $pagesDoc = $docs['pages'];

        $this->assertEquals(array('pages'), array_keys($docs));
        $this->assertInstanceOf('Depage\XmlDb\Document', $pagesDoc);
        $this->assertEquals('pages', $pagesDoc->getDocInfo()->name);
    }
    // }}}
    // {{{ testGetDocuments
    public function testGetDocuments()
    {
        $docs = $this->xmlDb->getDocuments();
        $expectedNames = array(
            'pages',
            'pages2',
            'pages3',
            'pages4',
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
        $this->assertFalse($this->xmlDb->docExists("non existent document"));
        $this->assertFalse($this->xmlDb->docExists(100));
        $this->assertEquals(1, $this->xmlDb->docExists("pages"));
        $this->assertEquals(1, $this->xmlDb->docExists(1));
    }
    // }}}

    // {{{ testGetDoc
    public function testGetDoc()
    {
        $searches = array(6, '6', 'pages4');

        foreach ($searches as $search) {
            $this->assertXmlStringEqualsXmlString($this->xmlPages4, $this->xmlDb->getDoc($search)->getXml(false));
        }
    }
    // }}}
    // {{{ testGetDocNonExistent
    public function testGetDocNonExistent()
    {
        $xml = $this->xmlDb->getDoc("non existing document");
        $this->assertFalse($xml);

        $xml = $this->xmlDb->getDoc(100);
        $this->assertFalse($xml);
    }
    // }}}

    // {{{ testGetDocByNodeId
    public function testGetDocByNodeId()
    {
        $this->assertEquals(1, $this->xmlDb->getDocByNodeId(1)->getDocId());
        $this->assertEquals(3, $this->xmlDb->getDocByNodeId(5)->getDocId());
        $this->assertEquals(2, $this->xmlDb->getDocByNodeId(4)->getDocId());
    }
    // }}}
    // {{{ testGetDocByNodeIdNonExistent
    public function testGetDocByNodeIdNonExistent()
    {
        $this->assertFalse($this->xmlDb->getDocByNodeId(42));
    }
    // }}}

    // {{{ testGetDocXml
    public function testGetDocXml()
    {
        $this->assertXmlStringEqualsXmlString($this->xmlPages4, $this->xmlDb->getDocXml('pages4', false));
        $this->assertXmlStringEqualsXmlString($this->xmlPages4, $this->xmlDb->getDocXml(6, false));
    }
    // }}}
    // {{{ testGetDocXmlFail
    public function testGetDocXmlFail()
    {
        $xml = $this->xmlDb->getDocXml('idontexist');

        $this->assertFalse($xml);
    }
    // }}}

    // {{{ testCreateDoc
    public function testCreateDoc()
    {
        $doc = $this->xmlDb->createDoc();

        $this->assertInstanceOf('Depage\XmlDb\Document', $doc);
        $this->assertEquals(7, $doc->getDocId());
    }
    // }}}
    // {{{ testCreateDocSpecific
    public function testCreateDocSpecific()
    {
        $doc = $this->xmlDb->createDoc('Depage\XmlDb\XmlDocTypes\Base', 'newDoc');

        $this->assertInstanceOf('Depage\XmlDb\Document', $doc);
        $this->assertEquals('newDoc', $doc->getDocInfo()->name);
        $this->assertEquals(7, $doc->getDocId());
    }
    // }}}
    // {{{ testCreateDocInvalidName
    /**
     * @expectedException Depage\XmlDb\Exceptions\XmlDbException
     * @expectedExceptionMessage Invalid or duplicate document name: ""
     */
    public function testCreateDocInvalidName()
    {
        $doc = $this->xmlDb->createDoc('Depage\XmlDb\XmlDocTypes\Base', false);
    }
    // }}}

    // {{{ testDuplicateDoc
    public function testDuplicateDoc()
    {
        $doc = $this->xmlDb->duplicateDoc('pages', 'newPages');

        $this->assertInstanceOf('Depage\XmlDb\Document', $doc);
        $this->assertEquals(7, $doc->getDocId());
        $this->assertEquals('newPages', $doc->getDocInfo()->name);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($this->xmlDb->getDoc('pages')->getXml(false), $doc->getXml(false));
    }
    // }}}
    // {{{ testDuplicateDocFail
    public function testDuplicateDocFail()
    {
        $doc = $this->xmlDb->duplicateDoc('idontexist', 'copy');

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

        $this->assertArrayHasKey('pages', $this->xmlDb->getDocuments('pages'));

        $this->xmlDb->docExists('pages'); // load id into cache
        $this->assertEquals($idsBefore, $this->xmlDb->doc_ids);

        $return = $this->xmlDb->removeDoc('pages');
        $this->assertTrue($return);

        $this->assertArrayNotHasKey('pages', $this->xmlDb->getDocuments('pages'));
        $this->assertEquals($idsAfter, $this->xmlDb->doc_ids);
    }
    // }}}
    // {{{ testRemoveDocUnavailable
    public function testRemoveDocUnavailable()
    {
        $return = $this->xmlDb->removeDoc('non existent document');

        $this->assertFalse($return);
        $this->assertArrayNotHasKey('non existent document', $this->xmlDb->getDocuments('non existent document'));
    }
    // }}}

    // {{{ testclearTables
    public function testClearTables()
    {
        // @todo foreign key constraints
        $this->insertDummyDataIntoTable('xmldb_proj_test_xmlnodetypes');
        $this->insertDummyDataIntoTable('xmldb_proj_test_xmldocs');

        $this->xmlDb->clearTables();

        $this->assertTableEmpty('xmldb_proj_test_xmlnodetypes');
        $this->assertTableEmpty('xmldb_proj_test_xmldocs');

        // make sure it'll work on empty tables
        $this->xmlDb->clearTables();

        $this->assertTableEmpty('xmldb_proj_test_xmlnodetypes');
        $this->assertTableEmpty('xmldb_proj_test_xmldocs');
    }
    // }}}

    // {{{ testCreateDocExisting
    /**
     * @expectedException Depage\XmlDb\Exceptions\XmlDbException
     * @expectedExceptionMessage Invalid or duplicate document name: "pages"
     */
    public function testCreateDocExisting()
    {
        $this->xmlDb->createDoc('Depage\XmlDb\XmlDocTypes\Base', 'pages');
    }
    // }}}

    // {{{ testCleanOperator
    public function testCleanOperator()
    {
        $this->assertEquals('=', $this->xmlDb->cleanOperator('='));
        $this->assertEquals('<', $this->xmlDb->cleanOperator('<'));
        $this->assertEquals('>', $this->xmlDb->cleanOperator('>'));
        $this->assertEquals('<=', $this->xmlDb->cleanOperator('<='));
        $this->assertEquals('>=', $this->xmlDb->cleanOperator('>='));
        $this->assertEquals('and', $this->xmlDb->cleanOperator('and'));
        $this->assertEquals('AND', $this->xmlDb->cleanOperator('AND'));
        $this->assertEquals('or', $this->xmlDb->cleanOperator('or'));
        $this->assertEquals('OR', $this->xmlDb->cleanOperator('OR'));

        $this->assertEquals('', $this->xmlDb->cleanOperator('\''));
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
