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
    protected $xpathContent = ".//article[not(ancestor::main) and not(ancestor::section)] | .//section[not(ancestor::main) and not(ancestor::article)] | .//main";

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
        $description = [];
        $headlines = [];
        $content = [];
        $images = [];
        $links = [];

        $xpath = new \DOMXPath($doc);

        // extract title
        $nodes = $xpath->query($this->xpathTitle);
        foreach ($nodes as $node) {
            $title[] = $node->textContent;
        }

        // extract description
        $nodes = $xpath->query($this->xpathDescription);
        foreach ($nodes as $node) {
            $description[] = $node->value;
        }

        // extract content and headline
        // @todo don't double include nodes
        $nodes = $xpath->query($this->xpathContent);
        foreach ($nodes as $node) {
            $content[] = $node->textContent;

            // search for headline
            $hNodes = $xpath->query($this->xpathHeadlines, $node);
            foreach ($hNodes as $hNode) {
                $headlines[] = $hNode->textContent;
            }

            // search for image alt tags
            $altNodes = $xpath->query($this->xpathHeadlines, $node);
            foreach ($altNodes as $altNode) {
                if (!empty($altNode->value)) {
                    $content[] = $altNode->value;
                }
            }

            // extract images
            $imgNodes = $xpath->query($this->xpathImages, $node);
            foreach ($imgNodes as $imgNode) {
                $src = $imgNode->value;
                if (preg_match_all("/([^ ]+) [^ ]+,?/", $src, $matches)) {
                    foreach ($matches[1] as $img) {
                        $images[] = $img;
                    }
                } else if (!empty($src)) {
                    $images[] = $src;
                }
            }
            $images = array_unique($images);
            // @todo update relative image paths to be dependend on base or on current url

            // extract links
            $aNodes = $xpath->query($this->xpathLinks, $node);
            foreach ($aNodes as $aNode) {
                $href = $aNode->value;
                if (!empty($href)) {
                    $links[] = $href;
                }
            }
            $links = array_unique($links);
        }


        echo("Title");
        var_dump($title);

        echo("Description");
        var_dump($description);

        echo("Headlines");
        var_dump($headlines);

        echo("Content");
        var_dump($content);

        echo("Images");
        var_dump($images);

        echo("Links");
        var_dump($links);

    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
