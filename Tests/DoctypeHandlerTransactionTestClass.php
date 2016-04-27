<?php

namespace Depage\XmlDb\Tests;

class DoctypeHandlerTransactionTestClass extends DoctypeHandlerTestClass
{
    public $onAddNode = 0;
    public $onCopyNode = 0;
    public $onMoveNode = 0;
    public $onDeleteNode = 0;
    public $onDocumentChange = 0;

    // {{{ onAddNode
    public function onAddNode(\DomNode $node, $target_id, $target_pos, $extras)
    {
        $this->transactionTest('onAddNode');
        $this->onAddNode++;

        return parent::onAddNode($node, $target_id, $target_pos, $extras);
    }
    // }}}
    // {{{ onCopyNode
    public function onCopyNode($node_id, $copy_id)
    {
        $this->transactionTest('onCopyNode');
        $this->onCopyNode++;

        return parent::onCopyNode($node_id, $copy_id);
    }
    // }}}
    // {{{ onMoveNode
    public function onMoveNode($node_id, $moved_id)
    {
        $this->transactionTest('onMoveNode');
        $this->onMoveNode++;

        return parent::onMoveNode($node_id, $moved_id);
    }
    // }}}
    // {{{ onDeleteNode
    public function onDeleteNode($node_id, $parent_id)
    {
        if (!$this->document->isInTransaction()) {
            throw new \Exception("onDeleteNode triggered outside transaction.");
        }
        $this->onDeleteNode++;

        return parent::onDeleteNode($node_id, $parent_id);
    }
    // }}}
    // {{{ onDocumentChange
    public function onDocumentChange()
    {
        $this->transactionTest('onDocumentChange');
        $this->onDocumentChange++;

        return parent::onDocumentChange();
    }
    // }}}

    // {{{ testDocument
    public function testDocument($xml)
    {
        return $this->testDocument;
    }
    // }}}

    // {{{ transactionTest
    public function transactionTest($hook)
    {
        if ($this->document->isInTransaction()) {
            throw new \Exception("$hook triggered during transaction.");
        }
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
