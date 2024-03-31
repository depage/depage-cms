<?php

namespace Depage\XmlDb\Tests;

abstract class XpathTestCase extends XmlDbTestCase
{
    protected $xmlDb;
    protected $cache;
    protected $doc;
    protected $testObject;

    // {{{ setUp
    protected function setUp():void
    {
        parent::setUp();

        $this->cache = \Depage\Cache\Cache::factory('xmldb', ['disposition' => 'uncached']);
        $this->xmlDb = new XmlDbTestClass($this->pdo->prefix . '_proj_test', $this->pdo, $this->cache, [
            'root',
            'child',
        ]);
        $this->doc = new DocumentTestClass($this->xmlDb, 5);
    }
    // }}}

    // {{{ getNodeIdsByDomXpath
    protected function getNodeIdsByDomXpath($doc, $xpath)
    {
        $ids = [];

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

        $this->assertSame($fallbackCalled, $this->xmlDb->fallbackCall, "Failed asserting that DOMXPath query fallback was called $xpath");
    }
    // }}}

    // {{{ testNoResult
    public function testNoResult()
    {
        $this->assertCorrectXpathIds([], '/nonode');
    }
    // }}}

    // {{{ testGetSubdocByXpathByName
    public function testGetSubdocByXpathByName()
    {
        $subdoc = $this->testObject->getSubdocByXpath('//pg:folder');

        $expected = '<pg:folder xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page" file_type="html" multilang="true" name="F5.1" db:id="17" db:lastchange="2016-02-03 16:09:05" db:lastchangeUid=""><pg:page file_type="html" multilang="true" name="P5.1.1" db:id="18"/><pg:page file_type="html" multilang="false" name="P5.1.2" db:id="19"><pg:folder name="F5.1.2.1" db:id="20"/>bla bla bla <pg:page name="P5.1.2.3" db:id="21">bla bla blub </pg:page></pg:page><pg:page name="P5.1.3" db:id="22"/><page name="P5.1.4" db:id="23"/><dpg:page name="P5.1.5" db:id="24"/><pg:folder name="P5.1.6" db:id="25"/><folder name="P5.1.7" db:id="26"/></pg:folder>';

        $this->assertXmlStringEqualsXmlString($expected, $subdoc);
    }
    // }}}
    // {{{ testGetSubdocByXpathNone
    public function testGetSubdocByXpathNone()
    {
        $this->assertFalse($this->testObject->getSubdocByXpath('//iamnosubdoc'));
    }
    // }}}

