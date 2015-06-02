<?php

namespace Depage\XmlDb\Tests;

class DocumentTest extends DatabaseTestCase
{
    // {{{ variables
    protected $xmlDb;
    protected $doc;
    // }}}
    // {{{ setUp
    protected function setUp()
    {
        parent::setUp();

        // get cache instance
        $cache = \Depage\Cache\Cache::factory('xmlDb', array('disposition' => 'uncached'));

        // get xmldb instance
        $this->xmlDb = new \Depage\XmlDb\XmlDb($this->pdo->prefix . '_proj_test', $this->pdo, $cache, array(
            'root',
            'child',
        ));

        $this->doc = new DocumentTestClass($this->xmlDb, 1);
    }
    // }}}
    // {{{ generateDomDocument
    protected function generateDomDocument($xml)
    {
        $doc = new \DomDocument();
        $doc->loadXml($xml);

        return $doc;
    }
    // }}}

    // {{{ testGetDoctypeHandler
    public function testGetDoctypeHandler()
    {
        $baseType = 'Depage\XmlDb\XmlDocTypes\Base';

        $this->assertEquals($baseType, $this->doc->getDocInfo()->type);
        $this->assertInstanceOf($baseType, $this->doc->getDoctypeHandler());
    }
    // }}}
    // {{{ testGetDoctypeHandlerNoType
    public function testGetDoctypeHandlerNoType()
    {
        // delete document type
        $this->pdo->exec('UPDATE xmldb_proj_test_xmldocs SET type=\'\' WHERE id=\'1\'');

        $this->assertEquals('', $this->doc->getDocInfo()->type);
        $this->assertInstanceOf('Depage\XmlDb\XmlDocTypes\Base', $this->doc->getDoctypeHandler());
    }
    // }}}

    // {{{ testGetSubdocByNodeId
    public function testGetSubdocByNodeId()
    {
        $expected = '<?xml version="1.0"?><pg:page xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2" db:lastchange="0000-00-00 00:00:00" db:lastchangeUid=""><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page>';

        $this->assertXmlStringEqualsXmlString($expected, $this->doc->getSubdocByNodeId(2));
    }
    // }}}
    // {{{ testGetSubdocByNodeIdCached
    public function testGetSubdocByNodeIdCached()
    {
        $cache = new MockCache();
        $cache->set(
            'xmldb_proj_test_xmldocs_d1/2.xml',
            '<page/>'
        );

        $xmlDb = new \Depage\XmlDb\XmlDb($this->pdo->prefix . '_proj_test', $this->pdo, $cache, array(
            'root',
            'child',
        ));
        $doc = $xmlDb->getDoc(1);

        $expected = '<?xml version="1.0"?><page/>';

        $this->assertXmlStringEqualsXmlString($expected, $doc->getSubdocByNodeId(2));
    }
    // }}}
    // {{{ testGetSubdocByNodeIdWrongNodeType
    /**
     * @expectedException Depage\XmlDb\XmlDbException
     * @expectedExceptionMessage This node is no ELEMENT_NODE or node does not exist
     */
    public function testGetSubdocByNodeIdWrongNodeType()
    {
        // set up document type
        $this->pdo->exec('UPDATE xmldb_proj_test_xmltree SET type=\'WRONG_NODE\' WHERE id=\'1\'');

        $this->doc->getSubdocByNodeId(1);
    }
    // }}}
    // {{{ testGetSubdocByNodeIdChangedDoc
    public function testGetSubdocByNodeIdChangedDoc()
    {
        // set up mock cache
        $cache = new MockCache();

        $xmlDb = new \Depage\XmlDb\XmlDb($this->pdo->prefix . '_proj_test', $this->pdo, $cache, array(
            'root',
            'child',
        ));
        $doc = $xmlDb->getDoc(1);

        // set up doc type handler, trigger save node
        $this->pdo->exec('UPDATE xmldb_proj_test_xmldocs SET type=\'Depage\\\\XmlDb\\\\Tests\\\\MockDoctypeHandler\' WHERE id=\'1\'');
        $doc->getSubdocByNodeId(1);

        // saveNode triggers clearCache, check for cleared cache
        $this->assertTrue($cache->deleted);
    }
    // }}}

