<?php

namespace Depage\Cms\XmlDocTypes;

// TODO configure
define('XML_TEMPLATE_DIR', __DIR__ . '/XmlTemplates/');

class Pages extends UniqueNames {

    // {{{ constructor
    public function __construct($xmldb, $document) {
        parent::__construct($xmldb, $document);

        // list of elements that may created by a user
        $this->availableNodes = array(
            'pg:page' => (object) array(
                'name' => _("Page"),
                'new' => _("(Untitled Page)"),
                'icon' => "",
                'attributes' => array(),
                'doc_type' => 'Depage\Cms\XmlDocTypes\Page',
                'xml_template' => 'page.xml'
            ),
            'pg:folder' => (object) array(
                'name' => _("Folder"),
                'new' => _("(Untitled Folder)"),
                'icon' => "",
                'attributes' => array(),
                'doc_type' => 'Depage\Cms\XmlDocTypes\Folder',
                'xml_template' => 'folder.xml',
            ),
            'pg:redirect' => (object) array(
                'name' => _("Redirect"),
                'new' => _("Redirect"),
                'icon' => "",
                'attributes' => array(),
                'doc_type' => 'Depage\Cms\XmlDocTypes\Page',
                'xml_template' => 'redirect.xml',
            ),
            'pg:separator' => (object) array(
                'name' => _("Separator"),
                'new' => "",
                'icon' => "",
                'attributes' => array(),
            ),
        );

        // list of valid parents given by nodename
        $this->validParents = array(
            'pg:page' => array(
                'dpg:pages',
                'proj:pages_struct',
                'pg:page',
                'pg:folder',
            ),
            'pg:folder' => array(
                'dpg:pages',
                'proj:pages_struct',
                'pg:page',
                'pg:folder',
            ),
            'pg:redirect' => array(
                'dpg:pages',
                'proj:pages_struct',
                'pg:page',
                'pg:folder',
            ),
            'pg:separator' => array(
                '*',
            ),
        );
    }
    // }}}

    // {{{ onAddNode
    /**
     * On Add Node
     *
     * @param \DomNode $node
     * @param $target_id
     * @param $target_pos
     * @param $extras
     * @return null
     */
    public function onAddNode(\DomNode $node, $target_id, $target_pos, $extras) {
        if (isset($this->availableNodes[$node->nodeName])) {
            $properties = $this->availableNodes[$node->nodeName];

            if (isset($properties->new)) {
                $node->setAttribute("name", $properties->new);
            }
            if (isset($properties->doc_type) && isset($properties->xml_template)) {
                $document = $this->xmldb->createDoc($properties->doc_type);
                $node->setAttribute('db:docref', $document->getDocId());
                $xml = $this->loadXmlTemplate($properties->xml_template);
                $rootId = $document->getDocInfo()->rootid;

                $docId = $document->save($xml);

                if (isset($extras['dataNodes'])) {
                    // add document data to page data document
                    $rootId = $document->getDocInfo()->rootid;

                    foreach ($extras['dataNodes'] as $dataNode) {
                        $document->addNode($dataNode, $rootId);
                    }
                }

                return $docId;
            }
        }

        return false;
    }
    // }}}
    // {{{ onCopyNode
    /**
     * On Copy Node
     *
     * @param \DomElement $node
     * @param $target_id
     * @param $target_pos
     * @return null
     */
    public function onCopyNode($node_id, $copy_id) {
        $log = new \Depage\Log\Log();

        // get all copied nodes
        $copiedXml = $this->document->getSubdocByNodeId($copy_id, true);
        $xpath = new \DOMXPath($copiedXml);
        $xpath->registerNamespace("db", "http://cms.depagecms.net/ns/database");

        $xp_result = $xpath->query("./descendant-or-self::node()[@db:docref]", $copiedXml);

        foreach ($xp_result as $node) {
            // get node ids and docrefids
            $nodeId = $node->getAttributeNS("http://cms.depagecms.net/ns/database", "id");
            $docrefId = $node->getAttributeNS("http://cms.depagecms.net/ns/database", "docref");

            // duplicate document as new
            $copiedDoc = $this->xmldb->duplicateDoc($docrefId);
            $info = $copiedDoc->getDocInfo();

            $this->document->setAttribute($nodeId, "db:docref", $info->id);
        }

        return true;
    }
    // }}}
    // {{{ onDeleteNode()
    /**
     * On Delete Node
     *
     * Deletes an xmldb document by the given id.
     *
     * @param $doc_id
     * @return boolean
     */
    public function onDeleteNode($node_id, $parent_id)
    {
        // @todo check wether to delete attached documents directly or later
        //$this->xmldb->removeDoc($doc_id);
        return true;
    }
    // }}}

    // {{{ testDocument
    public function testDocument($node) {
        $changed = parent::testDocument($node);

        $xmlnav = new \Depage\Cms\XmlNav();
        $xmlnav->addUrlAttributes($node);

        return $changed;
    }
    // }}}

    // {{{ loadXmlTemplate()
    /**
     * Load XML Template
     *
     * @param $template
     * @return \DOMDocument
     */
    private function loadXmlTemplate($template) {
        $doc = new \DOMDocument();
        $doc->load(XML_TEMPLATE_DIR . $template);
        return $doc;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
