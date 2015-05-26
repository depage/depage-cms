<?php

class DocumentTest extends Depage\XmlDb\Tests\DatabaseTestCase
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

    // {{{ testGetDoctypeHandler
    public function testGetDoctypeHandler()
    {
        $baseType = 'Depage\XmlDb\XmlDocTypes\Base';

        $this->assertEquals($baseType, $this->doc->getDocInfo()->type);
        $this->assertInstanceOf($baseType, $this->doc->getDoctypeHandler());
    }
    // }}}
    // {{{ testGetDoctypeHandlerNoType
    public function testGetDoctypeHandlerNoType()
    {
        // delete document type
        $this->pdo->exec('UPDATE xmldb_proj_test_xmldocs SET type=\'\' where id=\'1\'');

        $this->assertEquals('', $this->doc->getDocInfo()->type);
        $this->assertInstanceOf('Depage\XmlDb\XmlDocTypes\Base', $this->doc->getDoctypeHandler());
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
    // {{{ testGetSubDocByXpathNone
    public function testGetSubDocByXpathNone()
    {
        $this->assertFalse($this->doc->getSubDocByXpath('//iamnosubdoc'));
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

    // {{{ testGetNodeNameById
    public function testGetNodeNameById()
    {
        $this->assertEquals('dpg:pages', $this->doc->getNodeNameById(1));
        $this->assertEquals('pg:page', $this->doc->getNodeNameById(2));
    }
    // }}}
    // {{{ testGetNodeNameByIdNonExistent
    public function testGetNodeNameByIdNonExistent()
    {
        $this->assertFalse($this->doc->getNodeNameById(5));
        $this->assertFalse($this->doc->getNodeNameById('noId'));
        $this->assertFalse($this->doc->getNodeNameById(null));
    }
    // }}}

    // {{{ testSaveElementNodes
    public function testSaveElementNodes()
    {
        $xmlStr = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database"><child></child><child/><child/></root>';

        $xml = new DomDocument;
        $xml->loadXML($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveElementNodesMany
    public function testSaveElementNodesMany()
    {
        $nodes = '';
        for ($i = 0; $i < 10; $i++) {
            $nodes .= '<child></child><child/><child></child><child></child>text<child/><child/>text<child/><child/><child/>';
        }
        $xmlStr = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database">' . $nodes . '</root>';

        $xml = new DomDocument;
        $xml->loadXML($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveElementNodesWithAttribute
    public function testSaveElementNodesWithAttribute()
    {
        $xmlStr = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database"><child attr="test"></child></root>';

        $xml = new DomDocument;
        $xml->loadXML($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveElementNodesWithNamespaces
    public function testSaveElementNodesWithNamespaces()
    {
        $xmlStr = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database"><db:child attr="test"></db:child><child db:data="blub" /></root>';

        $xml = new DomDocument;
        $xml->loadXML($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveTextNodes
    public function testSaveTextNodes()
    {
        $xmlStr = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database"><child>bla</child>blub<b/><c/><child>bla</child></root>';

        $xml = new DomDocument;
        $xml->loadXML($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSavePiNode
    public function testSavePiNode()
    {
        $xmlStr = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database"><?php echo("bla"); ?></root>';

        $xml = new DomDocument;
        $xml->loadXML($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testSaveCommentNode
    public function testSaveCommentNode()
    {
        $xmlStr = '<?xml version="1.0"?><root xmlns:db="http://cms.depagecms.net/ns/database"><!-- comment --></root>';

        $xml = new DomDocument;
        $xml->loadXML($xmlStr);
        $this->doc->save($xml);

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($xmlStr, $this->doc->getXml(false));
    }
    // }}}

    // {{{ testUnlinkNode
    public function testUnlinkNode()
    {
        $deleted = $this->doc->unlinkNode(9);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" ><pg:page name="Home" multilang="true" file_type="html" db:dataid="3"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6">bla bla bla </pg:page></pg:page></dpg:pages>';
        $this->assertEquals(2, $deleted);
        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml(false));
    }
    // }}}
    // {{{ testAddNode
    public function testAddNode()
    {
        $doc = new DomDocument();
        $doc->loadXML('<root><node/></root>');

        $this->doc->addNode($doc, 2, 1);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><root db:id="12"><node db:id="13"/></root><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}
    // {{{ testSaveNode
    public function testSaveNode()
    {
        $doc = new DomDocument();
        $doc->loadXML('<root db:id="2" xmlns:db="http://cms.depagecms.net/ns/database"><node/></root>');

        $this->doc->saveNode($doc);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><root db:id="2"><node db:id="6"/></root></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}

    // {{{ testGetPermissionѕ
    public function testGetPermissionѕ()
    {
        $expected = array(
            'validParents' => array(
                '*' => array('*')
            ),
            'availableNodes' => array()
        );

        $this->assertEquals($expected, (array) $this->doc->getPermissions());
    }
    // }}}

    // {{{ testReplaceNode
    public function testReplaceNode()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<root><node/></root>');

        $this->doc->replaceNode($doc, 2);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><root db:id="2"><node db:id="6"/></root></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}

    // {{{ testMoveNodeIn
    public function testMoveNodeIn()
    {
        $this->doc->moveNodeIn(7, 8);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla <pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/></pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastChange($expected, $this->doc->getXml());
    }
    // }}}
    // {{{ testMoveNodeBefore
    public function testMoveNodeBefore()
    {
        $this->doc->moveNodeBefore(7, 2);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}
    // {{{ testMoveNodeAfter
    public function testMoveNodeAfter()
    {
        $this->doc->moveNodeAfter(7, 2);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}
    // {{{ testMoveNodeAfterSameLevel
    public function testMoveNodeAfterSameLevel()
    {
        $this->doc->moveNodeAfter(6, 7);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}

    // {{{ testCopyNodeIn
    public function testCopyNodeIn()
    {
        $this->doc->copyNodeIn(7, 8);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla <pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="12"/></pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}
    // {{{ testCopyNodeBefore
    public function testCopyNodeBefore()
    {
        $this->doc->copyNodeBefore(7, 2);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="12"/><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}
    // {{{ testCopyNodeAfter
    public function testCopyNodeAfter()
    {
        $this->doc->copyNodeAfter(7, 2);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="12"/></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}

    // {{{ testDuplicateNode
    public function testDuplicateNode()
    {
        $this->doc->duplicateNode(7);

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1" db:lastchange="2015-05-22 18:35:46" db:lastchangeUid=""><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="12"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}

    // {{{ testBuildNode
    public function testBuildNode()
    {
        $node = $this->doc->buildNode('newNode', array('att' => 'val', 'att2' => 'val2'));

        $expected = '<newNode xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" att="val" att2="val2"/>';

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $node->ownerDocument->saveXML($node));
    }
    // }}}

    // {{{ testSetAttribute
    public function testSetAttribute()
    {
        $this->doc->setAttribute(2, 'textattr', 'new value');
        $this->doc->setAttribute(6, 'multilang', 'false');

        $this->assertXmlStringEqualsXmlStringIgnoreLastchange('<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1"><pg:page name="Home" multilang="true" file_type="html" db:dataid="3" textattr="new value" db:id="2"><pg:page name="Subpage" multilang="false" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>', $this->doc->getXml());
    }
    // }}}
    // {{{ testRemoveAttribute
    public function testRemoveAttribute()
    {
        $return = $this->doc->removeAttribute(2, 'multilang');

        $expected = '<?xml version="1.0"?><dpg:pages xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" db:name="" db:id="1" db:lastchange="0000-00-00 00:00:00" db:lastchangeUid=""><pg:page name="Home" file_type="html" db:dataid="3" db:id="2"><pg:page name="Subpage" multilang="true" file_type="html" db:dataid="4" db:id="6"/><pg:page name="Subpage 2" multilang="true" file_type="html" db:dataid="5" db:id="7"/><pg:folder name="Subpage" multilang="true" file_type="html" db:dataid="7" db:id="9"/>bla bla blub <pg:page name="bla blub" multilang="true" file_type="html" db:dataid="6" db:id="8">bla bla bla </pg:page></pg:page></dpg:pages>';

        $this->assertTrue($return);
        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}
    // {{{ testRemoveAttributeNonExistent
    public function testRemoveAttributeNonExistent()
    {
        $expected = $this->doc->getXml();

        $return = $this->doc->removeAttribute(2, 'idontexist');

        $this->assertFalse($return);
        $this->assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $this->doc->getXml());
    }
    // }}}
    // {{{ testGetAttribute
    public function testGetAttribute()
    {
        $attr = $this->doc->getAttribute(2, 'name');
        $this->assertEquals('Home', $attr);

        $attr = $this->doc->getAttribute(2, 'undefindattr');
        $this->assertFalse($attr);
    }
    // }}}
    // {{{ testGetAttributes
    public function testGetAttributes()
    {
        $attrs = $this->doc->getAttributes(2);

        $expected = array(
            'name' => 'Home',
            'multilang' => 'true',
            'file_type' => 'html',
            'db:dataid' => '3',
        );

        $this->assertEquals($expected, $attrs);
    }
    // }}}

    // {{{ testGetParentIdById
    public function testGetParentIdById()
    {
        $this->assertNull($this->doc->getParentIdById(1));
        $this->assertEquals(2, $this->doc->getParentIdById(6));
    }
    // }}}
    // {{{ testGetParentIdByIdNonExistent
    public function testGetParentIdByIdNonExistent()
    {
        $this->assertFalse($this->doc->getParentIdById(3));
        $this->assertFalse($this->doc->getParentIdById(1000));
        $this->assertFalse($this->doc->getParentIdById('noId'));
        $this->assertFalse($this->doc->getParentIdById(null));
    }
    // }}}

    // {{{ testGetNodeId
    public function testGetNodeId()
    {
        $doc = new DOMDocument();
        $doc->loadXml('<root db:id="2" xmlns:db="http://cms.depagecms.net/ns/database"><node/></root>');

        $id = $this->doc->getNodeId($doc->documentElement);

        $this->assertEquals(2, $id);
    }
    // }}}
    // {{{ testGetNodeDataId
    public function testGetNodeDataId()
    {
        $doc = new DOMDocument();
        $doc->loadXml('<root db:dataid="2" xmlns:db="http://cms.depagecms.net/ns/database"><node/></root>');

        $id = $this->doc->getNodeDataId($doc->documentElement);

        $this->assertEquals(2, $id);
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
