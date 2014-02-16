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
            'userId' => $this->auth_user->id,
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
        $pageId = $this->getPageIdFor($urlPath);
        $xslDOM = $this->getXsltFor($this->template);

        $pageXml = $this->xmldb->getDocXml($pageId);
        
        libxml_disable_entity_loader(false);
        libxml_use_internal_errors(true);

        \depage\cms\Streams\Xmldb::registerStream("xmldb", array(
            "xmldb" => $this->xmldb,
        ));
        
        $xslt = new \XSLTProcessor();
        $xslt->setParameter("", array(
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
        $urls = $xmlnav->getAllUrls($pages->getXml());
        $nodeId = array_search($urlPath, $urls);

        $pageId = $pages->getAttribute($nodeId, "db:docref");

        return $pageId;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
