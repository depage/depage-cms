<?php

namespace Depage\Cms;

class XmlNav {
    const ACTIVE_STATUS = 'active';
    const PARENT_STATUS = 'parent-of-active';

    private $xml;

    /**
     * @brief rout
     **/
    public $routeHtmlThroughPhp = false;

    protected $xmldb = null;

    protected $docRefByPageId = [];
    protected $nodeByDocRef = [];
    protected $pageIdByUrl = [];
    protected $pageIdByDocRef = [];
    protected $pageInfoByDocRef = [];
    protected $pageOrderByDocRef = [];
    protected $pagedataIdByPageId = [];
    protected $urlsByPageId = [];

    // {{{ constructor
    /**
     * initializes xmlnav object
     *
     * @param $xml  filename to load as navigation xml or navigation as \DOMDocument
     */
    public function __construct($xmlgetter = null, $xml = '') {
        if (!empty($xmlgetter)) {
            $this->setXmlGetter($xmlgetter);
        }
        if (!empty($xml)) {
            $this->setPageXml($xml);
        }
    }
    // }}}
    // {{{ setXmlGetter()
    /**
     * @brief setXmlGetter
     *
     * @param mixed $xmlgetter
     * @return void
     **/
    public function setXmlGetter($xmlgetter)
    {
        $this->xmldb = $xmlgetter;
    }
    // }}}
    // {{{ setPageXml()
    /**
     * @brief setPageXml
     *
     * @param mixed $xml
     * @return void
     **/
    public function setPageXml($xml)
    {
        if ($xml != '' && is_string($xml)) {
            $this->loadXmlFromFile($xml);
        } else if ($xml instanceof \DOMDocument) {
            $this->xml = $xml;
        }

        $this->docRefByPageId = [];
        $this->nodeByDocRef = [];
        $this->pageIdByUrl = [];
        $this->pageIdByDocRef = [];
        $this->pageInfoByDocRef = [];
        $this->pageOrderByDocRef = [];
        $this->pagedataIdByPageId = [];
        $this->urlsByPageId = [];

        list($xml, $node) = \Depage\Xml\Document::getDocAndNode($this->xml);

        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace("pg", "http://cms.depagecms.net/ns/page");
        $pages = $xpath->query("//pg:*[@url]");

        if ($pages->length == 0) {
            // url attribute not available -> add now
            $this->addUrlAttributes($xml);
            $pages = $xpath->query("//pg:*[@url]");
        }

        $i = 0;
        foreach ($pages as $node) {
            $id = (int) $node->getAttribute("db:id");
            $docref = $node->getAttributeNS("http://cms.depagecms.net/ns/database", "docref");

            // base mappings
            $this->urlsByPageId[$id] = $node->getAttribute("url");
            $this->docRefByPageId[$id] = $docref;
            $this->pageIdByDocRef[$docref] = $id;
            $this->nodeByDocRef[$docref] = $node;

            // only for pages and redirects
            if ($node->nodeName != "pg:page" && $node->nodeName != "pg:redirect") {
                continue;
            }
            $this->pageIdByUrl[$node->getAttribute("url")] = $id;
            $this->pageOrderByDocRef[$docref] = $i;

            $i++;
        }
    }
    // }}}
    // {{{ getPageXml()
    /**
     * @brief getPageXml
     *
     * @param mixed
     * @return void
     **/
    public function getPageXml()
    {
        return $this->xml;

    }
    // }}}

    // {{{ loadXmlFromFile()
    /**
     * loads navigation xml from file
     *
     * @param string $path
     */
    public function loadXmlFromFile($path) {
        $this->xml = new \DOMDocument();

        if (!$this->xml->load($path)) {
            throw new \exception('Could not load the navigation XML file.');
        }
    }
    // }}}

