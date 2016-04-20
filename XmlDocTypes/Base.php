<?php

namespace Depage\XmlDb\XmlDocTypes;

class Base
{
    // {{{ variables
    // list of elements that may created by a user
    protected $availableNodes = array();

    // list of valid parents given by nodename
    protected $validParents = array(
        '*' => array(
            '*',
        ),
    );
    // }}}

    // {{{ constructor
    public function __construct($xmldb, $document) {
        $this->xmldb = $xmldb;
        $this->document = $document;
    }
    // }}}

    // {{{ getPermissions
    public function getPermissions() {
        return (object) array(
            'validParents' => $this->getValidParents(),
            'availableNodes' => $this->getAvailableNodes(),
        );
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
    // {{{ getNewNodeFor
    public function getNewNodeFor($name) {
        $result = false;

        if (isset($this->availableNodes[$name])) {
            $nodeInfo = $this->availableNodes[$name];
            $docInfo = $this->document->getNamespacesAndEntities();

            $xml = "<$name {$docInfo->namespaces}";
            if (!empty($nodeInfo->new)) {
                $xml .= " name=\"" . htmlspecialchars($nodeInfo->new) . "\"";
            }
            if (isset($nodeInfo->attributes)) {
                foreach ($nodeInfo->attributes as $attr => $value) {
                    $xml .= " $attr=\"" . htmlspecialchars($value) . "\"";
                }
            }
            $xml .= "/>";

            $doc = new \DOMDocument;
            $doc->loadXML($xml);

            $result = $doc->documentElement;
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
    // {{{ isAllowedUnlink
    public function isAllowedUnlink($nodeId) {
        return true;
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
        return null;
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
    // {{{ onDeleteNode
    /**
     * On Delete Node
     *
     * @param $node_id
     * @return bool
     */
    public function onDeleteNode($node_id, $parent_id) {
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

    // {{{ testDocument
    public function testDocument($xml) {
        return false;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
