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
        $this->doc = new DocumentTestClass($this->xmldb, 5);
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
    protected function assertCorrectXpathIds(array $expectedIds, $xpath, $domTest = true, $fallbackCalled = false)
    {
        if ($domTest) {
            $domXpathIds = $this->getNodeIdsByDomXpath($this->testObject, $xpath);
            sort($domXpathIds);
            $this->assertEquals($expectedIds, $domXpathIds, "Failed asserting that expected IDs match DOMXPath query node IDs. Is the test set up correctly for XPath query $xpath ?");
        }

        $actualIds = $this->testObject->getNodeIdsByXpath($xpath);
        sort($actualIds);
        $this->assertEquals($expectedIds, $actualIds, "Failed asserting that ID arrays match for XPath query $xpath");

        $this->assertSame($fallbackCalled, $this->xmldb->fallbackCall);
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

        $expected = '<pg:folder xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" file_type="html" multilang="true" name="F5.1" db:id="17" db:lastchange="2016-02-03 16:09:05" db:lastchangeUid=""><pg:page file_type="html" multilang="true" name="P5.1.1" db:id="18"/><pg:page file_type="html" multilang="false" name="P5.1.2" db:id="19"><pg:folder name="F5.1.2.1" db:id="20"/>bla bla bla <pg:page name="P5.1.2.3" db:id="21">bla bla blub </pg:page></pg:page><pg:page name="P5.1.3" db:id="22"/><page name="P5.1.4" db:id="23"/><dpg:page name="P5.1.5" db:id="24"/><pg:folder name="P5.1.6" db:id="25"/><folder name="P5.1.7" db:id="26"/></pg:folder>';

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
        $this->assertCorrectXpathIds(array(26), '/dpg:pages/pg:page/pg:folder/folder');
    }
    // }}}
    // {{{ testNameChildAndPositionShort
    public function testNameChildAndPositionShort()
    {
        $this->assertCorrectXpathIds(array(19), '/dpg:pages/pg:page/pg:folder/pg:page[2]');
    }
    // }}}
    // {{{ testNameChildAndPositionShortNoResult
    public function testNameChildAndPositionShortNoResult()
    {
        $this->assertCorrectXpathIds(array(), '/dpg:pages/pg:page[2]');
    }
    // }}}
    // {{{ testNameChildAndPositionShortMultiple
    public function testNameChildAndPositionShortMultiple()
    {
        $this->assertCorrectXpathIds(array(28), '/dpg:pages/pg:page[1]/pg:folder[2]');
    }
    // }}}
    // {{{ testNameChildAndPositionParsing
    public function testNameChildAndPositionParsing()
    {
        $this->assertCorrectXpathIds(array(19), '/dpg:pages/pg:page/pg:folder/*[position() = 2]');
        $this->assertCorrectXpathIds(array(19), '/dpg:pages/pg:page/pg:folder/*[position()= 2]');
        $this->assertCorrectXpathIds(array(19), '/dpg:pages/pg:page/pg:folder/*[position() =2]');
        $this->assertCorrectXpathIds(array(19), '/dpg:pages/pg:page/pg:folder/*[position()=2]');
        $this->assertCorrectXpathIds(array(19), '/dpg:pages/pg:page/pg:folder/*[position()   =   2]');
        $this->assertCorrectXpathIds(array(19), '/dpg:pages/pg:page/pg:folder/*[  position()   =   2  ]');
    }
    // }}}
    // {{{ testNameChildAndPosition
    public function testNameChildAndPosition()
    {
        $this->assertCorrectXpathIds(array(), '/dpg:pages/pg:page/pg:folder/*[position() = 13]');
        $this->assertCorrectXpathIds(array(23), '/dpg:pages/pg:page/pg:folder/*[position() = 4]');
    }
    // }}}
    // {{{ testNameChildAndPositionNot
    public function testNameChildAndPositionNot()
    {
        $this->assertCorrectXpathIds(array(18, 19, 22, 23, 24, 25, 26), '/dpg:pages/pg:page/pg:folder/*[position() != 13]');
        $this->assertCorrectXpathIds(array(18, 19, 23, 24, 25, 26), '/dpg:pages/pg:page/pg:folder/*[position() != 3]');
    }
    // }}}
    // {{{ testNameChildAndPositionLessThan
    public function testNameChildAndPositionLessThan()
    {
        $this->assertCorrectXpathIds(array(), '/dpg:pages/pg:page/pg:folder/*[position() < 1]');
        $this->assertCorrectXpathIds(array(18, 19, 22), '/dpg:pages/pg:page/pg:folder/*[position() < 4]');
    }
    // }}}
    // {{{ testNameChildAndPositionGreaterThan
    public function testNameChildAndPositionGreaterThan()
    {
        $this->assertCorrectXpathIds(array(), '/dpg:pages/pg:page/pg:folder/*[position() > 7]');
        $this->assertCorrectXpathIds(array(24, 25, 26), '/dpg:pages/pg:page/pg:folder/*[position() > 4]');
    }
    // }}}
    // {{{ testNameChildAndPositionLessThanOrEqualTo
    public function testNameChildAndPositionLessThanOrEqualTo()
    {
        $this->assertCorrectXpathIds(array(18, 19, 22, 23), '/dpg:pages/pg:page/pg:folder/*[position() <= 4]', false);
    }
    // }}}
    // {{{ testNameChildAndPositionGreaterThanOrEqualTo
    public function testNameChildAndPositionGreaterThanOrEqualTo()
    {
        $this->assertCorrectXpathIds(array(23, 24, 25, 26), '/dpg:pages/pg:page/pg:folder/*[position() >= 4]', false);
    }
    // }}}

    // {{{ testNameAndAttribute
    public function testNameAndAttribute()
    {
        $this->assertCorrectXpathIds(array(18, 19), '/dpg:pages/pg:page/pg:folder/pg:page[@multilang]');
    }
    // }}}
    // {{{ testNameAndAttributeValue
    public function testNameAndAttributeValue()
    {
        $this->assertCorrectXpathIds(array(19), '/dpg:pages/pg:page/pg:folder/pg:page[@name = \'P5.1.2\']');
    }
    // }}}
    // {{{ testNameAndAttributeAndOperatorValue
    public function testNameAndAttributeAndOperatorValue()
    {
        $this->assertCorrectXpathIds(array(18, 19), '/dpg:pages/pg:page/pg:folder/pg:page[@file_type = \'html\']');
        $this->assertCorrectXpathIds(array(18), '/dpg:pages/pg:page/pg:folder/pg:page[@file_type = \'html\' and @multilang = \'true\']');
    }
    // }}}
    // {{{ testNameAndAttributeOrOperatorValue
    public function testNameAndAttributeOrOperatorValue()
    {
        $this->assertCorrectXpathIds(array(18, 19), '/dpg:pages/pg:page/pg:folder/pg:page[@file_type = \'html\']');
        $this->assertCorrectXpathIds(array(18, 19), '/dpg:pages/pg:page/pg:folder/pg:page[@file_type = \'html\' or @multilang = \'true\']');
    }
    // }}}

    // {{{ testWildcardAndAttributeValue
    public function testWildcardAndAttributeValue()
    {
        $this->assertCorrectXpathIds(array(18, 19), '/dpg:pages/pg:page/pg:folder/*[@file_type = \'html\']');
    }
    // }}}
    // {{{ testWildcardNsAndAttributeValue
    public function testWildcardNsAndAttributeValue()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath >= 2.0
        $this->assertCorrectXpathIds(array(18, 19), '/dpg:pages/pg:page/pg:folder/*:page[@file_type = \'html\']', false);
    }
    // }}}
    // {{{ testWildcardNameAndAttributeValue
    public function testWildcardNameAndAttributeValue()
    {
        $this->assertCorrectXpathIds(array(18, 19), '/dpg:pages/pg:page/pg:folder/pg:*[@file_type = \'html\']');
    }
    // }}}

    // {{{ testAllName
    public function testAllName()
    {
        $this->assertCorrectXpathIds(array(24), '//dpg:page');
    }
    // }}}
    // {{{ testAllNameMultiple
    public function testAllNameMultiple()
    {
        $this->assertCorrectXpathIds(array(18, 19, 22), '//pg:page/pg:folder/pg:page');
    }
    // }}}
    // {{{ testAllNameAttribute
    public function testAllNameAttribute()
    {
        $this->assertCorrectXpathIds(array(16, 18, 19), '//pg:page[@multilang]');
    }
    // }}}
    // {{{ testAllNameAttributeValue
    public function testAllNameAttributeValue()
    {
        $this->assertCorrectXpathIds(array(19), '//pg:page[@name = \'P5.1.2\']');
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
        $this->assertCorrectXpathIds(array(29), '//pg:folder[3]');
    }
    // }}}
    // {{{ testAllNameAndPositionMultiple
    public function testAllNameAndPositionMultiple()
    {
        $this->assertCorrectXpathIds(array(17, 20, 25), '//pg:folder[1]');
    }
    // }}}

    // {{{ testAllWildCardAndIdAttributeValue
    public function testAllWildCardAndIdAttributeValue()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIds(array(16), '//*[@db:id = \'16\']', false);
    }
    // }}}
    // {{{ testAllWildCardNsAndIdAttributeValue
    public function testAllWildCardNsAndIdAttributeValue()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath => 2.0
        $this->assertCorrectXpathIds(array(16), '//*:page[@db:id = \'16\']', false);
    }
    // }}}
    // {{{ testAllWildCardNameAndIdAttributeValue
    public function testAllWildCardNameAndIdAttributeValue()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIds(array(16), '//pg:*[@db:id = \'16\']', false);
    }
    // }}}
    // {{{ testAllWildCardAndIdAttributeValueNoResult
    public function testAllWildCardAndIdAttributeValueNoResult()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIds(array(), '//*[@db:id = \'42\']', false);
    }
    // }}}

    // {{{ testAllLast
    public function testAllLast()
    {
        $this->assertCorrectXpathIds(array(20, 25, 29), '//pg:folder[last()]', true, true);
    }
    // }}}
    // {{{ testArbitraryDescendants
    public function testArbitraryDescendants()
    {
        $this->assertCorrectXpathIds(array(17, 20, 28, 29), '/dpg:pages//pg:page/pg:folder', true, true);
    }
    // }}}
}
