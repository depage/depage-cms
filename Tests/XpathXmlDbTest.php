<?php

namespace Depage\XmlDb\Tests;

class XpathXmlDbTest extends XpathTestCase
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

    // {{{ testAllWildCard
    public function testAllWildCard()
    {
        $this->assertCorrectXpathIds(array(1, 2, 3, 4, 5, 6, 7, 8, 9), '//*');
    }
    // }}}
}
