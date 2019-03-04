<?php

namespace Depage\Search;

use Depage\Http\Request;

/**
 * brief Indexer
 * Class Indexer
 */
class Indexer
{
    // {{{ variables
    /**
     * @brief xpathBase
     **/
    protected $xpathBase = "//base/@href";

    /**
     * @brief xpathExcluded
     **/
    protected $xpathExcluded = "//script";

    /**
     * @brief xpathTitle
     **/
    protected $xpathTitle = "/html/head/title";

    /**
     * @brief xpathDescription
     **/
    protected $xpathDescription = "/html/head/meta[@name = 'description']/@content";

    /**
     * @brief xpathHeadlines
     **/
    protected $xpathHeadlines = ".//h1 | .//h2 | .//h3 | .//h4 | .//h5 | .//h6";

    /**
     * @brief xpathContent
     **/
    //protected $xpathContent = ".//article[not(ancestor::main) and not(ancestor::section)] | .//section[not(ancestor::main) and not(ancestor::article)] | .//main";
    protected $xpathContent = ".//article | .//section | .//main";

    /**
     * @brief xpathImgAlt
     **/
    protected $xpathImgAlt = ".//img/@alt";

    /**
     * @brief xpathImages
     **/
    protected $xpathImages = ".//img/@src | .//img/@srcset";

    /**
     * @brief xpathLinks
     **/
    protected $xpathLinks = ".//a/@href";

    /**
     * @brief contentNodes
     **/
    protected $contentNodes = null;

    /**
     * @brief baseUrl
     **/
    protected $baseUrl = null;

    /**
     * @brief title
     **/
    protected $title = null;

    /**
     * @brief description
     **/
    protected $description = null;

    /**
     * @brief headlines
     **/
    protected $headlines = null;

    /**
     * @brief content
     **/
    protected $content = null;

    /**
     * @brief images
     **/
    protected $images = null;

    /**
     * @brief links
     **/
    protected $links = null;

    /**
     * @brief xpath
     **/
    protected $xpath = null;

    /**
     * @brief db
     **/
    protected $db = null;
    // }}}

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed
     * @return void
     **/
    public function __construct($db = null)
    {
        if (!empty($db)) {
            $this->db = new Providers\Pdo($db);
        }
    }
    // }}}
    // {{{ index()
    /**
     * @brief index
     *
     * @param mixed $
     * @return void
     **/
    public function index($url)
    {
        $this->load($url);

        $title = $this->getTitle();
        $description = $this->getDescription();
        $headlines = $this->getHeadlines();
        $content = $this->getContent();

        if (is_null($this->db)) {
            throw new \Exception("Database for indexer not set");
        }
        $this->db->add($url, $title, $description, $headlines, $content);

        return $this;
    }
    // }}}
    // {{{ remove()
    /**
     * @brief remove
     *
     * @param mixed $url
     * @return void
     **/
    public function remove($url)
    {
        if (is_null($this->db)) {
            throw new \Exception("Database for indexer not set");
        }
        $this->db->remove($url);
    }
    // }}}
    // {{{ load()
    /**
     * @brief load
     *
     * @param mixed $
     * @return void
     **/
    public function load($url)
    {
        $request = new Request($url);
        $response = $request->execute();
        $this->doc = $response->getXml();

        $this->loadXml($this->doc, $url);

        return $this;
    }
    // }}}
    // {{{ loadXml()
    /**
     * @brief loadXml
     *
     * @param mixed $content
     * @return void
     **/
    public function loadXml($content, $url = "")
    {
        if (is_string($content)) {
            $oldErrorHandler = set_error_handler(function() {});
            $useInternalErrors = libxml_use_internal_errors(false);

            $this->doc = new \Depage\Xml\Document();
            if (!$this->doc->loadHtml($content)) {
                throw new \Exception('Unable to parse content into XML: ' . libxml_get_last_error());
            }

            libxml_use_internal_errors($useInternalErrors);
            set_error_handler($oldErrorHandler);
            libxml_clear_errors();
        } else if (!is_a($content, "DOMDocument")) {
            throw new \Exception('loaded content is not a DOMDocument');
        }

        $this->baseUrl = null;
        $this->title = [];
        $this->description = [];
        $this->headlines = [];
        $this->content = [];
        $this->images = [];
        $this->links = [];

        $this->contentNodes = new \SplObjectStorage();

        $this->xpath = new \DOMXPath($this->doc);

        $this->extractBaseUrl($url);
        $this->extractContentNodes();

        return $this;
    }
    // }}}

