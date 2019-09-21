<?php

namespace Depage\XmlDb\Tests;

class XpathXmlDbTest extends XpathTestCase
{
    // {{{ setUp
    protected function setUp():void
    {
        parent::setUp();

        $this->testObject = $this->xmlDb;
    }
    // }}}
    // {{{ getNodeIdsByDomXpath
    protected function getNodeIdsByDomXpath($xmlDb, $xpath)
    {
        $ids = [];

        foreach ($xmlDb->getDocuments() as $doc) {
            $ids = array_merge($ids, parent::getNodeIdsByDomXpath($doc, $xpath));
        }

        return $ids;
    }
    // }}}

    // {{{ testAllWildCard
    public function testAllWildCard()
    {
        $this->assertCorrectXpathIds([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29], '//*');
    }
    // }}}
    // {{{ testAllWildCardName
    public function testAllWildCardName()
    {
        $this->assertCorrectXpathIds([5, 6, 7, 8, 10, 11, 12, 13, 14, 16, 17, 18, 19, 20, 21, 22, 25, 27, 28, 29], '//pg:*');
    }
    // }}}
    // {{{ testAllWildCardAndIdAttributeValueLessThan
    public function testAllWildCardAndIdAttributeValueLessThan()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIds([1, 2, 3, 4, 5], '//*[@db:id < \'6\']', false);
    }
    // }}}

    // {{{ testNoContext
    public function testNoContext()
    {
        $this->assertCorrectXpathIds([5, 10, 16], 'pg:page', true, true);
    }
    // }}}
}
