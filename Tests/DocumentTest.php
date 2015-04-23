<?php

class DocumentTest extends Generic_Tests_DatabaseTestCase
{
    protected $xmldb;

    // {{{ setUp
    protected function setUp()
    {
        parent::setUp();

        // get cache instance
        $cache = Depage\Cache\Cache::factory("xmldb", array("disposition" => "uncached"));

        // get xmldb instance
        $this->xmldb = new Depage\XmlDb\XmlDb($this->pdo->prefix . "_proj_test", $this->pdo, $cache, array(
            "root",
            "child",
        ));
    }
    // }}}

    // {{{ testGetSubdocByXpathByNameAll
    public function testGetSubdocByXpathByNameAll()
    {
        $doc = $this->xmldb->getDoc(1);
        $subDoc = $doc->getSubDocByXpath('//pg:page');

        $expected = '<pg:page xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" file_type="html" multilang="true" name="Home" db:dataid="3" db:id="2" db:lastchange="0000-00-00 00:00:00" db:lastchangeUid=""><pg:page file_type="html" multilang="true" name="Subpage" db:dataid="4" db:id="6"/><pg:page file_type="html" multilang="true" name="Subpage 2" db:dataid="5" db:id="7"/><pg:folder file_type="html" multilang="true" name="Subpage" db:dataid="7" db:id="9"/>bla bla blub <pg:page file_type="html" multilang="true" name="bla blub" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page>';

        $this->assertXmlStringEqualsXmlString($expected, $subDoc);
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
