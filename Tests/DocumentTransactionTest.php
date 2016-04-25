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

    // {{{ testCleanDoc
    public function testCleanDoc()
    {
        $this->doc->cleanDoc();
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
    }
    // }}}

    // {{{ testUnlinkNode
    public function testUnlinkNode()
    {
        $this->doc->unlinkNode(6);
    }
    // }}}
    // {{{ testSaveNode
    public function testSaveNode()
    {
        $doc = $this->generateDomDocument('<root><node/></root>');

        $this->doc->saveNode($doc, 4);
    }
    // }}}
    // {{{ testAddNode
    public function testAddNode()
    {
        $doc = $this->generateDomDocument('<root><node/></root>');

        $this->doc->addNode($doc, 6);
    }
    // }}}
    // {{{ testAddNodeByName
    public function testAddNodeByName()
    {
        $this->doc->addNodeByName('testNode', 8, 0);
    }
    // }}}
    // {{{ testBuildNode
    public function testBuildNode()
    {
        $this->doc->buildNode('newNode', array('att' => 'val'));
    }
    // }}}
    // {{{ testReplaceNode
    public function testReplaceNode()
    {
        $doc = $this->generateDomDocument('<root><node/></root>');

        $this->doc->replaceNode($doc, 5);
    }
    // }}}
    // {{{ testDuplicateNode
    public function testDuplicateNode()
    {
        $this->doc->duplicateNode(6);
    }
    // }}}

    // {{{ testMoveNode
    public function testMoveNode()
    {
        $this->doc->moveNode(6, 4, 0);
    }
    // }}}
    // {{{ testMoveNodeIn
    public function testMoveNodeIn()
    {
        $this->doc->moveNodeIn(6, 4);
    }
    // }}}
    // {{{ testMoveNodeBefore
    public function testMoveNodeBefore()
    {
        $this->doc->moveNodeBefore(6, 5);
    }
    // }}}
    // {{{ testMoveNodeAfter
    public function testMoveNodeAfter()
    {
        $this->doc->moveNodeAfter(6, 5);
    }
    // }}}

    // {{{ testCopyNode
    public function testCopyNode()
    {
        $this->doc->copyNode(7, 8, 0);
    }
    // }}}
    // {{{ testCopyNodeIn
    public function testCopyNodeIn()
    {
        $this->doc->copyNodeIn(7, 8);
    }
    // }}}
    // {{{ testCopyNodeBefore
    public function testCopyNodeBefore()
    {
        $this->doc->copyNodeBefore(7, 8);
    }
    // }}}
    // {{{ testCopyNodeAfter
    public function testCopyNodeAfter()
    {
        $this->doc->copyNodeAfter(7, 8);
    }
    // }}}

    // {{{ testSetAttribute
    public function testSetAttribute()
    {
        $this->doc->setAttribute(5, 'textattr', 'new value');
    }
    // }}}
    // {{{ testRemoveAttribute
    public function testRemoveAttribute()
    {
        $this->doc->removeAttribute(6, 'name');
    }
    // }}}
    // {{{ testRemoveIdAttr
    public function testRemoveIdAttr()
    {
        $xmlDoc = new \Depage\Xml\Document();
        $xmlDoc->loadXml('<root db:id="2" xmlns:db="http://cms.depagecms.net/ns/database"><node/></root>');
        $this->doc->removeIdAttr($xmlDoc);
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
