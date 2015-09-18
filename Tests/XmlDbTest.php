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
     * @expectedExceptionMessage You have to give a valid name to save a new document.
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
        $this->assertArrayHasKey('pages', $this->xmldb->getDocuments('pages'));
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
     * @expectedException PdoException
     * @expectedExceptionMessage SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'pages' for key 'SECONDARY'
     */
    public function testCreateDocExisting()
    {
        $this->xmldb->createDoc('Depage\XmlDb\XmlDocTypes\Base', 'pages');
    }
    // }}}

    // {{{ getNodeIdsByDomXpath
    protected function getNodeIdsByDomXpath($doc, $xpath)
    {
        $ids = array();

        $domXpath = new \DomXpath($doc->getXml());
        $list = $domXpath->query($xpath);
        foreach ($list as $item) {
            $ids[] = $item->attributes->getNamedItem('id')->nodeValue;
        }

        return $ids;
    }
    // }}}
    // {{{ getAllNodeIdsByDomXpath
    protected function getAllNodeIdsByDomXpath($xpath)
    {
        $ids = array();

        foreach ($this->xmldb->getDocuments() as $doc) {
            $ids = array_merge($ids, $this->getNodeIdsByDomXpath($doc, $xpath));
        }

        return $ids;
    }
    // }}}
    // {{{ assertCorrectXpathIds
    protected function assertCorrectXpathIds(array $expectedIds, $xpath)
    {
        $actualIds = $this->xmldb->getNodeIdsByXpath($xpath);

        $this->assertEquals($expectedIds, $this->getAllNodeIdsByDomXpath($xpath), 'Failed asserting that expected IDs match DOMXPath query node IDs. Is the test set up correctly?');
        $this->assertEquals($expectedIds, $actualIds);
    }
    // }}}
    // {{{ assertCorrectXpathIdsNoDomXpath
    protected function assertCorrectXpathIdsNoDomXpath(array $expectedIds, $xpath)
    {
        $this->assertEquals($expectedIds, $this->xmldb->getNodeIdsByXpath($xpath));
    }
    // }}}

    // {{{ testGetNodeIdsByXpathByNameAll
    public function testGetNodeIdsByXpathByNameAll()
    {
        $this->assertCorrectXpathIds(array('2', '6', '7', '8'), '//pg:page');
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameAllWithAttribute
    public function testGetNodeIdsByXpathByNameAllWithAttribute()
    {
        $this->assertCorrectXpathIds(array('2', '6', '7', '8'), '//pg:page[@name]');
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameAllWithAttributeWithValue
    public function testGetNodeIdsByXpathByNameAllWithAttributeWithValue()
    {
        $this->assertCorrectXpathIds(array('8'), '//pg:page[@name = \'bla blub\']');
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameWithChild
    public function testGetNodeIdsByXpathByNameWithChild()
    {
        $this->assertCorrectXpathIds(array('2'), '/dpg:pages/pg:page');
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameWithChildAndPosition
    public function testGetNodeIdsByXpathByNameWithChildAndPosition()
    {
        $this->assertCorrectXpathIds(array('8'), '/dpg:pages/pg:page/pg:page[3]');
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameAndAttribute
    public function testGetNodeIdsByXpathByNameAndAttribute()
    {
        $this->assertCorrectXpathIds(array('6', '7', '8'), '/dpg:pages/pg:page/pg:page[@name]');
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameAndAttributeWithValue
    public function testGetNodeIdsByXpathByNameAndAttributeWithValue()
    {
        $this->assertCorrectXpathIds(array('6'), '/dpg:pages/pg:page/pg:page[@name = \'Subpage\']');
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByWildcardAndAttributeWithValue
    public function testGetNodeIdsByXpathByWildcardAndAttributeWithValue()
    {
        $this->assertCorrectXpathIds(array('6', '9'), '/dpg:pages/pg:page/*[@name = \'Subpage\']');
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByWildcardNsAndAttributeWithValue
    public function testGetNodeIdsByXpathByWildcardNsAndAttributeWithValue()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath => 2.0
        $this->assertCorrectXpathIdsNoDomXpath(array('6'), '/dpg:pages/pg:page/*:page[@name = \'Subpage\']');
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByWildcardNameAndAttributeWithValue
    public function testGetNodeIdsByXpathByWildcardNameAndAttributeWithValue()
    {
        $this->assertCorrectXpathIds(array('6', '9'), '/dpg:pages/pg:page/pg:*[@name = \'Subpage\']');
    }
    // }}}
    // {{{ testGetNodeIdsByXpathNoResult
    public function testGetNodeIdsByXpathNoResult()
    {
        $this->assertCorrectXpathIds(array(), '/nonode');
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameAllAndPosition
    public function testGetNodeIdsByXpathByNameAllAndPosition()
    {
        $this->assertCorrectXpathIds(array('8'), '//pg:page[3]');
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameAllWithAttributeNoResult
    public function testGetNodeIdsByXpathByNameAllWithAttributeNoResult()
    {
        $this->assertCorrectXpathIds(array(), '//pg:page[@unknown]');
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameAllWithAttributeWithValueNoResult
    public function testGetNodeIdsByXpathByNameAllWithAttributeWithValueNoResult()
    {
        $this->assertCorrectXpathIds(array(), '//pg:page[@name = \'unknown\']');
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByWildCardAndIdAttributeWithValue
    public function testGetNodeIdsByXpathByWildCardAndIdAttributeWithValue()
    {
        // can't be verified by DOMXpath. Namespace issue (@id, @dḃ:id)
        $this->assertCorrectXpathIdsNoDomXpath(array('6'), '/*[@id = \'6\']');
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByWildCardNsAndIdAttributeWithValue
    public function testGetNodeIdsByXpathByWildCardNsAndIdAttributeWithValue()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath => 2.0
        $this->assertCorrectXpathIdsNoDomXpath(array('6'), '/*:page[@id = \'6\']');
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByWildCardNameAndIdAttributeWithValue
    public function testGetNodeIdsByXpathByWildCardNameAndIdAttributeWithValue()
    {
        // can't be verified by DOMXpath. Namespace issue (@id, @dḃ:id)
        $this->assertCorrectXpathIdsNoDomXpath(array('6'), '/pg:*[@id = \'6\']');
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByWildCardAndIdAttributeWithValueNoResult
    public function testGetNodeIdsByXpathByWildCardAndIdAttributeWithValueNoResult()
    {
        // can't be verified by DOMXpath. Namespace issue (@id, @dḃ:id)
        $this->assertCorrectXpathIdsNoDomXpath(array(), '/*[@id = \'20\']');
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