    // {{{ testNameChild
    public function testNameChild()
    {
        $this->assertCorrectXpathIds([26], '/dpg:pages/pg:page/pg:folder/folder');
    }
    // }}}
    // {{{ testNameChildAndPositionShort
    public function testNameChildAndPositionShort()
    {
        $this->assertCorrectXpathIds([19], '/dpg:pages/pg:page/pg:folder/pg:page[2]', true, true);
    }
    // }}}
    // {{{ testNameChildAndPositionShortNoResult
    public function testNameChildAndPositionShortNoResult()
    {
        $this->assertCorrectXpathIds([], '/dpg:pages/pg:page[2]', true, true);
    }
    // }}}
    // {{{ testNameChildAndPositionShortMultiple
    public function testNameChildAndPositionShortMultiple()
    {
        $this->assertCorrectXpathIds([28], '/dpg:pages/pg:page[1]/pg:folder[2]', true, true);
    }
    // }}}
    // {{{ testNameChildAndPositionParsing
    public function testNameChildAndPositionParsing()
    {
        $this->assertCorrectXpathIds([19], '/dpg:pages/pg:page/pg:folder/*[position() = 2]', true, true);
        $this->assertCorrectXpathIds([19], '/dpg:pages/pg:page/pg:folder/*[position()= 2]', true, true);
        $this->assertCorrectXpathIds([19], '/dpg:pages/pg:page/pg:folder/*[position() =2]', true, true);
        $this->assertCorrectXpathIds([19], '/dpg:pages/pg:page/pg:folder/*[position()=2]', true, true);
        $this->assertCorrectXpathIds([19], '/dpg:pages/pg:page/pg:folder/*[position()   =   2]', true, true);
        $this->assertCorrectXpathIds([19], '/dpg:pages/pg:page/pg:folder/*[  position()   =   2  ]', true, true);
    }
    // }}}
    // {{{ testNameChildAndPosition
    public function testNameChildAndPosition()
    {
        $this->assertCorrectXpathIds([], '/dpg:pages/pg:page/pg:folder/*[position() = 13]', true, true);
        $this->assertCorrectXpathIds([23], '/dpg:pages/pg:page/pg:folder/*[position() = 4]', true, true);
    }
    // }}}
    // {{{ testNameChildAndPositionNot
    public function testNameChildAndPositionNot()
    {
        $this->assertCorrectXpathIds([18, 19, 22, 23, 24, 25, 26], '/dpg:pages/pg:page/pg:folder/*[position() != 13]', true, true);
        $this->assertCorrectXpathIds([18, 19, 23, 24, 25, 26], '/dpg:pages/pg:page/pg:folder/*[position() != 3]', true, true);
    }
    // }}}
    // {{{ testNameChildAndPositionLessThan
    public function testNameChildAndPositionLessThan()
    {
        $this->assertCorrectXpathIds([], '/dpg:pages/pg:page/pg:folder/*[position() < 1]', true, true);
        $this->assertCorrectXpathIds([18, 19, 22], '/dpg:pages/pg:page/pg:folder/*[position() < 4]', true, true);
    }
    // }}}
    // {{{ testNameChildAndPositionGreaterThan
    public function testNameChildAndPositionGreaterThan()
    {
        $this->assertCorrectXpathIds([], '/dpg:pages/pg:page/pg:folder/*[position() > 7]', true, true);
        $this->assertCorrectXpathIds([24, 25, 26], '/dpg:pages/pg:page/pg:folder/*[position() > 4]', true, true);
    }
    // }}}
    // {{{ testNameChildAndPositionLessThanOrEqualTo
    public function testNameChildAndPositionLessThanOrEqualTo()
    {
        $this->assertCorrectXpathIds([18, 19, 22, 23], '/dpg:pages/pg:page/pg:folder/*[position() <= 4]', false, true);
    }
    // }}}
    // {{{ testNameChildAndPositionGreaterThanOrEqualTo
    public function testNameChildAndPositionGreaterThanOrEqualTo()
    {
        $this->assertCorrectXpathIds([23, 24, 25, 26], '/dpg:pages/pg:page/pg:folder/*[position() >= 4]', false, true);
    }
    // }}}

    // {{{ testNameAndAttribute
    public function testNameAndAttribute()
    {
        $this->assertCorrectXpathIds([18, 19], '/dpg:pages/pg:page/pg:folder/pg:page[@multilang]');
    }
    // }}}
    // {{{ testNameAndAttributeValue
    public function testNameAndAttributeValue()
    {
        $this->assertCorrectXpathIds([19], '/dpg:pages/pg:page/pg:folder/pg:page[@name = \'P5.1.2\']');
    }
    // }}}
    // {{{ testNameAndAttributeAndOperatorValue
    public function testNameAndAttributeAndOperatorValue()
    {
        $this->assertCorrectXpathIds([18, 19], '/dpg:pages/pg:page/pg:folder/pg:page[@file_type = \'html\']');
        $this->assertCorrectXpathIds([18], '/dpg:pages/pg:page/pg:folder/pg:page[@file_type = \'html\' and @multilang = \'true\']');
    }
    // }}}
    // {{{ testNameAndAttributeOrOperatorValue
    public function testNameAndAttributeOrOperatorValue()
    {
        $this->assertCorrectXpathIds([18, 19], '/dpg:pages/pg:page/pg:folder/pg:page[@file_type = \'html\']');
        $this->assertCorrectXpathIds([18, 19], '/dpg:pages/pg:page/pg:folder/pg:page[@file_type = \'html\' or @multilang = \'true\']');
    }
    // }}}

