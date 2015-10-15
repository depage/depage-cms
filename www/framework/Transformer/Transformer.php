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
    protected $lang = "";
    protected $isLive = false;
    protected $usedDocuments = array();
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
    }
    // }}}
    // {{{ lateInitialize()
    public function lateInitialize()
    {
        // add log object
        $this->log = new \Depage\Log\Log();

        // @todo complete baseurl this in a better way, also based on previewTyoe
        $this->baseUrl = DEPAGE_BASE . "project/{$this->projectName}/preview/{$this->template}/{$this->previewType}/";

        // set basic variables
        //$this->prefix = $this->pdo->prefix . "_proj_" . $this->projectName;

        $this->xsltPath = "projects/" . $this->projectName . "/xslt/";
        $this->xmlPath = "projects/" . $this->projectName . "/xml/";

        // get cache instance for templates
        $this->xsltCache = \Depage\Cache\Cache::factory("xslt");

        $this->initXsltProc();
    }
    // }}}
    // {{{ initXsltProc()
    public function initXsltProc()
    {
        libxml_disable_entity_loader(false);
        libxml_use_internal_errors(true);

        $this->xsltProc = new \XSLTProcessor();

        $this->registerStreams($this->xsltProc);
        $this->registerFunctions($this->xsltProc);

        $xslDOM = $this->getXsltTemplate($this->template);

        $this->xsltProc->setProfiling('logs/xslt-profiling.txt');
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
        //$regenerate = true;

        if ($regenerate) {
            $xslt = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
            $xslt .= $this->getXsltEntities();
            $xslt .= "<xsl:stylesheet xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\"  xmlns:dp=\"http://cms.depagecms.net/ns/depage\" xmlns:db=\"http://cms.depagecms.net/ns/database\" xmlns:proj=\"http://cms.depagecms.net/ns/project\" xmlns:pg=\"http://cms.depagecms.net/ns/page\" xmlns:sec=\"http://cms.depagecms.net/ns/section\" xmlns:edit=\"http://cms.depagecms.net/ns/edit\" version=\"1.0\" extension-element-prefixes=\"xsl db proj pg sec edit \">";

            $xslt .= "<xsl:include href=\"xslt://functions.xsl\" />";

            // add basic paramaters and variables
            $params = array(
                'currentLang' => null,
                'currentPageId' => null,
                'depageIsLive' => "false()",
                'baseUrl' => null,
                'currentColorscheme' => "dp:choose(//pg:meta[1]/@colorscheme, //pg:meta[1]/@colorscheme, \$colors//proj:colorscheme[@name][1]/@name)",
            );
            $variables = array(
                'navigation' => "document('xmldb://pages')",
                'settings' => "document('xmldb://settings')",
                'colors' => "document('xmldb://colors')",
                'languages' => "\$settings//proj:languages",
                'currentPage' => "\$navigation//pg:page[@status = 'active']",
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
        $this->currentPath = $urlPath;
        $this->lang = $lang;

        list($pageId, $pagedataId) = $this->getPageIdFor($this->currentPath);

        if ($pageId === false || $pagedataId === false) {
            // php fallback for transparent php links
            $this->currentPath = preg_replace("/\.html$/", ".php", $this->currentPath);

            list($pageId, $pagedataId) = $this->getPageIdFor($this->currentPath);
        }
        if ($pageId === false || $pagedataId === false) {
            throw new \Exception("page '{$urlPath}' does not exist");
        }

        $this->savePath = "projects/" . $this->projectName . "/cache-" . $this->template . "-" . $this->lang . $this->currentPath;

        return $this->transformPage($pageId, $pagedataId);
    }
    // }}}
    // {{{ transformPage()
    protected function transformPage($pageId, $pagedataId)
    {
        if (!is_null($this->transformCache) && $this->transformCache->exist($pagedataId, $this->lang)) {
            $content = $this->transformCache->get($pagedataId, $this->lang);
        } else {
            $pageXml = $this->xmlGetter->getDocXml($pagedataId);
            if ($pageXml === false) {
                throw new \Exception("page does not exist");
            }

            $this->clearUsedDocuments();
            $content = $this->transform($pageXml, array(
                "currentLang" => $this->lang,
                "currentPageId" => $pageId,
                "currentContentType" => "text/html",
                "currentEncoding" => "UTF-8",
                "depageVersion" => \Depage\Depage\Runner::getVersion(),
                "depageIsLive" => $this->isLive,
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

        $this->xsltProc->setParameter("", $parameters);

        if (!$content = $this->xsltProc->transformToXml($xml)) {
            // @todo add better error handling
            $errors = libxml_get_errors();
            foreach($errors as $error) {
                $this->log->log($error);
                //var_dump($error);
            }

            $error = libxml_get_last_error();
            $error = empty($error) ? 'Could not transform the navigation XML document.' : $error->message;

            throw new \Exception($error);
        } else {
            // @todo add better error handling
            $errors = libxml_get_errors();
            foreach($errors as $error) {
                $this->log->log($error);
                //var_dump($error);
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
            $request = new \Depage\Http\Request(DEPAGE_BASE . $this->savePath);
            $request->setPostData($_POST);
            $request->setCookie($_COOKIE);

            // because it's our own local server -> @todo make this configurable
            $request->allowUnsafeSSL = true;

            $response = $request->execute();

            // @todo capture and adjust redirects
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

        \Depage\Cms\Xslt\FuncDelegate::registerFunctions($proc, array(
            "changesrc" => array($this, "xsltCallChangeSrc"),
            "replaceEmailChars" => array($this, "xsltCallReplaceEmailChars"),
            "atomizeText" => array($this, "xsltCallAtomizeText"),
            "phpEscape" => array($this, "xsltCallPhpEscape"),
            "formatDate" => array($this, "xsltCallFormatDate"),
            "fileinfo" => array($this, "xsltCallFileinfo"),
            "urlencode" => "rawurlencode",
        ));
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
        if (empty($this->urlsByPageId)) {
            $this->getAllUrls();
        }

        return $this->urlsByPageId;
    }
    // }}}
    // {{{ getPageIdFor
    /**
     * @return  null
     */
    protected function getPageIdFor($urlPath)
    {
        $this->getAllUrls();

        if (isset($this->pageIdByUrl[$urlPath])) {
            $pageId = $this->pageIdByUrl[$urlPath];
            $pagedataId = $this->pagedataIdByPageId[$pageId];

            return array($pageId, $pagedataId);
        } else {
            return array(false, false);
        }
    }
    // }}}
    // {{{ getRelativePathTo
    /**
     * gets relative path to path of active page
     *
     * @public
     *
     * @param    $targetPath (string) path to target file
     *
     * @return    $path (string) relative path
     */
    public function getRelativePathTo($targetPath, $currentPath = null) {
        if ($currentPath === null) {
            $currentPath = $this->lang . $this->currentPath;
        }

        // link to self by default
        $path = '';
        if ($targetPath != '' && $targetPath != $currentPath) {
            $currentPath = explode('/', $currentPath);
            $targetPath = explode('/', $targetPath);

            $i = 0;
            while ((isset($currentPath[$i]) && $targetPath[$i]) && $currentPath[$i] == $targetPath[$i]) {
                $i++;
            }

            if (count($currentPath) - $i >= 1) {
                $path = str_repeat('../', count($currentPath) - $i - 1) . implode('/', array_slice($targetPath, $i));
            }
        }
        return $path;
    }
    // }}}

    // {{{ xsltCallFileinfo
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function xsltCallFileinfo($path, $extended = "true") {
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
            $xml .= " $key=\"" . htmlspecialchars($value) . "\"";
        }
        $xml .= " />";

        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        return $doc;
    }
    // }}}
    // {{{ xsltCallChangeSrc()
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function xsltCallChangeSrc($source) {
        $newSource = "";
        $posOffset = 0;
        // @todo check libref:/(/)
        while (($startPos = strpos($source, '"libref:/', $posOffset)) !== false) {
            $newSource .= substr($source, $posOffset, $startPos - $posOffset) . '"';
            $posOffset = $startPos + strlen("libref:/") + 3;
            $endPos = strpos($source, "\"", $posOffset);
            $newSource .= $this->getRelativePathTo('/lib' . substr($source, $startPos + 8, $endPos - ($startPos + 8)));
            $posOffset = $endPos;
        }
        $newSource .= substr($source, $posOffset);

        return $newSource;
    }
    // }}}
    // {{{ xsltCallReplaceEmailChars()
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function xsltCallReplaceEmailChars($email) {
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
    // {{{ xsltCallAtomizeText()
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function xsltCallAtomizeText($text) {
        $value = "<span>" . str_replace(" ", "</span> <span>", htmlspecialchars(trim($text))) . "</span>";

        return $value;
    }
    // }}}
    // {{{ xsltCallPhpEscape()
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function xsltCallPhpEscape($string) {
        $value = str_replace("\"", "\\\"", $string);

        return $value;
    }
    // }}}
    // {{{ xsltCallFormatDate()
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function xsltCallFormatDate($date, $format = '') {
        if ($format == '') {
            $format = "c";
        }
        $date = date($format, strtotime($date));

        return $date;
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
        );
    }
    // }}}
    // {{{ __wakeup()
    /**
     * allows Depage\Db\Pdo-object to be unserialized
     */
    public function __wakeup()
    {
        $this->init();
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