    // {{{ getPages()
    /**
     * @brief getPages
     *
     * @param mixed
     * @return void
     **/
    public function getPages()
    {
        foreach ($this->nodeByDocRef as $docref => $pos) {
            $this->getPageInfo($docref);
        }
        return array_filter($this->pageInfoByDocRef, function($page) {
            return $page->pageType != "pg:folder";
        });
    }
    // }}}
    // {{{ getRecentlyChangedPages()
    /**
     * @brief getRecentlyChangedPages
     *
     * @param max
     * @return array
     **/
    public function getRecentlyChangedPages($max = null)
    {
        $pages = $this->getPages();

        usort($pages, function($a, $b) {
            if (!$a->released && $b->released) {
                return -1;
            } else if ($a->released && !$b->released) {
                return 1;
            }

            return $b->lastchange->getTimestamp() <=> $a->lastchange->getTimestamp();
        });

        if ($max > 0) {
            $pages = array_splice($pages, 0, $max);
        }

        return $pages;
    }
    // }}}
    // {{{ getPublicPages()
    /**
     * @brief getPublicPages
     *
     * @param max
     * @return array
     **/
    public function getPublicPages($lastpublishDate = false)
    {
        $pages = $this->getPages();

        $pages = array_filter($pages, function($page) {
            return $page->released || $page->published;
        });

        usort($pages, function($a, $b) use ($lastpublishDate) {
            if (!$a->released && $b->released) {
                return -1;
            } else if ($a->released && !$b->released) {
                return 1;
            } else if (
                $lastpublishDate &&
                $a->lastrelease->getTimestamp() <= $lastpublishDate->getTimestamp() &&
                $b->lastrelease->getTimestamp() <= $lastpublishDate->getTimestamp()
            ) {
                return $a->pageOrder <=> $b->pageOrder;
            }

            return $b->lastchange->getTimestamp() <=> $a->lastchange->getTimestamp();
        });

        return $pages;
    }
    // }}}
    // {{{ getUnreleasedPages()
    /**
     * @brief getUnreleasedPages
     *
     * @param mixed
     * @return void
     **/
    public function getUnreleasedPages()
    {
        $pages = $this->getRecentlyChangedPages();

        $pages = array_filter($pages, function($page) {
            return $page->released == false;
        });

        return $pages;
    }
    // }}}
    // {{{ getUnpublishedPages()
    /**
     * @brief getUnpublishedPages
     *
     * @param mixed
     * @return void
     **/
    public function getUnpublishedPages($lastpublishDate, $released = null)
    {
        $pages = $this->getRecentlyChangedPages();

        if ($lastpublishDate === false) {
            return $pages;
        }

        $pages = array_filter($pages, function($page) use ($lastpublishDate, $released) {
            $r = true;
            if ($released === true) {
                $r = $page->released == $released;
            }
            if (!$page->lastrelease) {
                return false;
            }
            return $page->lastrelease->getTimestamp() > $lastpublishDate->getTimestamp() && $r;
        });

        return $pages;
    }
    // }}}

    // {{{ getUrl()
    /**
     * @brief getUrl
     *
     * @param mixed $
     * @return void
     **/
    public function getUrl($pageId)
    {
        return $this->urlsByPageId[$pageId] ?? false;
    }
    // }}}
    // {{{ getPageId()
    /**
     * @brief getPageId
     *
     * @param mixed $url
     * @return void
     **/
    public function getPageId($url)
    {
        return $this->pageIdByUrl[$url] ?? false;
    }
    // }}}
    // {{{ getPageDataId()
    /**
     * @brief getPageDataId
     *
     * @param mixed $pageId
     * @return void
     **/
    public function getPageDataId($pageId)
    {
        $this->getPageInfo($this->getDocRef($pageId));

        return $this->pagedataIdByPageId[$pageId] ?? false;
    }
    // }}}
    // {{{ getDocRef()
    /**
     * @brief getDocRef
     *
     * @param mixed getDocRef
     * @return void
     **/
    public function getDocRef($pageId)
    {
        return $this->docRefByPageId[$pageId] ?? false;
    }
    // }}}
    // {{{ getPageInfo()
    /**
     * @brief getPageInfo
     *
     * @param mixed $docref
     * @return void
     **/
    public function getPageInfo($docref)
    {
        if (!$this->xmldb) {
            return false;
        }
        if (!empty($this->pageInfoByDocRef[$docref])) {
            return $this->pageInfoByDocRef[$docref];
        }
        if (empty($this->nodeByDocRef[$docref])) {
            return false;
        }

        $node = $this->nodeByDocRef[$docref];
        $docInfo = $this->xmldb->getDocInfo($docref);

        $this->pagedataIdByPageId[$this->pageIdByDocRef[$docref]] = $docInfo->id;
        $docInfo->pageId = $node->getAttribute("db:id");
        $docInfo->pageOrder = $this->pageOrderByDocRef[$docref] ?? null;
        $docInfo->url = $node->getAttribute("url");
        $docInfo->fileType = $node->getAttribute("file_type");
        $docInfo->published = $node->getAttribute("db:published") == "true";
        $docInfo->released = $node->getAttribute("db:released") == "true";
        $docInfo->protected = $node->getAttribute("db:protected") == "true";
        $docInfo->pageType = $node->nodeName;

        $docInfo->nav = [];
        $docInfo->tags = [];
        foreach ($node->attributes as $name => $attrNode) {
            if (substr($name, 0, 4) == 'nav_') {
                $docInfo->nav[substr($name, 4)] = $attrNode->value;
            } else if (substr($name, 0, 4) == 'tag_') {
                $docInfo->tags[substr($name, 4)] = $attrNode->value;
            }
        }

        $this->pageInfoByDocRef[$docref] = $docInfo;

        return $this->pageInfoByDocRef[$docref] ?? false;
    }
    // }}}

    // addUrlAttributes() {{{
    /**
     *zM Add Urls Attributes
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
        if (in_array($node->nodeName, ['pg:folder', 'pg:page', 'pg:redirect'])) {
            $url .= \Depage\Html\Html::getEscapedUrl(mb_strtolower($node->getAttribute('name')));
        }
        if (in_array($node->nodeName, ['proj:folder'])) {
            $url .= $node->getAttribute('name');
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
    // {{{ addStatusAttributes()
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
        $pages = $xpath->query("//pg:page[@url='{$url}'] | //pg:redirect[@url='{$url}']");

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
    // {{{ addLocalizedAttributes()
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
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
