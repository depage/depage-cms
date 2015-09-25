<?php

namespace Depage\XmlDb\Tests;

class XpathDocumentTest extends DatabaseTestCase
{
    protected $xmldb;
    protected $cache;
    protected $doc;
    protected $testObject;

    // {{{ setUp
    protected function setUp()
    {
        parent::setUp();

        $this->cache = \Depage\Cache\Cache::factory('xmldb', array('disposition' => 'uncached'));
        $this->xmldb = new \Depage\XmlDb\XmlDb($this->pdo->prefix . '_proj_test', $this->pdo, $this->cache, array(
            'root',
            'child',
        ));
        $this->doc = new DocumentTestClass($this->xmldb, 1);
    }
    // }}}

    // {{{ getTestObject
    protected function getTestObject()
    {
        return $this->doc;
    }
    // }}}
    // {{{ getNodeIdsByDomXpath
    protected function getNodeIdsByDomXpath($doc, $xpath)
    {
        $ids = array();

        $domXpath = new \DomXpath($doc->getXml());
        $list = $domXpath->query($xpath);
        foreach ($list as $item) {
            $ids[] = $item->attributes->getNamedItem('id')->nodeValue;
        }

        return $ids;
    }
    // }}}

    // {{{ assertCorrectXpathIds
    protected function assertCorrectXpathIds(array $expectedIds, $xpath)
    {
        $object = $this->getTestObject();

        $actualIds = $object->getNodeIdsByXpath($xpath);

        $this->assertEmpty(array_diff($expectedIds, $this->getNodeIdsByDomXpath($object, $xpath)), 'Failed asserting that expected IDs match DOMXPath query node IDs. Is the test set up correctly?');
        $this->assertEquals($expectedIds, $actualIds);
    }
    // }}}
    // {{{ assertCorrectXpathIdsNoDomXpath
    protected function assertCorrectXpathIdsNoDomXpath(array $expectedIds, $xpath)
    {
        $this->assertEquals($expectedIds, $this->getTestObject()->getNodeIdsByXpath($xpath));
    }
    // }}}

    // {{{ testNameAll
    public function testNameAll()
    {
        $this->assertCorrectXpathIds(array('2', '6', '7', '8'), '//pg:page');
    }
    // }}}
    // {{{ testNameAllWithAttribute
    public function testNameAllWithAttribute()
    {
        $this->assertCorrectXpathIds(array('2', '6', '7', '8'), '//pg:page[@name]');
    }
    // }}}
    // {{{ testNameAllWithAttributeWithValue
    public function testNameAllWithAttributeWithValue()
    {
        $this->assertCorrectXpathIds(array('8'), '//pg:page[@name = \'bla blub\']');
    }
    // }}}
    // {{{ testNameWithChild
    public function testNameWithChild()
    {
        $this->assertCorrectXpathIds(array('2'), '/dpg:pages/pg:page');
    }
    // }}}
    // {{{ testNameWithChildAndPosition
    public function testNameWithChildAndPosition()
    {
        $this->assertCorrectXpathIds(array('8'), '/dpg:pages/pg:page/pg:page[3]');
    }
    // }}}
    // {{{ testNameAndAttribute
    public function testNameAndAttribute()
    {
        $this->assertCorrectXpathIds(array('6', '7', '8'), '/dpg:pages/pg:page/pg:page[@name]');
    }
    // }}}
    // {{{ testNameAndAttributeWithValue
    public function testNameAndAttributeWithValue()
    {
        $this->assertCorrectXpathIds(array('6'), '/dpg:pages/pg:page/pg:page[@name = \'Subpage\']');
    }
    // }}}
    // {{{ testWildcardAndAttributeWithValue
    public function testWildcardAndAttributeWithValue()
    {
        $this->assertCorrectXpathIds(array('6', '9'), '/dpg:pages/pg:page/*[@name = \'Subpage\']');
    }
    // }}}
    // {{{ testWildcardNsAndAttributeWithValue
    public function testWildcardNsAndAttributeWithValue()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath => 2.0
        $this->assertCorrectXpathIdsNoDomXpath(array('6'), '/dpg:pages/pg:page/*:page[@name = \'Subpage\']');
    }
    // }}}
    // {{{ testWildcardNameAndAttributeWithValue
    public function testWildcardNameAndAttributeWithValue()
    {
        $this->assertCorrectXpathIds(array('6', '9'), '/dpg:pages/pg:page/pg:*[@name = \'Subpage\']');
    }
    // }}}
    // {{{ testNoResult
    public function testNoResult()
    {
        $this->assertCorrectXpathIds(array(), '/nonode');
    }
    // }}}
    // {{{ testNameAllAndPosition
    public function testNameAllAndPosition()
    {
        $this->assertCorrectXpathIds(array('8'), '//pg:page[3]');
    }
    // }}}
    // {{{ testNameAllWithAttributeNoResult
    public function testNameAllWithAttributeNoResult()
    {
        $this->assertCorrectXpathIds(array(), '//pg:page[@unknown]');
    }
    // }}}
    // {{{ testNameAllWithAttributeWithValueNoResult
    public function testNameAllWithAttributeWithValueNoResult()
    {
        $this->assertCorrectXpathIds(array(), '//pg:page[@name = \'unknown\']');
    }
    // }}}
    // {{{ testWildCardAndIdAttributeWithValue
    public function testWildCardAndIdAttributeWithValue()
    {
        // can't be verified by DOMXpath. Namespace issue (@id, @dḃ:id)
        $this->assertCorrectXpathIdsNoDomXpath(array('6'), '/*[@id = \'6\']');
    }
    // }}}
    // {{{ testWildCardNsAndIdAttributeWithValue
    public function testWildCardNsAndIdAttributeWithValue()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath => 2.0
        $this->assertCorrectXpathIdsNoDomXpath(array('6'), '/*:page[@id = \'6\']');
    }
    // }}}
    // {{{ testWildCardNameAndIdAttributeWithValue
    public function testWildCardNameAndIdAttributeWithValue()
    {
        // can't be verified by DOMXpath. Namespace issue (@id, @dḃ:id)
        $this->assertCorrectXpathIdsNoDomXpath(array('6'), '/pg:*[@id = \'6\']');
    }
    // }}}
    // {{{ testWildCardAndIdAttributeWithValueNoResult
    public function testWildCardAndIdAttributeWithValueNoResult()
    {
        // can't be verified by DOMXpath. Namespace issue (@id, @dḃ:id)
        $this->assertCorrectXpathIdsNoDomXpath(array(), '/*[@id = \'20\']');
    }
    // }}}
}
