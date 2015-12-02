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
        $this->assertCorrectXpathIds(array(1, 2, 6, 7, 8, 9), '//*');
    }
    // }}}
}
