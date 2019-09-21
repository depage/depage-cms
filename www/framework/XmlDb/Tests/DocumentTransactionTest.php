<?php

namespace Depage\XmlDb\Tests;

class DocumentTransactionTest extends XmlDbTestCase
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

        $this->xmlDb = new \Depage\XmlDb\XmlDb($this->pdo->prefix . '_proj_test', $this->pdo, $this->cache, [
            'root',
            'child',
        ]);

        $this->doc = new DocumentTransactionTestClass($this->xmlDb, 3);
        $this->namespaces = 'xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page"';

        $this->dth = $this->doc->getDoctypeHandler();
        $this->assertEquals(0, $this->dth->onAddNode);
        $this->assertEquals(0, $this->dth->onCopyNode);
        $this->assertEquals(0, $this->dth->onMoveNode);
        $this->assertEquals(0, $this->dth->onDeleteNode);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}

    // {{{ testGetDocId
    public function testGetDocId()
    {
        $this->doc->getDocId();
        $this->assertEquals(0, $this->doc->cacheCleared);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testGetXml
    public function testGetXml()
    {
        $this->doc->getXml();
        $this->assertEquals(0, $this->doc->cacheCleared);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testGetDocInfo
    public function testGetDocInfo()
    {
        $this->doc->getDocInfo();
        $this->assertEquals(0, $this->doc->cacheCleared);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testGetDoctypeHandler
    public function testGetDoctypeHandler()
    {
        $this->doc->getDoctypeHandler();
        $this->assertEquals(0, $this->doc->cacheCleared);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testGetPermissions
    public function testGetPermissions()
    {
        $this->doc->getPermissions();
        $this->assertEquals(0, $this->doc->cacheCleared);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testGetNamespacesAndEntities
    public function testGetNamespacesAndEntities()
    {
        $this->doc->getNamespacesAndEntities();
        $this->assertEquals(0, $this->doc->cacheCleared);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testGetHistory
    public function testGetHistory()
    {
        $this->doc->getHistory();
        $this->assertEquals(0, $this->doc->cacheCleared);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testGetNodeId
    public function testGetNodeId()
    {
        $doc = $this->generateDomDocument('<root><node/></root>');
        $this->doc->getNodeId($doc);

        $this->assertEquals(0, $this->doc->cacheCleared);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testGetNodeDataId
    public function testGetNodeDataId()
    {
        $doc = $this->generateDomDocument('<root><node/></root>');
        $this->doc->getNodeDataId($doc);

        $this->assertEquals(0, $this->doc->cacheCleared);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testGetNodeNameById
    public function testGetNodeNameById()
    {
        $this->doc->getNodeNameById(1);
        $this->assertEquals(0, $this->doc->cacheCleared);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testGetNodeIdsByXpath
    public function testGetNodeIdsByXpath()
    {
        $this->doc->getNodeIdsByXpath('//*');
        $this->assertEquals(0, $this->doc->cacheCleared);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testGetParentIdById
    public function testGetParentIdById()
    {
        $this->doc->getParentIdById(5);
        $this->assertEquals(0, $this->doc->cacheCleared);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testGetSubdocByNodeId
    public function testGetSubdocByNodeId()
    {
        $this->doc->getSubdocByNodeId(5);
        $this->assertEquals(0, $this->doc->cacheCleared);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testGetSubdocByXpath
    public function testGetSubdocByXpath()
    {
        $this->doc->getSubdocByXpath('//*');
        $this->assertEquals(0, $this->doc->cacheCleared);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testGetAttribute
    public function testGetAttribute()
    {
        $this->doc->getAttribute(1, 'name');
        $this->assertEquals(0, $this->doc->cacheCleared);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testGetAttributes
    public function testGetAttributes()
    {
        $this->doc->getAttributes(1);
        $this->assertEquals(0, $this->doc->cacheCleared);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}

    // {{{ testRemoveIdAttr
    public function testRemoveIdAttr()
    {
        $xmlDoc = new \Depage\Xml\Document();
        $xmlDoc->loadXml('<root db:id="2" xmlns:db="http://cms.depagecms.net/ns/database"><node/></root>');
        $this->doc->removeIdAttr($xmlDoc);
        $this->assertEquals(0, $this->doc->cacheCleared);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testBuildNode
    public function testBuildNode()
    {
        $this->doc->buildNode('newNode', ['att' => 'val']);
        $this->assertEquals(0, $this->doc->cacheCleared);
        $this->assertEquals(0, $this->dth->onDocumentChange);
    }
    // }}}

    // {{{ testClearDoc
    public function testClearDoc()
    {
        $this->doc->clearDoc();
        $this->assertEquals(1, $this->doc->cacheCleared);
        $this->assertEquals(1, $this->dth->onDocumentChange);
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
        $this->assertEquals(1, $this->dth->onDocumentChange);
    }
    // }}}

    // {{{ testDeleteNode
    public function testDeleteNode()
    {
        $this->doc->deleteNode(6);

        $this->assertEquals(1, $this->doc->cacheCleared);
        $this->assertEquals(1, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testSaveNode
    public function testSaveNode()
    {
        $doc = $this->generateDomDocument('<root><node/></root>');
        $this->doc->saveNode($doc, null);

        $this->assertEquals(1, $this->doc->cacheCleared);
        $this->assertEquals(1, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testAddNode
    public function testAddNode()
    {
        $doc = $this->generateDomDocument('<root><node/></root>');
        $this->doc->addNode($doc, 6);

        $this->assertEquals(1, $this->doc->cacheCleared);
        $this->assertEquals(1, $this->dth->onDocumentChange);
        $this->assertEquals(1, $this->dth->onAddNode);
    }
    // }}}
    // {{{ testAddNodeByName
    public function testAddNodeByName()
    {
        $this->doc->addNodeByName('testNode', 8, 0);

        $this->assertEquals(1, $this->doc->cacheCleared);
        $this->assertEquals(1, $this->dth->onDocumentChange);
        $this->assertEquals(1, $this->dth->onAddNode);
    }
    // }}}
    // {{{ testReplaceNode
    public function testReplaceNode()
    {
        $doc = $this->generateDomDocument('<root><node/></root>');
        $this->doc->replaceNode($doc, 5);

        $this->assertEquals(1, $this->doc->cacheCleared);
        $this->assertEquals(1, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testDuplicateNode
    public function testDuplicateNode()
    {
        $this->doc->duplicateNode(6);

        $this->assertEquals(2, $this->doc->cacheCleared);
        $this->assertEquals(1, $this->dth->onDocumentChange);
    }
    // }}}

    // {{{ testMoveNode
    public function testMoveNode()
    {
        $this->doc->moveNode(6, 4, 0);

        $this->assertEquals(1, $this->doc->cacheCleared);
        $this->assertEquals(1, $this->dth->onDocumentChange);
        $this->assertEquals(1, $this->dth->onMoveNode);
    }
    // }}}
    // {{{ testMoveNodeIn
    public function testMoveNodeIn()
    {
        $this->doc->moveNodeIn(6, 4);

        $this->assertEquals(1, $this->doc->cacheCleared);
        $this->assertEquals(1, $this->dth->onDocumentChange);
        $this->assertEquals(1, $this->dth->onMoveNode);
    }
    // }}}
    // {{{ testMoveNodeBefore
    public function testMoveNodeBefore()
    {
        $this->doc->moveNodeBefore(6, 5);

        $this->assertEquals(1, $this->doc->cacheCleared);
        $this->assertEquals(1, $this->dth->onDocumentChange);
        $this->assertEquals(1, $this->dth->onMoveNode);
    }
    // }}}
    // {{{ testMoveNodeAfter
    public function testMoveNodeAfter()
    {
        $this->doc->moveNodeAfter(6, 5);

        $this->assertEquals(1, $this->doc->cacheCleared);
        $this->assertEquals(1, $this->dth->onDocumentChange);
        $this->assertEquals(1, $this->dth->onMoveNode);
    }
    // }}}

    // {{{ testCopyNode
    public function testCopyNode()
    {
        $this->doc->copyNode(7, 8, 0);

        $this->assertEquals(1, $this->doc->cacheCleared);
        $this->assertEquals(1, $this->dth->onDocumentChange);
        $this->assertEquals(1, $this->dth->onCopyNode);
    }
    // }}}
    // {{{ testCopyNodeIn
    public function testCopyNodeIn()
    {
        $this->doc->copyNodeIn(7, 8);

        $this->assertEquals(1, $this->doc->cacheCleared);
        $this->assertEquals(1, $this->dth->onDocumentChange);
        $this->assertEquals(1, $this->dth->onCopyNode);
    }
    // }}}
    // {{{ testCopyNodeBefore
    public function testCopyNodeBefore()
    {
        $this->doc->copyNodeBefore(7, 8);

        $this->assertEquals(1, $this->doc->cacheCleared);
        $this->assertEquals(1, $this->dth->onDocumentChange);
        $this->assertEquals(1, $this->dth->onCopyNode);
    }
    // }}}
    // {{{ testCopyNodeAfter
    public function testCopyNodeAfter()
    {
        $this->doc->copyNodeAfter(7, 8);

        $this->assertEquals(1, $this->doc->cacheCleared);
        $this->assertEquals(1, $this->dth->onDocumentChange);
        $this->assertEquals(1, $this->dth->onCopyNode);
    }
    // }}}

    // {{{ testSetAttribute
    public function testSetAttribute()
    {
        $this->doc->setAttribute(5, 'textattr', 'new value');

        $this->assertEquals(1, $this->doc->cacheCleared);
        $this->assertEquals(1, $this->dth->onDocumentChange);
    }
    // }}}
    // {{{ testRemoveAttribute
    public function testRemoveAttribute()
    {
        $this->doc->removeAttribute(6, 'name');

        $this->assertEquals(1, $this->doc->cacheCleared);
        $this->assertEquals(1, $this->dth->onDocumentChange);
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
