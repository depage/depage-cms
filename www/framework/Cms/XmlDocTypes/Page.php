<?php

namespace Depage\Cms\XmlDocTypes;

class Page extends Base
{
    use Traits\MultipleLanguages;
    use Traits\XmlTemplates;

    private $pathXMLtemplate = "";

    // {{{ constructor
    public function __construct($xmlDb, $document) {
        parent::__construct($xmlDb, $document);

        $this->initAvailableNodes();
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
        $this->testNodeLanguages($node);

        list($doc, $node) = \Depage\Xml\Document::getDocAndNode($node);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace("edit", "http://cms.depagecms.net/ns/edit");

        $nodelist = $xpath->query(".//edit:date[@value = '@now']", $node);
        if ($nodelist->length > 0) {
            // search for languages used in document
            for ($i = 0; $i < $nodelist->length; $i++) {
                $nodelist->item($i)->setAttribute('value', date('Y/m/d'));
            }
        }

        $nodelist = $xpath->query(".//edit:text_singleline[@value = '@author']", $node);
        if ($nodelist->length > 0) {
            // search for languages used in document
            for ($i = 0; $i < $nodelist->length; $i++) {
                if (!empty($this->xmlDb->options['userId'])) {
                    $user = \Depage\Auth\User::loadById($this->xmlDb->pdo, $this->xmlDb->options['userId']);
                    $nodelist->item($i)->setAttribute('value', $user->fullname);
                } else {
                    $nodelist->item($i)->setAttribute('value', "");
                }
            }
        }
    }
    // }}}
    // {{{ onDocumentChange()
    /**
     * @brief onDocumentChange
     *
     * @param mixed
     * @return void
     **/
    public function onDocumentChange()
    {
        $this->xmlDb->getDoc("pages")->clearCache();

        parent::onDocumentChange();

        return true;

    }
    // }}}
    // {{{ onHistorySave()
    /**
     * @brief onHistorySave
     *
     * @param mixed $param
     * @return void
     **/
    public function onHistorySave()
    {
        parent::onHistorySave();

        $doc = $this->xmlDb->getDoc("pages");
        $doc->clearCache();

        $pageInfo = $this->project->getXmlNav()->getPageInfo($this->document->getDocInfo()->name);
        if (!isset($pageInfo->pageId)) {
            return;
        }

        $parentPageId = $doc->getParentIdById($pageInfo->pageId);

        $prefix = $this->xmlDb->pdo->prefix . "_proj_" . $this->project->name;
        $deltaUpdates = new \Depage\WebSocket\JsTree\DeltaUpdates($prefix, $this->xmlDb->pdo, $this->xmlDb, $doc->getDocId(), $this->project);

        $deltaUpdates->recordChange($parentPageId);
    }
    // }}}

    // {{{ addNodeType
    public function addNodeType($name, $xml, $validParents, $pos) {
        $filename = \Depage\Html\Html::getEscapedUrl($name) . ".xml";

        $rootNode = $xml->documentElement;
        $rootNode->setAttribute("valid-parents", $validParents);
        $rootNode->setAttribute("pos", $pos);

        $xml->save($this->pathXMLtemplate . $filename);
    }
    // }}}
    // {{{ getNodeTypes
    public function getNodeTypes() {
        $nodetypes = [];
        $templates = $this->project->getXmlTemplates();

        foreach ($templates as $id => $t) {
            $xml = new \Depage\Xml\Document();
            if (!$xml->load($this->pathXMLtemplate . $t)) {
                continue;
            }
            if ($xml->documentElement->getAttribute("valid-parents") == "") {
                continue;
            }
            $data = (object)[
                'id' => $t,
                'icon' => "",
                'xmlTemplate' => $t,
                'validParents' => str_replace(" ", "", $xml->documentElement->getAttribute("valid-parents")),
                'pos' => (int) $xml->documentElement->getAttribute("pos"),
                'lastchange' => filemtime($this->pathXMLtemplate . $t),
                'name' => $xml->documentElement->getAttribute("name"),
                'newName' => '',
                'nodeName' => '',
            ];
            $data->xmlTemplateData = "";
            $names = [];
            foreach ($xml->documentElement->childNodes as $node) {
                if ($node->nodeType != \XML_COMMENT_NODE) {
                    $data->xmlTemplateData .= $xml->saveXML($node);
                }
                if ($node->nodeType == \XML_ELEMENT_NODE) {
                    $data->nodeName = $node->nodeName;
                    $names[] = $node->getAttribute("name");
                }
            }
            if (empty($data->name)) {
                $data->name = implode(" / ", $names);
            }
            $data->newName = $data->name;

            $nodetypes[$id] = $data;
        }

        uasort($nodetypes, function($a, $b) {
            return $a->pos <=> $b->pos;
        });

        return $nodetypes;
    }
    // }}}
    // {{{ getPageSubtypes()
    /**
     * @brief getPageSubtypes
     *
     * @param mixed
     * @return void
     **/
    public function getSubtypes($for)
    {
        $subtypes = [];

        foreach ($this->availableNodes as $node) {
            if (in_array($for, $node->validParents)) {
                $subtypes[] = $node;
            }
        }

        return $subtypes;
    }
    // }}}

