<?php
/**
 * @file    framework/cms/ui_base.php
 *
 * base class for cms-ui modules
 *
 *
 * copyright (c) 2011-2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms;

use \Depage\Html\Html;

class Import
{
    protected $pdo;
    protected $cache;
    protected $xmldb;

    protected $projectName;
    protected $pageIds = [];

    protected $xmlImport;
    protected $xmlSettings;
    protected $xmlColors;
    protected $xmlNavigation;

    protected $docSettings;
    protected $docNavigation;
    protected $docColors;

    protected $xsltPath;
    protected $xmlPath;

    protected $xslHeader = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<!DOCTYPE xsl:stylesheet [\n<!ENTITY % htmlentities SYSTEM \"xslt://htmlentities.ent\"> %htmlentities;\n]>\n<xsl:stylesheet\n    version=\"1.0\"\n    xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\"\n    xmlns:dp=\"http://cms.depagecms.net/ns/depage\"\n    xmlns:db=\"http://cms.depagecms.net/ns/database\"\n    xmlns:proj=\"http://cms.depagecms.net/ns/project\"\n    xmlns:pg=\"http://cms.depagecms.net/ns/page\"\n    xmlns:sec=\"http://cms.depagecms.net/ns/section\"\n    xmlns:edit=\"http://cms.depagecms.net/ns/edit\"\n    xmlns:exslt=\"http://exslt.org/common\"\n    extension-element-prefixes=\"xsl db proj pg sec edit exslt \">\n\n";
    protected $xslFooter = "\n    <!-- vim:set ft=xslt sw=4 sts=4 fdm=marker : -->\n</xsl:stylesheet>";
    protected $xmlHeader = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<proj:newnode xmlns:proj=\"http://cms.depagecms.net/ns/project\" xmlns:sec=\"http://cms.depagecms.net/ns/section\" xmlns:edit=\"http://cms.depagecms.net/ns/edit\" version=\"1.0\" extension-element-prefixes=\"proj sec edit \">\n";
    protected $xmlFooter = "\n    <!-- vim:set ft=xml sw=4 sts=4 fdm=marker : -->\n</proj:newnode>";

    // {{{ factory()
    /**
     * @brief factory
     *
     * @param mixed $
     * @return void
     **/
    public static function factory($project, $pdo)
    {
        $classFile = "projects/" . $project->name . "/import/Import.php";
        if (file_exists($classFile)) {
            // import class from project directory
            $class = "\\Depage\\Cms\\Import\\" . ucfirst($project->name);
            require_once($classFile);

            return new $class($project, $pdo);
        } else {
            return new \Depage\Cms\Import($project, $pdo);
        }
    }
    // }}}
    // {{{ constructor
    public function __construct($project, $pdo)
    {
        $this->projectName = $project->name;

        $this->xsltPath = "projects/" . $this->projectName . "/xslt/";
        $this->xmlPath = "projects/" . $this->projectName . "/xml/";

        $this->pdo = $pdo;
        $this->project = $project;
        $this->xmldb = $this->project->getXmlDb();
    }
    // }}}
    // {{{ importProject()
    public function importProject($xmlFile)
    {
        $this->loadBackup($xmlFile);

        $this->cleanDocs();

        $this->getDocs();

        $this->extractSettings();
        $this->extractNavigation();
        $this->extractTemplates();
        $this->extractNewnodes();
        $this->extractColorschemes();

        foreach($this->pageIds as $pageId) {
            $this->extractPagedataForId($pageId);
        }

        $this->clearTransformCache();

        return $this->xmlNavigation;
    }
    // }}}
    // {{{ addImportTask()
    public function addImportTask($taskName, $xmlFile)
    {
        $task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $taskName, $this->projectName);

        $this->loadBackup($xmlFile);

        $this->getDocs();

        // @todo update extractNavigtion not to be called twice here
        $this->extractSettings();
        $this->extractNavigation();

        $classFile = realpath("projects/" . $this->projectName . "/import/Import.php");
        if (file_exists($classFile)) {
            $initId = $task->addSubtask("init", "require_once(%s); \$import = %s;", [$classFile, $this]);
        } else {
            $initId = $task->addSubtask("init", "\$import = %s;", [$this]);
        }

        $loadId = $task->addSubtask("load", "\$import->loadBackup(%s);", [$xmlFile], $initId);
        $task->addSubtask("clean docs", "\$import->cleanDocs();", [], $loadId);

        $getDocsId = $task->addSubtask("getDocs", "\$import->getDocs();", [], $loadId);

        $task->addSubtask("extract settings", "\$import->extractSettings();", [], $getDocsId);
        $task->addSubtask("extract navigation", "\$import->extractNavigation();", [], $getDocsId);
        $task->addSubtask("extract templates", "\$import->extractTemplates();", [], $getDocsId);
        $task->addSubtask("extract newnodes", "\$import->extractNewnodes();", [], $getDocsId);
        $task->addSubtask("extract colorschemes", "\$import->extractColorschemes();", [], $getDocsId);

        foreach($this->pageIds as $pageId) {
            $task->addSubtask("extract page $pageId", "\$import->extractPagedataForId(%s);", [$pageId], $getDocsId);
        }

        $task->addSubtask("clear transform cache", "\$import->clearTransformCache();", [], $getDocsId);

        $task->begin();

        return $task;
    }
    // }}}

    // {{{ loadBackup()
    public function loadBackup($xmlFile)
    {
        $this->xmlImport = new \Depage\Xml\Document();
        $this->xmlImport->load($xmlFile);
    }
    // }}}

    // {{{ cleanDocs()
    public function cleanDocs()
    {
        $this->xmldb->clearTables();
        $this->xmldb->updateSchema();
    }
    // }}}
    // {{{ clearTransformCache()
    /**
     * @brief clearTransformCache
     *
     * @return void
     **/
    public function clearTransformCache()
    {
        $this->project->clearTransformCache();
    }
    // }}}
    // {{{ getDocs()
    public function getDocs()
    {
        $this->docNavigation = $this->xmldb->getDoc("pages");
        if (!$this->docNavigation) {
            $this->docNavigation = $this->xmldb->createDoc("Depage\\Cms\\XmlDocTypes\\Pages", "pages");
        }

        $this->docSettings = $this->xmldb->getDoc("settings");
        if (!$this->docSettings) {
            $this->docSettings = $this->xmldb->createDoc("Depage\\Cms\\XmlDocTypes\\Settings", "settings");
        }

        $this->docColors = $this->xmldb->getDoc("colors");
        if (!$this->docColors) {
            $this->docColors = $this->xmldb->createDoc("Depage\\Cms\\XmlDocTypes\\Colors", "colors");
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
            $this->xmlNavigation = new \Depage\Xml\Document();
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

        // update nav to tag names
        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $node = $nodelist->item($i);
            $attrs = $node->attributes;

            for ($j = $attrs->length - 1; $j >= 0; $j--) {
                $attrNode = $attrs->item($j);
                $attrName = $attrNode->nodeName;
                if (strpos($attrName, "nav_tag_") === 0 || strpos($attrName, "nav_cat_") === 0) {
                    $newName = "tag_" . substr($attrName, 8);
                    $node->setAttribute($newName, $attrNode->nodeValue);
                    $node->removeAttribute($attrName);
                }
            }
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
        $pagelist = $xpathImport->query("//*[@db:id = '$dbref']");

        // save pagedata
        if ($pagelist->length === 1) {
            $xmlData = new \Depage\Xml\Document();

            $dataNode = $xmlData->importNode($pagelist->item(0), true);
            $xmlData->appendChild($dataNode);
            list($ns, $docType) = explode(":", $this->docNavigation->getNodeNameById($pageId));
            $docType = ucfirst(strtolower($docType));

            $this->updatePageData($xmlData);

            $doc = $this->xmldb->createDoc("Depage\\Cms\\XmlDocTypes\\$docType");
            $newId = $doc->save($xmlData);
            $info = $doc->getDocInfo();

            // updated reference attributes
            $this->docNavigation->removeAttribute($pageId, "db:ref");
            $this->docNavigation->setAttribute($pageId, "db:docref", $info->name);

            $metaNode = $dataNode->getElementsByTagNameNS("http://cms.depagecms.net/ns/page", "meta")->item(0);
            $changeDate = $metaNode->getAttribute("lastchange_UTC");
            $uid = $this->getNewUserId($metaNode->getAttribute("lastchange_uid"));

            $doc->updateLastChange($changeDate, $uid);
        }
    }
    // }}}
    // {{{ extractTemplates()
    public function extractTemplates()
    {
        $xpath = new \DOMXPath($this->xmlImport);
        $nodelist = $xpath->query("//proj:tpl_templates_struct");

        if (!is_dir($this->xsltPath)) mkdir($this->xsltPath);

        $oldXsl = glob($this->xsltPath . "/*/*");
        foreach ($oldXsl as $file) {
            unlink($file);
        }

        // extract template tree
        if ($nodelist->length === 1) {
            $xmlTemplates = new \Depage\Xml\Document();
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
                    if (!is_dir($path)) {
                        mkdir($path);
                    }
                    $filename = $path . Html::getEscapedUrl($namePrefix . $child->getAttribute("name")) . ".xsl";

                    $replacements = $this->getTemplateReplacements();
                    $xsl = str_replace(array_keys($replacements), array_values($replacements), trim($dataNode->nodeValue));

                    $replacements = $this->getTemplateReplacementRegexes();
                    foreach ($replacements as $pattern => $replacement) {
                        $xsl = preg_replace($pattern, $replacement, $xsl);
                    }

                    file_put_contents($filename, "{$this->xslHeader}    {$xsl}\n{$this->xslFooter}");

                    if (strpos($filename, "CSS") !== false) {
                        $path = $this->xsltPath . "css/";
                        if (!is_dir($path)) {
                            mkdir($path);
                        }
                        $filename = $path . Html::getEscapedUrl($namePrefix . $child->getAttribute("name")) . ".xsl";
                        $extraXsl = "";
                        $extraXsl .= "    <xsl:output method=\"text\"  omit-xml-declaration=\"yes\"/>\n";
                        $extraXsl .= "    <xsl:template match=\"proj:colorschemes\"><xsl:call-template name=\"pg:css\" /></xsl:template>\n";
                        file_put_contents($filename, "{$this->xslHeader}{$extraXsl}\n    {$xsl}\n{$this->xslFooter}");
                    }
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

        $oldXml = glob($this->xmlPath . "/*");
        foreach ($oldXml as $file) {
            unlink($file);
        }

        for ($i = 0; $i < $nodelist->length; $i++) {
            $node = $nodelist->item($i);

            $name = $node->getAttribute("name");
            $pos = $i;

            $validParentsNode = $node->getElementsByTagNameNS("http://cms.depagecms.net/ns/edit", "newnode_valid_parents")->item(0);

            $contentNode = $node->getElementsByTagNameNS("http://cms.depagecms.net/ns/edit", "newnode")->item(0);
            $contentDoc = new \Depage\Xml\Document();
            $contentDoc->loadXML($this->xmlHeader . trim($contentNode->nodeValue) . $this->xmlFooter);

            $nodeTypes = new \Depage\Cms\XmlDocTypes\Page($this->xmldb, $this->docNavigation->getDocId());

            $nodeTypes->addNodeType(
                $name,
                $contentDoc,
                $validParentsNode->nodeValue,
                $pos
            );
        }
    }
    // }}}
    // {{{ extractColorschemes()
    public function extractColorschemes()
    {
        $xpath = new \DOMXPath($this->xmlImport);
        $nodelist = $xpath->query("//proj:colorschemes");

        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $this->xmlColors = new \Depage\Xml\Document();
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
            $this->xmlSettings = new \Depage\Xml\Document();
            $node = $this->xmlSettings->importNode($nodelist->item($i), true);
            $this->xmlSettings->appendChild($node);
        }
        $this->xmlSettings = $this->updateSettings($this->xmlSettings);

        $this->docSettings->save($this->xmlSettings);
    }
    // }}}

    // {{{ updatePageData()
    protected function updatePageData($xmlData)
    {
        $this->updatePageRefs($xmlData);
        $this->updateLibRefs($xmlData);
        $this->updateImageSizes($xmlData);
        $this->updateSourceVars($xmlData);
    }
    // }}}
    // {{{ updatePageRefs()
    protected function updatePageRefs($xmlData)
    {
        // test all links with a pageref
        $xpath = new \DOMXPath($xmlData);
        $nodelist = $xpath->query("//*[@href and starts-with(@href,'pageref:')]");

        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $node = $nodelist->item($i);
            $href = $node->getAttribute("href");

            $hrefId = preg_replace("/pageref:[\/]{0,3}/", "", $href);

            if (isset($this->pageIds[$hrefId])) {
                $node->setAttribute("href", "pageref://" . $this->pageIds[$hrefId]);
            } else {
                // clear links with a non-existent page reference
                $node->setAttribute("href", "");
            }
        }

        // test all links with href_id
        $xpath = new \DOMXPath($xmlData);
        $nodelist = $xpath->query("//*[@href_id]");

        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $node = $nodelist->item($i);
            $hrefId = $node->getAttribute("href_id");

            if (isset($this->pageIds[$hrefId])) {
                $node->setAttribute("href_id", $this->pageIds[$hrefId]);
            } else {
                // clear links with a non-existent page reference
                $node->setAttribute("href_id", "");
            }
        }
    }
    // }}}
    // {{{ updateLibRefs()
    protected function updateLibRefs($xmlData)
    {
        $xpath = new \DOMXPath($xmlData);
        $nodelist = $xpath->query("//*[@href and starts-with(@href,'libref:')]");

        // test all links with a libref
        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $node = $nodelist->item($i);
            $href = $node->getAttribute("href");

            $href = preg_replace("/libref:[\/]{1,3}/", "libref://", $href);

            $node->setAttribute("href", $href);
        }
        $nodelist = $xpath->query("//*[@src and starts-with(@src,'libref:')]");

        // test all links with a libref
        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $node = $nodelist->item($i);
            $href = $node->getAttribute("src");

            $href = preg_replace("/libref:[\/]{1,3}/", "libref://", $href);

            $node->setAttribute("src", $href);
        }

    }
    // }}}
    // {{{ updateImageSizes()
    protected function updateImageSizes($xmlData)
    {
        $xpath = new \DOMXPath($xmlData);
        $xpath->registerNamespace("edit", "http://cms.depagecms.net/ns/edit");
        $nodelist = $xpath->query("//edit:img[@force_width or @force_height]");

        // test all images with a forced with or height
        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $node = $nodelist->item($i);

            $size = "";
            $width = $node->getAttribute("force_width");
            $height = $node->getAttribute("force_height");

            if ($width != "" && $height != "") {
                $size = $width . "x" . $height;
            } elseif ($width != "") {
                $size = $width . "xX";
            } elseif ($height != "") {
                $size = "Xx" . $height;
            }
            // @todo replace with variables to keep it dynamic
            $node->setAttribute("force_size", $size);

            $node->removeAttribute("force_width");
            $node->removeAttribute("force_height");
        }

    }
    // }}}
    // {{{ updateSourceVars()
    protected function updateSourceVars($xmlData)
    {
        $xpath = new \DOMXPath($xmlData);
        $xpath->registerNamespace("edit", "http://cms.depagecms.net/ns/edit");
        $nodelist = $xpath->query("//edit:plain_source/text()");

        $replacements = [
            "\$tt_lang" => "\$currentLang",
        ];

        // test all source elements
        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $text = $nodelist->item($i)->data;
            $text = str_replace(array_keys($replacements), array_values($replacements), $text);

            $nodelist->item($i)->data = $text;
        }

    }
    // }}}
    // {{{ updateSettings()
    protected function updateSettings($xmlData)
    {
        $xsltProc = new \XSLTProcessor();

        $xslDom = new \Depage\Xml\Document();
        $xslDom->load(__DIR__ . "/Xslt/Import/LegacySettings.xsl");

        $xsltProc->importStylesheet($xslDom);
        $newXml = $xsltProc->transformToDoc($xmlData);

        $settingsXml = \Depage\Xml\Document::fromDomDocument($newXml);

        return $settingsXml;
    }
    // }}}

    // {{{ getTemplateReplacements()
    /**
     * @brief getTemplateReplacements
     *
     * @return void
     **/
    public function getTemplateReplacements()
    {
        // string replacement map
        $replacements = [
            // general
            "\t" => "    ",
            "\n" => "\n    ",
            "document('call:doctype/html/5')" => "'&lt;!DOCTYPE html&gt;&#xa;'",
            "document('get:navigation')" => "\$navigation",
            "document('get:colors')" => "\$colors",
            "document('get:settings')" => "\$settings",
            "document('get:languages')/proj:languages" => "\$languages",
            "document('call:getversion')" => "\$depageVersion",
            "document(concat('get:page/'," => "(dp:getPage(",
            "document(concat('call:changesrc/'," => "(dp:changesrc(",
            "document(concat('call:/changesrc/'," => "(dp:changesrc(",
            "(dp:changesrc( edit:plain_source))/*" => "(dp:changesrc(edit:plain_source))",
            "document(concat('call:urlencode/'," => "(dp:urlencode(",
            "document(concat('call:replaceemailchars/'," => "(dp:replaceEmailChars(",
            "document(concat('call:replaceEmailChars/'," => "(dp:replaceEmailChars(",
            "<xsl:value-of select=\"(dp:replaceEmailChars( 'mailto:'," => "mailto: <xsl:value-of select=\"(dp:replaceEmailChars(",
            "document(concat('call:atomizetext/'," => "(dp:atomizeText(",
            "document(concat('call:phpescape/'," => "(dp:phpEscape(",
            "document(concat('call:formatdate/'," => "(dp:formatDate(",
            "document('call:formatdate////Y')" => "dp:formatDate('', 'Y')",
            "href=\"get:xslt/" => "href=\"xslt://",
            "pageref:/" => "pageref://",
            "pageref:///" => "pageref://",
            "document(concat('call://fileinfo/libref:" => "dp:fileinfo(concat('libref:",
            "document(concat('call:fileinfo/'," => "(dp:fileinfo(",
            "\$baseurl" => "\$baseUrl",
            "<xsl:param name=\"baseurl\"" => "<xsl:param name=\"baseUrl\"",
            "\$tt_lang" => "\$currentLang",
            "\$content_type" => "\$currentContentType",
            "\$content_encoding" => "\$currentEncoding",
            "\$tt_actual_id" => "\$currentPageId",
            "\$tt_actual_colorscheme" => "\$currentColorscheme",
            "\$tt_multilang" => "\$currentPage/@multilang",
            "\$depage_is_live" => "\$depageIsLive",
            "\$tt_var_" => "\$var-",
            "\$media_type" => "'global'",
            "/pg:page/pg:page_data" => "/pg:page_data",
            "/pg:page/@multilang" => "\$currentPage/@multilang",
            "\"/pg:page\"" => "\"\$currentPage\"",
            "\"/pg:page/" => "\"\$currentPage/",
            "<xsl:template match=\"/\">" => "<xsl:output method=\"html\"/>\n    <xsl:template match=\"/\">",
            "nav_tag_" => "tag_",
        ];

        return $replacements;
    }
    // }}}
    // {{{ getTemplateReplacementRegexes()
    /**
     * @brief getTemplateReplacementRegexes
     *
     * @return void
     **/
    public function getTemplateReplacementRegexes()
    {
        // regex replacement map
        $replacements = [
            "/\\\$ttc_([-_a-z0-9]*)/i" => "dp:color('$1')",
            "/libref:[\/]{1,3}/i" => "libref://",
            "/pageref:[\/]{1,3}/i" => "pageref://",
        ];

        return $replacements;
    }
    // }}}
    // {{{ getNewUserId()
    /**
     * @brief getNewUserId
     *
     * @param mixed $
     * @return void
     **/
    public function getNewUserId($uid)
    {
        $user = \Depage\Auth\User::loadById($this->pdo, $uid);

        if (!$user) {
            $uid = null;
        }

        return $uid;
    }
    // }}}

    // {{{ __sleep()
    /**
     * allows Depage\Db\Pdo-object to be serialized
     */
    public function __sleep()
    {
        return [
            'projectName',
            'xsltPath',
            'xmlPath',
            'pdo',
            'project',
        ];
    }
    // }}}
    // {{{ __wakeup()
    /**
     * allows Depage\Db\Pdo-object to be unserialized
     */
    public function __wakeup()
    {
        $this->xmldb = $this->project->getXmlDb();
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
