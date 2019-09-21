<?php

namespace Depage\XmlDb\Tests;

class XmlDbTest extends XmlDbTestCase
{
    protected $xmlDb;
    protected $xmlPages = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="" db:lastchange="2016-02-03 16:09:05" db:lastchangeUid=""><pg:page name="Home3"><pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page><pg:page name="P3.2"/></pg:page></dpg:pages>';

    // {{{ setUp
    protected function setUp():void
    {
        parent::setUp();

        $cache = \Depage\Cache\Cache::factory('xmlDb', ['disposition' => 'uncached']);
        $this->xmlDb = new XmlDbTestClass($this->pdo->prefix . '_proj_test', $this->pdo, $cache, [
            'root',
            'child',
        ]);
    }
    // }}}

    // {{{ testGetDocumentsByName
    public function testGetDocumentsByName()
    {
        $docs = $this->xmlDb->getDocuments('pages');
        $pagesDoc = $docs['pages'];

        $this->assertEquals(['pages'], array_keys($docs));
        $this->assertInstanceOf('Depage\XmlDb\Document', $pagesDoc);
        $this->assertEquals('pages', $pagesDoc->getDocInfo()->name);
    }
    // }}}
    // {{{ testGetDocuments
    public function testGetDocuments()
    {
        $docs = $this->xmlDb->getDocuments();
        $expectedNames = [
            'pages',
            'pages2',
            'pages3',
            'tpl_newnodes',
            'tpl_templates',
        ];

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
        $this->assertEquals(3, $this->xmlDb->docExists("pages"));
        $this->assertEquals(1, $this->xmlDb->docExists(1));
    }
    // }}}

    // {{{ testGetDoc
    public function testGetDoc()
    {
        $searches = [3, '3', 'pages'];

        foreach ($searches as $search) {
            $this->assertXmlStringEqualsXmlString($this->xmlPages, $this->xmlDb->getDoc($search)->getXml(false));
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
        $this->assertEquals(2, $this->xmlDb->getDocByNodeId(3)->getDocId());
        $this->assertEquals(5, $this->xmlDb->getDocByNodeId(15)->getDocId());
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
        $this->assertXmlStringEqualsXmlString($this->xmlPages, $this->xmlDb->getDocXml('pages', false));
        $this->assertXmlStringEqualsXmlString($this->xmlPages, $this->xmlDb->getDocXml(3, false));
    }
    // }}}
    // {{{ testGetDocXmlFail
    public function testGetDocXmlFail()
    {
        $xml = $this->xmlDb->getDocXml('idontexist');

        $this->assertFalse($xml);
    }
    // }}}
    // {{{ testGetDocXmlEmpty
    public function testGetDocXmlEmpty()
    {
        $this->expectException(\Depage\XmlDb\Exceptions\XmlDbException::class);
        $this->expectExceptionMessage("Trying to get contents of empty document.");

        $doc = $this->xmlDb->createDoc();

        $this->xmlDb->getDocXml($doc->getDocId());
    }
    // }}}

    // {{{ testCreateDoc
    public function testCreateDoc()
    {
        $doc = $this->xmlDb->createDoc();

        $this->assertInstanceOf('Depage\XmlDb\Document', $doc);
        $this->assertEquals(6, $doc->getDocId());
    }
    // }}}
    // {{{ testCreateDocSpecific
    public function testCreateDocSpecific()
    {
        $doc = $this->xmlDb->createDoc('Depage\XmlDb\XmlDoctypes\Base', 'newDoc');

        $this->assertInstanceOf('Depage\XmlDb\Document', $doc);
        $this->assertEquals('newDoc', $doc->getDocInfo()->name);
        $this->assertEquals(6, $doc->getDocId());
    }
    // }}}
    // {{{ testCreateDocInvalidName
    public function testCreateDocInvalidName()
    {
        $this->expectException(\Depage\XmlDb\Exceptions\XmlDbException::class);
        $this->expectExceptionMessage("Invalid or duplicate document name \"\"");

        $doc = $this->xmlDb->createDoc('Depage\XmlDb\XmlDoctypes\Base', false);
    }
    // }}}

    // {{{ testDuplicateDoc
    public function testDuplicateDoc()
    {
        $doc = $this->xmlDb->duplicateDoc('pages', 'newPages');

        $this->assertInstanceOf('Depage\XmlDb\Document', $doc);
        $this->assertEquals(6, $doc->getDocId());
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
        $idsBefore = [
            3 => '3',
            'pages' => '3',
        ];

        $idsAfter = [];

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
        //$this->insertDummyDataIntoTable('xmldb_proj_test_xmlnodetypes');
        //$this->insertDummyDataIntoTable('xmldb_proj_test_xmldocs');

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
    public function testCreateDocExisting()
    {
        $this->expectException(\Depage\XmlDb\Exceptions\XmlDbException::class);
        $this->expectExceptionMessage("Invalid or duplicate document name \"pages\"");

        $this->xmlDb->createDoc('Depage\XmlDb\XmlDoctypes\Base', 'pages');
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
        $this->assertEquals('or', $this->xmlDb->cleanOperator('or'));
    }
    // }}}
    // {{{ testCleanOperatorFail
    public function testCleanOperatorFail()
    {
        $this->expectException(\Depage\XmlDb\Exceptions\XmlDbException::class);
        $this->expectExceptionMessage("Invalid XPath operator \"'\"");

        $this->xmlDb->cleanOperator('\'');
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
