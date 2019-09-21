<?php

namespace Depage\XmlDb\Tests;

use Depage\XmlDb\XmlDoctypes\Base;

class DoctypeHandlerTestClass extends Base
{
    public $isAllowedIn = true;
    public $isAllowedCopy = true;
    public $isAllowedMove = true;
    public $isAllowedDelete = true;
    public $isAllowedAdd = true;

    public $testDocument = false;
    public $availableNodes = [];
    public $validParents = [
        '*' => [
            '*',
        ],
    ];
    public $preserveWhitespace = [];

    // {{{ constructor
    public function __construct($xmlDb, $document)
    {
        parent::__construct($xmlDb, $document);

        $testNode = new \stdClass();
        $testNode->newName = 'customNameAttribute';
        $testNode->attributes = [
            'attr1' => 'value1',
            'attr2' => 'value2',
        ];

        $this->availableNodes['testNode'] = $testNode;
    }
    // }}}

    // {{{ isAllowedIn
    public function isAllowedIn($nodeName, $targetNodeName)
    {
        return ($this->isAllowedIn) ? parent::isAllowedIn($nodeName, $targetNodeName) : false;
    }
    // }}}
    // {{{ isAllowedCopy
    public function isAllowedCopy($nodeId, $targetId)
    {
        return ($this->isAllowedCopy) ? parent::isAllowedCopy($nodeId, $targetId) : false;
    }
    // }}}
    // {{{ isAllowedMove
    public function isAllowedMove($nodeId, $targetId)
    {
        return ($this->isAllowedMove) ? parent::isAllowedMove($nodeId, $targetId) : false;
    }
    // }}}
    // {{{ isAllowedDelete
    public function isAllowedDelete($nodeId)
    {
        return ($this->isAllowedDelete) ? parent::isAllowedDelete($nodeId) : false;
    }
    // }}}
    // {{{ isAllowedAdd
    public function isAllowedAdd($node, $targetId)
    {
        return ($this->isAllowedAdd) ? parent::isAllowedAdd($node, $targetId) : false;
    }
    // }}}

    // {{{ testDocument
    public function testDocument($xml)
    {
        return $this->testDocument;
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
