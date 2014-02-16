<?php

namespace depage\cms;

class xmlnav {
    const ACTIVE_STATUS = 'active';
    const PARENT_STATUS = 'parent-of-active';

    private $xslDOM;
    private $xmlDOM;
    
    // {{{ constructor
    /**
     * initializes xmlnav object
     *
     * @param $xsl  filename to load as xsl template or xsl template as \DOMDocument 
     * @param $xml  filename to load as navigation xml or navigation as \DOMDocument 
     */
    public function __construct($xsl = '', $xml = '') {
        if ($xsl != '' && is_string($xsl)) {
            $this->loadXslFromFile($xsl);
        } else if ($xsl instanceof \DOMDocument) {
            $this->xslDOM = $xsl;
        }
        if ($xml != '' && is_string($xml)) {
            $this->loadXmlFromFile($xml);
        } else if ($xml instanceof \DOMDocument) {
            $this->xmlDOM = $xml;
        }
    }
    // }}}
    
    // {{{ loadXslFromFile()
    /**
     * loads xsl template from filename
     *
     * @param string $path
     */
    public function loadXslFromFile($path) {
        $this->xslDOM = new \DOMDocument();
        
        if (!$this->xslDOM->load($path)) {
            throw new \exception('Could not load the navigation XSL file.');
        }
    }
    // }}}
    // {{{ loadXmlFromFile()
    /**
     * loads navigation xml from file
     *
     * @param string $path
     */
    public function loadXmlFromFile($path) {
        $this->xmlDOM = new \DOMDocument();
        
        if (!$this->xmlDOM->load($path)) {
            throw new \exception('Could not load the navigation XML file.');
        }
    }
    // }}}
    
    // getAllUrls() {{{
    /**
     * gets urls for all nodes
     * 
     * @param \DOMNode $xml
     * 
     * @return (array) array of nodes
     */
    public function getAllUrls(\DOMNode $node, $url = "") {
        $urls = array();

        list($xml, $node) = \depage\xml\Document::getDocAndNode($node);

        $xpath = new \DOMXpath($xml);
        $pages = $xpath->query("//pg:page[@url]");

        if ($pages->length == 0) {
            // attribute not available -> add now
            $this->addUrlAttributes($xml);
            $pages = $xpath->query("//pg:page[@url]");
        }

        foreach ($pages as $page) {
            $urls[$page->getAttribute("db:id")] = $page->getAttribute("url");
        }

        return $urls;
    }
    // }}}
    
    // addUrlAttributes() {{{
    /**
     * Add Urls Attributes
     * 
     * Adds a url attribute to each page in the XML DOM tree.
     * The url is built from the page name and the names of ancestor folders.
     * e.g. folder1/folder2/page1
     * 
     * @param \DOMNode $xml
     * 
     * @return (string) last url
     */
    public function addUrlAttributes(\DOMNode $node, $url = "") {
        list($xml, $node) = \depage\xml\Document::getDocAndNode($node);

        // get current part of url from name
        if ($node->nodeName == 'pg:folder' || $node->nodeName == 'pg:page') {
            $url .= \html::get_url_escaped(strtolower($node->getAttribute('name')));
        }

        // loop through child nodes
        if ($node->hasChildNodes()) {
            foreach($node->childNodes as $child){
                if ($child instanceof \DOMElement) {
                    $this->addUrlAttributes($child, $url . "/");
                }
            }
        }

        // get url based on current node
        if ($node->getAttribute("isIndex") == "true") {
            // set url as empty when index page
            $url = "";
        } elseif ($node->nodeName == 'pg:folder' && $node->firstChild) {
            // set url of folders to url of first child page
            $url = $node->firstChild->getAttribute("url");
        } elseif ($node->nodeName == 'pg:page') {
            if ($ext = $node->getAttribute("file_type")) {
                $url = $url . "." . $ext;
            } else {
                $url = $url . "/";
            }
        } else {
            $url = null;
        }
        if (!is_null($url) && $node->getAttribute("url") !== $url) {
            $node->setAttribute("url", $url);
        }
    }
    // }}}
    // addStatusAttributes() {{{
    /**
     * Add Status
     * 
     * Checks the request URL and sets the status of the active page
     * in the XML DOM tree.
     * 
     * Sets parent folder statuses to 'parent-of-active'.
     *
     * @param \DOMDocument $xml
     */
    public function addStatusAttributes(\DOMNode $node, $url) {
        list($xml, $node) = \depage\xml\Document::getDocAndNode($node);

        $xpath = new \DOMXpath($xml);
        
        $pages = $xpath->query("//pg:page[@url='{$url}']");
        
        if($pages->length) {
            // a page has activeUrl
            $page = $pages->item(0);
            $page->setAttribute('status', $this::ACTIVE_STATUS);
            $page = $page->parentNode;
        } else {
            // search for parent urls
            while ($pages->length == 0 && strrpos($url, "/") !== false) {
                $url = substr($url, 0, strrpos($url, "/"));
                $pages = $xpath->query("//pg:page[@url='{$url}/']");
            }
            if($pages->length) {
                $page = $pages->item(0);
            }
        }

        while ($page && $page->nodeType == XML_ELEMENT_NODE) {
            // loop to top
            $page->setAttribute('status', $this::PARENT_STATUS);
            $page = $page->parentNode;
        }
    }
    // }}}
    // addLocalizedAttributes() {{{
    /**
     * Add localized name
     * 
     * @param \DOMDocument $xml
     */
    private function addLocalizedAttributes(\DOMDocument $xml, $lang) {
        $xpath = new \DOMXpath($xml);
        
        $nodes = $xpath->query("//*[@name]");
        
        foreach ($nodes as $node) {
            $node->setAttribute('localized', _($node->getAttribute('name')));
        }
    }
    // }}}
    
    // {{{ transform()
    /**
     * Transform
     * 
     * Transforms the XML navigation to an HTML format
     * according to the XSL provided.
     * 
     * @param \DOMDocument $xml
     * @param \DOMDocument $xslt
     *  
     * @return (string) $html
     */
    public function transform($activeUrl, $lang, $xslParam = array()) {
        if (!($this->xslDOM instanceof \DOMDocument)) {
            throw new \exception('You have to load a navigation xsl-template.');
        }
        if (!($this->xmlDOM instanceof \DOMDocument)) {
            throw new \exception('You have to load a navigation xml-file.');
        }

        if ($lang !== '' && substr($lang, -1) != '/') {
            $lang .= "/";
        }

        // add attributes to dom tree
        $this->addUrlAttributes($this->xmlDOM, $lang);
        $this->addStatusAttributes($this->xmlDOM, $lang . $activeUrl);
        $this->addLocalizedAttributes($this->xmlDOM, $lang);

        // initialize processor and transform
        $xslt = new \XSLTProcessor();
        $xslt->setParameter("", $xslParam);
        $xslt->importStylesheet($this->xslDOM);
        
        libxml_use_internal_errors(true);
        
        if (!$html = $xslt->transformToXml($this->xmlDOM)) {   
            var_dump(libxml_get_errors());
            
            $error = libxml_get_last_error();
            $error = empty($error)? 'Could not transform the navigation XML document.' : $error->message;
            
            throw new \exception($error);
        }
        
        return $html;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
