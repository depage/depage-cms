<?php

namespace Depage\XmlDb\Tests;

class DocumentTest extends XmlDbTestCase
{
    // {{{ variables
    protected $xmlDb;
    protected $doc;
    // }}}
    // {{{ setUp
    protected function setUp():void
    {
        parent::setUp();

        $this->cache = \Depage\Cache\Cache::factory('xmlDb', ['disposition' => 'uncached']);

        $this->dbPrefix = $this->pdo->prefix . '_proj_test';
        $this->xmlTree = $this->dbPrefix . '_xmltree';

        $this->xmlDb = new \Depage\XmlDb\XmlDb($this->dbPrefix, $this->pdo, $this->cache, [
            'root',
            'child',
        ]);

        $this->doc = new DocumentTestClass($this->xmlDb, 3);
        $this->namespaces = 'xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page"';
    }
    // }}}
    // {{{ getNodeRowById
    public function getNodeRowById($id)
    {
        $statement = $this->pdo->prepare('SELECT * FROM ' . $this->xmlTree . ' WHERE id=?;');
        $params = [$id];
        $statement->execute($params);

        return $statement->fetch(\PDO::FETCH_ASSOC);
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
        $baseType = 'Depage\XmlDb\XmlDoctypes\Base';

        $this->assertEquals($baseType, $this->doc->getDocInfo()->type);
        $this->assertInstanceOf($baseType, $this->doc->getDoctypeHandler());
    }
    // }}}
    // {{{ testGetDoctypeHandlerFail
    public function testGetDoctypeHandlerFail()
    {
        $this->expectException(\Depage\XmlDb\Exceptions\XmlDbException::class);
        $this->expectExceptionMessage("Doctype handler must implement DoctypeInterface");

        // set doctype handler to class that doesn't implement doctype handler interface
        $this->pdo->exec('UPDATE xmldb_proj_test_xmldocs SET type=\'Depage\\\\XmlDb\\\\Document\' WHERE id=\'3\'');

        $this->doc->getDoctypeHandler();
    }
    // }}}
    // {{{ testGetDoctypeHandlerNoType
    public function testGetDoctypeHandlerNoType()
    {
        // delete document type
        $this->pdo->exec('UPDATE xmldb_proj_test_xmldocs SET type=\'\' WHERE id=\'3\'');

        $this->assertEquals('', $this->doc->getDocInfo()->type);
        $this->assertInstanceOf('Depage\XmlDb\XmlDoctypes\Base', $this->doc->getDoctypeHandler());
    }
    // }}}

    // {{{ testGetSubdocByNodeId
    public function testGetSubdocByNodeId()
    {
        $expected = '<pg:page ' . $this->namespaces . ' name="P3.1" db:id="6" db:lastchange="2016-02-03 16:09:05" db:lastchangeUid="">bla bla blub <pg:page name="P3.1.2" db:id="7"/></pg:page>';

        $this->assertXmlStringEqualsXmlString($expected, $this->doc->getSubdocByNodeId(6));
    }
    // }}}
    // {{{ testGetSubdocByNodeIdNodeDoesntExist
    public function testGetSubdocByNodeIdNodeDoesntExist()
    {
        $this->expectException(\Depage\XmlDb\Exceptions\XmlDbException::class);
        $this->expectExceptionMessage("This node is no ELEMENT_NODE or node does not exist");

        $this->doc->getSubdocByNodeId(1000);
    }
    // }}}
    // {{{ testGetSubdocByNodeIdCached
    public function testGetSubdocByNodeIdCached()
    {
        $cache = new MockCache();
        $cache->set(
            'xmldb_proj_test_xmldocs_d3/2.xml',
            '<page/>'
        );

        $xmlDb = new \Depage\XmlDb\XmlDb($this->pdo->prefix . '_proj_test', $this->pdo, $cache, [
            'root',
            'child',
        ]);
        $doc = $xmlDb->getDoc(3);

        $expected = '<page/>';

        $this->assertXmlStringEqualsXmlString($expected, $doc->getSubdocByNodeId(2));
    }
    // }}}
    // {{{ testGetSubdocByNodeIdChangedDoc
    public function testGetSubdocByNodeIdChangedDoc()
    {
        // set up mock cache
        $cache = new MockCache();

        $xmlDb = new \Depage\XmlDb\XmlDb($this->pdo->prefix . '_proj_test', $this->pdo, $cache, [
            'root',
            'child',
        ]);
        $doc = new DocumentTestClass($xmlDb, 1);

        // set up doc type handler, pretend the document changed, trigger save node
        $dth = new DoctypeHandlerTestClass($this->xmlDb, $doc);
        $doc->setDoctypeHandler($dth);
        $doc->getDoctypeHandler()->testDocument = true;
        $doc->getSubdocByNodeId(1);

        // saveNode triggers clearCache, check for cleared cache
        $this->assertTrue($cache->deleted);
    }
    // }}}

