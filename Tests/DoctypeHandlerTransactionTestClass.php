<?php

namespace Depage\XmlDb\Tests;

class DoctypeHandlerTransactionTestClass extends DoctypeHandlerTestClass
{
    // {{{ onAddNode
    public function onAddNode(\DomNode $node, $target_id, $target_pos, $extras)
    {
        $this->transactionTest('onAddNode');

        return parent::onAddNode($node, $target_id, $target_pos, $extras);
    }
    // }}}
    // {{{ onCopyNode
    public function onCopyNode($node_id, $copy_id)
    {
        $this->transactionTest('onCopyNode');

        return parent::onCopyNode($node_id, $copy_id);
    }
    // }}}
    // {{{ onDeleteNode
    public function onDeleteNode($node_id)
    {
        $this->transactionTest('onDeleteNode');

        return parent::onDeleteNode($node_id);
    }
    // }}}
    // {{{ onDocumentChange
    public function onDocumentChange()
    {
        $this->transactionTest('onDocumentChange');

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