    // {{{ testDocument
    public function testDocument($node) {
        $changed = false;

        $changed = $this->testNodeLanguages($node) || $changed;
        $changed = $this->updateLibrefs($node) || $changed;
        $changed = $this->updatePagerefs($node) || $changed;

        $this->addReleaseStatusAttributes($node->firstChild);

        return $changed;
    }
    // }}}
    // {{{ testDocumentForHistory
    public function testDocumentForHistory($xml) {
        parent::testDocumentForHistory($xml);

        $xml->firstChild->setAttributeNS("http://cms.depagecms.net/ns/database", "db:released", "true");
    }
    // }}}
    // {{{ addReleaseStatusAttributes()
    /**
     * @brief addReleaseStatusAttributes
     *
     * @param mixed $
     * @return void
     **/
    public function addReleaseStatusAttributes($node)
    {
        $info = $this->document->getDocInfo();
        $versions = array_values($this->document->getHistory()->getVersions(true, 1));

        // @todo fix not to get from timestamp when sha1 did no change
        if (count($versions) > 0 && $info->lastchange->getTimestamp() < $versions[0]->lastsaved->getTimestamp()) {
            $node->setAttributeNS("http://cms.depagecms.net/ns/database", "db:released", "true");
        } else {
            $node->setAttributeNS("http://cms.depagecms.net/ns/database", "db:released", "false");
        }
    }
    // }}}

    // {{{ updateLibrefs()
    protected function updateLibrefs($node)
    {
        $changed = false;

        $fl = new \Depage\Cms\FileLibrary($this->project->getPdo(), $this->project);

        list($xml, $node) = \Depage\Xml\Document::getDocAndNode($node);

        $xpath = new \DOMXPath($xml);

        $nodelist = $xpath->query("./descendant-or-self::node()[starts-with(@src, 'libref://')]", $node);
        if ($nodelist->length > 0) {
            for ($i = 0; $i < $nodelist->length; $i++) {
                $src = $nodelist->item($i)->getAttribute('src');
                $libid = $fl->toLibid($src);
                if ($libid) {
                    $nodelist->item($i)->setAttribute('src', $libid);
                    $changed = true;
                }
            }
        }

        $nodelist = $xpath->query("./descendant-or-self::node()[starts-with(@href, 'libref://')]", $node);
        if ($nodelist->length > 0) {
            for ($i = 0; $i < $nodelist->length; $i++) {
                $href = $nodelist->item($i)->getAttribute('href');
                $libid = $fl->toLibid($href);
                if ($libid) {
                    $nodelist->item($i)->setAttribute('href', $libid);
                    $changed = true;
                }
            }
        }

        return $changed;
    }
    // }}}
    // {{{ updatePagerefs()
    protected function updatePagerefs($node)
    {
        $changed = false;

        list($xml, $node) = \Depage\Xml\Document::getDocAndNode($node);

        $xpath = new \DOMXPath($xml);

        $nodelist = $xpath->query("./descendant-or-self::node()[@href_id != '']", $node);
        if ($nodelist->length > 0) {
            for ($i = 0; $i < $nodelist->length; $i++) {
                $href = "pageref://" . $nodelist->item($i)->getAttribute('href_id');
                $nodelist->item($i)->setAttribute('href', $href);
                $nodelist->item($i)->removeAttribute('href_id');
            }

            $changed = true;
        }

        return $changed;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
