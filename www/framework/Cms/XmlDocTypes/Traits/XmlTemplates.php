<?php

namespace Depage\Cms\XmlDocTypes\Traits;

trait XmlTemplates
{
    protected $pathXMLtemplate;

    // {{{ initAvailableNodes()
    public function initAvailableNodes()
    {
        $this->pathXMLtemplate = $this->xmlDb->options['pathXMLtemplate'];

        $types = $this->getNodeTypes();
        $this->availableNodes = [];
        $this->validParents = [];

        foreach ($this->getNodeTypes() as $id => $t) {
            $doc = new \DOMDocument();
            $success = $doc->load("{$this->pathXMLtemplate}/{$t->xmlTemplate}");

            if (!$success) continue;

            $contentElement = $doc->documentElement->firstChild;
            while ($contentElement && $contentElement->nodeType != \XML_ELEMENT_NODE) {
                $contentElement = $contentElement->nextSibling;
            }
            if ($contentElement) {
                $nodeName = $contentElement->nodeName;

                $this->validParents[$nodeName] = explode(",", $t->validParents);

                $t->validParents = $this->validParents[$nodeName];
                $this->availableNodes[$t->xmlTemplate] = $t;
            }
        }
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

}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
