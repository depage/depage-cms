<?php

namespace Depage\XmlDb\Tests;

abstract class XpathTestCase extends DatabaseTestCase
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
        $domXpathIds = $this->getNodeIdsByDomXpath($object, $xpath);
        sort($domXpathIds);

        $this->assertEquals($expectedIds, $domXpathIds, "Failed asserting that expected IDs match DOMXPath query node IDs. Is the test set up correctly for XPath query $xpath ?");
        $this->assertCorrectXpathIdsNoDomXpath($expectedIds, $xpath);
    }
    // }}}
    // {{{ assertCorrectXpathIdsNoDomXpath
    protected function assertCorrectXpathIdsNoDomXpath(array $expectedIds, $xpath)
    {
        $object = $this->getTestObject();
        $actualIds = $object->getNodeIdsByXpath($xpath);
        sort($actualIds);

        $this->assertEquals($expectedIds, $actualIds, "Failed asserting that ID arrays match for XPath query $xpath");
    }
    // }}}

    // {{{ testNoResult
    public function testNoResult()
    {
        $this->assertCorrectXpathIds(array(), '/nonode');
    }
    // }}}

    // {{{ testNameWithChild
    public function testNameWithChild()
    {
        $this->assertCorrectXpathIds(array(2), '/dpg:pages/pg:page');
    }
    // }}}
    // {{{ testNameWithChildAndPosition
    public function testNameWithChildAndPosition()
    {
        $this->assertCorrectXpathIds(array(8), '/dpg:pages/pg:page/pg:page[3]');
    }
    // }}}
    // {{{ testNameAndAttribute
    public function testNameAndAttribute()
    {
        $this->assertCorrectXpathIds(array(6, 7, 8), '/dpg:pages/pg:page/pg:page[@name]');
    }
    // }}}
    // {{{ testNameAndAttributeWithValue
    public function testNameAndAttributeWithValue()
    {
        $this->assertCorrectXpathIds(array(6), '/dpg:pages/pg:page/pg:page[@name = \'Subpage\']');
    }
    // }}}

    // {{{ testWildcardAndAttributeWithValue
    public function testWildcardAndAttributeWithValue()
    {
        $this->assertCorrectXpathIds(array(6, 9), '/dpg:pages/pg:page/*[@name = \'Subpage\']');
    }
    // }}}
    // {{{ testWildcardNsAndAttributeWithValue
    public function testWildcardNsAndAttributeWithValue()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath => 2.0
        $this->assertCorrectXpathIdsNoDomXpath(array(6), '/dpg:pages/pg:page/*:page[@name = \'Subpage\']');
    }
    // }}}
    // {{{ testWildcardNameAndAttributeWithValue
    public function testWildcardNameAndAttributeWithValue()
    {
        $this->assertCorrectXpathIds(array(6, 9), '/dpg:pages/pg:page/pg:*[@name = \'Subpage\']');
    }
    // }}}

    // {{{ testAllName
    public function testAllName()
    {
        $this->assertCorrectXpathIds(array(2, 6, 7, 8), '//pg:page');
    }
    // }}}
    // {{{ testAllNameWithAttribute
    public function testAllNameWithAttribute()
    {
        $this->assertCorrectXpathIds(array(2, 6, 7, 8), '//pg:page[@name]');
    }
    // }}}
    // {{{ testAllNameWithAttributeWithValue
    public function testAllNameWithAttributeWithValue()
    {
        $this->assertCorrectXpathIds(array(8), '//pg:page[@name = \'bla blub\']');
    }
    // }}}
    // {{{ testAllNameWithAttributeNoResult
    public function testAllNameWithAttributeNoResult()
    {
        $this->assertCorrectXpathIds(array(), '//pg:page[@unknown]');
    }
    // }}}
    // {{{ testAllNameWithAttributeWithValueNoResult
    public function testAllNameWithAttributeWithValueNoResult()
    {
        $this->assertCorrectXpathIds(array(), '//pg:page[@name = \'unknown\']');
    }
    // }}}
    // {{{ testAllNameAndPosition
    public function testAllNameAndPosition()
    {
        $this->assertCorrectXpathIds(array(8), '//pg:page[3]');
    }
    // }}}

    // {{{ testAllWildCardNs
    public function testAllWildCardNs()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath => 2.0
        $this->assertCorrectXpathIdsNoDomXpath(array(2, 6, 7, 8), '//*:page');
    }
    // }}}
    // {{{ testAllWildCardName
    public function testAllWildCardName()
    {
        $this->assertCorrectXpathIds(array(2, 6, 7, 8, 9), '//pg:*');
    }
    // }}}
    // {{{ testAllWildCardAndIdAttributeWithValue
    public function testAllWildCardAndIdAttributeWithValue()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIdsNoDomXpath(array(6), '//*[@db:id = \'6\']');
    }
    // }}}
    // {{{ testAllWildCardNsAndIdAttributeWithValue
    public function testAllWildCardNsAndIdAttributeWithValue()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath => 2.0
        $this->assertCorrectXpathIdsNoDomXpath(array(6), '//*:page[@db:id = \'6\']');
    }
    // }}}
    // {{{ testAllWildCardNameAndIdAttributeWithValue
    public function testAllWildCardNameAndIdAttributeWithValue()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIdsNoDomXpath(array(6), '//pg:*[@db:id = \'6\']');
    }
    // }}}
    // {{{ testAllWildCardAndIdAttributeWithValueNoResult
    public function testAllWildCardAndIdAttributeWithValueNoResult()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIdsNoDomXpath(array(), '//*[@db:id = \'20\']');
    }
    // }}}
}
