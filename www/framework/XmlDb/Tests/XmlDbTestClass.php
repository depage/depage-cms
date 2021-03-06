<?php

namespace Depage\XmlDb\Tests;

class XmlDbTestClass extends \Depage\XmlDb\XmlDb
{
    public $doc_ids;
    public $fallbackCall = false;
    public $transactions = 0;

    public function getNodeIdsByXpathDom($xpath, $docId = null)
    {
        $this->fallbackCall = true;
        return parent::getNodeIdsByXpathDom($xpath, $docId);
    }

    public function cleanOperator($operator)
    {
        return parent::cleanOperator($operator);
    }
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
