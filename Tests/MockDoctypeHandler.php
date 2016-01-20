<?php

namespace Depage\XmlDb\Tests;

class MockDoctypeHandler
{
    public $isAllowedUnlink = true;
    public $isAllowedAdd = true;
    public $isAllowedMove = true;

    public function testDocument($xmlDoc)
    {
        return true;
    }
    public function onDeleteNode($id)
    {
    }
    public function onAddNode($id)
    {
    }
    public function onCopyNode($id, $copyId)
    {
    }
    public function onDocumentChange()
    {
    }
    public function isAllowedUnlink($id)
    {
        return $this->isAllowedUnlink;
    }
    public function isAllowedAdd($id)
    {
        return $this->isAllowedAdd;
    }
    public function getNewNodeFor($name)
    {
        $doc = new \DomDocument();
        $doc->loadXML("<root><node>$name</node></root>");

        return $doc;
    }
    public function isAllowedMove($nodeId, $targetId)
    {
        return $this->isAllowedMove;
    }
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
