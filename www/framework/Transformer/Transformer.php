<?php

namespace Depage\Transformer;

use \Depage\Html\Html;

abstract class Transformer
{
    protected $projectName;
    protected $template;
    protected $xmlGetter;
    protected $xsltPath;
    protected $xmlPath;
    protected $xsltProc = null;
    protected $isLive = false;
    protected $profiling = false;
    protected $usedDocuments = array();
    protected $aliases = [];
    public $baseUrl = "";
    public $useAbsolutePaths = false;
    public $useBaseUrl = false;
    public $routeHtmlThroughPhp = false;
    public $lang = "";
    public $currentPath = "";
    public $urlsByPageId = array();
    public $pageIdByUrl = array();
    public $pagedataIdByPageId = array();

    // {{{ factory()
    static public function factory($previewType, $xmlGetter, $projectName, $template, $transformCache = null)
    {
        if ($previewType == "live") {
            return new Live($xmlGetter, $projectName, $template, $transformCache);
        } elseif ($previewType == "pre" || $previewType == "preview") {
            return new Preview($xmlGetter, $projectName, $template, $transformCache);
        } else {
            return new Dev($xmlGetter, $projectName, $template, $transformCache);
        }
    }
    // }}}
    // {{{ constructor()
    public function __construct($xmlGetter, $projectName, $template, $transformCache = null)
    {
        $this->xmlGetter = $xmlGetter;
        $this->projectName = $projectName;
        $this->template = $template;
        $this->transformCache = $transformCache;

        // @todo complete baseurl this in a better way, also based on previewTyoe
        // @todo fix this for live view !important
        $this->baseUrl = DEPAGE_BASE . "project/{$this->projectName}/preview/{$this->template}/{$this->previewType}/";
    }
    // }}}
    // {{{ lateInitialize()
    public function lateInitialize()
    {
        // add log object
        $this->log = new \Depage\Log\Log();

        // set basic variables
        //$this->prefix = $this->pdo->prefix . "_proj_" . $this->projectName;

        $this->xsltPath = "projects/" . $this->projectName . "/xslt/";
        $this->xmlPath = "projects/" . $this->projectName . "/xml/";
        $this->libPath = "projects/" . $this->projectName . "/lib/";

        // get cache instance for templates
        $this->xsltCache = \Depage\Cache\Cache::factory("xslt");

        $this->initXsltProc();
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
    // {{{ initXsltProc()
    public function initXsltProc()
    {
        libxml_disable_entity_loader(false);
        libxml_use_internal_errors(true);

        // @todo use own dtd loaded with caching instead of having own htmlentities file
        // @todo add a list of whitelisted dtds and entities
        // @see https://www.oasis-open.org/committees/entity/spec.html
        // @see http://xmlsoft.org/catalog.html
        // libxml_set_external_entity_loader
        /*
        libxml_set_external_entity_loader(
            function ($public, $system, $context) {
                var_dump($public);
                var_dump($system);
                var_dump($context);
                $f = fopen($system, "r");
                //$f = fopen("php://temp", "r+");
                //fwrite($f, "");
                rewind($f);
                return $f;
            }
        );
         */

        $this->xsltProc = new \XSLTProcessor();

        $this->registerStreams($this->xsltProc);
        $this->registerFunctions($this->xsltProc);

        $xslDOM = $this->getXsltTemplate($this->template);

        if ($this->profiling) {
            $this->xsltProc->setProfiling('logs/xslt-profiling.txt');
        }
        $this->xsltProc->importStylesheet($xslDOM);

    }
    // }}}
    // {{{ getXsltTemplate()
    /**
     * @return  null
     */
    protected function getXsltTemplate($template)
    {
        $regenerate = false;
        $xslFile = "{$this->projectName}/{$template}/{$this->previewType}.xsl";
        $files = glob("{$this->xsltPath}{$template}/*.xsl");

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

        if ($regenerate) {
            if (!is_null($this->transformCache)) {
                $this->transformCache->clearAll();
            }
            $xslt = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
            $xslt .= $this->getXsltEntities();
            $xslt .= "<xsl:stylesheet
                version=\"1.0\"
                xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\"
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
            >";

            $xslt .= "<xsl:include href=\"xslt://functions.xsl\" />";

            // add basic paramaters and variables
            $params = array(
                'currentLang' => null,
                'currentPageId' => null,
                'depageIsLive' => null,
                'baseUrl' => null,
                'currentColorscheme' => "dp:choose(//pg:meta[1]/@colorscheme, //pg:meta[1]/@colorscheme, \$colors//proj:colorscheme[@name][1]/@name)",
            );
            $variables = array(
                'navigation' => "document('xmldb://pages')",
                'settings' => "document('xmldb://settings')",
                'colors' => "document('xmldb://colors')",
                'languages' => "\$settings/proj:settings/proj:languages",
                'currentPage' => "\$navigation//pg:page[@status = 'active']",
                'libPath' => "'" . htmlspecialchars('file://' . str_replace(" ", "%20", realpath($this->libPath))) . "'",
            );

            // add variables from settings
            $settings = $this->xmlGetter->getDocXml("settings");

            $xpath = new \DOMXPath($settings);
            $nodelist = $xpath->query("//proj:variable");

            for ($i = $nodelist->length - 1; $i >= 0; $i--) {
                $node = $nodelist->item($i);
                $variables["var-" . $node->getAttribute("name")] = "'" . htmlspecialchars($node->getAttribute("value")) . "'";
            }

            // now add to xslt
            foreach ($params as $key => $value) {
                if (!empty($value)) {
                    $xslt .= "\n<xsl:param name=\"$key\" select=\"$value\" />";
                } else {
                    $xslt .= "\n<xsl:param name=\"$key\" />";
                }
            }
            foreach ($variables as $key => $value) {
                $xslt .= "\n<xsl:variable name=\"$key\" select=\"$value\" />";
            }

            $xslt .= $this->getXsltIncludes($files);
            $xslt .= "\n</xsl:stylesheet>";

            $doc = new \Depage\Xml\Document();
            $doc->resolveExternals = true;
            $doc->loadXML($xslt);

            $this->xsltCache->set($xslFile, $doc);
        }

        $doc = new \Depage\Xml\Document();
        $doc->load($this->xsltCache->getPath($xslFile));

        return $doc;
    }
    // }}}
    // {{{ getXsltEntities()
    abstract protected function getXsltEntities();
    // }}}
    // {{{ getXsltIncludes()
    abstract protected function getXsltIncludes($files);
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

        $this->savePath = "projects/" . $this->projectName . "/cache-" . $this->template . "-" . $this->lang . $this->currentPath;

        $content = $this->transformDoc($pageId, $pagedataId, $lang);

        return $content;
    }
    // }}}
    // {{{ transformDoc()
    public function transformDoc($pageId, $pagedataId, $lang)
    {
        $this->lang = $lang;

        if (!is_null($this->transformCache) && $this->transformCache->exist($pagedataId, $this->lang)) {
            $content = $this->transformCache->get($pagedataId, $this->lang);
        } else {
            if (is_null($this->xsltProc)) {
                $this->lateInitialize();
            }

            $pageXml = $this->xmlGetter->getDocXml($pagedataId);
            if ($pageXml === false) {
                throw new \Exception("page does not exist");
            }

            $this->clearUsedDocuments();
            $this->addToUsedDocuments($pagedataId);
            $content = $this->transform($pageXml, array(
                "currentLang" => $this->lang,
                "currentPageId" => $pageId,
                "currentPagedataId" => $pagedataId,
                "currentContentType" => "text/html",
                "currentEncoding" => "UTF-8",
                "depageVersion" => \Depage\Depage\Runner::getName() . " " . \Depage\Depage\Runner::getVersion(),
                "depageIsLive" => $this->isLive ? "true" : "",
                "baseUrl" => $this->baseUrl,
            ));

            $cleaner = new \Depage\Html\Cleaner();
            $content = $cleaner->clean($content);

            if (!is_null($this->transformCache)) {
                $this->transformCache->set($pagedataId, $this->getUsedDocuments(), $content, $this->lang);
            }
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

        $oldUseAbsolutePath = $this->useAbsolutePaths;
        $oldUseBaseUrl = $this->useBaseUrl;

        $this->xsltProc->setParameter("", $parameters);

        if (!$content = $this->xsltProc->transformToXml($xml)) {
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
    protected function clearUsedDocuments()
    {
        $this->usedDocuments = array();

    }
    // }}}
    // {{{ addToUsedDocuments()
    /**
     * @brief addToUsedDocuments
     *
     * @param mixed $docId
     * @return void
     **/
    public function addToUsedDocuments($docId)
    {
        $this->usedDocuments[] = $docId;

    }
    // }}}
    // {{{ getUsedDocuments()
    /**
     * @brief getUsedDocuments
     *
     * @param mixed
     * @return void
     **/
    public function getUsedDocuments()
    {
        return array_unique($this->usedDocuments);
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
        \Depage\Cms\Streams\XmlDb::registerStream("xmldb", array(
            "xmldb" => $this->xmlGetter,
            "transformer" => $this,
        ));

        // register stream to get global xsl templates
        \Depage\Cms\Streams\Xslt::registerStream("xslt");

        // register stream to get page-links
        \Depage\Cms\Streams\Pageref::registerStream("pageref", array(
            "transformer" => $this,
        ));

        // register stream to get links to library
        \Depage\Cms\Streams\Libref::registerStream("libref", array(
            "transformer" => $this,
        ));
    }
    // }}}
    // {{{ registerFunctions
    /**
     * @return  null
     */
    protected function registerFunctions($proc)
    {
        /*
         * @done
         * call:changesrc
         * call:urlencode
         * call:replaceEmailChars
         * call:atomizetext
         * call:phpescape
         * call:formatdate
         * call:fileinfo
         */

        \Depage\Cms\Xslt\FuncDelegate::registerFunctions($proc, [
            "atomizeText" => [$this, "xsltAtomizeText"],
            "autokeywords" => [$this, "xsltAutokeywords"],
            "changesrc" => [$this, "xsltChangeSrc"],
            "fileinfo" => [$this, "xsltFileinfo"],
            "glob" => [$this, "xsltGlob"],
            "formatDate" => [$this, "xsltFormatDate"],
            "getLibRef" => [$this, "xsltGetLibRef"],
            "getPageRef" => [$this, "xsltGetPageRef"],
            "phpEscape" => [$this, "xsltPhpEscape"],
            "replaceEmailChars" => [$this, "xsltReplaceEmailChars"],
            "urlencode" => "rawurlencode",
            "useAbsolutePaths" => [$this, "xsltUseAbsolutePaths"],
            "useBaseUrl" => [$this, "xsltUseBaseUrl"],
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

    // {{{ getAllUrls
    /**
     * @return  null
     */
    public function getAllUrls()
    {
        if (empty($this->urlsByPageId) ||
            empty($this->pageIdByUrl)
        ) {
            $pages = $this->xmlGetter->getDocXml("pages");

            $xmlnav = new \Depage\Cms\XmlNav();
            list($this->urlsByPageId, $this->pageIdByUrl, $this->pagedataIdByPageId) = $xmlnav->getAllUrls($pages);

            foreach($this->pagedataIdByPageId as &$value) {
                $value = $this->xmlGetter->docExists($value);
            }
        }

        return array_keys($this->pageIdByUrl);
    }
    // }}}
    // {{{ getUrlsByPageId()
    /**
     * @return  null
     */
    public function getUrlsByPageId()
    {
        if (empty($this->pageIdByUrl)) {
            $this->getAllUrls();
        }

        return array_flip($this->pageIdByUrl);
    }
    // }}}
    // {{{ getPageIdFor
    /**
     * @return  null
     */
    public function getPageIdFor($urlPath)
    {
        $this->getAllUrls();

        if (!isset($this->pageIdByUrl[$urlPath]) && $this->routeHtmlThroughPhp) {
            $urlPath = preg_replace("/\.html$/", ".php", $urlPath);
        }
        if (!isset($this->pageIdByUrl[$urlPath]) && !empty($this->aliases)) {
            foreach ($this->aliases as $regex => $repl) {
                $regex = "/" . str_replace("/", "\/", $regex) . "/";
                $urlPath = preg_replace($regex, $repl, $urlPath);
            }
        }

        if (isset($this->pageIdByUrl[$urlPath])) {
            $pageId = $this->pageIdByUrl[$urlPath];
            $docRef = $this->pagedataIdByPageId[$pageId];

            return array($pageId, $docRef, $urlPath);
        } else {
            return array(false, false, false);
        }
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
        $url = parse_url($path);

        if (!empty($url['path'])) {
            $path = "lib/" . $url['host'] . $url['path'];
        } else {
            $path = "lib/" . $url['host'];
        }

        if ($absolute == "absolute" || $this->useAbsolutePaths) {
            $path = $this->baseUrl . $path;
        } else if ($this->useBaseUrl) {
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

        $this->getAllUrls();

        if (isset($this->urlsByPageId[$pageId])) {
            $path = $lang . $this->urlsByPageId[$pageId];
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
            $path = preg_replace("/\.php$/", ".html", $path);
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
        $xml = "";
        $path = "projects/" . $this->projectName . "/lib" . substr($path, 8);

        $fileinfo = new \Depage\Media\MediaInfo();

        if ($extended === "false") {
            $info = $fileinfo->getBasicInfo($path);
        } else {
            $info = $fileinfo->getInfo($path);
        }
        if (isset($info['date'])) {
            $info['date'] = $info['date']->format("Y-m-d H:i:s");
        }

        $xml = "<file";
        foreach ($info as $key => $value) {
            $xml .= " $key=\"" . htmlspecialchars($value, \ENT_COMPAT | \ENT_XML1 | \ENT_DISALLOWED, "utf-8") . "\"";
        }
        $xml .= " />";

        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        return $doc;
    }
    // }}}
    // {{{ xsltGlob
    /**
     * gets list of files for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function xsltGlob($path) {
        $xml = "";
        $path = "projects/" . $this->projectName . "/lib" . substr($path, 8);
        $prefixLen = strlen("projects/" . $this->projectName . "/lib/");
        $files = glob($path);

        $xml = "<glob>";
        foreach ($files as $filename) {
            $filename = "libref://" . substr($filename, $prefixLen);
            $xml .= "<file>" . htmlspecialchars($filename, \ENT_COMPAT | \ENT_XML1 | \ENT_DISALLOWED, "utf-8") . "</file>";
        }
        $xml .= "</glob>";

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
        $xml = "<spans><span>" . str_replace(" ", "</span> <span>", htmlspecialchars(trim($text))) . "</span></spans>";

        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        return $doc;
    }
    // }}}
    // {{{ xsltPhpEscape()
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function xsltPhpEscape($string) {
        $value = var_export($string, true);

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
            'projectName',
            'template',
            'xsltPath',
            'xmlPath',
            'transformCache',
            'baseUrl',
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
