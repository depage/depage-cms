<?php

namespace Depage\XmlDb\Tests;

class XpathXmlDbTest extends XpathDocumentTest
{
    // {{{ getTestObject
    protected function getTestObject()
    {
        return $this->xmldb;
    }
    // }}}
    // {{{ getNodeIdsByDomXpath
    protected function getNodeIdsByDomXpath($xmldb, $xpath)
    {
        $ids = array();

        foreach ($xmldb->getDocuments() as $doc) {
            $ids = array_merge($ids, parent::getNodeIdsByDomXpath($doc, $xpath));
        }

        return $ids;
    }
    // }}}
}
