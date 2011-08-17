<?php
require_once '../www/framework/depage/depage.php';

define("DEPAGE_BASE", "");

spl_autoload_register("depage::autoload");

/**
 * Test class for xmldb.
 */
class xmldbTest extends \PHPUnit_Extensions_Database_TestCase
{
    /**
     * @var xmldb
     */
    protected $xmldb;

    // {{{ setUp()
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();

        // get database instance
        $pdo = new db_pdo (
            "mysql:dbname=depage_phpunit;host=localhost",
            "root",
            "",
            array(
                'prefix' => "xmldb", // database prefix
                \PDO::ATTR_PERSISTENT => true,
            )
        );

        // get cache instance
        $cache = depage\cache\cache::factory("xmldb", array(
            'disposition' => "uncached",
        ));

        // get xmldb instance
        $this->xmldb = new depage\xmldb\xmldb($pdo->prefix . "_proj_test", $pdo, $cache, array(
            "root",
            "child",
        ));
    }
    // }}}
    // {{{ tearDown()
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        //unset($this->xmldb);

        parent::tearDown();
    }
    // }}}
    // {{{ getConnection()
    /**
     * gets database connection
     */
    protected function getConnection() {
        $pdo = new pdo("mysql:dbname=depage_phpunit;host=localhost", "root", "", array(
            \PDO::ATTR_PERSISTENT => true,
        ));

        return $this->createDefaultDBConnection($pdo, 'testdb');
    }
    // }}}
    // {{{ getDataSet()
    /**
     * gets dataset
     */
    protected function getDataSet() {
        return $this->createXMLDataSet(__DIR__.'/xmldb_dataset.xml');
    }
    // }}}
    
    // {{{ testGet_doc_list()
    public function testGet_doc_list() {
        // get list for one document
        $docs = $this->xmldb->get_doc_list("pages");

        $this->assertEquals(array(
            'pages' => (object) array(
                'name' => 'pages',
                'id' => '1',
                'rootid' => '1',
            ),
        ), $docs);

        // get list of all documents
        $docs = $this->xmldb->get_doc_list();

        $this->assertEquals(array(
            'pages' => (object) array(
                'name' => 'pages',
                'id' => '1',
                'rootid' => '1',
            ),
            'tpl_newnodes' => (object) array(
                'name' => 'tpl_newnodes',
                'id' => '3',
                'rootid' => '5',
            ),
            'tpl_templates' => (object) array(
                'name' => 'tpl_templates',
                'id' => '2',
                'rootid' => '3',
            ),
        ), $docs);
    }
    // }}}
    // {{{ testGet_doc_info_by_id()
    public function testGet_doc_info_by_id() {
        $info = $this->xmldb->get_doc_info(1);

        $this->assertEquals((object) array(
            'name' => 'pages',
            'id' => '1',
            'rootid' => '1',
        ), $info);
    }
    // }}}
    // {{{ testGet_doc_info_by_name()
    public function testGet_doc_info_by_name() {
        $info = $this->xmldb->get_doc_info("pages");

        $this->assertEquals((object) array(
            'name' => 'pages',
            'id' => '1',
            'rootid' => '1',
        ), $info);
    }
    // }}}
    // {{{ testDoc_exists()
    public function testDoc_exists() {
        $this->assertFalse($this->xmldb->doc_exists("non existent document"));
        $this->assertFalse($this->xmldb->doc_exists(100));
        $this->assertEquals(1, $this->xmldb->doc_exists("pages"));
        $this->assertEquals(1, $this->xmldb->doc_exists(1));
    }
    // }}}
    
    // {{{ testGet_doc()
    public function testGet_doc() {
        $xml = $this->xmldb->get_doc("pages");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>', $xml->saveXML());
    }
    // }}}
    // {{{ testGet_doc_non_existent()
    /**
     * @todo Implement testGet_doc().
     */
    public function testGet_doc_non_existent() {
        $xml = $this->xmldb->get_doc("non existing document");

        $this->assertFalse($xml);
    }
    // }}}
    
    // {{{ testSave_doc_element_nodes()
    public function testSave_doc_element_nodes() {
        $xml_str = '<?xml version="1.0"?>
<root xmlns:db="http://cms.depagecms.net/ns/database"><child></child><child/><child/></root>';

        $xml = new \DOMDocument;
        $xml->loadXML($xml_str);

        $this->xmldb->save_doc("testdoc", $xml);
        $saved_xml = $this->xmldb->get_doc("testdoc", false);

        $this->assertXmlStringEqualsXmlString($xml_str, $saved_xml->saveXML());
    }
    // }}}
    // {{{ testSave_doc_element_nodes_many()
    public function testSave_doc_element_nodes_many() {
        $nodes = '';
        for ($i = 0; $i < 10; $i++) {
            $nodes .= '<child></child><child/><child></child><child></child>text<child/><child/>text<child/><child/><child/>';
        }
        $xml_str = '<?xml version="1.0"?>
<root xmlns:db="http://cms.depagecms.net/ns/database">' . $nodes . '</root>';

        $xml = new \DOMDocument;
        $xml->loadXML($xml_str);

        $this->xmldb->save_doc("testdoc", $xml);
        $saved_xml = $this->xmldb->get_doc("testdoc", false);

        $this->assertXmlStringEqualsXmlString($xml_str, $saved_xml->saveXML());
    }
    // }}}
    // {{{ testSave_doc_element_nodes_with_attribute()
    public function testSave_doc_element_nodes_with_attribute() {
        $xml_str = '<?xml version="1.0"?>
<root xmlns:db="http://cms.depagecms.net/ns/database"><child attr="test"></child></root>';

        $xml = new \DOMDocument;
        $xml->loadXML($xml_str);

        $this->xmldb->save_doc("testdoc", $xml);
        $saved_xml = $this->xmldb->get_doc("testdoc", false);

        $this->assertXmlStringEqualsXmlString($xml_str, $saved_xml->saveXML());
    }
    // }}}
    // {{{ testSave_doc_element_nodes_with_namespaces()
    public function testSave_doc_element_nodes_with_namespaces() {
        $xml_str = '<?xml version="1.0"?>
<root xmlns:db="http://cms.depagecms.net/ns/database"><db:child attr="test"></db:child><child db:data="blub" /></root>';

        $xml = new \DOMDocument;
        $xml->loadXML($xml_str);

        $this->xmldb->save_doc("testdoc", $xml);
        $saved_xml = $this->xmldb->get_doc("testdoc", false);

        $this->assertXmlStringEqualsXmlString($xml_str, $saved_xml->saveXML());
    }
    // }}}
    // {{{ testSave_doc_text_nodes()
    public function testSave_doc_text_nodes() {
        $xml_str = '<?xml version="1.0"?>
<root xmlns:db="http://cms.depagecms.net/ns/database"><child>bla</child>blub<b/><c/><child>bla</child></root>';

        $xml = new \DOMDocument;
        $xml->loadXML($xml_str);

        $this->xmldb->save_doc("testdoc", $xml);
        $saved_xml = $this->xmldb->get_doc("testdoc", false);

        $this->assertXmlStringEqualsXmlString($xml_str, $saved_xml->saveXML());
    }
    // }}}
    // {{{ testSave_doc_pi_node()
    public function testSave_doc_pi_node() {
        $xml_str = '<?xml version="1.0"?>
            <root xmlns:db="http://cms.depagecms.net/ns/database"><?php echo("bla"); ?></root>';

        $xml = new \DOMDocument;
        $xml->loadXML($xml_str);

        $this->xmldb->save_doc("testdoc", $xml);
        $saved_xml = $this->xmldb->get_doc("testdoc", false);

        $this->assertXmlStringEqualsXmlString($xml_str, $saved_xml->saveXML());
    }
    // }}}
    // {{{ testSave_doc_comment_node()
    public function testSave_doc_comment_node() {
        $xml_str = '<?xml version="1.0"?>
<root xmlns:db="http://cms.depagecms.net/ns/database"><!-- comment --></root>';

        $xml = new \DOMDocument;
        $xml->loadXML($xml_str);

        $this->xmldb->save_doc("testdoc", $xml);
        $saved_xml = $this->xmldb->get_doc("testdoc", false);

        $this->assertXmlStringEqualsXmlString($xml_str, $saved_xml->saveXML());
    }
    // }}}
    // {{{ testSave_doc_existing()
    public function testSave_doc_existing() {
        $xml = $this->xmldb->get_doc("pages");

        $this->xmldb->save_doc("pages", $xml);

        $saved_xml = $this->xmldb->get_doc("pages");

        $this->assertXmlStringEqualsXmlString($xml->saveXML(), $saved_xml->saveXML());
    }
    // }}}
    // {{{ testSave_doc_no_xml()
    public function testSave_doc_no_xml() {
        try {
            $this->xmldb->save_doc("pages", "");
        } catch (\depage\xmldb\xmldbException $expected) {
            return;
        }
        $this->fail('Expected xmldbException.');
    }
    // }}}
    
    // {{{ testRemove_doc()
    public function testRemove_doc() {
        $val = $this->xmldb->remove_doc("pages");

        $this->assertTrue($val);
        $this->assertArrayNotHasKey("pages", $this->xmldb->get_doc_list("pages"));
    }
    // }}}
    // {{{ testRemove_doc_nodoc()
    public function testRemove_doc_nodoc() {
        $val = $this->xmldb->remove_doc("non existent document");

        $this->assertFalse($val);
        $this->assertArrayNotHasKey("non existent document", $this->xmldb->get_doc_list("non existent document"));
    }
    // }}}
    
    // {{{ testGet_subdoc_by_xpath_by_name_all()
    public function testGet_subdoc_by_xpath_by_name_all() {
        $xml = $this->xmldb->get_subdoc_by_xpath(1, "//pg:page");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<pg:page name="Home" multilang="true" file_type="html" db:dataid="3" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page>', $xml->saveXML());
    }
    // }}}
    // {{{ testGet_subdoc_by_xpath_by_name_all_with_attribute()
    public function testGet_subdoc_by_xpath_by_name_all_with_attribute() {
        $xml = $this->xmldb->get_subdoc_by_xpath(1, "//pg:page[@name = 'bla blub']");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<pg:page name="bla blub" multilang="true" file_type="html" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:dataid="6" db:id="8">bla bla bla </pg:page>', $xml->saveXML());
    }
    // }}}
    // {{{ testGet_subdoc_by_xpath_by_name_with_child()
    public function testGet_subdoc_by_xpath_by_name_with_child() {
        $xml = $this->xmldb->get_subdoc_by_xpath(1, "/dpg:pages/pg:page");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<pg:page xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page>', $xml->saveXML());
    }
    // }}}
    // {{{ testGet_subdoc_by_xpath_by_name_and_position()
    public function testGet_subdoc_by_xpath_by_name_and_position() {
        $xml = $this->xmldb->get_subdoc_by_xpath(1, "/dpg:pages/pg:page/pg:page[3]");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<pg:page name="bla blub" multilang="true" file_type="html" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:dataid="6" db:id="8">bla bla bla </pg:page>', $xml->saveXML());
    }
    // }}}
    // {{{ testGet_subdoc_by_xpath_by_name_and_attribute()
    public function testGet_subdoc_by_xpath_by_name_and_attribute() {
        $xml = $this->xmldb->get_subdoc_by_xpath(1, "/dpg:pages/pg:page/pg:page[@name]");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<pg:page xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/>', $xml->saveXML());
    }
    // }}}
    // {{{ testGet_subdoc_by_xpath_by_name_and_attribute_with_value()
    public function testGet_subdoc_by_xpath_by_name_and_attribute_with_value() {
        $xml = $this->xmldb->get_subdoc_by_xpath(1, "/dpg:pages/pg:page/pg:page[@name = 'Subpage']");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<pg:page xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/>', $xml->saveXML());
    }
    // }}}
    // {{{ testGet_subdoc_by_xpath_by_wildcard_and_attribute_with_value()
    public function testGet_subdoc_by_xpath_by_wildcard_and_attribute_with_value() {
        $xml = $this->xmldb->get_subdoc_by_xpath(1, "/dpg:pages/pg:page/*[@name = 'Subpage']");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<pg:page xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/>', $xml->saveXML());
    }
    // }}}
    // {{{ testGet_subdoc_by_xpath_by_wildcard_ns_and_attribute_with_value()
    public function testGet_subdoc_by_xpath_by_wildcard_ns_and_attribute_with_value() {
        $xml = $this->xmldb->get_subdoc_by_xpath(1, "/dpg:pages/pg:page/*:page[@name = 'Subpage']");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<pg:page xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/>', $xml->saveXML());
    }
    // }}}
    // {{{ testGet_subdoc_by_xpath_by_wildcard_name_and_attribute_with_value()
    public function testGet_subdoc_by_xpath_by_wildcard_name_and_attribute_with_value() {
        $xml = $this->xmldb->get_subdoc_by_xpath(1, "/dpg:pages/pg:page/pg:*[@name = 'Subpage']");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<pg:page xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/>', $xml->saveXML());
    }
    // }}}
    // {{{ testGet_subdoc_by_xpath_no_result()
    public function testGet_subdoc_by_xpath_no_result() {
        $xml = $this->xmldb->get_subdoc_by_xpath(1, "/nonode");

        $this->assertFalse($xml);
    }
    // }}}
    
    // {{{ testUnlink_node()
    /**
     * @todo Implement testUnlink_node().
     */
    public function testUnlink_node() {
        $deleted = $this->xmldb->unlink_node(1, 9);

        $xml = $this->xmldb->get_doc("pages");
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>', $xml->saveXML());

        $deleted = $this->xmldb->unlink_node(1, 2);

        $xml = $this->xmldb->get_doc("pages");
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"></dpg:pages>', $xml->saveXML());
    }
    // }}}
    // {{{ testAdd_node()
    public function testAdd_node() {
        $doc = new DOMDocument();
        $doc->loadXML('<root><node/></root>');

        $this->xmldb->add_node(1, $doc, 2, 1);
        $xml = $this->xmldb->get_doc("pages");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><root db:id="12"><node db:id="13"/></root><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>', $xml->saveXML());
    }
    // }}}
    // {{{ testSave_node
    public function testSave_node() {
        $doc = new DOMDocument();
        $doc->loadXML('<root db:id="2" xmlns:db="http://cms.depagecms.net/ns/database"><node/></root>');

        $this->xmldb->save_node(1, $doc);
        $xml = $this->xmldb->get_doc("pages");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><root db:id="2"><node db:id="6"/></root></dpg:pages>', $xml->saveXML());
    }
    // }}}
    // {{{ testSave_node_root()
    public function testSave_node_root() {
        $xml = $this->xmldb->get_doc("pages");
        $this->xmldb->save_node(1, $xml);

        $saved_xml = $this->xmldb->get_doc("pages");

        $this->assertXmlStringEqualsXmlString($xml->saveXML(), $saved_xml->saveXML());
    }
    // }}}
    
    // {{{ testReplace_node()
    public function testReplace_node() {
        $doc = new DOMDocument();
        $doc->loadXML('<root><node/></root>');

        $this->xmldb->replace_node(1, $doc, 2, 1);
        $xml = $this->xmldb->get_doc("pages");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><root db:id="2"><node db:id="6"/></root></dpg:pages>', $xml->saveXML());
    }
    // }}}
    
    // {{{ testMove_node_in()
    public function testMove_node_in() {
        $this->xmldb->move_node_in("pages", 7, 8);
        $xml = $this->xmldb->get_doc("pages");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla <pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/></pg:page></pg:page></dpg:pages>', $xml->saveXML());
    }
    // }}}
    // {{{ testMove_node_before()
    public function testMove_node_before() {
        $this->xmldb->move_node_before(1, 7, 2);
        $xml = $this->xmldb->get_doc("pages");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>', $xml->saveXML());
    }
    // }}}
    // {{{ testMove_node_after()
    public function testMove_node_after() {
        $this->xmldb->move_node_after(1, 7, 2);
        $xml = $this->xmldb->get_doc("pages");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/></dpg:pages>', $xml->saveXML());
    }
    // }}}
    // {{{ testMove_node_after_same_level()
    public function testMove_node_after_same_level() {
        $this->xmldb->move_node_after(1, 6, 7);
        $xml = $this->xmldb->get_doc("pages");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>', $xml->saveXML());
    }
    // }}}
    
    // {{{ testCopy_node_in()
    public function testCopy_node_in() {
        $this->xmldb->copy_node_in(1, 7, 8);
        $xml = $this->xmldb->get_doc("pages");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla <pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="12"/></pg:page></pg:page></dpg:pages>', $xml->saveXML());
    }
    // }}}
    // {{{ testCopy_node_before()
    public function testCopy_node_before() {
        $this->xmldb->copy_node_before(1, 7, 2);
        $xml = $this->xmldb->get_doc("pages");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="12"/><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>', $xml->saveXML());
    }
    // }}}
    // {{{ testCopy_node_after()
    public function testCopy_node_after() {
        $this->xmldb->copy_node_after(1, 7, 2);
        $xml = $this->xmldb->get_doc("pages");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="12"/></dpg:pages>', $xml->saveXML());
    }
    // }}}
    
    // {{{ testSet_attribute()
    public function testSet_attribute() {
        $this->xmldb->set_attribute(1, 2, "textattr", "new value");
        $this->xmldb->set_attribute(1, 6, "multilang", "false");
        $xml = $this->xmldb->get_doc("pages");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" textattr="new value" db:id="2"><pg:page name="Subpage" multilang="false" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>', $xml->saveXML());
    }
    // }}}
    // {{{ testGet_attribute()
    public function testGet_attribute() {
        $attr = $this->xmldb->get_attribute(1, 2, "name");

        $this->assertEquals("Home", $attr);

        $attr = $this->xmldb->get_attribute(1, 2, "undefindattr");

        $this->assertFalse($attr);
    }
    // }}}
    // {{{ testGet_attributes()
    public function testGet_attributes() {
        $attrs = $this->xmldb->get_attributes(1, 2);

        $this->assertEquals(array(
            'name' => "Home",
            'multilang' => "true",
            'file_type' => "html",
            'db:dataid' => "3",
        ), $attrs);

    }
    // }}}
    
    // {{{ testGet_node_elementId()
    public function testGet_node_elementId() {
        $doc = new DOMDocument();
        $doc->loadXML('<root db:id="2" xmlns:db="http://cms.depagecms.net/ns/database"><node/></root>');

        $id = $this->xmldb->get_node_elementId($doc->documentElement);

        $this->assertEquals(2, $id);
    }
    // }}}
    // {{{ testGet_node_dataId()
    public function testGet_node_dataId() {
        $doc = new DOMDocument();
        $doc->loadXML('<root db:dataid="2" xmlns:db="http://cms.depagecms.net/ns/database"><node/></root>');

        $id = $this->xmldb->get_node_dataId($doc->documentElement);

        $this->assertEquals(2, $id);
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
