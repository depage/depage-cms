<?php

namespace Depage\XmlDb\Tests;

class DoctypeHandlerTest extends DoctypeHandlerBaseTest
{
    // {{{ setUpHandler
    protected function setUpHandler()
    {
        $this->dth = new DoctypeHandlerTestClass($this->xmlDb, $this->doc);

        $this->validParents = ['*' => ['*']];
        $this->availableNodes = [];

        $testNode = new \stdClass();
        $testNode->newName = 'customNameAttribute';
        $testNode->attributes = [
            'attr1' => 'value1',
            'attr2' => 'value2',
        ];

        $this->availableNodes['testNode'] = $testNode;
    }
    // }}}

    // {{{ testGetNewNodeFor
    public function testGetNewNodeFor()
    {
        $nodes = $this->dth->getNewNodeFor('testNode');

        $expected = '<testNode xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" attr1="value1" attr2="value2" name="customNameAttribute"/>';

        // convert node to xml string
        $doc = new \DOMDocument;
        $doc->appendChild($doc->importNode($nodes->item(0)));
        $xml = $doc->saveHTML();

        $this->assertXmlStringEqualsXmlString($expected, $xml);
        $this->assertFalse($this->dth->getNewNodeFor('unknownNode'));
    }
    // }}}

    // {{{ testIsAllowedInRestrictedTargets
    public function testIsAllowedInRestrictedTargets()
    {
        $this->dth->validParents = [
            '*' => [
                'target1',
                'target2',
            ],
        ];

        $this->assertFalse($this->dth->isAllowedIn('testNode', 'targetTestNode'));
        $this->assertTrue($this->dth->isAllowedIn('testNode', 'target1'));
    }
    // }}}
    // {{{ testIsAllowedInRestrictedNodes
    public function testIsAllowedInRestrictedNodes()
    {
        $this->dth->validParents = [
            'node1' => [
                'target3',
            ],
            'node2' => [
                '*',
            ],
        ];

        $this->assertFalse($this->dth->isAllowedIn('testNode', 'targetTestNode'));
        $this->assertTrue($this->dth->isAllowedIn('node1', 'target3'));
        $this->assertTrue($this->dth->isAllowedIn('node2', 'targetTestNode'));
    }
    // }}}

    // {{{ testStripWhitespace
    public function testStripWhitespace()
    {
        $xml = $this->generateDomDocument('<node>' .
            "   \t  " .
            '<subnode/> ' .
            "   \t  " .
        '</node>');

        $this->doc->save($xml);

        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<node xmlns:db="http://cms.depagecms.net/ns/database"><subnode/></node>' . "\n";

        $this->assertEqualsIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testStripWhitespaceDont
    public function testStripWhitespaceDont()
    {
        $this->dth->preserveWhitespace = ['node'];

        $xml = $this->generateDomDocument('<node>' .
            "   \t  " .
            '<subnode/>' .
            "   \t  " .
        '</node>');

        $this->doc->save($xml);

        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<node xmlns:db="http://cms.depagecms.net/ns/database">' . "   \t  " .
            '<subnode/>' . "   \t  " .
            '</node>' . "\n";

        $this->assertEqualsIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
}
