<?php

namespace depage\xmldb\xmldoctypes;
    
class base {
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
    function __construct($xmldb, $docId) {
        $this->xmldb = $xmldb;
        $this->docId = $docId;
    }
    // }}}
    
    // {{{ getPermissions
    function getPermissions() {
        return (object) array(
            'validParents' => $this->getValidParents(),
            'availableNodes' => $this->getAvailableNodes(),
        );
    }
    // }}}
    
    // {{{ getValidParents
    function getValidParents() {
        return $this->validParents;
    }
    // }}}
    
    // {{{ getAvailableNodes 
    function getAvailableNodes() {
        return $this->availableNodes;
    }
    // }}}
    
    // {{{ getNewNodeFor
    function getNewNodeFor($name) {
        $doc = $this->xmldb->getDoc($this->docId);
        if ($doc && isset($this->availableNodes[$name])) {
            $nodeInfo = $this->availableNodes[$name];
            $docInfo = $doc->getNamespacesAndEntities();

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

            return $doc->documentElement;
        }

        return false;
    }
    // }}}
    
    // {{{ isAllowedIn
    function isAllowedIn($nodeName, $targetNodeName) {
        if (isset($this->validParents['*'])) {
            return in_array('*', $this->validParents['*']) || in_array($targetNodeName, $this->validParents['*']);
        } else if (isset($this->validParents[$nodeName])) {
            return in_array('*', $this->validParents[$nodeName]) || in_array($targetNodeName, $this->validParents[$nodeName]);
        }
        return false;
    }
    // }}}
    
    // {{{ isAllowedMove
    function isAllowedMove($nodeId, $targetId) {
        if($doc = $this->xmldb->getDoc($this->docId)) {
            return $this->isAllowedIn(
                $doc->getNodeNameById($nodeId),
                $doc->getNodeNameById($targetId)
            );
        }

        return false;
    }
    // }}}
    
    // {{{ isAllowedUnlink
    function isAllowedUnlink($nodeId) {
        return true;
    }
    // }}}
    
    // {{{ isAllowedAdd
    function isAllowedAdd($node, $targetId) {
        if($doc = $this->xmldb->getDoc($this->docId)) {
            return $this->isAllowedIn(
                $node->nodeName,
                $doc->getNodeNameById($targetId)
            );
        }

        return false;
    }
    // }}}
}  

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
