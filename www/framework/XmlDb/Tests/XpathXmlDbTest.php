<?php

namespace Depage\XmlDb\Tests;

class XpathXmlDbTest extends XpathTestCase
{
    // {{{ setUp
    protected function setUp()
    {
        parent::setUp();

        $this->testObject = $this->xmldb;
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
    // {{{ testAllWildCardAndIdAttributeValueLessThan
    public function testAllWildCardAndIdAttributeValueLessThan()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIds(array(1, 2, 3, 4, 5), '//*[@db:id < \'6\']', false);
    }
    // }}}
}
