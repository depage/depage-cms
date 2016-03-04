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
        $this->assertCorrectXpathIds(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29), '//*');
    }
    // }}}
    // {{{ testAllWildCardNs
    public function testAllWildCardNs()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath => 2.0
        $this->assertCorrectXpathIds(array(5, 6, 7, 8, 10, 11, 12, 13, 14, 16, 18, 19, 21, 22, 24, 27), '//*:page', false);
    }
    // }}}
    // {{{ testAllWildCardName
    public function testAllWildCardName()
    {
        $this->assertCorrectXpathIds(array(5, 6, 7, 8, 10, 11, 12, 13, 14, 16, 17, 18, 19, 20, 21, 22, 25, 27, 28, 29), '//pg:*');
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
