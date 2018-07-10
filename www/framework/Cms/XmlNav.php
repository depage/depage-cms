<?php

namespace Depage\Cms;

class XmlNav {
    const ACTIVE_STATUS = 'active';
    const PARENT_STATUS = 'parent-of-active';

    private $xslDOM;
    private $xmlDOM;

    /**
     * @brief rout
     **/
    public $routeHtmlThroughPhp = false;

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
        $urlsByPageId = [];
        $pageIdByUrl = [];
        $pagedataIdByPageId = [];

        list($xml, $node) = \Depage\Xml\Document::getDocAndNode($node);

        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace("pg", "http://cms.depagecms.net/ns/page");
        $pages = $xpath->query("//pg:*[@url]");

        if ($pages->length == 0) {
            // attribute not available -> add now
            $this->addUrlAttributes($xml);
            $pages = $xpath->query("//pg:*[@url]");
        }

        foreach ($pages as $page) {
            $id = (int) $page->getAttribute("db:id");
            $urlsByPageId[$id] = $page->getAttribute("url");
            $pagedataIdByPageId[$id] = $page->getAttribute("db:docref");
            if ($page->nodeName == "pg:page" || $page->nodeName == "pg:redirect") {
                $pageIdByUrl[$page->getAttribute("url")] = $id;
            }
        }

        return [$urlsByPageId, $pageIdByUrl, $pagedataIdByPageId];
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
        list($xml, $node) = \Depage\Xml\Document::getDocAndNode($node);

        // get current part of url from name
        if (in_array($node->nodeName, ['pg:folder', 'pg:page', 'pg:redirect', 'proj:folder'])) {
            $url .= \Depage\Html\Html::getEscapedUrl(mb_strtolower($node->getAttribute('name')));
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
        } elseif ($node->nodeName == 'pg:folder') {
            // set url of folders to url of first child page
            $xpath = new \DOMXpath($xml);
            $urlNodes = $xpath->query("(.//pg:page/@url)[1]", $node, true);
            $url = $urlNodes->item(0)->value ?? "";
        } elseif ($node->nodeName == 'pg:redirect') {
            $url = $url . ".php";
        } elseif ($node->nodeName == 'pg:page') {
            if ($ext = $node->getAttribute("file_type")) {
                if ($this->routeHtmlThroughPhp && $ext == "html") {
                    $ext = "php";
                }
                $url = $url . "." . $ext;
            } else {
                $url = $url . "/";
            }
        } elseif ($node->nodeName == 'proj:folder') {
            $url = $url . "/";
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
        list($xml, $node) = \Depage\Xml\Document::getDocAndNode($node);

        $xpath = new \DOMXpath($xml);

        $page = null;
        $pages = $xpath->query("//pg:page[@url='{$url}']");

        if ($pages && $pages->length) {
            // a page has activeUrl
            $page = $pages->item(0);
            $page->setAttribute('status', $this::ACTIVE_STATUS);
            $page = $page->parentNode;

            while ($page && $page->nodeType == XML_ELEMENT_NODE) {
                // loop to top
                $page->setAttribute('status', $this::PARENT_STATUS);
                $page = $page->parentNode;
            }
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
    public function transform($activeUrl, $lang, $xslParam = []) {
        if (!($this->xslDOM instanceof \DOMDocument)) {
            throw new \exception('You have to load a navigation xsl-template.');
        }
        if (!($this->xmlDOM instanceof \DOMDocument)) {
            throw new \exception('You have to load a navigation xml-file.');
        }

        // add attributes to dom tree
        $this->addUrlAttributes($this->xmlDOM, $lang);
        $this->addStatusAttributes($this->xmlDOM, $lang . "/" . $activeUrl);
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
