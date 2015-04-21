<?php

class XmlDbTest extends Generic_Tests_DatabaseTestCase
{
    protected $xmldb;

    // {{{ setUp
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
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

    // {{{ testGetDocumentsByName
    public function testGetDocumentsByName() {
        $docs = $this->xmldb->getDocuments('pages');
        $pagesDoc = $docs['pages'];

        $this->assertEquals(array('pages'), array_keys($docs));
        $this->assertInstanceOf('Depage\XmlDb\Document', $pagesDoc);
        $this->assertEquals('pages', $pagesDoc->getDocInfo()->name);
    }
    // }}}
    // {{{ testGetDocuments
    public function testGetDocuments() {
        $docs = $this->xmldb->getDocuments();
        $expectedNames = array(
            'pages',
            'tpl_newnodes',
            'tpl_templates',
        );

        $this->assertEquals($expectedNames, array_keys($docs));

        foreach ($expectedNames as $expectedName) {
            $expectedDoc = $docs[$expectedName];
            $this->assertInstanceOf('Depage\XmlDb\Document', $expectedDoc);
            $this->assertEquals($expectedName, $expectedDoc->getDocInfo()->name);
        }
    }
    // }}}
    // {{{ testDocExists()
    public function testDocExists() {
        $this->assertFalse($this->xmldb->docExists("non existent document"));
        $this->assertFalse($this->xmldb->docExists(100));
        $this->assertEquals(1, $this->xmldb->docExists("pages"));
        $this->assertEquals(1, $this->xmldb->docExists(1));
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
