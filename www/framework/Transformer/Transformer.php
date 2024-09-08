<?php

namespace Depage\Transformer;

use \Depage\Html\Html;

abstract class Transformer
{
    protected $xmlGetter;
    protected $xmlPath;
    protected $xsltPath;
    protected $libPath;
    protected $addonsPath;
    protected $xsltProc = null;
    protected $xsltProcs = [];
    protected $xsltCache = null;
    protected $xmlnav = null;
    protected $isLive = false;
    protected $profiling = false;
    protected $usedDocuments = [];
    protected $aliases = [];
    protected $addons = [];
    protected $log;
    protected $fl;
    protected $transformCache;
    protected $previewType = "pre";
    protected $savePath = "";

    public $template;
    public $project;
    public $baseUrl = "";
    public $publishId = "";
    public $baseUrlStatic = "";
    public $useAbsolutePaths = false;
    public $useBaseUrl = false;
    public $routeHtmlThroughPhp = false;
    public $lang = "";
    public $currentPath = "";
    public $currentSubtype = "";

    // {{{ factory()
    static public function factory($previewType, $xmlGetter, $project, $template, $transformCache = null)
    {
        if ($previewType == "live") {
            return new Live($xmlGetter, $project, $template, $transformCache);
        } elseif ($previewType == "pre" || $previewType == "preview") {
            return new Preview($xmlGetter, $project, $template, $transformCache);
        } elseif ($previewType == "history" || preg_match("/^(history).*$/", $previewType)) {
            $t = new History($xmlGetter, $project, $template, null);
            $t->previewType = $previewType;
            $t->baseUrl = DEPAGE_BASE . "project/{$project->name}/preview/{$template}/{$previewType}/";
            $t->baseUrlStatic = DEPAGE_BASE . "project/{$project->name}/preview/{$template}/{$previewType}/";

            return $t;
        } else {
            return new Dev($xmlGetter, $project, $template, null);
        }
    }
   // }}}
    // {{{ constructor()
    public function __construct($xmlGetter, $project, $template, $transformCache = null)
    {
        $this->xmlGetter = $xmlGetter;
        $this->project = $project;
        $this->template = $template;
        $this->transformCache = $transformCache;

        // @todo complete baseurl this in a better way, also based on previewTyoe
        // @todo fix this for live view !important
        $this->baseUrl = DEPAGE_BASE . "project/{$this->project->name}/preview/{$this->template}/{$this->previewType}/";
        $this->baseUrlStatic = DEPAGE_BASE . "project/{$this->project->name}/preview/{$this->template}/{$this->previewType}/";

        $this->lateInitialize();
    }
    // }}}
    // {{{ lateInitialize()
    public function lateInitialize()
    {
        // add log object
        $this->log = new \Depage\Log\Log();

        // set basic variables
        $this->fl = new \Depage\Cms\FileLibrary($this->project->getPdo(), $this->project);

        $this->xsltPath = "projects/{$this->project->name}/xslt/";
        $this->xmlPath = "projects/{$this->project->name}/xml/";
        $this->libPath = "projects/{$this->project->name}/lib/";
        $this->addonsPath = "projects/{$this->project->name}/addons/";

        // get cache instance for templates
        $this->xsltCache = \Depage\Cache\Cache::factory("xslt");
    }
    // }}}
    // {{{ setBaseUrl()
    /**
     * @brief setBaseUrl
     *
     * @param mixed $url
     * @return void
     **/
    public function setBaseUrl($url)
    {
        $this->baseUrl = $url;
    }
    // }}}
    // {{{ setBaseUrlStatic()
    /**
     * @brief setBaseUrlStatic
     *
     * @param mixed $url
     * @return void
     **/
    public function setBaseUrlStatic($url)
    {
        $this->baseUrlStatic = $url;
    }
    // }}}
    // {{{ getXsltProc()
    public function getXsltProc($subtype = "_"):\XSLTProcessor
    {
        if (isset($this->xsltProcs[$subtype])) {
            return $this->xsltProcs[$subtype];
        }

        libxml_use_internal_errors(true);

        $xsltProc = new \XSLTProcessor();

        $this->registerStreams($xsltProc);
        $this->registerFunctions($xsltProc);

        $this->loadAddons($xsltProc);

        $xslDOM = $this->getXsltTemplate($this->template, $subtype);

        if ($this->profiling) {
            $file = "logs/xslt-profiling";
            if ($subtype != "_") {
                $file .= "-$subtype";
            }
            $xsltProc->setProfiling("$file.txt");
        }
        if (!$xsltProc->importStylesheet($xslDOM)) {
            $messages = $this->handleLibXmlErrors();
            $error = "Could not import stylesheet:\n" . implode("\n", $messages);

            throw new \Exception($error);
        }

        $this->xsltProcs[$subtype] = $xsltProc;

        return $xsltProc;
    }
    // }}}
    // {{{ loadAddons()
    public function loadAddons($xsltProc)
    {
        $this->addons = [];

        $files = glob($this->addonsPath . "*/Transformer.php");
        foreach ($files as $file) {
            $addon = basename(dirname($file));
            $class = ucfirst($this->project->name) . "\\" . $addon . "\\Transformer";

            require_once($file);
            $this->addons[$addon] = new $class($this->project);

            if (method_exists($this->addons[$addon], "registerStreams")) {
                $this->addons[$addon]->registerStreams($xsltProc);
            }
            if (method_exists($this->addons[$addon], "registerFunctions")) {
                $this->addons[$addon]->registerFunctions($xsltProc);
            }
        }
    }
    // }}}
    // {{{ getXsltTemplate()
    /**
     * @return  null
     */
    protected function getXsltTemplate($template, $subtype = "_")
    {
        $regenerate = false;

        $xsltSharedPath = "{$this->xsltPath}{$template}/_/";
        if ($subtype == "_") {
            $xsltPath = "{$this->xsltPath}{$template}/";
        } else {
            $xsltPath = "{$this->xsltPath}{$template}/{$subtype}/";
        }

        $files = (glob("{$xsltPath}*.xsl") + glob("{$xsltSharedPath}*.xsl")) ?? [];
        $files = array_merge(
            glob("{$xsltSharedPath}*.xsl") ?? [],
            glob("{$xsltPath}*.xsl") ?? []
        );
        $hash = sha1(implode(" ", $files));

        $xslFile = "{$this->project->name}/{$template}/{$this->previewType}_{$subtype}_{$hash}.xsl";

        if (count($files) == 0) {
            throw new \Exception("No XSL templates found in '$this->xsltPath$template/'.");
        }

        if (($age = $this->xsltCache->age($xslFile)) !== false) {
            foreach ($files as $file) {
                $regenerate = $regenerate || $age < filemtime($file);
                if ($regenerate) {
                    break;
                }
            }
        } else {
            $regenerate = true;
        }
        // @todo clear xsl cache when settings are saved
        // @todo clear transform cache when regenerating xsl template
        //$regenerate = true;

        $doc = new \Depage\Xml\Document();
        $doc->resolveExternals = true;

        if (!$regenerate) {
            $doc->load($this->xsltCache->getPath($xslFile));

            return $doc;
        }

        $this->xsltCache->delete("{$this->project->name}/{$template}/{$this->previewType}_{$subtype}*.xsl");

        if (!is_null($this->transformCache)) {
            $this->transformCache->clearAll();
        }
        $xslt = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        $xslt .= $this->getXsltEntities();
        $xslt .= "<xsl:stylesheet
            version=\"1.0\"
            xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\"
            xmlns:php=\"http://php.net/xsl\"
            xmlns:dp=\"http://cms.depagecms.net/ns/depage\"
            xmlns:db=\"http://cms.depagecms.net/ns/database\"
            xmlns:proj=\"http://cms.depagecms.net/ns/project\"
            xmlns:pg=\"http://cms.depagecms.net/ns/page\"
            xmlns:sec=\"http://cms.depagecms.net/ns/section\"
            xmlns:edit=\"http://cms.depagecms.net/ns/edit\"
            xmlns:exslt=\"http://exslt.org/common\"
            xmlns:func=\"http://exslt.org/functions\"
            xmlns:str=\"http://exslt.org/strings\"
            extension-element-prefixes=\"xsl db proj pg sec edit func exslt str \"
        />";

        $doc->loadXML($xslt);
        $root = $doc->documentElement;

        // add include base functions
        $n = $doc->createElementNS("http://www.w3.org/1999/XSL/Transform", "xsl:include");
        $n->setAttribute("href", "xslt://functions.xsl");
        $root->appendChild($n);

        // add basic paramaters and variables
        $params = [
            'currentLang' => null,
            'currentPageId' => null,
            'currentPath' => "''",
            'depageIsLive' => null,
            'depagePreviewType' => null,
            'baseUrl' => null,
            'baseUrlStatic' => null,
            'projectName' => null,
            'libPath' => "'" . htmlspecialchars('file://' . str_replace(" ", "%20", realpath($this->libPath))) . "'",
        ];
        // if there are no partial xsl templates, add old default parameters
        $subFiles = glob("{$this->xsltPath}{$template}/*/*.xsl") ?? [];
        if (count($subFiles) == 0) {
            $params += [
                'navigation' => "document('xmldb://pages')",
                'settings' => "document('xmldb://settings')",
                'colors' => "document('xmldb://colors')",
                'currentColorscheme' => "dp:choose(//pg:meta[1]/@colorscheme, //pg:meta[1]/@colorscheme, \$colors//proj:colorscheme[@name][1]/@name)",
                'languages' => "\$settings/proj:settings/proj:languages",
                'currentPage' => "\$navigation//pg:*[@status = 'active']",
            ];
        }

        $variables = [
            'var-ga-Account' => "''",
            'var-ga-Domain' => "''",
            'var-pa-siteId' => "''",
            'var-pa-Domain' => "''",
            'var-fb-Account' => "''",
            'var-pinterest-tagId' => "''",
        ];

        // add variables from settings
        $settings = $this->xmlGetter->getDocXml("settings");
        if (!$settings) {
            throw new \Exception("no settings document");
        }

        $xpath = new \DOMXPath($settings);
        $nodelist = $xpath->query("//proj:variable");

        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $node = $nodelist->item($i);
            $variables["var-" . $node->getAttribute("name")] = "'" . htmlspecialchars($node->getAttribute("value")) . "'";
        }

