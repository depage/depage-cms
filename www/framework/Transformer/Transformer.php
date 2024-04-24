<?php

namespace Depage\Transformer;

use \Depage\Html\Html;

abstract class Transformer
{
    protected $project;
    protected $template;
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
    public $baseUrl = "";
    public $baseUrlStatic = "";
    public $useAbsolutePaths = false;
    public $useBaseUrl = false;
    public $routeHtmlThroughPhp = false;
    public $lang = "";
    public $currentPath = "";

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
        //$this->prefix = $this->pdo->prefix . "_proj_" . $this->project->name;
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
    public function getXsltProc($subtype = ""):\XSLTProcessor
    {
        $id = empty($subtype) ? "_" : $subtype;
        if (isset($this->xsltProcs[$id])) {
            return $this->xsltProcs[$id];
        }

        libxml_use_internal_errors(true);

        $xsltProc = new \XSLTProcessor();

        $this->registerStreams($xsltProc);
        $this->registerFunctions($xsltProc);

        $this->loadAddons($xsltProc);

        $xslDOM = $this->getXsltTemplate($this->template, $subtype);

        if ($this->profiling) {
            $file = "logs/xslt-profiling";
            if (!empty($subtype)) {
                $file .= "-$subtype";
            }
            $xsltProc->setProfiling("$file.txt");
        }
        $xsltProc->importStylesheet($xslDOM);

        $this->xsltProcs[$id] = $xsltProc;

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
    protected function getXsltTemplate($template, $subtype = "")
    {
        $regenerate = false;

        $xsltSharedPath = "{$this->xsltPath}{$template}/_/";
        if (empty($subtype)) {
            $xslFile = "{$this->project->name}/{$template}/{$this->previewType}.xsl";
            $xsltPath = "{$this->xsltPath}{$template}/";
        } else {
            $xslFile = "{$this->project->name}/{$template}/{$this->previewType}_{$subtype}.xsl";
            $xsltPath = "{$this->xsltPath}{$template}/{$subtype}/";
        }

        $files = (glob("{$xsltPath}*.xsl") + glob("{$xsltSharedPath}*.xsl")) ?? [];
        $files = array_merge(
            glob("{$xsltSharedPath}*.xsl") ?? [],
            glob("{$xsltPath}*.xsl") ?? []
        );

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

        if ($regenerate) {
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
                if (!empty($value)) {
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
        } else {
            $doc->load($this->xsltCache->getPath($xslFile));
        }

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

        if (!is_null($this->transformCache) && $this->transformCache->exist($pagedataId, $id)) {
            $content = $this->transformCache->get($pagedataId, $this->lang);

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

        if (!$content = $xsltProc->transformToXml($pageXml)) {
            // @todo add better error handling
            $messages = "";
            $errors = libxml_get_errors();
            foreach($errors as $error) {
                $this->log->log("LibXMLError: " . $error->message);
                $messages .= $error->message . "\n";
            }

            $error = "Could not transform the XML document:\n" . $messages;

            throw new \Exception($error);
        } else {
            // @todo add better error handling
            $errors = libxml_get_errors();
            foreach($errors as $error) {
                $this->log->log("LibXMLError: " . $error->message);
                //var_dump($error);
            }
        }

        // reset absolute path settings after transform
        $this->useAbsolutePaths = $oldUseAbsolutePath;
        $this->useBaseUrl = $oldUseBaseUrl;

        $cleaner = new \Depage\Html\Cleaner();
        $content = $cleaner->clean($content);

        if (!is_null($this->transformCache)) {
            $this->transformCache->set($pagedataId, $this->getUsedDocuments(), $content, $id);
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

        if (!$content = $xsltProc->transformToXml($xml)) {
            // @todo add better error handling
            $messages = "";
            $errors = libxml_get_errors();
            foreach($errors as $error) {
                $this->log->log("LibXMLError: " . $error->message);
                $messages .= $error->message . "\n";
            }

            $error = "Could not transform the XML document:\n" . $messages;

            throw new \Exception($error);
        } else {
            // @todo add better error handling
            $errors = libxml_get_errors();
            foreach($errors as $error) {
                $this->log->log("LibXMLError: " . $error->message);
                //var_dump($error);
            }
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
        $id = "{$lang}_{$subtype}";

        $docId = $this->xmlGetter->docExists($docId);

        if ($docId === false) {
            throw new \Exception("page does not exist");
        }

        if (!is_null($this->transformCache) && $this->transformCache->exist($docId, $id)) {
            $content = new \DOMDocument();
            $success = $content->loadXML($this->transformCache->get($docId, $id));

            if ($success) {
                return $content;
            }
        }

        $pageXml = $this->xmlGetter->getDocXml($docId);

        $oldUseAbsolutePath = $this->useAbsolutePaths;
        $oldUseBaseUrl = $this->useBaseUrl;

        $this->useAbsolutePaths = false;
        $this->useBaseUrl = true;

        $this->clearUsedDocuments($subtype);
        $this->addToUsedDocuments($docId, $subtype);
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
        if (!empty($_GET['__dpPreviewColor'])) {
            $params['currentColorscheme'] = $_GET['__dpPreviewColor'];
        }
        $xsltProc = $this->getXsltProc($subtype);

        $xsltProc->setParameter("", $params);

        if (!$content = $xsltProc->transformToDoc($pageXml)) {
            // @todo add better error handling
            $messages = "";
            $errors = libxml_get_errors();
            foreach($errors as $error) {
                $this->log->log("LibXMLError: " . $error->message);
                $messages .= $error->message . "\n";
            }

            $error = "Could not transform the XML document:\n" . $messages;

            throw new \Exception($error);
        } else {
            // @todo add better error handling
            $errors = libxml_get_errors();
            foreach($errors as $error) {
                $this->log->log("LibXMLError: " . $error->message);
                //var_dump($error);
            }
        }

        // reset absolute path settings after transform
        $this->useAbsolutePaths = $oldUseAbsolutePath;
        $this->useBaseUrl = $oldUseBaseUrl;

        if (!is_null($this->transformCache)) {
            $contentString = $content->saveXML();
            if (!empty($contentString)) {
                $this->transformCache->set($docId, $this->getUsedDocuments($subtype), $contentString, $id);
            }
        }

        return $content;
    }
    // }}}
    // {{{ saveTransformed()
    /**
     * @return  null
     */
    protected function saveTransformed($savePath, $content)
    {
        $dynamic = array(
            "php",
            "php5",
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

        // cache transformed source
        $dynamic = $this->saveTransformed($this->savePath, $html);

        if ($dynamic) {
            $GLOBALS['replacementScript'] = $this->savePath;
            return "";

            $query = "";
            if (!empty($_SERVER['QUERY_STRING'])) {
                $query = "?" . $_SERVER['QUERY_STRING'];
            }
            // @todo add headers
            // @todo add spoofed location header
            $request = new \Depage\Http\Request(DEPAGE_BASE . $this->savePath . $query);
            $request
                ->setPostData($_POST)
                ->setCookie($_COOKIE)
                // because it's our own local server -> @todo make this configurable
                ->allowUnsafeSSL = true;

            $response = $request->execute();

            if ($response->isRedirect()) {
                \Depage\Depage\Runner::redirect($response->getRedirectUrl());
            }

            return $response;
        } else {
            return $html;
        }
    }
    // }}}

    // {{{ clearUsedDocuments()
    /**
     * @brief clearUsedDocuments
     *
     * @param mixed
     * @return void
     **/
    protected function clearUsedDocuments($subtype = "")
    {
        if (empty($subtype)) {
            $subtype = "_";
        }
        $this->usedDocuments[$subtype] = [];
    }
    // }}}
    // {{{ addToUsedDocuments()
    /**
     * @brief addToUsedDocuments
     *
     * @param mixed $docId
     * @return void
     **/
    public function addToUsedDocuments($docId, $subtype = "")
    {
        if (empty($subtype)) {
            $subtype = "_";
        }
        if (!isset($this->usedDocuments[$subtype])) {
            $this->usedDocuments[$subtype] = [];
        }
        $this->usedDocuments[$subtype][] = $docId;

    }
    // }}}
    // {{{ getUsedDocuments()
    /**
     * @brief getUsedDocuments
     *
     * @param mixed
     * @return void
     **/
    public function getUsedDocuments($subtype = "")
    {
        if (empty($subtype)) {
            $subtype = "_";
        }
        return array_unique($this->usedDocuments[$subtype]);
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
         * get:css -> replace with transforming css directly
         * get:redirect -> analogous to css
         * get:atom -> analogous to css
         *
         * @done but @thinkabout
         * get:page -> replaced with dp:getpage function -> better replace manualy in template
         *
         * @done
         * pageref:
         * libref:
         * get:xslt -> replaced with xslt://
         * get:template -> deleted
         * get:navigation -> replaced with $navigation
         * get:colors -> replaced with $colors
         * get:settings -> replaced with $settings
         * get:languages -> replaced with $languages
         * call:doctype -> not necessary anymore
         * call:getversion -> replaced by $depageVersion
         */
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
        ]);

        // register stream to get links to library
        \Depage\Cms\Streams\Libref::registerStream("libref", [
            "transformer" => $this,
        ]);
        \Depage\Cms\Streams\Libref::registerStream("libid", [
            "transformer" => $this,
        ]);
    }
    // }}}
    // {{{ registerFunctions
    /**
     * @return  null
     */
    protected function registerFunctions($proc)
    {
        \Depage\Cms\Xslt\FuncDelegate::resetFunctions();
        \Depage\Cms\Xslt\FuncDelegate::registerFunctions($proc, [
            "atomizeText" => [$this, "xsltAtomizeText"],
            "autokeywords" => [$this, "xsltAutokeywords"],
            "changesrc" => [$this, "xsltChangeSrc"],
            "cssEscape" => [$this, "xsltCssEscape"],
            "fileinfo" => [$this, "xsltFileinfo"],
            "urlinfo" => [$this, "xsltUrlinfo"],
            "filesInFolder" => [$this, "xsltFilesInFolder"],
            "formatDate" => [$this, "xsltFormatDate"],
            "getLibRef" => [$this, "xsltGetLibRef"],
            "getPageRef" => [$this, "xsltGetPageRef"],
            "glob" => [$this, "xsltGlob"],
            "includeUnparsed" => [$this, "xsltIncludeUnparsed"],
            "jsEscape" => [$this, "xsltJsEscape"],
            "phpEscape" => [$this, "xsltPhpEscape"],
            "replaceEmailChars" => [$this, "xsltReplaceEmailChars"],
            "urlencode" => "rawurlencode",
            "tolower" => "mb_strtolower",
            "useAbsolutePaths" => [$this, "xsltUseAbsolutePaths"],
            "useBaseUrl" => [$this, "xsltUseBaseUrl"],
            "transformDoc" => [$this, "transformSubdoc"],
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

    // {{{ xsltGetLibRef()
    /**
     * @brief xsltGetLibRef
     *
     * @param mixed $path
     * @return void
     **/
    public function xsltGetLibRef($path, $absolute = false)
    {
        $p = $this->fl->toLibref($path);

        if ($p) $path = $p;

        $url = parse_url($path);

        $path = "lib/" . ($url['host'] ?? '') . ($url['path'] ?? '');

        if ($absolute != "relative" && !empty($this->baseUrlStatic) && $this->baseUrl != $this->baseUrlStatic) {
            $path = $this->baseUrlStatic . $path;
        } else if ($absolute == "absolute" || $this->useAbsolutePaths) {
            $path = $this->baseUrl . $path;
        } else if ($absolute != "relative" && $this->useBaseUrl) {
            $path = $path;
        } else {
            $url = new \Depage\Http\Url($this->currentPath);
            $path = $url->getRelativePathTo($path);
        }

        return $path;
    }
    // }}}
    // {{{ xsltGetPageRef()
    /**
     * @brief xsltGetPageRefgetPageRef
     *
     * @param mixed $pageId, $lang, $absolute
     * @return void
     **/
    public function xsltGetPageRef($pageId, $lang = null, $absolute = false)
    {
        if ($lang === null) {
            $lang = $this->lang;
        }
        $path = "";

        $xmlnav = $this->getXmlNav();

        if ($url = $xmlnav->getUrl($pageId)) {
            $path = $lang . $url;
        }

        if ($absolute == "absolute" || $this->useAbsolutePaths) {
            $path = $this->baseUrl . $path;
        } else if ($this->useBaseUrl) {
            $path = $path;
        } else {
            $url = new \Depage\Http\Url($this->currentPath);
            $path = $url->getRelativePathTo($path);
        }
        if ($this->routeHtmlThroughPhp) {
            //$path = preg_replace("/\.php$/", ".html", $path);
        }

        return $path;
    }
    // }}}
    // {{{ xsltFileinfo
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function xsltFileinfo($path, $extended = "true") {
        $info = $this->fl->getFileInfoByRef($path);

        if (!$info) {
            $xml = "<file exists=\"false\" />";
            $doc = new \DOMDocument();
            $doc->loadXML($xml);

            return $doc;
        }

        return $info->toXml();
    }
    // }}}
    // {{{ xsltUrlinfo
    /**
     * gets urlinfo for url
     *
     * @public
     *
     * @param    $path (string) url to get info about
     *
     * @return    $xml (xml) url info as xml string
     */
    public function xsltUrlinfo($url) {
        $analyzer = new \Depage\Media\UrlAnalyzer();
        $info = $analyzer->analyze($url);

        return $info->toXml();
    }
    // }}}
    // {{{ xsltFilesInFolder
    /**
     * gets fileinfo for all files in a specific folder
     *
     * @public
     *
     * @param    $id (int) id of folder
     *
     * @return    $xml (xml) file infos of files
     */
    public function xsltFilesInFolder($folderId) {
        $files = $this->fl->getFilesInFolder($folderId);

        $doc = new \DOMDocument();
        $doc->loadXML("<files />");

        foreach ($files as $f) {
            $node = $doc->importNode($f->toXML()->documentElement, true);
            $doc->documentElement->appendChild($node);
        }

        return $doc;
    }
    // }}}
    // {{{ xsltIncludeUnparsed
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function xsltIncludeUnparsed($path) {
        $xml = "";
        $path = "projects/" . $this->project->name . "/lib" . substr($path, 8);

        $xml = "<text>";
        if (file_exists($path)) {
            $xml .= htmlspecialchars(file_get_contents($path),
                \ENT_COMPAT | \ENT_XML1 | \ENT_DISALLOWED, "utf-8");
        }
        $xml .= "</text>";

        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        return $doc;
    }
    // }}}
    // {{{ xsltUseAbsolutePaths()
    /**
     * @brief xsltUseAbsolutePaths
     *
     * @param mixed
     * @return void
     **/
    public function xsltUseAbsolutePaths()
    {
        $this->useAbsolutePaths = true;
        $this->useBaseUrl = false;

        return "<true />";
    }
    // }}}
    // {{{ xsltUseBaseUrl()
    /**
     * @brief xsltUseBaseUrl
     *
     * @param mixed
     * @return void
     **/
    public function xsltUseBaseUrl()
    {
        $this->useBaseUrl = true;
        $this->useAbsolutePaths = false;

        return "<true />";
    }
    // }}}
    // {{{ xsltChangeSrc()
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function xsltChangeSrc($source) {
        $url = new \Depage\Http\Url($this->currentPath);
        $newSource = "";
        $posOffset = 0;
        // @todo check libref:/(/)
        while (($startPos = strpos($source, '"libref://', $posOffset)) !== false) {
            $newSource .= substr($source, $posOffset, $startPos - $posOffset) . '"';
            $posOffset = $startPos + strlen("libref://") + 3;
            $endPos = strpos($source, "\"", $posOffset);
            $newSource .= $url->getRelativePathTo('/lib' . substr($source, $startPos + 9, $endPos - ($startPos + 9)));
            $posOffset = $endPos;
        }
        $newSource .= substr($source, $posOffset);

        return $newSource;
    }
    // }}}
    // {{{ xsltReplaceEmailChars()
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function xsltReplaceEmailChars($email) {
        $original = array(
            "@",
            ".",
            "-",
            "_",
        );
        if ($this->lang == "de") {
            $repl = array(
                " *at* ",
                " *punkt* ",
                " *minus* ",
                " *unterstrich* ",
            );
        } else {
            $repl = array(
                " *at* ",
                " *dot* ",
                " *minus* ",
                " *underscore* ",
            );
        }
        $value = str_replace($original, $repl, $email);

        return $value;
    }
    // }}}
    // {{{ xsltAtomizeText()
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function xsltAtomizeText($text) {
        $xml = "<spans><span>" . str_replace(" ", " </span><span>", htmlspecialchars(trim($text))) . " </span></spans>";

        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        return $doc;
    }
    // }}}
    // {{{ xsltPhpEscape()
    /**
     * escapes string for use as php code in xsl
     *
     * @public
     *
     * @param    $string
     *
     * @return    escaped string
     */
    public function xsltPhpEscape($string) {
        $value = var_export($string, true);

        return $value;
    }
    // }}}
    // {{{ xsltJsEscape()
    /**
     * escapes string for use in javascript code in xsl
     *
     * @public
     *
     * @param    $string
     *
     * @return    escaped string
     */
    public function xsltJsEscape($string) {
        $value = json_encode($string, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_NUMERIC_CHECK);

        return $value;
    }
    // }}}
    // {{{ xsltCssEscape()
    /**
     * escapes css identifier for use in xsl
     *
     * @public
     *
     * @param    $string
     *
     * @return    escaped string
     */
    public function xsltCssEscape($string) {
        $value = \Depage\Html\Html::getEscapedUrl($string);

        return $value;
    }
    // }}}
    // {{{ xsltFormatDate()
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function xsltFormatDate($date = '', $format = '') {
        if ($format == '') {
            $format = "c";
        }
        if (empty($date)) {
            $date = date($format);
        } else {
            $date = date($format, strtotime($date));
        }

        return $date;
    }
    // }}}
    // {{{ xsltAutokeywords()
    /**
     * @brief xsltAutokeywords
     *
     * @param mixed $keywords, $content
     * @return void
     **/
    public function xsltAutokeywords($keys, $content)
    {
        // @todo add keyword aliases?
        $val = "";
        $keywords = [];
        $originalKeywords = $this->extractWords($keys);
        foreach ($originalKeywords as $key => $value) {
            $keywords[$key] = mb_strtolower($value);
        }
        $contentWords = $this->extractWords($content, true);

        $found = array_intersect($contentWords, $keywords);

        foreach ($found as $word) {
            $val .= $originalKeywords[array_search($word, $keywords)] . ", ";
        }
        /*
        var_dump($keys);
        var_dump($keywords);
        var_dump($contentWords);
        var_dump($val);
        die();
         */
        return trim($val, ", ");
    }
    // }}}

    // {{{ getXmlNav()
    /**
     * @brief getXmlNav
     *
     * @param mixed
     * @return void
     **/
    protected function getXmlNav()
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
    // {{{ extractWords()
    /**
     * @brief extractWords
     *
     * @param mixed $
     * @return void
     **/
    private function extractWords($string, $normalize = false)
    {
        preg_match_all("/\w+(-\w+)?/u", $string, $matches);
        if (!isset($matches[0])) {
            return [];
        }

        if ($normalize) {
            foreach ($matches[0] as &$value) {
                $value = mb_strtolower($value);
            }

            return array_unique($matches[0]);
        } else {
            return $matches[0];
        }

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
