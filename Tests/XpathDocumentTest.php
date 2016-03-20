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
        $this->assertCorrectXpathIds(array(15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29), '//*');
    }
    // }}}
    // {{{ testAllWildCardName
    public function testAllWildCardName()
    {
        $this->assertCorrectXpathIds(array(16, 17, 18, 19, 20, 21, 22, 25, 27, 28, 29), '//pg:*');
    }
    // }}}
    // {{{ testAllWildCardAndIdAttributeValueLessThan
    public function testAllWildCardAndIdAttributeValueLessThan()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIds(array(15, 16), '//*[@db:id < \'17\']', false);
    }
    // }}}
}
