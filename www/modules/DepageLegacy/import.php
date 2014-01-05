<?php
/**
 * @file    framework/cms/ui_base.php
 *
 * base class for cms-ui modules
 *
 *
 * copyright (c) 2011-2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace DepageLegacy;

class Import
{
    protected $pdo;
    protected $cache;
    protected $xmldb;

    protected $projectName;
    protected $pageIds = array();

    protected $xmlImport;
    protected $xmlSettings;
    protected $xmlColors;
    protected $xmlNavigation;

    protected $docSettings;
    protected $docNavigation;
    protected $docColors;

    protected $xsltPath;
    protected $xmlPath;

    protected $xslHeader = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<!DOCTYPE xsl:stylesheet [\n    <!ENTITY nbsp \"&#160;\">\n]>\n<xsl:stylesheet xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\" xmlns:db=\"http://cms.depagecms.net/ns/database\" xmlns:proj=\"http://cms.depagecms.net/ns/project\" xmlns:pg=\"http://cms.depagecms.net/ns/page\" xmlns:sec=\"http://cms.depagecms.net/ns/section\" xmlns:edit=\"http://cms.depagecms.net/ns/edit\" version=\"1.0\" extension-element-prefixes=\"xsl db proj pg sec edit \">\n";
    protected $xslFooter = "\n    <!-- vim:set ft=xslt sw=4 sts=4 fdm=marker : -->\n</xsl:stylesheet>";
    protected $xmlHeader = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<proj:newnode xmlns:proj=\"http://cms.depagecms.net/ns/project\" xmlns:sec=\"http://cms.depagecms.net/ns/section\" xmlns:edit=\"http://cms.depagecms.net/ns/edit\" version=\"1.0\" extension-element-prefixes=\"proj sec edit \">\n";
    protected $xmlFooter = "\n    <!-- vim:set ft=xml sw=4 sts=4 fdm=marker : -->\n</proj:newnode>";

    // {{{ constructor
    public function __construct($name, $pdo, $cache)
    {
        $this->projectName = $name;

        $this->xsltPath = "projects/" . $this->projectName . "/xslt/";
        $this->xmlPath = "projects/" . $this->projectName . "/xml/";

        $this->pdo = $pdo;
        $this->cache = $cache;
        $this->xmldb = new \depage\xmldb\xmldb("{$this->pdo->prefix}_proj_{$this->projectName}", $this->pdo, $this->cache, array(
            'pathXMLtemplate' => $this->xmlPath,
        ));
    }
    // }}}
    // {{{ importProject()
    public function importProject($xmlFile)
    {
        $this->loadBackup($xmlFile);

        // @todo test why cleaning leads to constraint error
        //$this->cleanDocs();

        $this->getDocs();

        $this->extractNavigation();
        $this->extractTemplates();
        $this->extractNewnodes();
        $this->extractColorschemes();
        $this->extractSettings();

        foreach($this->pageIds as $pageId) {
            $this->extractPagedataForId($pageId);
        }

        return $this->xmlNavigation;
    }
    // }}}
    
    // {{{ loadBackup()
    public function loadBackup($xmlFile)
    {
        $this->xmlImport = new \depage\xml\Document();
        $this->xmlImport->load($xmlFile);
    }
    // }}}
    
    // {{{ cleanDocs()
    public function cleanDocs()
    {
        $docs = $this->xmldb->getDocuments();

        foreach ($docs as $name => $doc) {
            $this->xmldb->removeDoc($name);
        }
    }
    // }}}
    // {{{ getDocs()
    public function getDocs()
    {
        $this->docNavigation = $this->xmldb->getDoc("pages");
        if (!$this->docNavigation) {
            $this->docNavigation = $this->xmldb->createDoc("pages", "depage\\cms\\xmldoctypes\\pages");
        }

        $this->docSettings = $this->xmldb->getDoc("settings");
        if (!$this->docSettings) {
            // @todo update doctype
            $this->docSettings = $this->xmldb->createDoc("settings", "depage\\xmldb\\xmldoctypes\\base");
        }
        
        $this->docColors = $this->xmldb->getDoc("colors");
        if (!$this->docColors) {
            // @todo update doctype
            $this->docColors = $this->xmldb->createDoc("colors", "depage\\xmldb\\xmldoctypes\\base");
        }
    }
    // }}}
    // {{{ removeDbIds()
    public function removeDbIds($xml)
    {
        $xpath = new \DOMXPath($xml);
        $nodelist = $xpath->query("//@db:id");

        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $node = $nodelist->item($i);

            if ($node->nodeType == XML_ATTRIBUTE_NODE) {
                $node->parentNode->removeAttributeNode($node);
            } else {
                $node->parentNode->removeChild($node);
            }
        }
    }
    // }}}
    
    // {{{ extractNavigation()
    public function extractNavigation()
    {
        $xpath = new \DOMXPath($this->xmlImport);
        $nodelist = $xpath->query("//proj:pages_struct");

        // extract navigation tree
        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $this->xmlNavigation = new \depage\xml\Document();
            $node = $this->xmlNavigation->importNode($nodelist->item($i), true);
            $this->xmlNavigation->appendChild($node);
        }

        $xpath = new \DOMXPath($this->xmlNavigation);
        $nodelist = $xpath->query("//pg:*[@db:id]");

        // save old db:ids
        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $node = $nodelist->item($i);
            $node->setAttribute("db:oldid", $node->getAttribute("db:id"));
        }

        $this->docNavigation->save($this->xmlNavigation);

        // save db:ids in pageIds
        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $node = $nodelist->item($i);
            $nodeId = $node->getAttribute("db:id");
            $this->pageIds[$node->getAttribute("db:oldid")] = $nodeId;

            $this->docNavigation->removeAttribute($nodeId, "db:oldid");
        }
    }
    // }}}
    // {{{ extractPagedataForId()
    public function extractPagedataForId($pageId)
    {
        $dbref = $this->docNavigation->getAttribute($pageId, "db:ref");

        $xpathImport = new \DOMXPath($this->xmlImport);
        $pagelist = $xpathImport->query("//*[@db:id = $dbref]");
        
        // save pagedata
        if ($pagelist->length === 1) {
            $xmlData = new \depage\xml\Document();

            $dataNode = $xmlData->importNode($pagelist->item(0), true);
            $xmlData->appendChild($dataNode);
            list($ns, $docType) = explode(":", $this->docNavigation->getNodeNameById($pageId));
            $docName = '_' . $docType . '_' . sha1(uniqid(dechex(mt_rand(256, 4095))));

            $this->updatePageRefs($xmlData);

            $doc = $this->xmldb->createDoc($docName, "depage\\cms\\xmldoctypes\\$docType");
            $newId = $doc->save($xmlData);

            // updated reference attributes
            $this->docNavigation->removeAttribute($pageId, "db:ref");
            $this->docNavigation->setAttribute($pageId, "db:docref", $newId);
        }
    }
    // }}}
    // {{{ extractTemplates()
    public function extractTemplates()
    {
        $xpath = new \DOMXPath($this->xmlImport);
        $nodelist = $xpath->query("//proj:tpl_templates_struct");

        if (!is_dir($this->xsltPath)) mkdir($this->xsltPath);

        // extract template tree
        if ($nodelist->length === 1) {
            $xmlTemplates = new \depage\xml\Document();
            $node = $xmlTemplates->importNode($nodelist->item(0), true);
            $xmlTemplates->appendChild($node);
        }

        $this->extractTemplateData($xmlTemplates->documentElement);
    }
    // }}}
    // {{{ extractTemplatesData()
    public function extractTemplateData($node, $namePrefix = "")
    {
        if ($namePrefix !== "") {
            $namePrefix .= "-";
        }
        for ($i = 0; $i < $node->childNodes->length; $i++) {
            $child = $node->childNodes->item($i);

            if ($child->nodeName == "pg:template") {
                $xpath = new \DOMXPath($this->xmlImport);
                $dataId = $child->getAttribute("db:ref");
                $tpllist = $xpath->query("//*[@db:id = $dataId]");

                // save template data
                if ($tpllist->length === 1) {
                    $dataNode = $tpllist->item(0);

                    // make path for temlate group
                    $path = $this->xsltPath . $dataNode->getAttribute("type") . "/";
                    if (!is_dir($path)) mkdir($path);
                    $filename = $path . \html::get_url_escaped($namePrefix . $child->getAttribute("name")) . ".xsl";

                    // replace tabes with spaces and indent content
                    $xsl = str_replace(array(
                        "\t",
                        "\n",
                    ), array(
                        "    ",
                        "\n    ",
                    ), trim($dataNode->nodeValue));

                    // @todo automatically replace custom php calls etc. for automatic xsl updates

                    file_put_contents($filename, "{$this->xslHeader}    {$xsl}\n{$this->xslFooter}");
                }
            }
            if ($child->nodeName == "pg:folder" || $child->nodeName == "pg:template") {
                $this->extractTemplateData($child, $namePrefix . $child->getAttribute("name"));
            } 
        }
    }
    // }}}
    // {{{ extractNewnodes()
    public function extractNewnodes()
    {
        $xpath = new \DOMXPath($this->xmlImport);
        $nodelist = $xpath->query("//proj:tpl_newnodes/pg:newnode");

        if (!is_dir($this->xmlPath)) mkdir($this->xmlPath);

        //for ($i = $nodelist->length - 1; $i >= 0; $i--) {
        for ($i = 0; $i < $nodelist->length; $i++) {
            $node = $nodelist->item($i);

            $name = $node->getAttribute("name");
            $pos = $i;

            $validParentsNode = $node->getElementsByTagNameNS("http://cms.depagecms.net/ns/edit", "newnode_valid_parents")->item(0);
            $validParents = explode(",", $validParentsNode->nodeValue);

            $contentNode = $node->getElementsByTagNameNS("http://cms.depagecms.net/ns/edit", "newnode")->item(0);
            $contentDoc = new \depage\xml\Document();
            //$contentDoc->preserveWhiteSpace = false;
            //$contentDoc->formatOutput = true;
            $contentDoc->loadXML($this->xmlHeader . trim($contentNode->nodeValue) . $this->xmlFooter);

            $nodeTypes = new \depage\cms\xmldoctypes\page($this->xmldb, $this->docNavigation->getDocId());

            $nodeTypes->addNodeType($contentDoc->documentElement->nodeName, array(
                'pos' => $pos,
                'name' => $name,
                'newName' => $contentDoc->documentElement->getAttribute("name"),
                'icon' => $contentDoc->documentElement->getAttribute("icon"),
                'validParents' => $validParents,
                'xmlTemplate' => \html::get_url_escaped($name) . ".xml",
                'xmlTemplateData' => $contentDoc->saveXML(),
            ));
        }
    }
    // }}}
    // {{{ extractColorschemes()
    public function extractColorschemes()
    {
        $xpath = new \DOMXPath($this->xmlImport);
        $nodelist = $xpath->query("//proj:colorschemes");

        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $this->xmlColors = new \depage\xml\Document();
            $node = $this->xmlColors->importNode($nodelist->item($i), true);
            $this->xmlColors->appendChild($node);
        }

        $this->docColors->save($this->xmlColors);
    }
    // }}}
    // {{{ extractSettings()
    public function extractSettings()
    {
        $xpath = new \DOMXPath($this->xmlImport);
        $nodelist = $xpath->query("//proj:settings");

        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $this->xmlSettings = new \depage\xml\Document();
            $node = $this->xmlSettings->importNode($nodelist->item($i), true);
            $this->xmlSettings->appendChild($node);
        }

        $this->docSettings->save($this->xmlSettings);
    }
    // }}}
    
    // {{{ updatePageRefs()
    protected function updatePageRefs($xmlData)
    {
        $xpath = new \DOMXPath($xmlData);
        $nodelist = $xpath->query("//*[@href and starts-with(@href,'pageref:')]");

        // test all links with a pageref
        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $node = $nodelist->item($i);
            $href = $node->getAttribute("href");

            $id = substr($href, 8);

            if (isset($this->pageIds[$id])) {
                $node->setAttribute("href", "pageref:{$this->pageIds[$id]}");
            } else {
                // clear links with a non-existant page reference
                $node->setAttribute("href", "");
            }
        }
        
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
