<?php
/**
 * @file    framework/cms/UI/Preview.php
 *
 * preview ui handler
 *
 *
 * copyright (c) 2013-2014 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace depage\cms\UI;

class Preview extends \depage_ui {
    protected $html_options = array();
    protected $basetitle = "";
    protected $cached = false;
    protected $projectName = "";
    protected $template = "";
    protected $lang = "";
    protected $urlsByPageId = array();
    protected $pageIdByUrl = array();
    public $routeThroughIndex = true;

    // {{{ _init
    public function _init(array $importVariables = array()) {
        parent::_init($importVariables);

        if (empty($this->pdo)) {
            // get database instance
            $this->pdo = new \db_pdo (
                $this->options->db->dsn, // dsn
                $this->options->db->user, // user
                $this->options->db->password, // password
                array(
                    'prefix' => $this->options->db->prefix, // database prefix
                )
            );
        }

        // get auth object
        $this->auth = \auth::factory(
            $this->pdo, // db_pdo 
            $this->options->auth->realm, // auth realm
            DEPAGE_BASE, // domain
            $this->options->auth->method // method
        );

        // set html-options
        $this->html_options = array(
            'template_path' => __DIR__ . "/../tpl/",
            'clean' => "space",
            'env' => $this->options->env,
        );
        $this->basetitle = \depage::getName() . " " . \depage::getVersion();
    }
    // }}}
    // {{{ _package
    /**
     * gets a list of projects
     *
     * @return  null
     */
    public function _package($output) {
        // pack into base-html if output is html-object
        if (!isset($_REQUEST['ajax']) && is_object($output) && is_a($output, "html")) {
            // pack into body html
            $output = new \html("html.tpl", array(
                'title' => $this->basetitle,
                'subtitle' => $output->title,
                'content' => $output,
            ), $this->html_options);
        }

        return $output;
    }
    // }}}
    
    // {{{ index
    /**
     * function to route all previews through
     *
     * @return  null
     */
    public function index()
    {
        $args = func_get_args();

        // get parameters 
        $this->projectName = $this->urlSubArgs[0];
        $this->template = array_shift($args);
        $this->cached = array_shift($args) == "cached" ? true : false;
        $this->lang = array_shift($args);

        $urlPath = implode("/", $args);

        // set basic variables
        $this->prefix = $this->pdo->prefix . "_proj_" . $this->projectName;

        $this->xsltPath = "projects/" . $this->projectName . "/xslt/";
        $this->xmlPath = "projects/" . $this->projectName . "/xml/";

        // get cache instance
        $this->cache = \depage\cache\cache::factory("xmldb");

        // create xmldb-project
        $this->xmldb = new \depage\xmldb\xmldb ($this->prefix, $this->pdo, $this->cache, array(
            'pathXMLtemplate' => $this->xmlPath,
            //'userId' => $this->auth_user->id,
        ));

        return $this->preview($urlPath);
    }
    // }}}
    // {{{ error
    /**
     * function to show error messages
     *
     * @return  null
     */
    public function error($error, $env) {
        $content = parent::error($error, $env);

        $h = new \html("box.tpl", array(
            'id' => "error",
            'class' => "first",
            'content' => new \html(array(
                'content' => $content,
            )),
        ), $this->html_options);

        return $this->_package($h);
    }
    // }}}
    
    // {{{ preview
    /**
     * @return  null
     */
    protected function preview($urlPath)
    {
        $urlPath = "/$urlPath";
        $this->currentPath = $this->lang . $urlPath;
        $pageId = $this->getPageIdFor($urlPath);
        $xslDOM = $this->getXsltFor($this->template);

        $pageXml = $this->xmldb->getDocXml($pageId);
        
        libxml_disable_entity_loader(false);
        libxml_use_internal_errors(true);

        $this->registerStreams();

        $xslt = new \XSLTProcessor();
        $xslt->setParameter("", array(
            "tt_lang" => $this->lang,
        ));
        //$xslt->setProfiling('profiling.txt');
        $xslt->importStylesheet($xslDOM);

        if (!$html = $xslt->transformToXml($pageXml)) {   
            var_dump(libxml_get_errors());
            
            $error = libxml_get_last_error();
            $error = empty($error)? 'Could not transform the navigation XML document.' : $error->message;
            
            throw new \exception($error);
        }

        return $html;
    }
    // }}}
    // {{{ registerStreams
    /**
     * @return  null
     */
    protected function registerStreams()
    {
        /*
         * get:page
         * get:redirect
         * get:css
         * get:template
         * get:xslt
         * get:navigation
         * get:atom
         * get:colors
         * get:languages
         * get:settings
         * call:changesrc
         * call:filetype
         * call:doctype
         * call:atomizetext
         * call:urlencode
         * call:phpescape
         * call:formatdate
         * call:replaceEmailChars
         * call:getversion
         * pageref:
         * libref:
         */
        // register stream to get documents from xmldb
        \depage\cms\Streams\Xmldb::registerStream("xmldb", array(
            "xmldb" => $this->xmldb,
        ));
        
        // register stream to get global xsl templates
        \depage\cms\Streams\Xslt::registerStream("xslt");

        // register stream to get page-links
        \depage\cms\Streams\Pageref::registerStream("pageref", array(
            "urls" => $this->urlsByPageId,
            "preview" => $this,
            "lang" => $this->lang,
        ));
    }
    // }}}
    // {{{ getXslFor
    /**
     * @return  null
     */
    protected function getXsltFor($template)
    {
        $files = glob("{$this->xsltPath}{$template}/*.xsl");

        $xslt = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<xsl:stylesheet xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\" xmlns:db=\"http://cms.depagecms.net/ns/database\" xmlns:proj=\"http://cms.depagecms.net/ns/project\" xmlns:pg=\"http://cms.depagecms.net/ns/page\" xmlns:sec=\"http://cms.depagecms.net/ns/section\" xmlns:edit=\"http://cms.depagecms.net/ns/edit\" version=\"1.0\" extension-element-prefixes=\"xsl db proj pg sec edit \">";

        // add basic variables
        $xslt .= "\n<xsl:param name=\"navigation\" select=\"document('xmldb://pages')\" />";
        $xslt .= "\n<xsl:param name=\"settings\" select=\"document('xmldb://settings')\" />";
        $xslt .= "\n<xsl:param name=\"colors\" select=\"document('xmldb://colors')\" />";
        $xslt .= "\n<xsl:param name=\"tt_lang\" />";
        
        foreach ($files as $file) {
            $xslt .= "\n<xsl:include href=\"" . htmlentities($file) . "\" />";
        }
        $xslt .= "</xsl:stylesheet>";

        $doc = new \depage\xml\Document();
        $doc->loadXML($xslt);

        return $doc;
    }
    // }}}
    // {{{ getPageIdFor
    /**
     * @return  null
     */
    protected function getPageIdFor($urlPath)
    {
        $pages = $this->xmldb->getDoc("pages");

        $xmlnav = new \depage\cms\xmlnav();
        list($this->urlsByPageId, $this->pageIdByUrl) = $xmlnav->getAllUrls($pages->getXml());
        $nodeId = $this->pageIdByUrl[$urlPath];

        $pageId = $pages->getAttribute($nodeId, "db:docref");

        return $pageId;
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
            $currentPath = $this->currentPath;
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
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
