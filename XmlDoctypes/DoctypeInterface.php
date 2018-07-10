<?php

namespace Depage\XmlDb\XmlDoctypes;

interface DoctypeInterface
{
    public function getPermissions();
    public function getValidParents();
    public function getAvailableNodes();
    public function getPreserveWhitespace();
    public function getNewNodeFor($name);

    public function isAllowedIn($nodeName, $targetNodeName);
    public function isAllowedAdd($nodeName, $targetId);
    public function isAllowedCopy($nodeId, $targetId);
    public function isAllowedMove($nodeId, $targetId);
    public function isAllowedDelete($nodeId);

    public function onDocumentChange();
    public function onAddNode(\DomNode $node, $targetId, $targetPos, $extras);
    public function onCopyNode($nodeId, $copyId);
    public function onMoveNode($nodeId, $oldParentId);
    public function onDeleteNode($nodeId, $parentId);
    public function onSetAttribute($nodeId, $attrName, $oldVal, $newVal);

    public function testDocument($xml);
    public function testDocumentForHistory($xml);
}