    // {{{ testGetSubDocByXpathByNameAll
    public function testGetSubDocByXpathByNameAll()
    {
        $subDoc = $this->doc->getSubDocByXpath('//pg:page');

        $expected = '<pg:page xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" file_type="html" multilang="true" name="Home" db:dataid="3" db:id="2" db:lastchange="0000-00-00 00:00:00" db:lastchangeUid=""><pg:page file_type="html" multilang="true" name="Subpage" db:dataid="4" db:id="6"/><pg:page file_type="html" multilang="true" name="Subpage 2" db:dataid="5" db:id="7"/><pg:folder file_type="html" multilang="true" name="Subpage" db:dataid="7" db:id="9"/>bla bla blub <pg:page file_type="html" multilang="true" name="bla blub" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page>';

        $this->assertXmlStringEqualsXmlString($expected, $subDoc);
    }
    // }}}
    // {{{ testGetSubDocByXpathNone
    public function testGetSubDocByXpathNone()
    {
        $this->assertFalse($this->doc->getSubDocByXpath('//iamnosubdoc'));
    }
    // }}}

    // {{{ testGetNodeIdsByXpathByNameAll
    public function testGetNodeIdsByXpathByNameAll()
    {
        $ids = $this->doc->getNodeIdsByXpath('//pg:page');

        $this->assertEquals(array('2', '6', '7', '8'), $ids);
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameAllWithAttribute
    public function testGetNodeIdsByXpathByNameAllWithAttribute()
    {
        $ids = $this->doc->getNodeIdsByXpath('//pg:page[@name = \'bla blub\']');

        $this->assertEquals(array('8'), $ids);
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameAllWithChild
    public function testGetNodeIdsByXpathByNameAllWithChild()
    {
        $ids = $this->doc->getNodeIdsByXpath('/dpg:pages/pg:page');

        $this->assertEquals(array('2'), $ids);
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameAndPosition
    public function testGetNodeIdsByXpathByNameAndPosition()
    {
        $ids = $this->doc->getNodeIdsByXpath('/dpg:pages/pg:page/pg:page[3]');

        $this->assertEquals(array('8'), $ids);
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameAndAttribute
    public function testGetNodeIdsByXpathByNameAndAttribute()
    {
        $ids = $this->doc->getNodeIdsByXpath('/dpg:pages/pg:page/pg:page[@name]');

        $this->assertEquals(array('6', '7', '8'), $ids);
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameAndAttributeWithValue
    public function testGetNodeIdsByXpathByNameAndAttributeWithValue()
    {
        $ids = $this->doc->getNodeIdsByXpath('/dpg:pages/pg:page/pg:page[@name = \'Subpage\']');

        $this->assertEquals(array('6'), $ids);
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByWildcardAndAttributeWithValue
    public function testGetNodeIdsByXpathByWildcardAndAttributeWithValue()
    {
        $ids = $this->doc->getNodeIdsByXpath('/dpg:pages/pg:page/*[@name = \'Subpage\']');

        $this->assertEquals(array('6', '9'), $ids);
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByWildcardNsAndAttributeWithValue
    public function testGetNodeIdsByXpathByWildcardNsAndAttributeWithValue()
    {
        $ids = $this->doc->getNodeIdsByXpath('/dpg:pages/pg:page/*:page[@name = \'Subpage\']');

        $this->assertEquals(array('6'), $ids);
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByWildcardNameAndAttributeWithValue
    public function testGetNodeIdsByXpathByWildcardNameAndAttributeWithValue()
    {
        $ids = $this->doc->getNodeIdsByXpath('/dpg:pages/pg:page/pg:*[@name = \'Subpage\']');

        $this->assertEquals(array('6', '9'), $ids);
    }
    // }}}
    // {{{ testGetNodeIdsByXpathNoResult
    public function testGetNodeIdsByXpathNoResult()
    {
        $ids = $this->doc->getNodeIdsByXpath('/nonode');

        $this->assertEquals(array(), $ids);
    }
    // }}}

    // {{{ testGetNodeNameById
    public function testGetNodeNameById()
    {
        $this->assertEquals('dpg:pages', $this->doc->getNodeNameById(1));
        $this->assertEquals('pg:page', $this->doc->getNodeNameById(2));
    }
    // }}}
    // {{{ testGetNodeNameByIdNonExistent
    public function testGetNodeNameByIdNonExistent()
    {
        $this->assertFalse($this->doc->getNodeNameById(5));
        $this->assertFalse($this->doc->getNodeNameById('noId'));
        $this->assertFalse($this->doc->getNodeNameById(null));
    }
    // }}}

    // {{{ testSaveElementNodes
    public function testSaveElementNodes()
    {
        $xmlStr = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database"><child></child><child/><child/></root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveElementNodesMany
    public function testSaveElementNodesMany()
    {
        $nodes = '';
        for ($i = 0; $i < 10; $i++) {
            $nodes .= '<child></child><child/><child></child><child></child>text<child/><child/>text<child/><child/><child/>';
        }
        $xmlStr = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database">' . $nodes . '</root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveElementNodesWithAttribute
    public function testSaveElementNodesWithAttribute()
    {
        $xmlStr = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database"><child attr="test"></child></root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveElementNodesWithNamespaces
    public function testSaveElementNodesWithNamespaces()
    {
        $xmlStr = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database"><db:child attr="test"></db:child><child db:data="blub" /></root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveTextNodes
    public function testSaveTextNodes()
    {
        $xmlStr = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database"><child>bla</child>blub<b/><c/><child>bla</child></root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSavePiNode
    public function testSavePiNode()
    {
        $xmlStr = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database"><?php echo("bla"); ?></root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveCommentNode
    public function testSaveCommentNode()
    {
        $xmlStr = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database"><!-- comment --></root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}

    // {{{ testUnlinkNode
    public function testUnlinkNode()
    {
        $deleted = $this->doc->unlinkNode(9);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" ><pg:page name="Home" multilang="true" file_type="html" db:dataid="3"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6">bla bla bla </pg:page></pg:page></dpg:pages>';
        $this->assertEquals(2, $deleted);
        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testUnlinkNodeDenied
    public function testUnlinkNodeDenied()
    {
        // set up doc type handler
        $this->pdo->exec('UPDATE xmldb_proj_test_xmldocs SET type=\'Depage\\\\XmlDb\\\\Tests\\\\MockDoctypeHandler\' WHERE id=\'1\'');
        $this->doc->getDoctypeHandler()->isAllowedUnlink = false;

        $this->assertFalse($this->doc->unlinkNode(9));

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" ><pg:page file_type="html" multilang="true" name="Home" db:dataid="3"><pg:page file_type="html" multilang="true" name="Subpage" db:dataid="4"/><pg:page file_type="html" multilang="true" name="Subpage 2" db:dataid="5"/><pg:folder file_type="html" multilang="true" name="Subpage" db:dataid="7"/>bla bla blub <pg:page file_type="html" multilang="true" name="bla blub" db:dataid="6">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}

    // {{{ testAddNode
    public function testAddNode()
    {
        $doc = $this->generateDomDocument('<root><node/></root>');

        $this->doc->addNode($doc, 2, 1);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name=""><pg:page name="Home" multilang="true" file_type="html" db:dataid="3"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4"/><root><node/></root><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testAddNodeDenied
    public function testAddNodeDenied()
    {
        // set up doc type handler
        $this->pdo->exec('UPDATE xmldb_proj_test_xmldocs SET type=\'Depage\\\\XmlDb\\\\Tests\\\\MockDoctypeHandler\' WHERE id=\'1\'');
        $this->doc->getDoctypeHandler()->isAllowedAdd = false;

        $doc = $this->generateDomDocument('<root><node/></root>');

        $this->assertFalse($this->doc->addNode($doc, 2, 1));

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" ><pg:page file_type="html" multilang="true" name="Home" db:dataid="3"><pg:page file_type="html" multilang="true" name="Subpage" db:dataid="4"/><pg:page file_type="html" multilang="true" name="Subpage 2" db:dataid="5"/><pg:folder file_type="html" multilang="true" name="Subpage" db:dataid="7"/>bla bla blub <pg:page file_type="html" multilang="true" name="bla blub" db:dataid="6">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}

    // {{{ testAddNodeByName
    public function testAddNodeByName()
    {
        // set up doc type handler
        $this->pdo->exec('UPDATE xmldb_proj_test_xmldocs SET type=\'Depage\\\\XmlDb\\\\Tests\\\\MockDoctypeHandler\' WHERE id=\'1\'');

        $this->doc->addNodeByName('test', 2, 1);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name=""><pg:page name="Home" multilang="true" file_type="html" db:dataid="3"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4"/><root><node>test</node></root><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testAddNodeByNameFail
    public function testAddNodeByNameFail()
    {
        $this->assertFalse($this->doc->addNodeByName('test', 2, 1));

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name=""><pg:page name="Home" multilang="true" file_type="html" db:dataid="3"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}

    // {{{ testSaveNode
    public function testSaveNode()
    {
        $doc = $this->generateDomDocument('<root db:id="2" xmlns:db="http://cms.depagecms.net/ns/database"><node/></root>');

        $this->doc->saveNode($doc);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><root db:id="2"><node db:id="6"/></root></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}

    // {{{ testGetPermissionѕ
    public function testGetPermissionѕ()
    {
        $expected = array(
            'validParents' => array(
                '*' => array('*')
            ),
            'availableNodes' => array()
        );

        $this->assertEquals($expected, (array) $this->doc->getPermissions());
    }
    // }}}

    // {{{ testReplaceNode
    public function testReplaceNode()
    {
        $doc = $this->generateDomDocument('<root><node/></root>');

        $this->doc->replaceNode($doc, 2);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><root db:id="2"><node db:id="6"/></root></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}

    // {{{ testGetPosById
    public function testGetPosById()
    {
        $this->assertEquals(0, $this->doc->getPosById(2));
    }
    // }}}
    // {{{ testGetPosByIdFail
    public function testGetPosByIdFail()
    {
        // there's no node with id 999
        $this->assertNull($this->doc->getPosById(999));
    }
    // }}}

    // {{{ testMoveNodeIn
    public function testMoveNodeIn()
    {
        $this->doc->moveNodeIn(7, 8);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla <pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/></pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastChange($expected, $this->doc->getXml());
    }
    // }}}
    // {{{ testMoveNodeBefore
    public function testMoveNodeBefore()
    {
        $this->doc->moveNodeBefore(7, 2);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}
    // {{{ testMoveNodeAfter
    public function testMoveNodeAfter()
    {
        $this->doc->moveNodeAfter(7, 2);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}
    // {{{ testMoveNodeAfterSameLevel
    public function testMoveNodeAfterSameLevel()
    {
        $this->doc->moveNodeAfter(6, 7);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}

    // {{{ testCopyNode
    public function testCopyNode()
    {
        $this->assertEquals(12, $this->doc->copyNode(7, 8, 1));

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name=""><pg:page name="Home" multilang="true" file_type="html" db:dataid="3"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6">bla bla bla <pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5"/></pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testCopyNodeDenied
    public function testCopyNodeDenied()
    {
        // set up doc type handler
        $this->pdo->exec('UPDATE xmldb_proj_test_xmldocs SET type=\'Depage\\\\XmlDb\\\\Tests\\\\MockDoctypeHandler\' WHERE id=\'1\'');
        $this->doc->getDoctypeHandler()->isAllowedMove = false;

        $this->assertFalse($this->doc->copyNode(7, 8, 1));

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name=""><pg:page name="Home" multilang="true" file_type="html" db:dataid="3"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testCopyNodeIn
    public function testCopyNodeIn()
    {
        $this->doc->copyNodeIn(7, 8);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name=""><pg:page name="Home" multilang="true" file_type="html" db:dataid="3"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6">bla bla bla <pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5"/></pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testCopyNodeBefore
    public function testCopyNodeBefore()
    {
        $this->doc->copyNodeBefore(7, 2);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="12"/><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}
    // {{{ testCopyNodeAfter
    public function testCopyNodeAfter()
    {
        $this->doc->copyNodeAfter(7, 2);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="12"/></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}

    // {{{ testDuplicateNode
    public function testDuplicateNode()
    {
        $this->assertEquals(12, $this->doc->duplicateNode(7));

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1" db:lastchange="2015-05-22 18:35:46" db:lastchangeUid=""><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="12"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}
    // {{{ testDuplicateNodeDenied
    public function testDuplicateNodeDenied()
    {
        // set up doc type handler
        $this->pdo->exec('UPDATE xmldb_proj_test_xmldocs SET type=\'Depage\\\\XmlDb\\\\Tests\\\\MockDoctypeHandler\' WHERE id=\'1\'');
        $this->doc->getDoctypeHandler()->isAllowedMove = false;

        $this->assertFalse($this->doc->duplicateNode(7));

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name=""><pg:page name="Home" multilang="true" file_type="html" db:dataid="3"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}

    // {{{ testBuildNode
    public function testBuildNode()
    {
        $node = $this->doc->buildNode('newNode', array('att' => 'val', 'att2' => 'val2'));

        $expected = '<newNode xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" att="val" att2="val2"/>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $node->ownerDocument->saveXML($node));
    }
    // }}}

    // {{{ testSetAttribute
    public function testSetAttribute()
    {
        $this->doc->setAttribute(2, 'textattr', 'new value');
        $this->doc->setAttribute(6, 'multilang', 'false');

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange('<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" textattr="new value" db:id="2"><pg:page name="Subpage" multilang="false" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>', $this->doc->getXml());
    }
    // }}}
    // {{{ testRemoveAttribute
    public function testRemoveAttribute()
    {
        $return = $this->doc->removeAttribute(2, 'multilang');

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1" db:lastchange="0000-00-00 00:00:00" db:lastchangeUid=""><pg:page name="Home" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertTrue($return);
        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}
    // {{{ testRemoveAttributeNonExistent
    public function testRemoveAttributeNonExistent()
    {
        $expected = $this->doc->getXml();

        $return = $this->doc->removeAttribute(2, 'idontexist');

        $this->assertFalse($return);
        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}
    // {{{ testGetAttribute
    public function testGetAttribute()
    {
        $attr = $this->doc->getAttribute(2, 'name');
        $this->assertEquals('Home', $attr);

        $attr = $this->doc->getAttribute(2, 'undefindattr');
        $this->assertFalse($attr);
    }
    // }}}
    // {{{ testGetAttributes
    public function testGetAttributes()
    {
        $attrs = $this->doc->getAttributes(2);

        $expected = array(
            'name' => 'Home',
            'multilang' => 'true',
            'file_type' => 'html',
            'db:dataid' => '3',
        );

        $this->assertEquals($expected, $attrs);
    }
    // }}}

    // {{{ testGetParentIdById
    public function testGetParentIdById()
    {
        $this->assertNull($this->doc->getParentIdById(1));
        $this->assertEquals(2, $this->doc->getParentIdById(6));
    }
    // }}}
    // {{{ testGetParentIdByIdNonExistent
    public function testGetParentIdByIdNonExistent()
    {
        $this->assertFalse($this->doc->getParentIdById(3));
        $this->assertFalse($this->doc->getParentIdById(1000));
        $this->assertFalse($this->doc->getParentIdById('noId'));
        $this->assertFalse($this->doc->getParentIdById(null));
    }
    // }}}

    // {{{ testGetNodeId
    public function testGetNodeId()
    {
        $doc = $this->generateDomDocument('<root db:id="2" xmlns:db="http://cms.depagecms.net/ns/database"><node/></root>');

        $id = $this->doc->getNodeId($doc->documentElement);

        $this->assertEquals(2, $id);
    }
    // }}}
    // {{{ testGetNodeDataId
    public function testGetNodeDataId()
    {
        $doc = $this->generateDomDocument('<root db:dataid="2" xmlns:db="http://cms.depagecms.net/ns/database"><node/></root>');

        $id = $this->doc->getNodeDataId($doc->documentElement);

        $this->assertEquals(2, $id);
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