    // {{{ testWildcardAndAttributeValue
    public function testWildcardAndAttributeValue()
    {
        $this->assertCorrectXpathIds([18, 19], '/dpg:pages/pg:page/pg:folder/*[@file_type = \'html\']');
    }
    // }}}
    // {{{ testWildcardNsAndAttributeValue
    public function testWildcardNsAndAttributeValue()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath >= 2.0
        $this->assertCorrectXpathIds([18, 19], '/dpg:pages/pg:page/pg:folder/*:page[@file_type = \'html\']', false);
    }
    // }}}
    // {{{ testWildcardNameAndAttributeValue
    public function testWildcardNameAndAttributeValue()
    {
        $this->assertCorrectXpathIds([18, 19], '/dpg:pages/pg:page/pg:folder/pg:*[@file_type = \'html\']');
    }
    // }}}

    // {{{ testAllName
    public function testAllName()
    {
        $this->assertCorrectXpathIds([24], '//dpg:page');
    }
    // }}}
    // {{{ testAllNameMultiple
    public function testAllNameMultiple()
    {
        $this->assertCorrectXpathIds([18, 19, 22], '//pg:page/pg:folder/pg:page');
    }
    // }}}
    // {{{ testAllNameAttribute
    public function testAllNameAttribute()
    {
        $this->assertCorrectXpathIds([16, 18, 19], '//pg:page[@multilang]');
    }
    // }}}
    // {{{ testAllNameAttributeValue
    public function testAllNameAttributeValue()
    {
        $this->assertCorrectXpathIds([19], '//pg:page[@name = \'P5.1.2\']');
    }
    // }}}
    // {{{ testAllNameAttributeNoResult
    public function testAllNameAttributeNoResult()
    {
        $this->assertCorrectXpathIds([], '//pg:page[@unknown]');
    }
    // }}}
    // {{{ testAllNameAttributeValueNoResult
    public function testAllNameAttributeValueNoResult()
    {
        $this->assertCorrectXpathIds([], '//pg:page[@name = \'unknown\']');
    }
    // }}}
    // {{{ testAllNameAndPosition
    public function testAllNameAndPosition()
    {
        $this->assertCorrectXpathIds([29], '//pg:folder[3]', true, true);
    }
    // }}}
    // {{{ testAllNameAndPositionMultiple
    public function testAllNameAndPositionMultiple()
    {
        $this->assertCorrectXpathIds([17, 20, 25], '//pg:folder[1]', true, true);
    }
    // }}}

    // {{{ testAllWildCardPartial
    public function testAllWildCardPartial()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath >= 2.0
        $this->assertCorrectXpathIds([17, 20, 25, 28, 29], '//*g:f*', false);
    }
    // }}}
    // {{{ testAllWildCardNs
    public function testAllWildCardNs()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath >= 2.0
        $this->assertCorrectXpathIds([17, 20, 25, 28, 29], '//*:folder', false);
    }
    // }}}
    // {{{ testAllWildCardAndIdAttributeValue
    public function testAllWildCardAndIdAttributeValue()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIds([16], '//*[@db:id = \'16\']', false);
    }
    // }}}
    // {{{ testAllWildCardNsAndIdAttributeValue
    public function testAllWildCardNsAndIdAttributeValue()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath >= 2.0
        $this->assertCorrectXpathIds([16], '//*:page[@db:id = \'16\']', false);
    }
    // }}}
    // {{{ testAllWildCardNameAndIdAttributeValue
    public function testAllWildCardNameAndIdAttributeValue()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIds([16], '//pg:*[@db:id = \'16\']', false);
    }
    // }}}
    // {{{ testAllWildCardAndIdAttributeValueNoResult
    public function testAllWildCardAndIdAttributeValueNoResult()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIds([], '//*[@db:id = \'42\']', false);
    }
    // }}}

    // {{{ testAllLast
    public function testAllLast()
    {
        $this->assertCorrectXpathIds([20, 25, 29], '//pg:folder[last()]', true, true);
    }
    // }}}
    // {{{ testArbitraryDescendants
    public function testArbitraryDescendants()
    {
        $this->assertCorrectXpathIds([17, 20, 28, 29], '/dpg:pages//pg:page/pg:folder', true, true);
    }
    // }}}

    // {{{ testInvalidXpathOperator
    public function testInvalidXpathOperator()
    {
        $this->expectException(\Depage\XmlDb\Exceptions\XmlDbException::class);
        $this->expectExceptionMessage("Invalid XPath syntax");

        $this->testObject->getNodeIdsByXpath('//pg:page[@file_type = \'html\' op @multilang = \'true\']');
    }
    // }}}
}
