<?php

namespace Depage\Search;

use Depage\Http\Request;

/**
 * brief Indexer
 * Class Indexer
 */
class Indexer
{
    /**
     * @brief xpathTitle
     **/
    protected $xpathTitle = "/html/head/title";

    /**
     * @brief xpathHeadlines
     **/
    protected $xpathHeadlines = "//h1 | //h2 | //h3 | //h4 | //h5 | //h6 | //hgroup";

    /**
     * @brief xpathContent
     **/
    protected $xpathContent = "//article | //section | //main";

    /**
     * @brief xpathImages
     **/
    protected $xpathImages = "//img";

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed
     * @return void
     **/
    public function __construct()
    {

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
        $request = new Request($url);
        $response = $request->execute();
        $doc = $response->getXml();

        $title = [];
        $headlines = [];
        $content = [];
        $images = [];

        $xpath = new \DOMXPath($doc);

        $nodes = $xpath->query($this->xpathTitle);
        foreach ($nodes as $node) {
            $title[] = $node->textContent;
        }

        $nodes = $xpath->query($this->xpathHeadlines);
        foreach ($nodes as $node) {
            $headlines[] = $node->textContent;
        }

        $nodes = $xpath->query($this->xpathContent);
        foreach ($nodes as $node) {
            $content[] = $node->textContent;
        }

        $nodes = $xpath->query($this->xpathImages);
        foreach ($nodes as $node) {
            $src = $node->getAttribute("src");
            $srcset = $node->getAttribute("srcset");

            if (!empty($src)) {
                $images[] = $src;
            }
            if (!empty($srcset)) {
                $imgs = preg_match_all("/([^ ]+) [^ ]+,?/", $srcset, $matches);
                foreach ($matches[1] as $img) {
                    $images[] = $img;
                }
            }
        }
        $images = array_unique($images);
        // @todo update relative image paths to be dependend on base or on current url

        var_dump($title);
        var_dump($headlines);
        var_dump($content);
        var_dump($images);
        die();

    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
