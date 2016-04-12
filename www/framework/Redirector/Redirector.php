<?php

namespace Depage\Redirector;

/**
 * brief Redirector
 * Class Redirector
 */
class Redirector
{
    /**
     * @brief langugaes
     **/
    protected $langugaes = array();

    /**
     * @brief pages
     **/
    protected $pages = array();

    /**
     * @brief pageTree
     **/
    protected $pageTree = array();

    /**
     * @brief aliases
     **/
    protected $aliases = array();

    /**
     * @brief baseUrl
     **/
    protected $baseUrl = "";

    /**
     * @brief scheme
     **/
    protected $scheme = "http";

    /**
     * @brief port
     **/
    protected $port = 80;

    /**
     * @brief basePath
     **/
    protected $basePath = "/";

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed
     * @return void
     **/
    public function __construct($baseUrl = "")
    {
        if (!empty($baseUrl)) {
            $this->setBaseUrl($baseUrl);
        }
    }
    // }}}

    // {{{ setLanguages()
    /**
     * @brief setLanguages
     *
     * @param mixed $
     * @return void
     **/
    public function setLanguages($languages)
    {
        $this->languages = $languages;

        return $this;
    }
    // }}}
    // {{{ setPages()
    /**
     * @brief setPages
     *
     * @param mixed $
     * @return void
     **/
    public function setPages($pages)
    {
        $this->pages = $pages;

        // sort folders and pages into hierarchical tree structure
        foreach ($this->pages as $page) {
            $node = &$this->pageTree;
            $parts = explode("/", trim($page, "/"));

            foreach ($parts as $part) {
                if (!isset($node[$part])) {
                    $node[$part] = array();
                }
                $node = &$node[$part];
            }
        }

        return $this;
    }
    // }}}
    // {{{ setAliases()
    /**
     * @brief setAliases
     *
     * @param mixed $
     * @return void
     **/
    public function setAliases($aliases)
    {
        $this->aliases = $aliases;

        return $this;
    }
    // }}}
    // {{{ setBaseUrl()
    /**
     * @brief setBaseUrl
     *
     * @param mixed $
     * @return void
     **/
    public function setBaseUrl($url)
    {
        $this->baseUrl = $url;

        $parts = parse_url($this->baseUrl);

        $this->scheme = !empty($parts['scheme']) ? $parts['scheme'] : "";
        $this->port = !empty($parts['port']) ? $parts['port'] : "";
        $this->basePath = !empty($parts['path']) ? $parts['path'] : "/";

        return $this;
    }
    // }}}

    // {{{ getLanguageByBrowser()
    /**
     * @brief getLanguageByBrowser
     *
     * @param mixed $param
     * @return void
     **/
    public function getLanguageByBrowser($acceptString)
    {
        $language = $this->languages[0];

        $browserLanguages = explode(',', $acceptString);

        foreach ($browserLanguages as $lang) {
            $currentLanguages = explode(';', $lang);
            $currentLanguages = explode('-', $currentLanguages[0]);
            $currentLanguage = trim($currentLanguages[0]);
            if (in_array($currentLanguage, $this->languages)) {
                $language = $currentLanguage;
                break;
            }
        }

        return $language;
    }
    // }}}
    // {{{ getAlternativePage()
    /**
     * @brief getAlternativePage
     *
     * @param mixed
     * @return void
     *
     * @todo use pagtree instead of matching substrings
     **/
    public function getAlternativePage($request)
    {
        $altPage = "";
        $isFallback = false;
        $pages = array_merge(array_keys($this->aliases), $this->pages);

        if ($this->basePath != "/") {
            // remove basePath from request
            // @todo throw error if request does not start with basePath?
            $request = substr($request, strlen($this->basePath) - 1);
        }

        $request = explode("/", $request);

        // @todo handle languages in request

        //search for pages
        while ($altPage == "" && count($request) > 1) {
            $tempUrl = implode("/", $request) . "/";
            foreach ($pages as $page) {
                if (substr($page . "/", 0, strlen($tempUrl)) == $tempUrl) {
                    $altPage = $page;

                    break;
                }
            }
            array_pop($request);
        }

        // resolve alias
        if (isset($this->aliases[$altPage])) {
            $altPage = $this->aliases[$altPage];
        }

        // fallback to first url
        if ($altPage == "") {
            return $this->getIndexPage();
        }

        return new Result($altPage, $isFallback);
    }
    // }}}
    // {{{ getIndexPage()
    /**
     * @brief getIndexPage
     *
     * @param mixed
     * @return void
     **/
    public function getIndexPage()
    {
        return new Result($this->pages[0], true);
    }
    // }}}

    // {{{ redirectToAlternativePage()
    /**
     * @brief redirectToAlternativePage
     *
     * @param $request
     * @return void
     **/
    public function redirectToAlternativePage($request, $acceptLanguage = "")
    {
        $url = $this->basePath;

        if (!empty($this->languages)) {
            $url .= $this->getLanguageByBrowser($acceptLanguage);
        }
        $url .= $this->getAlternativePage($request);

        header("Location: $url");
    }
    // }}}
    // {{{ redirectToIndex()
    /**
     * @brief redirectToIndex
     *
     * @return void
     **/
    public function redirectToIndex($acceptLanguage = "")
    {
        $url = $this->basePath;

        if (!empty($this->languages)) {
            $url .= $this->getLanguageByBrowser($acceptLanguage);
        }
        $url .= $this->getIndexPage();

        header("Location: $url");
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