    // {{{ testGetNodeNameById
    public function testGetNodeNameById()
    {
        $this->assertEquals('dpg:pages', $this->doc->getNodeNameById(4));
        $this->assertEquals('pg:page', $this->doc->getNodeNameById(5));
    }
    // }}}
    // {{{ testGetNodeNameByIdNonExistent
    public function testGetNodeNameByIdNonExistent()
    {
        $this->assertFalse($this->doc->getNodeNameById(100));
        $this->assertFalse($this->doc->getNodeNameById('noId'));
        $this->assertFalse($this->doc->getNodeNameById(null));
    }
    // }}}

    // {{{ testSaveElementNodes
    public function testSaveElementNodes()
    {
        $xmlStr = '<root xmlns:db="http://cms.depagecms.net/ns/database">' .
            '<child>' .
                '<child/>' .
            '</child>' .
            '<child/>' .
        '</root>';

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
        $xmlStr = '<root xmlns:db="http://cms.depagecms.net/ns/database">' .
            '<child attr="test"></child>' .
        '</root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveElementNodesWithNamespaces
    public function testSaveElementNodesWithNamespaces()
    {
        $xmlStr = '<root xmlns:db="http://cms.depagecms.net/ns/database">' .
            '<db:child attr="test"></db:child>' .
            '<child db:data="blub" />' .
        '</root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveTextNodes
    public function testSaveTextNodes()
    {
        $xmlStr = '<root xmlns:db="http://cms.depagecms.net/ns/database">' .
            '<child>bla</child>blub<b/><c/><child>bla</child>' .
        '</root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSavePiNode
    public function testSavePiNode()
    {
        $xmlStr = '<root xmlns:db="http://cms.depagecms.net/ns/database">' .
            '<?php echo("bla"); ?>' .
        '</root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveCommentNode
    public function testSaveCommentNode()
    {
        $xmlStr = '<root xmlns:db="http://cms.depagecms.net/ns/database">' .
            '<!-- comment -->' .
        '</root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}

    // {{{ testDeleteNode
    public function testDeleteNode()
    {
        $this->assertEquals(5, $this->doc->deleteNode(6));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testDeleteNodeDenied
    public function testDeleteNodeDenied()
    {
        $dth = new DoctypeHandlerTestClass($this->xmlDb, $this->doc);
        $this->doc->setDoctypeHandler($dth);
        $this->doc->getDoctypeHandler()->isAllowedDelete = false;

        $this->assertFalse($this->doc->deleteNode(6));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}

    // {{{ testAddNode
    public function testAddNode()
    {
        $doc = $this->generateDomDocument('<root><node/></root>');

        $this->doc->addNode($doc, 6);

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/><root><node/></root></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testAddNodeDenied
    public function testAddNodeDenied()
    {
        // set up doc type handler
        $dth = new DoctypeHandlerTestClass($this->xmlDb, $this->doc);
        $this->doc->setDoctypeHandler($dth);
        $this->doc->getDoctypeHandler()->isAllowedAdd = false;

        $doc = $this->generateDomDocument('<root><node/></root>');

        $this->assertFalse($this->doc->addNode($doc, 6));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testAddNodeByName
    public function testAddNodeByName()
    {
        // set up doc type handler
        $dth = new DoctypeHandlerTestClass($this->xmlDb, $this->doc);
        $this->doc->setDoctypeHandler($dth);

        $this->doc->addNodeByName('testNode', 8, 0);

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2">' .
                    '<testNode attr1="value1" attr2="value2" name="customNameAttribute"/>' .
                '</pg:page>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testAddNodeByNameFail
    public function testAddNodeByNameFail()
    {
        $this->assertFalse($this->doc->addNodeByName('test', 8, 0));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}

    // {{{ testGetPermissionѕ
    public function testGetPermissionѕ()
    {
        $expected = [
            'validParents' => [
                '*' => ['*']
            ],
            'availableNodes' => []
        ];

        $this->assertEquals($expected, (array) $this->doc->getPermissions());
    }
    // }}}

    // {{{ testReplaceNode
    public function testReplaceNode()
    {
        $doc = $this->generateDomDocument('<root><node/></root>');

        $this->doc->replaceNode($doc, 5);

        $expected = '<dpg:pages ' . $this->namespaces . ' name="" db:id="4">' .
            '<root db:id="5">' .
                '<node db:id="6"/>' .
            '</root>' .
        '</dpg:pages>';

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
        $this->assertFalse($this->doc->getPosById(999));
    }
    // }}}

    // {{{ testGetTargetPos
    public function testGetTargetPos()
    {
        $this->assertEquals(2, $this->doc->getTargetPos(6));
        $this->assertEquals(0, $this->doc->getTargetPos(7));
    }
    // }}}

    // {{{ testSaveNode
    public function testSaveNode()
    {
        $doc = $this->generateDomDocument('<pg:page ' . $this->namespaces . ' name="newName" db:id="8"/>');

        $this->assertEquals(8, $this->doc->saveNode($doc));

        $expectedXml = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="newName"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expectedXml, $this->doc->getXml(false));

        $expectedNode = [
            'id' => '8',
            'id_doc' => '3',
            'id_parent' => '5',
            'pos' => '1',
            'name' => 'pg:page',
            'value' => 'name="newName" ',
            'type' => 'ELEMENT_NODE',
        ];

        $this->assertEquals($expectedNode, $this->getNodeRowById(8));
    }
    // }}}
    // {{{ testSaveNodeNew
    public function testSaveNodeNew()
    {
        $expected = $this->doc->getXml(false);

        $doc = $this->generateDomDocument('<pg:page ' . $this->namespaces . ' name="newNode"/>');
        $this->assertEquals(37, $this->doc->saveNode($doc));

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));

        $expectedNode = [
            'id' => '37',
            'id_doc' => '3',
            'pos' => null,
            'name' => 'pg:page',
            'value' => 'name="newNode" ',
            'type' => 'ELEMENT_NODE',
            'id_parent' => null,
        ];

        $this->assertEquals($expectedNode, $this->getNodeRowById(37));
    }
    // }}}
    // {{{ testSaveNodeRecursive
    public function testSaveNodeRecursive()
    {
        $doc = $this->generateDomDocument('<pg:page ' . $this->namespaces . ' name="newName" db:id="8"><pg:page name="newName2"/></pg:page>');

        $this->assertEquals(8, $this->doc->saveNode($doc));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="newName">' .
                    '<pg:page name="newName2"/>' .
                '</pg:page>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));

        $expectedNode1 = [
            'id' => '8',
            'id_doc' => '3',
            'id_parent' => '5',
            'pos' => '1',
            'name' => 'pg:page',
            'value' => 'name="newName" ',
            'type' => 'ELEMENT_NODE',
        ];

        $expectedNode2 = [
            'id' => '37',
            'id_doc' => '3',
            'id_parent' => '8',
            'pos' => '0',
            'name' => 'pg:page',
            'value' => 'name="newName2" ',
            'type' => 'ELEMENT_NODE',
        ];

        $this->assertEquals($expectedNode1, $this->getNodeRowById(8));
        $this->assertEquals($expectedNode2, $this->getNodeRowById(37));
    }
    // }}}
    // {{{ testSaveNodeRootIdNull
    public function testSaveNodeRootIdNull()
    {
        $doc = new \DomText('test');
        $this->assertEquals(37, $this->doc->saveNode($doc));

        $expectedNode = [
            'id' => '37',
            'id_doc' => '3',
            'id_parent' => null,
            'pos' => '0',
            'name' => null,
            'value' => 'test',
            'type' => 'TEXT_NODE',
        ];

        $this->assertEquals($expectedNode, $this->getNodeRowById(37));
    }
    // }}}
    // {{{ testSaveNodeDocument
    public function testSaveNodeDocument()
    {
        $xml = '<dpg:pages ' . $this->namespaces . ' name="newName" db:id="4">' .
            '<pg:page name="NewHome3" db:id="5">' .
                '<pg:page name="NewP3.1" db:id="6">bla bla blub <pg:page name="NewP3.1.2" db:id="7"/></pg:page>' .
                '<pg:page name="NewP3.2" db:id="8"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $doc = $this->generateDomDocument($xml);

        $this->assertEquals(4, $this->doc->saveNode($doc));

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xml, $this->doc->getXml());
    }
    // }}}

    // {{{ testSaveNodeIn
    public function testSaveNodeIn()
    {
        $doc = $this->generateDomDocument('<node/>');

        $this->assertEquals(37, $this->doc->saveNodeIn($doc, 6, -1, true));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/><node/></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));

        $expectedNode = [
            'id' => '37',
            'id_doc' => '3',
            'id_parent' => '6',
            'pos' => '2',
            'name' => 'node',
            'value' => '',
            'type' => 'ELEMENT_NODE',
        ];

        $this->assertEquals($expectedNode, $this->getNodeRowById(37));
    }
    // }}}
    // {{{ testSaveNodeInPos0
    public function testSaveNodeInPos0()
    {
        $doc = $this->generateDomDocument('<node/>');

        $this->assertEquals(37, $this->doc->saveNodeIn($doc, 6, 0, true));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1"><node/>bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));

        $expectedNode = [
            'id' => '37',
            'id_doc' => '3',
            'id_parent' => '6',
            'pos' => '0',
            'name' => 'node',
            'value' => '',
            'type' => 'ELEMENT_NODE',
        ];

        $this->assertEquals($expectedNode, $this->getNodeRowById(37));
    }
    // }}}
    // {{{ testSaveNodeInPos1
    public function testSaveNodeInPos1()
    {
        $doc = $this->generateDomDocument('<node/>');

        $this->assertEquals(37, $this->doc->saveNodeIn($doc, 6, 1, true));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <node/><pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));

        $expectedNode = [
            'id' => '37',
            'id_doc' => '3',
            'id_parent' => '6',
            'pos' => '1',
            'name' => 'node',
            'value' => '',
            'type' => 'ELEMENT_NODE',
        ];

        $this->assertEquals($expectedNode, $this->getNodeRowById(37));
    }
    // }}}
    // {{{ testSaveNodeInRoot
    public function testSaveNodeInRoot()
    {
        $doc = $this->generateDomDocument('<node/>');

        $this->assertEquals(4, $this->doc->saveNodeIn($doc, null, -1, true));

        $expected = '<node ' . $this->namespaces . ' />';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));

