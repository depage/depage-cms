<?php

class DocumentTest extends Generic_Tests_DatabaseTestCase
{
    // {{{ variables
    protected $xmlDb;
    protected $doc;
    // }}}
    // {{{ setUp
    protected function setUp()
    {
        parent::setUp();

        // get cache instance
        $cache = Depage\Cache\Cache::factory('xmlDb', array('disposition' => 'uncached'));

        // get xmldb instance
        $this->xmlDb = new Depage\XmlDb\XmlDb($this->pdo->prefix . '_proj_test', $this->pdo, $cache, array(
            'root',
            'child',
        ));

        $this->doc = $this->xmlDb->getDoc(1);
    }
    // }}}

    // {{{ testGetSubDocByXpathByNameAll
    public function testGetSubDocByXpathByNameAll()
    {
        $subDoc = $this->doc->getSubDocByXpath('//pg:page');

        $expected = '<pg:page xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" file_type="html" multilang="true" name="Home" db:dataid="3" db:id="2" db:lastchange="0000-00-00 00:00:00" db:lastchangeUid=""><pg:page file_type="html" multilang="true" name="Subpage" db:dataid="4" db:id="6"/><pg:page file_type="html" multilang="true" name="Subpage 2" db:dataid="5" db:id="7"/><pg:folder file_type="html" multilang="true" name="Subpage" db:dataid="7" db:id="9"/>bla bla blub <pg:page file_type="html" multilang="true" name="bla blub" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page>';

        $this->assertXmlStringEqualsXmlString($expected, $subDoc);
    }
    // }}}

    // {{{ testGetNodeIdsByXpathByNameAll
    public function testGetNodeIdsByXpathByNameAll()
    {
        $ids = $this->doc->getNodeIdsByXpath('//pg:page');

        $this->assertEquals(array('2', '6', '7', '8'), $ids);
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameAllWithAttribute
    public function testGetNodeIdsByXpathByNameAllWithAttribute()
    {
        $ids = $this->doc->getNodeIdsByXpath('//pg:page[@name = \'bla blub\']');

        $this->assertEquals(array('8'), $ids);
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameAllWithChild
    public function testGetNodeIdsByXpathByNameAllWithChild()
    {
        $ids = $this->doc->getNodeIdsByXpath('/dpg:pages/pg:page');

        $this->assertEquals(array('2'), $ids);
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameAndPosition
    public function testGetNodeIdsByXpathByNameAndPosition()
    {
        $ids = $this->doc->getNodeIdsByXpath('/dpg:pages/pg:page/pg:page[3]');

        $this->assertEquals(array('8'), $ids);
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameAndAttribute
    public function testGetNodeIdsByXpathByNameAndAttribute()
    {
        $ids = $this->doc->getNodeIdsByXpath('/dpg:pages/pg:page/pg:page[@name]');

        $this->assertEquals(array('6', '7', '8'), $ids);
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByNameAndAttributeWithValue
    public function testGetNodeIdsByXpathByNameAndAttributeWithValue()
    {
        $ids = $this->doc->getNodeIdsByXpath('/dpg:pages/pg:page/pg:page[@name = \'Subpage\']');

        $this->assertEquals(array('6'), $ids);
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByWildcardAndAttributeWithValue
    public function testGetNodeIdsByXpathByWildcardAndAttributeWithValue()
    {
        $ids = $this->doc->getNodeIdsByXpath('/dpg:pages/pg:page/*[@name = \'Subpage\']');

        $this->assertEquals(array('6', '9'), $ids);
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByWildcardNsAndAttributeWithValue
    public function testGetNodeIdsByXpathByWildcardNsAndAttributeWithValue()
    {
        $ids = $this->doc->getNodeIdsByXpath('/dpg:pages/pg:page/*:page[@name = \'Subpage\']');

        $this->assertEquals(array('6'), $ids);
    }
    // }}}
    // {{{ testGetNodeIdsByXpathByWildcardNameAndAttributeWithValue
    public function testGetNodeIdsByXpathByWildcardNameAndAttributeWithValue()
    {
        $ids = $this->doc->getNodeIdsByXpath('/dpg:pages/pg:page/pg:*[@name = \'Subpage\']');

        $this->assertEquals(array('6', '9'), $ids);
    }
    // }}}
    // {{{ testGetNodeIdsByXpathNoResult
    public function testGetNodeIdsByXpathNoResult()
    {
        $ids = $this->doc->getNodeIdsByXpath('/nonode');

        $this->assertEquals(array(), $ids);
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
