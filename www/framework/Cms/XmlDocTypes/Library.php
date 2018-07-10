<?php

namespace Depage\Cms\XmlDocTypes;

// TODO configure

class Library extends Base {
    use Traits\UniqueNames;

    const XML_TEMPLATE_DIR = __DIR__ . '/LibraryXml/';

    // {{{ constructor
    public function __construct($xmlDb, $document) {
        parent::__construct($xmlDb, $document);

        $doctypePage = new \Depage\Cms\XmlDocTypes\Page($this->xmlDb, null);

        // list of elements that may created by a user
        $this->availableNodes = [
            'proj:folder' => (object) [
                'name' => _("Folder"),
                'newName' => _("(Untitled Folder)"),
                'icon' => "",
            ],
        ];

        foreach ($this->availableNodes as $nodeName => &$node) {
            $node->nodeName = $nodeName;
        }

        // list of valid parents given by nodename
        $this->validParents = [
            'proj:folder' => [
                'proj:folder',
                'proj:library',
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
        if (isset($extras)) {
            $node->setAttribute("name", $extras);
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
    public function onCopyNode($node_id, $copy_id)
    {
        return true;
    }
    // }}}
    // {{{ onDeleteNode()
    /**
     * On Delete Node
     *
     * Deletes an xmlDb document by the given id.
     *
     * @param $doc_id
     * @return boolean
     */
    public function onDeleteNode($node_id, $parent_id)
    {
        return true;
    }
    // }}}

    // {{{ testDocument
    public function testDocument($node) {
        $changed = $this->testUniqueNames($node, "//proj:*");

        $xmlnav = new \Depage\Cms\XmlNav();

        // add parent url if $node is not root node
        list($xml, $node) = \Depage\Xml\Document::getDocAndNode($node);
        $url = "";
        if ($node->nodeName != "proj:library") {
            $nodeId = (int) $node->getAttributeNS("http://cms.depagecms.net/ns/database", "id");
            while (($nodeId = $this->document->getParentIdById($nodeId)) != null) {
                $url = \Depage\Html\Html::getEscapedUrl(mb_strtolower($this->document->getAttribute($nodeId, 'name'))) . "/" . $url;
            }
        }
        $xmlnav->addUrlAttributes($node, $url);

        return $changed;
    }
    // }}}
    // {{{ testDocumentForHistory
    public function testDocumentForHistory($xml) {
        parent::testDocumentForHistory($xml);

        $xmlnav = new \Depage\Cms\XmlNav();
        $xmlnav->addUrlAttributes($xml);
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