        $expectedNode = [
            'id' => '4',
            'id_doc' => '3',
            'id_parent' => null,
            'pos' => '0',
            'name' => 'node',
            'value' => '',
            'type' => 'ELEMENT_NODE',
        ];

        $this->assertEquals($expectedNode, $this->getNodeRowById(4));
    }
    // }}}

    // {{{ testSaveNodeInChild
    public function testSaveNodeInChild()
    {
        $doc = $this->generateDomDocument('<node><subnode/></node>');

        $this->assertEquals(37, $this->doc->saveNodeIn($doc, 6, -1, true));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/><node><subnode/></node></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));

        $expectedNode = [
            'id' => '37',
            'id_doc' => '3',
            'id_parent' => '6',
            'pos' => '2',
            'name' => 'node',
            'value' => '',
            'type' => 'ELEMENT_NODE',
        ];
        $expectedSubNode = [
            'id' => '38',
            'id_doc' => '3',
            'id_parent' => '37',
            'pos' => '0',
            'name' => 'subnode',
            'value' => '',
            'type' => 'ELEMENT_NODE',
        ];

        $this->assertEquals($expectedNode, $this->getNodeRowById(37));
        $this->assertEquals($expectedSubNode, $this->getNodeRowById(38));
    }
    // }}}
    // {{{ testSaveNodeInPos0Child
    public function testSaveNodeInPos0Child()
    {
        $doc = $this->generateDomDocument('<node><subnode/></node>');

        $this->assertEquals(37, $this->doc->saveNodeIn($doc, 6, 0, true));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1"><node><subnode/></node>bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));

        $expectedNode = [
            'id' => '37',
            'id_doc' => '3',
            'id_parent' => '6',
            'pos' => '0',
            'name' => 'node',
            'value' => '',
            'type' => 'ELEMENT_NODE',
        ];
        $expectedSubNode = [
            'id' => '38',
            'id_doc' => '3',
            'id_parent' => '37',
            'pos' => '0',
            'name' => 'subnode',
            'value' => '',
            'type' => 'ELEMENT_NODE',
        ];

        $this->assertEquals($expectedNode, $this->getNodeRowById(37));
        $this->assertEquals($expectedSubNode, $this->getNodeRowById(38));
    }
    // }}}
    // {{{ testSaveNodeInPos1Child
    public function testSaveNodeInPos1Child()
    {
        $doc = $this->generateDomDocument('<node><subnode/></node>');

        $this->assertEquals(37, $this->doc->saveNodeIn($doc, 6, 1, true));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <node><subnode/></node><pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));

        $expectedNode = [
            'id' => '37',
            'id_doc' => '3',
            'id_parent' => '6',
            'pos' => '1',
            'name' => 'node',
            'value' => '',
            'type' => 'ELEMENT_NODE',
        ];
        $expectedSubNode = [
            'id' => '38',
            'id_doc' => '3',
            'id_parent' => '37',
            'pos' => '0',
            'name' => 'subnode',
            'value' => '',
            'type' => 'ELEMENT_NODE',
        ];

        $this->assertEquals($expectedNode, $this->getNodeRowById(37));
        $this->assertEquals($expectedSubNode, $this->getNodeRowById(38));
    }
    // }}}
    // {{{ testSaveNodeInRootChild
    public function testSaveNodeInRootChild()
    {
        $doc = $this->generateDomDocument('<node><subnode/></node>');

        $this->assertEquals(4, $this->doc->saveNodeIn($doc, null, -1, true));

        $expected = '<node ' . $this->namespaces . '><subnode/></node>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));

        $expectedNode = [
            'id' => '4',
            'id_doc' => '3',
            'id_parent' => null,
            'pos' => '0',
            'name' => 'node',
            'value' => '',
            'type' => 'ELEMENT_NODE',
        ];
        $expectedSubNode = [
            'id' => '5',
            'id_doc' => '3',
            'id_parent' => '4',
            'pos' => '0',
            'name' => 'subnode',
            'value' => '',
            'type' => 'ELEMENT_NODE',
        ];

        $this->assertEquals($expectedNode, $this->getNodeRowById(4));
        $this->assertEquals($expectedSubNode, $this->getNodeRowById(5));
    }
    // }}}

    // {{{ testSaveNodeInDontIncChild
    public function testSaveNodeInDontIncChild()
    {
        $doc = $this->generateDomDocument('<node><subnode/></node>');

        $this->assertEquals(37, $this->doc->saveNodeIn($doc, 6, -1, false));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/><node/></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));

        $expectedNode = [
            'id' => '37',
            'id_doc' => '3',
            'id_parent' => '6',
            'pos' => '2',
            'name' => 'node',
            'value' => '',
            'type' => 'ELEMENT_NODE',
        ];

        $this->assertEquals($expectedNode, $this->getNodeRowById(37));
        $this->assertFalse($this->getNodeRowById(38));
    }
    // }}}

    // {{{ testSaveNodeToDb
    public function testSaveNodeToDb()
    {
        $doc = new \DomDocument();
        $nodeElement = $doc->createElement('test');

        $this->assertEquals(37, $this->doc->saveNodeToDb($nodeElement, 37, 8, 0));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2">' .
                    '<test/>' .
                '</pg:page>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveNodeToDbEntityRef
    public function testSaveNodeToDbEntityRef()
    {
        $doc = new \DomDocument();
        $nodeElement = $doc->createEntityReference('test');

        $this->assertEquals(37, $this->doc->saveNodeToDb($nodeElement, 37, 4, 0));

        $expectedNode = [
            'id' => '37',
            'id_doc' => '3',
            'id_parent' => '4',
            'pos' => '0',
            'name' => null,
            'value' => 'test',
            'type' => 'ENTITY_REF_NODE',
        ];

        $this->assertEquals($expectedNode, $this->getNodeRowById(37));
    }
    // }}}
    // {{{ testSaveNodeToDbIdNull
    public function testSaveNodeToDbIdNull()
    {
        $doc = new \DomDocument();
        $nodeElement = $doc->createElement('test');

        $this->assertEquals(37, $this->doc->saveNodeToDb($nodeElement, null, 8, 0));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2">' .
                    '<test/>' .
                '</pg:page>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveNodeToDbIdNullText
    public function testSaveNodeToDbIdNullText()
    {
        $doc = new \DomDocument();
        $nodeElement = $doc->createTextNode('test');

        $this->assertEquals(37, $this->doc->saveNodeToDb($nodeElement, null, 8, 0));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2">test</pg:page>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveNodeToDbUnknownNodeType
    public function testSaveNodeToDbUnknownNodeType()
    {
        $this->expectException(\Depage\XmlDb\Exceptions\XmlDbException::class);
        $this->expectExceptionMessage("Unknown DOM node type: \"11\".");

        $doc = new \DomDocument();
        $nodeElement = $doc->createDocumentFragment();

        $this->doc->saveNodeToDb($nodeElement, 37, 8, 0);
    }
    // }}}

    // {{{ testUpdateLastChange
    public function testUpdateLastChange()
    {
        $this->setForeignKeyChecks(false);
        $timestamp = $this->doc->updateLastChange();
        $this->setForeignKeyChecks(true);

        $date = date('Y-m-d H:i:s', $timestamp);
        $after = '<dpg:pages ' . $this->namespaces . ' name="" db:lastchange="' . $date . '" db:lastchangeUid="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlString($after, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testUpdateLastChangeTimestamp
    public function testUpdateLastChangeTimestamp()
    {
        $this->setForeignKeyChecks(false);
        $timestamp = $this->doc->updateLastChange(1445444940);
        $this->setForeignKeyChecks(true);

        $after = '<dpg:pages ' . $this->namespaces . ' name="" db:lastchange="2015-10-21 16:29:00" db:lastchangeUid="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlString($after, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testUpdateLastChangeTimeString
    public function testUpdateLastChangeTimeString()
    {
        $this->setForeignKeyChecks(false);
        $timestamp = $this->doc->updateLastChange('2015-10-21 16:29');
        $this->setForeignKeyChecks(true);

        $after = '<dpg:pages ' . $this->namespaces . ' name="" db:lastchange="2015-10-21 16:29:00" db:lastchangeUid="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlString($after, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testUpdateLastChangeUser
    public function testUpdateLastChangeUser()
    {
        $this->setForeignKeyChecks(false);
        $timestamp = $this->doc->updateLastChange(null, 42);
        $this->setForeignKeyChecks(true);

        $date = date('Y-m-d H:i:s', $timestamp);
        $after = '<dpg:pages ' . $this->namespaces . ' name="" db:lastchange="' . $date . '" db:lastchangeUid="42">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlString($after, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testUpdateLastChangeXmlDbUser
    public function testUpdateLastChangeXmlDbUser()
    {
        // set user id
        $xmlDb = new \Depage\XmlDb\XmlDb($this->pdo->prefix . '_proj_test', $this->pdo, $this->cache, ['userId' => 42]);
        $doc = new DocumentTestClass($xmlDb, 3);

        $this->setForeignKeyChecks(false);
        $timestamp = $doc->updateLastChange();
        $this->setForeignKeyChecks(true);

        $date = date('Y-m-d H:i:s', $timestamp);
        $after = '<dpg:pages ' . $this->namespaces . ' name="" db:lastchange="' . $date . '" db:lastchangeUid="42">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlString($after, $doc->getXml(false));
    }
    // }}}

    // {{{ testMoveNode
    public function testMoveNode()
    {
        $this->assertEquals(5, $this->doc->moveNode(6, 4, 0));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastChange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testMoveNodeIn
    public function testMoveNodeIn()
    {
        $this->assertEquals(5, $this->doc->moveNodeIn(6, 4));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
            '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastChange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testMoveNodeBefore
    public function testMoveNodeBefore()
    {
        $this->assertEquals(5, $this->doc->moveNodeBefore(6, 5));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testMoveNodeAfter
    public function testMoveNodeAfter()
    {
        $this->assertEquals(6, $this->doc->moveNodeAfter(7, 6));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub </pg:page>' .
                '<pg:page name="P3.1.2"/>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testMoveNodeAfterSameLevel
    public function testMoveNodeAfterSameLevel()
    {
        $this->assertEquals(5, $this->doc->moveNodeAfter(6, 8));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.2"/>' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}

    // {{{ testCopyNode
    public function testCopyNode()
    {
        $this->assertEquals(37, $this->doc->copyNode(7, 8, 0));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2">' .
                    '<pg:page name="P3.1.2"/>' .
                '</pg:page>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testCopyNodeDenied
    public function testCopyNodeDenied()
    {
        // set up doc type handler
        $dth = new DoctypeHandlerTestClass($this->xmlDb, $this->doc);
        $this->doc->setDoctypeHandler($dth);
        $this->doc->getDoctypeHandler()->isAllowedMove = false;

        $this->assertFalse($this->doc->copyNode(7, 8, 0));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testCopyNodeIn
    public function testCopyNodeIn()
    {
        $this->assertEquals(37, $this->doc->copyNodeIn(7, 8));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2">' .
                    '<pg:page name="P3.1.2"/>' .
                '</pg:page>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testCopyNodeBefore
    public function testCopyNodeBefore()
    {
        $this->assertEquals(37, $this->doc->copyNodeBefore(7, 8));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.1.2"/>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testCopyNodeAfter
    public function testCopyNodeAfter()
    {
        $this->assertEquals(37, $this->doc->copyNodeAfter(7, 8));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2"/>' .
                '<pg:page name="P3.1.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}

    // {{{ testDuplicateNode
    public function testDuplicateNode()
    {
        $this->assertEquals(37, $this->doc->duplicateNode(6));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.1"/>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testDuplicateNodeDenied
    public function testDuplicateNodeDenied()
    {
        // set up doc type handler
        $dth = new DoctypeHandlerTestClass($this->xmlDb, $this->doc);
        $this->doc->setDoctypeHandler($dth);
        $this->doc->getDoctypeHandler()->isAllowedMove = false;

        $this->assertFalse($this->doc->duplicateNode(5));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page name="P3.1">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}

    // {{{ testBuildNode
    public function testBuildNode()
    {
        $node = $this->doc->buildNode('newNode', ['att' => 'val', 'att2' => 'val2']);

        $expected = '<newNode xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" att="val" att2="val2"/>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $node->ownerDocument->saveXML($node));
    }
    // }}}

    // {{{ testSetAttribute
    public function testSetAttribute()
    {
        $this->doc->setAttribute(5, 'textattr', 'new value');
        $this->doc->setAttribute(6, 'name', 'newName');

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3" textattr="new value">' .
                '<pg:page name="newName">bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testRemoveAttribute
    public function testRemoveAttribute()
    {
        $this->assertTrue($this->doc->removeAttribute(6, 'name'));

        $expected = '<dpg:pages ' . $this->namespaces . ' name="">' .
            '<pg:page name="Home3">' .
                '<pg:page>bla bla blub <pg:page name="P3.1.2"/></pg:page>' .
                '<pg:page name="P3.2"/>' .
            '</pg:page>' .
        '</dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testRemoveAttributeNonExistent
    public function testRemoveAttributeNonExistent()
    {
        $expected = $this->doc->getXml();

        $this->assertFalse($this->doc->removeAttribute(6, 'idontexist'));

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}
    // {{{ testRemoveIdAttr
    public function testRemoveIdAttr()
    {
        $xmlDoc = new \Depage\Xml\Document();
        $xmlDoc->loadXml('<root db:id="2" xmlns:db="http://cms.depagecms.net/ns/database"><node/></root>');
        $this->doc->removeIdAttr($xmlDoc);

        $expected = '<root xmlns:db="http://cms.depagecms.net/ns/database">' .
                        '<node/>' .
                    '</root>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $xmlDoc->saveXml());
    }
    // }}}
    // {{{ testGetAttribute
    public function testGetAttribute()
    {
        $this->assertEquals('Home3', $this->doc->getAttribute(5, 'name'));

        $this->assertFalse($this->doc->getAttribute(5, 'undefindattr'));
    }
    // }}}
    // {{{ testGetAttributes
    public function testGetAttributes()
    {
        $attrs = $this->doc->getAttributes(5);

        $expected = [
            'name' => 'Home3',
        ];

        $this->assertEquals($expected, $attrs);
    }
    // }}}

    // {{{ testGetParentIdById
    public function testGetParentIdById()
    {
        $this->assertEquals(4, $this->doc->getParentIdById(5));
    }
    // }}}
    // {{{ testGetParentIdByIdRoot
    public function testGetParentIdByIdRoot()
    {
        $this->assertNull($this->doc->getParentIdById(4));
    }
    // }}}
    // {{{ testGetParentIdByIdFail
    public function testGetParentIdByIdFail()
    {
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
        $nodeArray = [];
        $node = $this->generateDomDocument('<root db:id="2" xmlns:db="http://cms.depagecms.net/ns/database"></root>');

        $this->doc->getNodeArrayForSaving($nodeArray, $node);

        $this->assertEquals(1, count($nodeArray));
        $this->assertEquals(2, $nodeArray[0]['id']);
    }
    // }}}
    // {{{ testGetNodeArrayForSavingStripWhitespace
    public function testGetNodeArrayForSavingStripWhitespace()
    {
        $nodeArray = [];
        $node = $this->generateDomDocument('<root db:id="2" xmlns:db="http://cms.depagecms.net/ns/database"></root>');

        $this->doc->getNodeArrayForSaving($nodeArray, $node, null, 0, false);

        $this->assertEquals(1, count($nodeArray));
        $this->assertEquals(2, $nodeArray[0]['id']);
    }
    // }}}

    // {{{ testGetFreeNodeIdsDefault
    public function testGetFreeNodeIdsDefault()
    {
        $this->doc->getFreeNodeIds();

        $this->assertSame([37], $this->doc->free_element_ids);
    }
    // }}}
    // {{{ testGetFreeNodeIds
    public function testGetFreeNodeIds()
    {
        $this->doc->getFreeNodeIds(10);

        $this->assertSame([37, 38, 39, 40, 41, 42, 43, 44, 45, 46], $this->doc->free_element_ids);
    }
    // }}}
    // {{{ testGetFreeNodeIdsAfterDelete
    public function testGetFreeNodeIdsAfterDelete()
    {
        $this->doc->deleteNode(4);

        $this->doc->getFreeNodeIds(10);

        $this->assertEquals([4, 5, 6, 7, 8, 30, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46], $this->doc->free_element_ids);
    }
    // }}}
    // {{{ testGetFreeNodeIdsAfterDeletePreference
    public function testGetFreeNodeIdsAfterDeletePreference()
    {
        $this->doc->deleteNode(4);

        $this->doc->getFreeNodeIds(3, [4, 5, 6]);

        $this->assertEquals([4, 5, 6], $this->doc->free_element_ids);
    }
    // }}}
    // {{{ testGetFreeNodeIdsAfterDeletePreferenceMore
    public function testGetFreeNodeIdsAfterDeletePreferenceMore()
    {
        $this->doc->deleteNode(4);

        $this->doc->getFreeNodeIds(8, [4, 5, 6, 7, 8, 9, 10, 11]);

        $this->assertEquals([4, 5, 6, 7, 8, 30, 37, 38, 39, 40, 41, 42, 43, 44], $this->doc->free_element_ids);
    }
    // }}}
    // {{{ testGetFreeNodeIdsPreference
    public function testGetFreeNodeIdsPreference()
    {
        $this->doc->getFreeNodeIds(3, [4, 5, 6]);

        $this->assertEquals([37, 38, 39], $this->doc->free_element_ids);
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
