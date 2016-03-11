<?php

namespace Depage\XmlDb\Tests;

class DoctypeHandlerTest extends DoctypeHandlerBaseTest
{
    // {{{ setUpHandler
    protected function setUpHandler()
    {
        $this->dth = new DoctypeHandlerTestClass($this->xmlDb, $this->doc);

        $this->validParents = array('*' => array('*'));
        $this->availableNodes = array();

        $testNode = new \stdClass();
        $testNode->new = 'customNameAttribute';
        $testNode->attributes = array(
            'attr1' => 'value1',
            'attr2' => 'value2',
        );

        $this->availableNodes['testNode'] = $testNode;
    }
    // }}}

    // {{{ testGetNewNodeFor
    public function testGetNewNodeFor()
    {
        $node = $this->dth->getNewNodeFor('testNode');

        $expected = '<testNode xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" attr1="value1" attr2="value2" name="customNameAttribute"/>';

        // convert to xml string
        $doc = new \DOMDocument;
        $doc->appendChild($doc->importNode($node));
        $xml = $doc->saveHTML();

        $this->assertXmlStringEqualsXmlString($expected, $xml);
    }
    // }}}
}
