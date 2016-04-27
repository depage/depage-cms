<?php

namespace Depage\XmlDb\Tests;

class DocumentTransactionTest extends XmlDbTestCase
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

        $this->doc = new DocumentTransactionTestClass($this->xmlDb, 3);
        $this->namespaces = 'xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page"';
    }
    // }}}

    // {{{ testGetDocId
    public function testGetDocId()
    {
        $this->doc->getDocId();
        $this->assertEquals(0, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testGetXml
    public function testGetXml()
    {
        $this->doc->getXml();
        $this->assertEquals(0, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testGetDocInfo
    public function testGetDocInfo()
    {
        $this->doc->getDocInfo();
        $this->assertEquals(0, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testGetDoctypeHandler
    public function testGetDoctypeHandler()
    {
        $this->doc->getDoctypeHandler();
        $this->assertEquals(0, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testGetPermissions
    public function testGetPermissions()
    {
        $this->doc->getPermissions();
        $this->assertEquals(0, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testGetNamespacesAndEntities
    public function testGetNamespacesAndEntities()
    {
        $this->doc->getNamespacesAndEntities();
        $this->assertEquals(0, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testGetHistory
    public function testGetHistory()
    {
        $this->doc->getHistory();
        $this->assertEquals(0, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testGetNodeId
    public function testGetNodeId()
    {
        $doc = $this->generateDomDocument('<root><node/></root>');
        $this->doc->getNodeId($doc);

        $this->assertEquals(0, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testGetNodeDataId
    public function testGetNodeDataId()
    {
        $doc = $this->generateDomDocument('<root><node/></root>');
        $this->doc->getNodeDataId($doc);

        $this->assertEquals(0, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testGetNodeNameById
    public function testGetNodeNameById()
    {
        $this->doc->getNodeNameById(1);
        $this->assertEquals(0, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testGetNodeIdsByXpath
    public function testGetNodeIdsByXpath()
    {
        $this->doc->getNodeIdsByXpath('//*');
        $this->assertEquals(0, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testGetParentIdById
    public function testGetParentIdById()
    {
        $this->doc->getParentIdById(5);
        $this->assertEquals(0, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testGetSubdocByNodeId
    public function testGetSubdocByNodeId()
    {
        $this->doc->getSubdocByNodeId(5);
        $this->assertEquals(0, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testGetSubDocByXpath
    public function testGetSubDocByXpath()
    {
        $this->doc->getSubDocByXpath('//*');
        $this->assertEquals(0, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testGetAttribute
    public function testGetAttribute()
    {
        $this->doc->getAttribute(1, 'name');
        $this->assertEquals(0, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testGetAttributes
    public function testGetAttributes()
    {
        $this->doc->getAttributes(1);
        $this->assertEquals(0, $this->doc->cacheCleared);
    }
    // }}}

    // {{{ testRemoveIdAttr
    public function testRemoveIdAttr()
    {
        $xmlDoc = new \Depage\Xml\Document();
        $xmlDoc->loadXml('<root db:id="2" xmlns:db="http://cms.depagecms.net/ns/database"><node/></root>');
        $this->doc->removeIdAttr($xmlDoc);
        $this->assertEquals(0, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testBuildNode
    public function testBuildNode()
    {
        $this->doc->buildNode('newNode', array('att' => 'val'));
        $this->assertEquals(0, $this->doc->cacheCleared);
    }
    // }}}

    // {{{ testCleanDoc
    public function testCleanDoc()
    {
        $this->doc->cleanDoc();
        $this->assertEquals(1, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testSave
    public function testSave()
    {
        $xmlStr = '<root xmlns:db="http://cms.depagecms.net/ns/database">' .
            '<child>' .
                '<child/>' .
            '</child>' .
            '<child/>' .
        '</root>';

        $xml = $this->generateDomDocument($xmlStr);
        $this->doc->save($xml);

        $this->assertEquals(1, $this->doc->cacheCleared);
    }
    // }}}

    // {{{ testUnlinkNode
    public function testUnlinkNode()
    {
        $this->doc->unlinkNode(6);
        $this->assertEquals(1, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testSaveNode
    public function testSaveNode()
    {
        $doc = $this->generateDomDocument('<root><node/></root>');

        $this->doc->saveNode($doc, 4);
        $this->assertEquals(1, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testAddNode
    public function testAddNode()
    {
        $doc = $this->generateDomDocument('<root><node/></root>');

        $this->doc->addNode($doc, 6);
        $this->assertEquals(1, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testAddNodeByName
    public function testAddNodeByName()
    {
        $this->doc->addNodeByName('testNode', 8, 0);
        $this->assertEquals(1, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testReplaceNode
    public function testReplaceNode()
    {
        $doc = $this->generateDomDocument('<root><node/></root>');

        $this->doc->replaceNode($doc, 5);
        $this->assertEquals(1, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testDuplicateNode
    public function testDuplicateNode()
    {
        $this->doc->duplicateNode(6);
        $this->assertEquals(1, $this->doc->cacheCleared);
    }
    // }}}

    // {{{ testMoveNode
    public function testMoveNode()
    {
        $this->doc->moveNode(6, 4, 0);
        $this->assertEquals(1, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testMoveNodeIn
    public function testMoveNodeIn()
    {
        $this->doc->moveNodeIn(6, 4);
        $this->assertEquals(1, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testMoveNodeBefore
    public function testMoveNodeBefore()
    {
        $this->doc->moveNodeBefore(6, 5);
        $this->assertEquals(1, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testMoveNodeAfter
    public function testMoveNodeAfter()
    {
        $this->doc->moveNodeAfter(6, 5);
        $this->assertEquals(1, $this->doc->cacheCleared);
    }
    // }}}

    // {{{ testCopyNode
    public function testCopyNode()
    {
        $this->doc->copyNode(7, 8, 0);
        $this->assertEquals(1, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testCopyNodeIn
    public function testCopyNodeIn()
    {
        $this->doc->copyNodeIn(7, 8);
        $this->assertEquals(1, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testCopyNodeBefore
    public function testCopyNodeBefore()
    {
        $this->doc->copyNodeBefore(7, 8);
        $this->assertEquals(1, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testCopyNodeAfter
    public function testCopyNodeAfter()
    {
        $this->doc->copyNodeAfter(7, 8);
        $this->assertEquals(1, $this->doc->cacheCleared);
    }
    // }}}

    // {{{ testSetAttribute
    public function testSetAttribute()
    {
        $this->doc->setAttribute(5, 'textattr', 'new value');
        $this->assertEquals(1, $this->doc->cacheCleared);
    }
    // }}}
    // {{{ testRemoveAttribute
    public function testRemoveAttribute()
    {
        $this->doc->removeAttribute(6, 'name');
        $this->assertEquals(1, $this->doc->cacheCleared);
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
