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
    
    // addUrls() {{{
    /**
     * Add Urls
     * 
     * Adds a url attribute to each page in the XML DOM tree.
     * The url is built from the page name and the names of ancestor folders.
     * e.g. folder1/folder2/page1
     * 
     * @param \DOMDocument $xml
     * 
     * @return (string) last url
     */
    private function addUrls(\DOMElement $node, $url = '', $lang) {
        if($node->nodeName == 'pg:folder') {
            $url .= \html::get_url_escaped($node->getAttribute('name')) . '/';
        } elseif ($node->nodeName == 'pg:page') {
            $url .= \html::get_url_escaped($node->getAttribute('name')) . '/';

            if ($node->getAttribute("isIndex") == "true") {
                // set url as empty when index page
                $node->setAttribute('url', $lang);
            } else {
                // set url calculated path
                $node->setAttribute('url', $url);
            }
        }
        if ($node->hasChildNodes()) {
            $i = 0;
            foreach($node->childNodes as $child){
                if ($child instanceof \DOMElement) {
                    $lastUrl = $this->addUrls($child, $url, $lang);

                    if ($i == 0) {
                        // keep url of first child as url for folder
                        $folderUrl = $lastUrl;
                    }
                    $i++;
                }
            }
        }
        if($node->nodeName == 'pg:folder') {
            $node->setAttribute('url', $folderUrl);
        }

        return $url;
    }
    // }}}
    // addStatus() {{{
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
    private function addStatus(\DOMDocument $xml, $activeUrl, $lang) {
        $url = $lang . $activeUrl;

        $xpath = new \DOMXpath($xml);
        
        $pages = $xpath->query("//pg:page[@url='{$url}']");
        
        if($pages->length) {
            $page = $pages->item(0);
            $page->setAttribute('status', $this::ACTIVE_STATUS);
            
            $ancestors = $xpath->query("//pg:page[@url='{$url}']/ancestor::pg:*");
            
            foreach($ancestors as $ancestor) {
                $ancestor->setAttribute('status', $this::PARENT_STATUS);
            }
        }
    }
    // }}}
    // addLocalized() {{{
    /**
     * Add localized name
     * 
     * @param \DOMDocument $xml
     */
    private function addLocalized(\DOMDocument $xml, $lang) {
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
        $this->addUrls($this->xmlDOM, $lang, $lang);
        $this->addStatus($this->xmlDOM, $activeUrl, $lang);
        $this->addLocalized($this->xmlDOM, $lang);

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
