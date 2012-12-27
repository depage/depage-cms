<?php

namespace depage\xmldb\xmldoctypes;
    
class base {
    // {{{ constructor
    function __construct($xmldb, $docId) {
        $this->xmldb = $xmldb;
        $this->docId = $docId;
    }
    // }}}
    
    // {{{ isAllowedMove
    function isAllowedMove($nodeId, $targetId) {
        return true;
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
