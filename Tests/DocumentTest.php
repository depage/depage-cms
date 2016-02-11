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

        $this->cache = \Depage\Cache\Cache::factory('xmlDb', array('disposition' => 'uncached'));

        $this->xmlDb = new \Depage\XmlDb\XmlDb($this->pdo->prefix . '_proj_test', $this->pdo, $this->cache, array(
            'root',
            'child',
        ));

        $this->doc = new DocumentTestClass($this->xmlDb, 6);
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

    // {{{ testGetHistory
    public function testGetHistory()
    {
        $this->assertInstanceOf('\\Depage\\XmlDb\\DocumentHistory', ($this->doc->getHistory()));
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
        $this->pdo->exec('UPDATE xmldb_proj_test_xmldocs SET type=\'\' WHERE id=\'6\'');

        $this->assertEquals('', $this->doc->getDocInfo()->type);
        $this->assertInstanceOf('Depage\XmlDb\XmlDocTypes\Base', $this->doc->getDoctypeHandler());
    }
    // }}}

    // {{{ testGetSubdocByNodeId
    public function testGetSubdocByNodeId()
    {
        $expected = '<pg:page xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="P6.1" db:id="29" db:lastchange="2016-02-03 16:09:05" db:lastchangeUid="">bla bla blub <pg:page name="P6.1.2" db:id="30"/></pg:page>';

        $this->assertXmlStringEqualsXmlString($expected, $this->doc->getSubdocByNodeId(29));
    }
    // }}}
    // {{{ testGetSubdocByNodeIdNodeDoesntExist
    /**
     * @expectedException Depage\XmlDb\Exceptions\XmlDbException
     * @expectedExceptionMessage This node is no ELEMENT_NODE or node does not exist
     */
    public function testGetSubdocByNodeIdNodeDoesntExist()
    {
        $this->doc->getSubdocByNodeId(1000);
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

        $expected = '<page/>';

        $this->assertXmlStringEqualsXmlString($expected, $doc->getSubdocByNodeId(2));
    }
    // }}}
    // {{{ testGetSubdocByNodeIdWrongNodeType
    /**
     * @expectedException Depage\XmlDb\Exceptions\XmlDbException
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

    // {{{ testGetNodeNameById
    public function testGetNodeNameById()
    {
        $this->assertEquals('dpg:pages', $this->doc->getNodeNameById(27));
        $this->assertEquals('pg:page', $this->doc->getNodeNameById(28));
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
        $xmlStr = '<root xmlns:db="http://cms.depagecms.net/ns/database"><child></child><child/><child/></root>';

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
        $xmlStr = '<root xmlns:db="http://cms.depagecms.net/ns/database">' . $nodes . '</root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveElementNodesWithAttribute
    public function testSaveElementNodesWithAttribute()
    {
        $xmlStr = '<root xmlns:db="http://cms.depagecms.net/ns/database"><child attr="test"></child></root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveElementNodesWithNamespaces
    public function testSaveElementNodesWithNamespaces()
    {
        $xmlStr = '<root xmlns:db="http://cms.depagecms.net/ns/database"><db:child attr="test"></db:child><child db:data="blub" /></root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveTextNodes
    public function testSaveTextNodes()
    {
        $xmlStr = '<root xmlns:db="http://cms.depagecms.net/ns/database"><child>bla</child>blub<b/><c/><child>bla</child></root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSavePiNode
    public function testSavePiNode()
    {
        $xmlStr = '<root xmlns:db="http://cms.depagecms.net/ns/database"><?php echo("bla"); ?></root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveCommentNode
    public function testSaveCommentNode()
    {
        $xmlStr = '<root xmlns:db="http://cms.depagecms.net/ns/database"><!-- comment --></root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}

    // {{{ testUnlinkNode
    public function testUnlinkNode()
    {
        $this->assertEquals(29, $this->doc->unlinkNode(30));

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub </pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';

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

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/></pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}

    // {{{ testAddNode
    public function testAddNode()
    {
        $doc = $this->generateDomDocument('<root><node/></root>');

        $this->doc->addNode($doc, 29);

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/><root><node/></root></pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testAddNodeDenied
    public function testAddNodeDenied()
    {
        // set up doc type handler
        $this->pdo->exec('UPDATE xmldb_proj_test_xmldocs SET type=\'Depage\\\\XmlDb\\\\Tests\\\\MockDoctypeHandler\' WHERE id=\'6\'');
        $this->doc->getDoctypeHandler()->isAllowedAdd = false;

        $doc = $this->generateDomDocument('<root><node/></root>');

        $this->assertFalse($this->doc->addNode($doc, 29));

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/></pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testAddNodeByName
    public function testAddNodeByName()
    {
        // set up doc type handler
        $this->pdo->exec('UPDATE xmldb_proj_test_xmldocs SET type=\'Depage\\\\XmlDb\\\\Tests\\\\MockDoctypeHandler\' WHERE id=\'6\'');

        $this->doc->addNodeByName('test', 30, 0);

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"><root><node>test</node></root></pg:page></pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testAddNodeByNameFail
    public function testAddNodeByNameFail()
    {
        $this->assertFalse($this->doc->addNodeByName('test', 30, 0));

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/></pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}

    // {{{ testSaveNode
    public function testSaveNode()
    {
        $doc = $this->generateDomDocument('<root db:id="28" xmlns:db="http://cms.depagecms.net/ns/database"><node/></root>');

        $this->doc->saveNode($doc);

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><root><node/></root></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
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

        $this->doc->replaceNode($doc, 28);

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="" db:id="27"><root db:id="28"><node db:id="29"/></root></dpg:pages>';

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

    // {{{ testSaveNodeToDb
    public function testSaveNodeToDb()
    {
        $doc = new \DomDocument();
        $nodeElement = $doc->createElement('test');

        $this->assertEquals(41, $this->doc->saveNodeToDb($nodeElement, 41, 28, 0));

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/></pg:page><test/><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveNodeToDbEntityRef
    public function testSaveNodeToDbEntityRef()
    {
        $doc = new \DomDocument();
        $nodeElement = $doc->createEntityReference('test');

        $this->assertEquals(41, $this->doc->saveNodeToDb($nodeElement, 41, 28, 0));

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/></pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveNodeToDbIdNull
    public function testSaveNodeToDbIdNull()
    {
        $doc = new \DomDocument();
        $nodeElement = $doc->createElement('test');

        $this->assertEquals(41, $this->doc->saveNodeToDb($nodeElement, null, 28, 0));

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/></pg:page><test/><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveNodeToDbIdNullText
    public function testSaveNodeToDbIdNullText()
    {
        $doc = new \DomDocument();
        $nodeElement = $doc->createTextNode('test');

        $this->assertEquals(41, $this->doc->saveNodeToDb($nodeElement, null, 28, 0));

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/></pg:page>test<pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}

    // {{{ testUpdateLastchange
    public function testUpdateLastchange()
    {
        $xmlDb = new \Depage\XmlDb\XmlDb($this->pdo->prefix . '_proj_test', $this->pdo, $this->cache, array('userId' => 42));
        $doc = new DocumentTestClass($xmlDb, 6);

        $before = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="" db:lastchange="2016-02-03 16:09:05" db:lastchangeUid=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/></pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlString($before, $doc->getXml(false));

        $this->setForeignKeyChecks(false);
        $timestamp = $doc->updateLastChange();
        $this->setForeignKeyChecks(true);

        $date = date('Y-m-d H:i:s', $timestamp);
        $after = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="" db:lastchange="' . $date . '" db:lastchangeUid="42"><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/></pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlString($after, $doc->getXml(false));
    }
    // }}}

    // {{{ testMoveNodeIn
    public function testMoveNodeIn()
    {
        $this->doc->moveNodeIn(30, 29);

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/></pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastChange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testMoveNodeBefore
    public function testMoveNodeBefore()
    {
        $this->doc->moveNodeBefore(30, 29);

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1.2"/><pg:page name="P6.1">bla bla blub </pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testMoveNodeAfter
    public function testMoveNodeAfter()
    {
        $this->doc->moveNodeAfter(30, 29);

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub </pg:page><pg:page name="P6.1.2"/><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testMoveNodeAfterSameLevel
    public function testMoveNodeAfterSameLevel()
    {
        $this->doc->moveNodeAfter(31, 30);

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/><pg:page name="P6.2"/></pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}

    // {{{ testCopyNode
    public function testCopyNode()
    {
        $this->assertEquals(41, $this->doc->copyNode(28, 29, 0));

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1"><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/></pg:page><pg:page name="P6.2"/></pg:page>bla bla blub <pg:page name="P6.1.2"/></pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testCopyNodeDenied
    public function testCopyNodeDenied()
    {
        // set up doc type handler
        $this->pdo->exec('UPDATE xmldb_proj_test_xmldocs SET type=\'Depage\\\\XmlDb\\\\Tests\\\\MockDoctypeHandler\' WHERE id=\'6\'');
        $this->doc->getDoctypeHandler()->isAllowedMove = false;

        $this->assertFalse($this->doc->copyNode(28, 29, 0));

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/></pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testCopyNodeIn
    public function testCopyNodeIn()
    {
        $this->assertEquals(41, $this->doc->copyNodeIn(31, 29));

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="">
            <pg:page name="Home6">
                <pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/><pg:page name="P6.2"/></pg:page>
                <pg:page name="P6.2"/>
            </pg:page>
        </dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testCopyNodeBefore
    public function testCopyNodeBefore()
    {
        $this->assertEquals(41, $this->doc->copyNodeBefore(31, 29));

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="" db:id="27"><pg:page name="Home6" db:id="28"><pg:page name="P6.2" db:id="41"/><pg:page name="P6.1" db:id="29">bla bla blub <pg:page name="P6.1.2" db:id="30"/></pg:page><pg:page name="P6.2" db:id="31"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}
    // {{{ testCopyNodeAfter
    public function testCopyNodeAfter()
    {
        $this->assertEquals(41, $this->doc->copyNodeAfter(31, 29));

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name="" db:id="27"><pg:page name="Home6" db:id="28"><pg:page name="P6.1" db:id="29">bla bla blub <pg:page name="P6.1.2" db:id="30"/></pg:page><pg:page name="P6.2" db:id="41"/><pg:page name="P6.2" db:id="31"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}

    // {{{ testDuplicateNode
    public function testDuplicateNode()
    {
        $this->assertEquals(41, $this->doc->duplicateNode(29));

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/></pg:page><pg:page name="P6.1"/><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testDuplicateNodeDenied
    public function testDuplicateNodeDenied()
    {
        // set up doc type handler
        $this->pdo->exec('UPDATE xmldb_proj_test_xmldocs SET type=\'Depage\\\\XmlDb\\\\Tests\\\\MockDoctypeHandler\' WHERE id=\'6\'');
        $this->doc->getDoctypeHandler()->isAllowedMove = false;

        $this->assertFalse($this->doc->duplicateNode(29));

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page name="P6.1.2"/></pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}

    // {{{ testBuildNode
    public function testBuildNode()
    {
        $node = $this->doc->buildNode('newNode', array('att' => 'val', 'att2' => 'val2'));

        $expected = '<newNode xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" att="val" att2="val2"/>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $node->ownerDocument->saveXML($node));
    }
    // }}}

    // {{{ testSetAttribute
    public function testSetAttribute()
    {
        $this->doc->setAttribute(29, 'textattr', 'new value');
        $this->doc->setAttribute(30, 'name', 'newName');

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1" textattr="new value">bla bla blub <pg:page name="newName"/></pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testRemoveAttribute
    public function testRemoveAttribute()
    {
        $this->assertTrue($this->doc->removeAttribute(30, 'name'));

        $expected = '<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" name=""><pg:page name="Home6"><pg:page name="P6.1">bla bla blub <pg:page/></pg:page><pg:page name="P6.2"/></pg:page></dpg:pages>';;

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testRemoveAttributeNonExistent
    public function testRemoveAttributeNonExistent()
    {
        $expected = $this->doc->getXml();

        $this->assertFalse($this->doc->removeAttribute(30, 'idontexist'));

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}
    // {{{ testGetAttribute
    public function testGetAttribute()
    {
        $attr = $this->doc->getAttribute(28, 'name');
        $this->assertEquals('Home6', $attr);

        $attr = $this->doc->getAttribute(28, 'undefindattr');
        $this->assertFalse($attr);
    }
    // }}}
    // {{{ testGetAttributes
    public function testGetAttributes()
    {
        $attrs = $this->doc->getAttributes(28);

        $expected = array(
            'name' => 'Home6',
        );

        $this->assertEquals($expected, $attrs);
    }
    // }}}

    // {{{ testGetParentIdById
    public function testGetParentIdById()
    {
        $this->assertNull($this->doc->getParentIdById(27));
        $this->assertEquals(29, $this->doc->getParentIdById(30));
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

    // {{{ testGetNodeArrayForSaving
    public function testGetNodeArrayForSaving()
    {
        $nodeArray = array();
        $node = $this->generateDomDocument('<root db:id="2" xmlns:db="http://cms.depagecms.net/ns/database"></root>');

        $this->doc->getNodeArrayForSaving($nodeArray, $node);

        $this->assertEquals(1, count($nodeArray));
        $this->assertEquals(2, $nodeArray[0]['id']);
    }
    // }}}
    // {{{ testGetNodeArrayForSavingStripWhitespace
    public function testGetNodeArrayForSavingStripWhitespace()
    {
        $nodeArray = array();
        $node = $this->generateDomDocument('<root db:id="2" xmlns:db="http://cms.depagecms.net/ns/database"></root>');

        $this->doc->getNodeArrayForSaving($nodeArray, $node, null, 0, false);

        $this->assertEquals(1, count($nodeArray));
        $this->assertEquals(2, $nodeArray[0]['id']);
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