    // {{{ extractBaseUrl()
    /**
     * @brief extractBaseUrl
     *
     * @param mixed $url = ""
     * @return void
     **/
    public function extractBaseUrl($url = "")
    {
        $nodes = $this->xpath->query($this->xpathBase);
        foreach ($nodes as $node) {
            $this->baseUrl = $node->value;
        }
        if (empty($this->baseUrl)) {
            $this->baseUrl = $url;
        }
        $this->baseUrl = substr($this->baseUrl, 0, strrpos($this->baseUrl, "/") + 1);
    }
    // }}}
    // {{{ extractContentNodes()
    /**
     * @brief extractContentNodes
     *
     * @return void
     **/
    protected function extractContentNodes()
    {
        // remove excluded nodes from document
        $nodes = $this->xpath->query($this->xpathExcluded);
        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }

        // extract content nodes
        $nodes = $this->xpath->query($this->xpathContent);
        foreach ($nodes as $node) {
            $this->contentNodes->attach($node);
        }

        // remove node if parent node is already included
        foreach ($this->contentNodes as $node) {
            $parentNode = $node->parentNode;
            while ($parentNode != null) {
                if ($this->contentNodes->contains($parentNode)) {
                    $this->contentNodes->detach($node);
                }
                $parentNode = $parentNode->parentNode;
            }
        }
    }
    // }}}

    // {{{ cleanContent()
    /**
     * @brief cleanContent
     *
     * @param mixed $param
     * @return void
     **/
    protected function cleanContent($content)
    {
        $content = implode(" ", $content);
        $content = preg_replace('/[\s\r\n]+/m', ' ', $content);
        $content = trim($content);

        return $content;
    }
    // }}}
    // {{{ getTitle()
    /**
     * @brief getTitle
     *
     * @param mixed
     * @return void
     **/
    public function getTitle()
    {
        // extract title
        $nodes = $this->xpath->query($this->xpathTitle);
        foreach ($nodes as $node) {
            $this->title[] = $node->textContent;
        }

        return $this->cleanContent($this->title);
    }
    // }}}
    // {{{ getDescription()
    /**
     * @brief getDescription
     *
     * @param mixed
     * @return void
     **/
    public function getDescription()
    {
        // extract description
        $nodes = $this->xpath->query($this->xpathDescription);
        foreach ($nodes as $node) {
            $this->description[] = $node->value;
        }

        return $this->cleanContent($this->description);
    }
    // }}}
    // {{{ getHeadlines()
    /**
     * @brief getHeadlines
     *
     * @return void
     **/
    public function getHeadlines()
    {
        foreach ($this->contentNodes as $contentNode) {
            // search for headline
            $nodes = $this->xpath->query($this->xpathHeadlines, $contentNode);
            foreach ($nodes as $node) {
                $this->headlines[] = $node->textContent;
            }
        }

        return $this->cleanContent($this->headlines);
    }
    // }}}
    // {{{ getContent()
    /**
     * @brief getContent
     *
     * @return void
     **/
    public function getContent()
    {
        foreach ($this->contentNodes as $contentNode) {
            $this->content[] = $contentNode->textContent;

            // search for image alt tags
            $nodes = $this->xpath->query($this->xpathImgAlt, $contentNode);
            foreach ($nodes as $node) {
                if (!empty($node->value)) {
                    $this->content[] = $node->value;
                }
            }
        }

        return $this->cleanContent($this->content);
    }
    // }}}
    // {{{ getImages()
    /**
     * @brief getImages
     *
     * @return void
     **/
    public function getImages()
    {
        $images = [];

        foreach ($this->contentNodes as $contentNode) {
            // extract images
            $nodes = $this->xpath->query($this->xpathImages, $contentNode);
            foreach ($nodes as $node) {
                $src = $node->value;
                if (preg_match_all("/([^ ]+) [^ ]+,?/", $src, $matches)) {
                    foreach ($matches[1] as $img) {
                        $images[] = $img;
                    }
                } else if (!empty($src)) {
                    $images[] = $src;
                }
            }
        }

        $images = array_unique($images);

        // update relative image paths to be dependent on base or on current url
        $url = new \Depage\Http\Url($this->baseUrl);
        foreach ($images as &$image) {
            $image = $url->getAbsolutePathTo($image);
        }

        $this->images = array_unique($images);

        return $this->images;
    }
    // }}}
    // {{{ getLinks()
    /**
     * @brief getLinks
     *
     * @return void
     **/
    public function getLinks()
    {
        $links = [];

        foreach ($this->contentNodes as $contentNode) {
            // extract links
            $nodes = $this->xpath->query($this->xpathLinks, $contentNode);
            foreach ($nodes as $node) {
                $href = $node->value;
                if (!empty($href)) {
                    $links[] = $href;
                }
            }
        }

        $this->links = array_unique($links);

        // update relative image paths to be dependent on base or on current url
        $url = new \Depage\Http\Url($this->baseUrl);
        foreach ($this->links as &$link) {
            $link = $url->getAbsolutePathTo($link);

        }

        return $this->links;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
