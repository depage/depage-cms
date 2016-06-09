<?php

namespace Depage\Cms\XmlDocTypes;

// TODO configure

class Pages extends Base {
    use Traits\UniqueNames;

    const XML_TEMPLATE_DIR = __DIR__ . '/PagesXml/';

    // {{{ constructor
    public function __construct($xmldb, $document) {
        parent::__construct($xmldb, $document);

        // list of elements that may created by a user
        $this->availableNodes = [
            'pg:page' => (object) [
                'name' => _("Page"),
                'new' => _("(Untitled Page)"),
                'icon' => "",
                'attributes' => [],
                'doc_type' => 'Depage\Cms\XmlDocTypes\Page',
                'xml_template' => 'page.xml'
            ],
            'pg:folder' => (object) [
                'name' => _("Folder"),
                'new' => _("(Untitled Folder)"),
                'icon' => "",
                'attributes' => [],
                'doc_type' => 'Depage\Cms\XmlDocTypes\Folder',
                'xml_template' => 'folder.xml',
            ],
            'pg:redirect' => (object) [
                'name' => _("Redirect"),
                'new' => _("Redirect"),
                'icon' => "",
                'attributes' => [],
                'doc_type' => 'Depage\Cms\XmlDocTypes\Page',
                'xml_template' => 'redirect.xml',
            ],
            'pg:separator' => (object) [
                'name' => _("Separator"),
                'new' => "",
                'icon' => "",
                'attributes' => [],
            ],
        ];

        // list of valid parents given by nodename
        $this->validParents = [
            'pg:page' => [
                'dpg:pages',
                'proj:pages_struct',
                'pg:page',
                'pg:folder',
            ],
            'pg:folder' => [
                'dpg:pages',
                'proj:pages_struct',
                'pg:page',
                'pg:folder',
            ],
            'pg:redirect' => [
                'dpg:pages',
                'proj:pages_struct',
                'pg:page',
                'pg:folder',
            ],
            'pg:separator' => [
                '*',
            ],
        ];
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
                $doc = $this->xmldb->createDoc($properties->doc_type);
                $xml = $this->loadXmlTemplate($properties->xml_template);

                $docId = $doc->save($xml);
                $info = $doc->getDocInfo();
                $node->setAttribute('db:docref', $info->name);

                if (isset($extras['dataNodes'])) {
                    // add doc data to page data doc
                    foreach ($extras['dataNodes'] as $dataNode) {
                        $doc->addNode($dataNode, $info->rootId);
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

            $this->document->setAttribute($nodeId, "db:docref", $info->name);
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
        $changed = $this->testUniqueNames($node, "//proj:pages_struct | //pg:*");

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
        $doc->load(self::XML_TEMPLATE_DIR . $template);
        return $doc;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
