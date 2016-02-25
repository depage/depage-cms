<?php

namespace Depage\XmlDb\Tests;

class XpathDocumentTest extends XpathTestCase
{
    // {{{ setUp
    protected function setUp()
    {
        parent::setUp();

        $this->testObject = $this->doc;
    }
    // }}}

    // {{{ testAllWildCard
    public function testAllWildCard()
    {
        $this->assertCorrectXpathIds(array(1, 2, 6, 7, 8, 9, 10, 11, 12, 13, 14), '//*');
    }
    // }}}
    // {{{ testAllWildCardAndIdAttributeValueLessThan
    public function testAllWildCardAndIdAttributeValueLessThan()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIds(array(1, 2), '//*[@db:id < \'6\']', false);
    }
    // }}}
}
