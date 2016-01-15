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
        $this->xmldb = new XmlDbTestClass($this->pdo->prefix . '_proj_test', $this->pdo, $this->cache, array(
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
        $this->assertCorrectXpathIdsNoFallbackTest($expectedIds, $xpath);
        $this->assertFalse($this->xmldb->fallbackCall);
    }
    // }}}
    // {{{ assertCorrectXpathIdsWithFallback
    protected function assertCorrectXpathIdsWithFallback(array $expectedIds, $xpath)
    {
        $this->assertCorrectXpathIdsNoFallbackTest($expectedIds, $xpath);
        $this->assertTrue($this->xmldb->fallbackCall);
    }
    // }}}
    // {{{ assertCorrectXpathIdsNoFallbackTest
    protected function assertCorrectXpathIdsNoFallbackTest(array $expectedIds, $xpath)
    {
        $domXpathIds = $this->getNodeIdsByDomXpath($this->testObject, $xpath);
        sort($domXpathIds);

        $this->assertEquals($expectedIds, $domXpathIds, "Failed asserting that expected IDs match DOMXPath query node IDs. Is the test set up correctly for XPath query $xpath ?");
        $this->assertCorrectXpathIdsNoDomXpathNoFallbackTest($expectedIds, $xpath);
    }
    // }}}
    // {{{ assertCorrectXpathIdsNoDomXpath
    protected function assertCorrectXpathIdsNoDomXpath(array $expectedIds, $xpath)
    {
        $this->assertCorrectXpathIdsNoDomXpathNoFallbackTest($expectedIds, $xpath);
        $this->assertFalse($this->xmldb->fallbackCall);
    }
    // }}}
    // {{{ assertCorrectXpathIdsNoDomXpathNoFallbackTest
    protected function assertCorrectXpathIdsNoDomXpathNoFallbackTest(array $expectedIds, $xpath)
    {
        $actualIds = $this->testObject->getNodeIdsByXpath($xpath);
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

    // {{{ testGetSubDocByXpathByName
    public function testGetSubDocByXpathByName()
    {
        $subDoc = $this->testObject->getSubDocByXpath('//pg:folder');

        $expected = '<pg:folder xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" file_type="html" multilang="true" name="Subpage" db:dataid="7" db:id="9" db:lastchange="0000-00-00 00:00:00" db:lastchangeUid=""/>';

        $this->assertXmlStringEqualsXmlString($expected, $subDoc);
    }
    // }}}
    // {{{ testGetSubDocByXpathNone
    public function testGetSubDocByXpathNone()
    {
        $this->assertFalse($this->testObject->getSubDocByXpath('//iamnosubdoc'));
    }
    // }}}

    // {{{ testNameChild
    public function testNameChild()
    {
        $this->assertCorrectXpathIds(array(2), '/dpg:pages/pg:page');
    }
    // }}}
    // {{{ testNameChildAndPosition
    public function testNameChildAndPosition()
    {
        $this->assertCorrectXpathIds(array(8), '/dpg:pages/pg:page/pg:page[3]');
    }
    // }}}
    // {{{ testNameChildAndPositionNoResult
    public function testNameChildAndPositionNoResult()
    {
        $this->assertCorrectXpathIds(array(), '/dpg:pages/pg:page[2]');
    }
    // }}}
    // {{{ testNameChildAndPositionMultiple
    public function testNameChildAndPositionMultiple()
    {
        $this->assertCorrectXpathIds(array(7), '/dpg:pages/pg:page[1]/pg:page[2]');
    }
    // }}}
    // {{{ testNameAndAttribute
    public function testNameAndAttribute()
    {
        $this->assertCorrectXpathIds(array(6, 7, 8), '/dpg:pages/pg:page/pg:page[@name]');
    }
    // }}}
    // {{{ testNameAndAttributeValue
    public function testNameAndAttributeValue()
    {
        $this->assertCorrectXpathIds(array(6), '/dpg:pages/pg:page/pg:page[@name = \'Subpage\']');
    }
    // }}}
    // {{{ testNameAndAttributeAndOperatorValue
    public function testNameAndAttributeAndOperatorValue()
    {
        $this->assertCorrectXpathIds(array(6, 7, 8), '/dpg:pages/pg:page/pg:page[@multilang = \'true\']');
        $this->assertCorrectXpathIds(array(7), '/dpg:pages/pg:page/pg:page[@multilang = \'true\' and @name = \'Subpage 2\']');
    }
    // }}}
    // {{{ testNameAndAttributeOrOperatorValue
    public function testNameAndAttributeOrOperatorValue()
    {
        $this->assertCorrectXpathIds(array(6), '/dpg:pages/pg:page/pg:page[@name = \'Subpage\']');
        $this->assertCorrectXpathIds(array(6, 7), '/dpg:pages/pg:page/pg:page[@name = \'Subpage\' or @name = \'Subpage 2\']');
    }
    // }}}

    // {{{ testWildcardAndAttributeValue
    public function testWildcardAndAttributeValue()
    {
        $this->assertCorrectXpathIds(array(6, 9), '/dpg:pages/pg:page/*[@name = \'Subpage\']');
    }
    // }}}
    // {{{ testWildcardNsAndAttributeValue
    public function testWildcardNsAndAttributeValue()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath => 2.0
        $this->assertCorrectXpathIdsNoDomXpath(array(6), '/dpg:pages/pg:page/*:page[@name = \'Subpage\']');
    }
    // }}}
    // {{{ testWildcardNameAndAttributeValue
    public function testWildcardNameAndAttributeValue()
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
    // {{{ testAllNameMultiple
    public function testAllNameMultiple()
    {
        $this->assertCorrectXpathIds(array(9), '//pg:page/pg:folder');
    }
    // }}}
    // {{{ testAllNameAttribute
    public function testAllNameAttribute()
    {
        $this->assertCorrectXpathIds(array(2, 6, 7, 8), '//pg:page[@name]');
    }
    // }}}
    // {{{ testAllNameAttributeValue
    public function testAllNameAttributeValue()
    {
        $this->assertCorrectXpathIds(array(8), '//pg:page[@name = \'bla blub\']');
    }
    // }}}
    // {{{ testAllNameAttributeNoResult
    public function testAllNameAttributeNoResult()
    {
        $this->assertCorrectXpathIds(array(), '//pg:page[@unknown]');
    }
    // }}}
    // {{{ testAllNameAttributeValueNoResult
    public function testAllNameAttributeValueNoResult()
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
    // {{{ testAllNameAndPositionMultiple
    public function testAllNameAndPositionMultiple()
    {
        $this->assertCorrectXpathIds(array(2, 6), '//pg:page[1]');
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
    // {{{ testAllWildCardAndIdAttributeValue
    public function testAllWildCardAndIdAttributeValue()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIdsNoDomXpath(array(6), '//*[@db:id = \'6\']');
    }
    // }}}
    // {{{ testAllWildCardNsAndIdAttributeValue
    public function testAllWildCardNsAndIdAttributeValue()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath => 2.0
        $this->assertCorrectXpathIdsNoDomXpath(array(6), '//*:page[@db:id = \'6\']');
    }
    // }}}
    // {{{ testAllWildCardNameAndIdAttributeValue
    public function testAllWildCardNameAndIdAttributeValue()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIdsNoDomXpath(array(6), '//pg:*[@db:id = \'6\']');
    }
    // }}}
    // {{{ testAllWildCardAndIdAttributeValueNoResult
    public function testAllWildCardAndIdAttributeValueNoResult()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIdsNoDomXpath(array(), '//*[@db:id = \'20\']');
    }
    // }}}

    // {{{ testAllLast
    public function testAllLast()
    {
        $this->assertCorrectXpathIdsWithFallback(array(2, 8), '//pg:page[last()]');
    }
    // }}}
    // {{{ testAllIdLessThan
    public function testAllLessThan()
    {
        $this->assertCorrectXpathIdsWithFallback(array(2, 6), '//pg:page[@db:dataid < \'5\']');
    }
    // }}}
}
