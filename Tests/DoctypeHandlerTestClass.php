<?php

namespace Depage\XmlDb\Tests;

use Depage\XmlDb\XmlDocTypes\Base;

class DoctypeHandlerTestClass extends Base
{
    public $isAllowedUnlink = true;
    public $isAllowedAdd = true;
    public $isAllowedMove = true;
    public $testDocument = false;
    public $availableNodes = array();

    public function __construct($xmlDb, $document)
    {
        parent::__construct($xmlDb, $document);

        $testNode = new \stdClass();
        $testNode->new = 'customNameAttribute';
        $testNode->attributes = array(
            'attr1' => 'value1',
            'attr2' => 'value2',
        );

        $this->availableNodes['testNode'] = $testNode;
    }

    public function onDocumentChange()
    {
        return parent::onDocumentChange();
    }
    public function isAllowedUnlink($nodeId)
    {
        return ($this->isAllowedUnlink) ? parent::isAllowedUnlink($nodeId) : false;
    }
    public function isAllowedAdd($nodeId, $targetId)
    {
        return ($this->isAllowedAdd) ? parent::isAllowedAdd($nodeId, $targetId) : false;
    }
    public function isAllowedMove($nodeId, $targetId)
    {
        return ($this->isAllowedMove) ? parent::isAllowedMove($nodeId, $targetId) : false;
    }
    public function testDocument()
    {
        return $this->testDocument;
    }
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