        // now add to xslt
        foreach ($params as $key => $value) {
            $n = $doc->createElementNS("http://www.w3.org/1999/XSL/Transform", "xsl:param");
            $n->setAttribute("name", $key);
            if (is_string($value) || !empty($value)) {
                $n->setAttribute("select", $value);
            } else {
                $n->setAttribute("select", "false()");
            }
            $root->appendChild($n);
        }
        foreach ($variables as $key => $value) {
            $n = $doc->createElementNS("http://www.w3.org/1999/XSL/Transform", "xsl:variable");
            $n->setAttribute("name", $key);
            $n->setAttribute("select", $value);
            $root->appendChild($n);
        }
        $this->addXsltIncludes($doc, $files);

        $this->xsltCache->set($xslFile, $doc);

        return $doc;
    }
    // }}}
    // {{{ getXsltEntities()
    protected function getXsltEntities()
    {
        return "<!DOCTYPE xsl:stylesheet [ <!ENTITY % htmlentities SYSTEM \"xslt://htmlentities.ent\"> %htmlentities; ]>";
    }
    // }}}

    // {{{ transformUrl()
    public function transformUrl($urlPath, $lang)
    {
        $this->lang = $lang;

        list($pageId, $pagedataId, $this->currentPath) = $this->getPageIdFor($urlPath);

        if ($pageId === false || $pagedataId === false) {
            // php fallback for transparent php links
            $this->currentPath = preg_replace("/\.html$/", ".php", $urlPath);

            list($pageId, $pagedataId, $this->currentPath) = $this->getPageIdFor($this->currentPath);
        }
        if ($pageId === false || $pagedataId === false) {
            throw new \Exception("page '{$urlPath}' does not exist");
        }

        $this->savePath = "projects/" . $this->project->name . "/cache-" . $this->template . "-" . $this->lang . $this->currentPath;

        $content = $this->transformDoc($pageId, $pagedataId, $lang);

        return $content;
    }
    // }}}
    // {{{ transformDoc()
    public function transformDoc($pageId, $pagedataId, $lang)
    {
        $this->lang = $lang;
        $id = $lang;
        $templateName = $this->template . "-" . $this->previewType . $this->publishId;

        if (!is_null($this->transformCache) && $this->transformCache->exist($pagedataId, $templateName, $id)) {
            $content = $this->transformCache->get($pagedataId, $templateName, $id);

            return $content;
        }

        $pageXml = $this->xmlGetter->getDocXml($pagedataId);
        if ($pageXml === false) {
            throw new \Exception("page does not exist");
        }

        $this->clearUsedDocuments();
        $this->addToUsedDocuments($pagedataId);
        $params = [
            "currentLang" => $this->lang,
            "currentPageId" => $pageId,
            "currentPagedataId" => $pagedataId,
            "currentPath" => $this->currentPath,
            "currentContentType" => "text/html",
            "currentEncoding" => "UTF-8",
            "projectName" => $this->project->name,
            "depageVersion" => \Depage\Depage\Runner::getName() . " " . \Depage\Depage\Runner::getVersion(),
            "depageIsLive" => $this->isLive ? "true" : "",
            "depagePreviewType" => $this->previewType,
            "baseUrl" => $this->baseUrl,
            "baseUrlStatic" => $this->baseUrlStatic,
        ];
        if (!empty($_GET['__dpPreviewColor'])) {
            $params['currentColorscheme'] = $_GET['__dpPreviewColor'];
        }
        $xsltProc = $this->getXsltProc();

        $oldUseAbsolutePath = $this->useAbsolutePaths;
        $oldUseBaseUrl = $this->useBaseUrl;

        $xsltProc->setParameter("", $params);

        $content = $xsltProc->transformToXml($pageXml);
        $messages = $this->handleLibXmlErrors();

        if (!$content) {
            $error = "Could not transform the XML document:\n" . implode("\n", $messages);

            throw new \Exception($error);
        }

        // reset absolute path settings after transform
        $this->useAbsolutePaths = $oldUseAbsolutePath;
        $this->useBaseUrl = $oldUseBaseUrl;

        $cleaner = new \Depage\Html\Cleaner();
        $content = $cleaner->clean($content);

        if (!is_null($this->transformCache)) {
            $this->transformCache->set($pagedataId, $this->getUsedDocuments(), $content, $templateName, $id);
        }

        return $content;
    }
    // }}}
    // {{{ transform()
    /**
     * @brief transform
     *
     * @param mixed $
     * @return void
     **/
    public function transform($xml, $parameters)
    {
        if (is_null($this->xsltProc)) {
            $this->lateInitialize();
        }
        $xsltProc = $this->getXsltProc();

        $oldUseAbsolutePath = $this->useAbsolutePaths;
        $oldUseBaseUrl = $this->useBaseUrl;

        $xsltProc->setParameter("", $parameters);

        $content = $xsltProc->transformToXml($xml);
        $messages = $this->handleLibXmlErrors();

        if (!$content) {
            $error = "Could not transform the XML document:\n" . implode("\n", $messages);

            throw new \Exception($error);
        }

        // reset absolute path settings after transform
        $this->useAbsolutePaths = $oldUseAbsolutePath;
        $this->useBaseUrl = $oldUseBaseUrl;

        return $content;
    }
    // }}}
    // {{{ transformSubdoc()
    public function transformSubdoc($docId, $lang, $subtype)
    {
        $this->lang = $lang;
        $id = $lang;
        $templateName = $this->template . "-" . $this->previewType . $this->publishId . "-" . $subtype;

        $docId = $this->xmlGetter->docExists($docId);

        if ($docId === false) {
            throw new \Exception("page does not exist");
        }

        if (!is_null($this->transformCache) && $this->transformCache->exist($docId, $templateName, $id)) {
            $content = new \DOMDocument();
            $success = $content->loadXML($this->transformCache->get($docId, $templateName, $id));

            if ($success) {
                $this->addToUsedDocuments(...$this->transformCache->getUsedFor($docId, $templateName, $id));

                return $content;
            }
        }

        $pageXml = $this->xmlGetter->getDocXml($docId);

        $oldUseAbsolutePath = $this->useAbsolutePaths;
        $oldUseBaseUrl = $this->useBaseUrl;

        $this->useAbsolutePaths = false;
        $this->useBaseUrl = true;

        $this->currentSubtype = $subtype;
        $this->clearUsedDocuments($subtype);
        $this->addToUsedDocuments($docId);
        $params = [
            "currentLang" => $this->lang,
            "currentPath" => "",
            "currentPagedataId" => $docId,
            "currentContentType" => "text/html",
            "currentEncoding" => "UTF-8",
            "projectName" => $this->project->name,
            "depageVersion" => \Depage\Depage\Runner::getName() . " " . \Depage\Depage\Runner::getVersion(),
            "depageIsLive" => $this->isLive ? "true" : "",
            "depagePreviewType" => $this->previewType,
            "baseUrl" => $this->baseUrl,
            "baseUrlStatic" => $this->baseUrlStatic,
        ];
        $xsltProc = $this->getXsltProc($subtype);

        $xsltProc->setParameter("", $params);

        $content = $xsltProc->transformToDoc($pageXml);
        $messages = $this->handleLibXmlErrors();

        if (!$content) {
            $error = "Could not transform the XML document:\n" . implode("\n", $messages);

            throw new \Exception($error);
        }

        // reset absolute path settings after transform
        $this->useAbsolutePaths = $oldUseAbsolutePath;
        $this->useBaseUrl = $oldUseBaseUrl;

        if (!is_null($this->transformCache)) {
            $contentString = $content->saveXML();
            if (!empty($contentString)) {
                $this->transformCache->set($docId, $this->getUsedDocuments($subtype), $contentString, $templateName, $id);
            }
        }
        $this->currentSubtype = "";

        return $content;
    }
    // }}}
    // {{{ saveTransformed()
    /**
     * @return  bool
     */
    protected function saveTransformed($savePath, $content):bool
    {
        $dynamic = array(
            "php",
        );
        $info = pathinfo($savePath);
        if (!is_dir($info['dirname'])) {
            @mkdir($info['dirname'], 0777, true);
        }

        file_put_contents($savePath, $content);

        return in_array($info['extension'], $dynamic);
    }
    // }}}
    // {{{ display()
    public function display($urlPath, $lang)
    {
        $html = $this->transformUrl($urlPath, $lang);

        // cache transformed source to be served by browser directly
        $dynamic = $this->saveTransformed($this->savePath, $html);

        if ($dynamic) {
            // tell index php to load the dynamic content
            $GLOBALS['replacementScript'] = $this->savePath;

            return "";
        }

        return $html;
    }
    // }}}

    // {{{ clearUsedDocuments()
    /**
     * @brief clearUsedDocuments
     *
     * @param mixed
     * @return void
     **/
    protected function clearUsedDocuments($subtype = "_")
    {
        if ($subtype == "_") {
            $this->usedDocuments = [];
            $this->usedDocuments["_"] = [];
        } else {
            $this->usedDocuments[$subtype] = [];
        }
    }
    // }}}
    // {{{ addToUsedDocuments()
    /**
     * @brief addToUsedDocuments
     *
     * @param mixed $docId
     * @return void
     **/
    public function addToUsedDocuments(...$docIds)
    {
        foreach ($docIds as $id) {
            $this->usedDocuments["_"][$id] = true;
        }
        if (empty($this->currentSubtype)) {
            return;
        }
        foreach ($docIds as $id) {
            $this->usedDocuments[$this->currentSubtype][$id] = true;
        }
    }
    // }}}
    // {{{ getUsedDocuments()
    /**
     * @brief getUsedDocuments
     *
     * @param mixed
     * @return void
     **/
    public function getUsedDocuments($subtype = "_")
    {
        return array_keys($this->usedDocuments[$subtype]);
    }
    // }}}

    // {{{ registerStreams
    /**
     * @return  null
     */
    protected function registerStreams($proc)
    {
        /*
         * @todo
         * call:doctype -> not necessary anymore, replace in debug xsl
         */
        $funcClass = new XsltFunctions($this, $this->fl);

        // register stream to get documents from xmldb
        \Depage\Cms\Streams\XmlDb::registerStream("xmldb", [
            "xmldb" => $this->xmlGetter,
            "transformer" => $this,
        ]);

        // register stream to get global xsl templates
        \Depage\Cms\Streams\Xslt::registerStream("xslt");

        // register stream to get page-links
        \Depage\Cms\Streams\Pageref::registerStream("pageref", [
            "transformer" => $this,
            "funcClass" => $funcClass,
        ]);

        // register stream to get links to library
        \Depage\Cms\Streams\Libref::registerStream("libref", [
            "transformer" => $this,
            "funcClass" => $funcClass,
        ]);
        \Depage\Cms\Streams\Libref::registerStream("libid", [
            "transformer" => $this,
            "funcClass" => $funcClass,
        ]);
    }
    // }}}
    // {{{ registerFunctions
    /**
     * @return  null
     */
    protected function registerFunctions($proc)
    {
        $funcClass = new XsltFunctions($this, $this->fl);

        \Depage\Cms\Xslt\FuncDelegate::resetFunctions();
        \Depage\Cms\Xslt\FuncDelegate::registerFunctions($proc, [
            "atomizeText" => [$funcClass, "atomizeText"],
            "autokeywords" => [$funcClass, "autokeywords"],
            "changesrc" => [$funcClass, "changeSrc"],
            "cssEscape" => [$funcClass, "cssEscape"],
            "fileinfo" => [$funcClass, "fileinfo"],
            "filesInFolder" => [$funcClass, "filesInFolder"],
            "formatDate" => [$funcClass, "formatDate"],
            "getLibRef" => [$funcClass, "getLibRef"],
            "getPageRef" => [$funcClass, "getPageRef"],
            "getUseAbsolutePaths" => [$funcClass, "getUseAbsolutePaths"],
            "getUseBaseUrl" => [$funcClass, "getUseBaseUrl"],
            "glob" => [$funcClass, "glob"],
            "includeUnparsed" => [$funcClass, "includeUnparsed"],
            "jsEscape" => [$funcClass, "JsEscape"],
            "phpEscape" => [$funcClass, "phpEscape"],
            "replaceEmailChars" => [$funcClass, "ReplaceEmailChars"],
            "setUseAbsolutePaths" => [$funcClass, "setUseAbsolutePaths"],
            "setUseBaseUrl" => [$funcClass, "setUseBaseUrl"],
            "tolower" => "mb_strtolower",
            "transformDoc" => [$this, "transformSubdoc"],
            "urlencode" => "rawurlencode",
            "urlinfo" => [$funcClass, "urlinfo"],
        ]);
    }
    // }}}
    // {{{ registerAliases()
    /**
     * @brief registerAliases
     *
     * @param mixed $aliases
     * @return void
     **/
    public function registerAliases($aliases)
    {
        $this->aliases = $aliases;
    }
    // }}}

    // {{{ getPageIdFor
    /**
     * @return  null
     */
    public function getPageIdFor($urlPath)
    {
        $xmlnav = $this->getXmlNav();

        if (!$xmlnav->getPageId($urlPath) && $this->routeHtmlThroughPhp) {
            $urlPath = preg_replace("/\.html$/", ".php", $urlPath);
        }
        if (!$xmlnav->getPageId($urlPath) && !empty($this->aliases)) {
            foreach ($this->aliases as $regex => $repl) {
                $regex = "/" . str_replace("/", "\/", $regex) . "/";
                $urlPath = preg_replace($regex, $repl, $urlPath);
            }
        }

        if ($pageId = $xmlnav->getPageId($urlPath)) {
            $docRef = $xmlnav->getPageDataId($pageId);

            return [$pageId, $docRef, $urlPath];
        }

        return [false, false, false];
    }
    // }}}

    // {{{ getXmlNav()
    /**
     * @brief getXmlNav
     *
     * @param mixed
     * @return void
     **/
    public function getXmlNav()
    {
        if (is_null($this->xmlnav)) {
            $this->xmlnav = new \Depage\Cms\XmlNav();
            $this->xmlnav->routeHtmlThroughPhp = $this->routeHtmlThroughPhp;
            $this->xmlnav->setXmlGetter($this->xmlGetter);
            $this->xmlnav->setPageXml($this->xmlGetter->getDocXml('pages'));
        }

        return $this->xmlnav;
    }
    // }}}

    // {{{ handleLibXmlErrors()
    /**
     * @brief handleLibXmlErrors
     *
     * @param mixed
     * @return void
     **/
    protected function handleLibXmlErrors()
    {
        $messages = [];
        $errors = libxml_get_errors();
        foreach($errors as $error) {
            $errorStr = $error->message . " in " . $error->file . " on line " . $error->line;
            $this->log->log("LibXMLError: " . $errorStr);
            $messages[] = $errorStr;
        }
        libxml_clear_errors();

        return $messages;
    }
    // }}}

    // {{{ __sleep()
    /**
     * allows Depage\Db\Pdo-object to be serialized
     */
    public function __sleep()
    {
        return array(
            'xmlGetter',
            'project',
            'template',
            'xsltPath',
            'xmlPath',
            'transformCache',
            'baseUrl',
            'baseUrlStatic',
            'publishId',
        );
    }
    // }}}
    // {{{ __wakeup()
    /**
     * allows Depage\Db\Pdo-object to be unserialized
     */
    public function __wakeup()
    {
        $this->lateInitialize();
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
