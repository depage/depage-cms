<?php

namespace depage\xmldb\xmldoctypes;
    
class base {
    // {{{ variables
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
        return array();
    }
    // }}}
    
    // {{{ getValidParents
    function getValidParents() {
        return $this->validParents;
    }
    // }}}
    
    // {{{ isAllowedIn
    function isAllowedIn($nodeName, $targetNodeName) {
        $log = new \log();
        $log->log($this->validParents);
        $log->log("nodeName: " . $nodeName);
        $log->log("targetNodeName: " . $targetNodeName);

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
        return $this->isAllowedIn(
            $this->xmldb->getNodeNameById($this->docId, $nodeId),
            $this->xmldb->getNodeNameById($this->docId, $targetId)
        );
    }
    // }}}
    
    // {{{ isAllowedUnlink
    function isAllowedUnlink($nodeId) {
        return true;
    }
    // }}}
    
    // {{{ isAllowedAdd
    function isAllowedAdd($node, $targetId) {
        return true;
    }
    // }}}
}  

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
