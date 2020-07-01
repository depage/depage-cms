<?php

namespace Depage\XmlDb\XmlDoctypes;

class Base implements DoctypeInterface
{
    // {{{ variables
    // list of elements that may created by a user
    protected $availableNodes = [];

    // list of valid parents given by nodename
    protected $validParents = [
        '*' => [
            '*',
        ],
    ];

    // list of names of nodes not to be affected by whitespace stripping
    protected $preserveWhitespace = [];
    // }}}

    // {{{ constructor
    public function __construct($xmlDb, $document) {
        $this->xmlDb = $xmlDb;
        $this->document = $document;
    }
    // }}}

    // {{{ getPermissions
    public function getPermissions() {
        return (object) [
            'validParents' => $this->getValidParents(),
            'availableNodes' => $this->getAvailableNodes(),
        ];
    }
    // }}}
    // {{{ getValidParents
    public function getValidParents() {
        return $this->validParents;
    }
    // }}}
    // {{{ getAvailableNodes
    public function getAvailableNodes() {
        return $this->availableNodes;
    }
    // }}}
    // {{{ getPreserveWhitespace
    public function getPreserveWhitespace() {
        return $this->preserveWhitespace;
    }
    // }}}
    // {{{ getNewNodeFor
    public function getNewNodeFor($name) {
        $result = false;

        if (isset($this->availableNodes[$name])) {
            $nodeInfo = $this->availableNodes[$name];
            $docInfo = $this->document->getNamespacesAndEntities();

            if (isset($nodeInfo->xmlTemplateData)) {
                $xml = $nodeInfo->xmlTemplateData;
            } else {
                $xml = "<$name {$docInfo->namespaces}";
                if (!empty($nodeInfo->newName)) {
                    $xml .= " name=\"" . htmlspecialchars($nodeInfo->newName) . "\"";
                }
                if (isset($nodeInfo->attributes)) {
                    foreach ($nodeInfo->attributes as $attr => $value) {
                        $xml .= " $attr=\"" . htmlspecialchars($value) . "\"";
                    }
                }
                $xml .= '/>';
            }
            $doc = new \DOMDocument;
            $doc->loadXML("<root>$xml</root>");

            $result = $doc->documentElement->childNodes;
        }

        return $result;
    }
    // }}}

    // {{{ isAllowedIn
    public function isAllowedIn($nodeName, $targetNodeName) {
        $result = false;

        if (isset($this->validParents['*'])) {
            $result = in_array('*', $this->validParents['*']) || in_array($targetNodeName, $this->validParents['*']);
        } else if (isset($this->validParents[$nodeName])) {
            $result = in_array('*', $this->validParents[$nodeName]) || in_array($targetNodeName, $this->validParents[$nodeName]);
        }

        return $result;
    }
    // }}}
    // {{{ isAllowedAdd
    public function isAllowedAdd($node, $targetId) {
        return $this->isAllowedIn(
            $node->nodeName,
            $this->document->getNodeNameById($targetId)
        );
    }
    // }}}
    // {{{ isAllowedCopy
    public function isAllowedCopy($nodeId, $targetId) {
        return $this->isAllowedMove($nodeId, $targetId);
    }
    // }}}
    // {{{ isAllowedMove
    public function isAllowedMove($nodeId, $targetId) {
        return $this->isAllowedIn(
            $this->document->getNodeNameById($nodeId),
            $this->document->getNodeNameById($targetId)
        );
    }
    // }}}
    // {{{ isAllowedDelete
    public function isAllowedDelete($nodeId) {
        return true;
    }
    // }}}

    // {{{ onDocumentChange
    /**
     * On Document Change
     *
     * @return bool
     */
    public function onDocumentChange()
    {
        return true;
    }
    // }}}
    // {{{ onAddNode
    /**
     * On Add Node
     *
     * @param \DomNode $node
     * @param $target_id
     * @param $target_pos
     * @param $extras
     * @return null
     */
    public function onAddNode(\DomNode $node, $target_id, $target_pos, $extras) {
        return true;
    }
    // }}}
    // {{{ onCopyNode
    /**
     * On Copy Node
     *
     * @param \DomElement $node
     * @param $target_id
     * @param $target_pos
     * @return null
     */
    public function onCopyNode($node_id, $copy_id) {
        return true;
    }
    // }}}
    // {{{ onMoveNode
    /**
     * On Move Node
     *
     * @param \DomElement $node
     * @param $node_id
     * @param $target_id
     * @return bool
     */
    public function onMoveNode($node_id, $oldParentId) {
        return true;
    }
    // }}}
    // {{{ onDeleteNode
    /**
     * On Delete Node
     *
     * @param $node_id
     * @param $parent_id
     * @return bool
     */
    public function onDeleteNode($node_id, $parent_id) {
        return true;
    }
    // }}}
    // {{{ onSetAttribute
    /**
     * On Delete Node
     *
     * @param $node_id
     * @param $parent_id
     * @return bool
     */
    public function onSetAttribute($nodeId, $attrName, $oldVal, $newVal) {
        return true;
    }
    // }}}
    // {{{ onHistorySave
    /**
     * On History Save
     *
     * @return bool
     */
    public function onHistorySave()
    {
        return true;
    }
    // }}}

    // {{{ testDocument
    public function testDocument($xml) {
        return false;
    }
    // }}}
    // {{{ testDocumentForHistory
    public function testDocumentForHistory($xml) {
        return false;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
