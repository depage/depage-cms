<?php

namespace Depage\Cms\XmlDocTypes;

// TODO configure

class Pages extends Base {
    use Traits\UniqueNames;

    const XML_TEMPLATE_DIR = __DIR__ . '/PagesXml/';

    /**
     * @brief routeHtmlThroughPhp
     **/
    protected $routeHtmlThroughPhp = false;

    // {{{ constructor
    public function __construct($xmlDb, $document) {
        parent::__construct($xmlDb, $document);

        $this->routeHtmlThroughPhp = $this->project->getProjectConfig()->routeHtmlThroughPhp;

        $doctypePage = new \Depage\Cms\XmlDocTypes\Page($this->xmlDb, null);

        // list of elements that may created by a user
        $this->availableNodes = [
            'pg:page' => (object) [
                'name' => _("Page"),
                'newName' => _("(Untitled Page)"),
                'icon' => "",
                'attributes' => [
                    'multilang' => "true",
                    'file_type' => "html",
                ],
                'docType' => 'Depage\Cms\XmlDocTypes\Page',
                'xmlTemplate' => 'page.xml',
                'subTypes' => $doctypePage->getSubtypes("pg:page_data"),
            ],
            'pg:folder' => (object) [
                'name' => _("Folder"),
                'newName' => _("(Untitled Folder)"),
                'icon' => "",
                'docType' => 'Depage\Cms\XmlDocTypes\Folder',
                'xmlTemplate' => 'folder.xml',
            ],
            'pg:redirect' => (object) [
                'name' => _("Redirect"),
                'newName' => _("Redirect"),
                'icon' => "",
                'attributes' => [
                    'multilang' => "true",
                    'file_type' => "php",
                    'redirect' => "true",
                ],
                'docType' => 'Depage\Cms\XmlDocTypes\Page',
                'xmlTemplate' => 'redirect.xml',
            ],
            'sec:separator' => (object) [
                'name' => _("Separator"),
                'newName' => "",
                'icon' => "",
                'attributes' => [],
            ],
        ];

        foreach ($this->availableNodes as $nodeName => &$node) {
            $node->id = $nodeName;
            $node->nodeName = $nodeName;
        }

        // list of valid parents given by nodename
        $this->validParents = [
            'pg:page' => [
                'dpg:pages',
                'proj:pages_struct',
                'pg:page',
                'pg:folder',
                'pg:redirect',
            ],
            'pg:folder' => [
                'dpg:pages',
                'proj:pages_struct',
                'pg:page',
                'pg:folder',
                'pg:redirect',
            ],
            'pg:redirect' => [
                'dpg:pages',
                'proj:pages_struct',
                'pg:page',
                'pg:folder',
                'pg:redirect',
            ],
            'sec:separator' => [
                'dpg:pages',
                'proj:pages_struct',
                'pg:page',
                'pg:folder',
                'pg:redirect',
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

            if (!empty($properties->newName)) {
                $node->setAttribute("name", $properties->newName);
            }
            if (isset($properties->docType) && isset($properties->xmlTemplate)) {
                $doc = $this->xmlDb->createDoc($properties->docType);
                $xml = $this->loadXmlTemplate($properties->xmlTemplate);

                $docId = $doc->save($xml);
                $info = $doc->getDocInfo();
                $node->setAttribute('db:docref', $info->name);

                if (isset($extras['dataNodes'])) {
                    // add doc data to page data doc
                    foreach ($extras['dataNodes'] as $dataNode) {
                        $doc->addNode($dataNode, $info->rootid);
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
            $copiedDoc = $this->xmlDb->duplicateDoc($docrefId);
            $info = $copiedDoc->getDocInfo();

            // reset release state for copied document
            $copiedDoc->setAttribute($info->rootid, "db:released", "false");
            $copiedDoc->setAttribute($info->rootid, "db:published", "false");

            $this->document->setAttribute($nodeId, "db:docref", $info->name);
        }

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
        // @todo check wether to delete attached documents directly or later
        //$this->xmlDb->removeDoc($doc_id);
        return true;
    }
    // }}}

    // {{{ testDocument
    public function testDocument($node) {
        $changed = $this->testUniqueNames($node, "//proj:pages_struct | //pg:*");

        $xmlnav = new \Depage\Cms\XmlNav();
        $xmlnav->routeHtmlThroughPhp = $this->routeHtmlThroughPhp;

        // add parent url if $node is not root node
        list($xml, $node) = \Depage\Xml\Document::getDocAndNode($node);
        $url = "";
        if ($node->nodeName != "proj:pages_struct") {
            $nodeId = (int) $node->getAttributeNS("http://cms.depagecms.net/ns/database", "id");
            while (($nodeId = $this->document->getParentIdById($nodeId)) != null) {
                $url = \Depage\Html\Html::getEscapedUrl(mb_strtolower($this->document->getAttribute($nodeId, 'name'))) . "/" . $url;
            }
        }
        $xmlnav->addUrlAttributes($node, $url);

        $this->addReleaseStatusAttributes($node);

        return $changed;
    }
    // }}}
    // {{{ testDocumentForHistory
    public function testDocumentForHistory($xml) {
        parent::testDocumentForHistory($xml);

        $this->addReleaseStatusAttributes($xml, true);

        $xpath = new \DOMXPath($xml);

        // remove unpublished pages
        $unpublishedPages = $xpath->query("//pg:page[@db:published = 'false']");
        foreach ($unpublishedPages as $page) {
            $page->parentNode->removeChild($page);
        }

        // remove empty folders
        do {
            $emptyFolders = $xpath->query("//pg:folder[not(.//pg:page)]");
            foreach ($emptyFolders as $folder) {
                $folder->parentNode->removeChild($folder);
            }
        } while ($emptyFolders->length > 0);

        $xmlnav = new \Depage\Cms\XmlNav();
        $xmlnav->routeHtmlThroughPhp = $this->routeHtmlThroughPhp;
        $xmlnav->addUrlAttributes($xml);
    }
    // }}}
    // {{{ addReleaseStatusAttributes()
    /**
     * @brief addReleaseStatusAttributes
     *
     * @param mixed $
     * @return void
     **/
    public function addReleaseStatusAttributes($node, $getAnyVersion = false)
    {
        list($xml, $node) = \Depage\Xml\Document::getDocAndNode($node);

        $xpath = new \DOMXPath($xml);
        $pages = $xpath->query("//pg:page");

        foreach ($pages as $page) {
            $doc = $this->xmlDb->getDoc($page->getAttribute("db:docref"));

            $released = false;
            $published = false;

            if ($doc && $doc->isReleased()) {
                $released = true;
            }
            if ($doc && $doc->isPublished()) {
                $published = true;
            }
            $page->setAttributeNS("http://cms.depagecms.net/ns/database", "db:released", $released ? "true" : "false");
            $page->setAttributeNS("http://cms.depagecms.net/ns/database", "db:published", $published ? "true" : "false");
        }
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
