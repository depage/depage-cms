<?php

namespace Depage\Redirector {

/**
 * brief Redirector
 * Class Redirector
 */
class Redirector
{
    /**
     * @brief languages
     **/
    protected $languages = [];

    /**
     * @brief pages
     **/
    protected $pages = [];

    /**
     * @brief alternatePages
     **/
    protected $alternatePages = [];

    /**
     * @brief pageTree
     **/
    protected $pageTree = [];

    /**
     * @brief aliases
     **/
    protected $aliases = [];

    /**
     * @brief rootAliases
     **/
    protected $rootAliases = [];

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

    /**
     * @brief lang
     **/
    protected $lang = "";

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
                    $node[$part] = [];
                }
                $node = &$node[$part];
            }
        }

        return $this;
    }
    // }}}
    // {{{ setAlternatePages()
    /**
     * @brief setAlternatePages
     *
     * @param mixed $urls
     * @return void
     **/
    public function setAlternatePages($urls)
    {
        $this->alternatePages = $urls;
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
    // {{{ setRootAliases()
    /**
     * @brief setRootAliases
     *
     * @param mixed $aliases
     * @return void
     **/
    public function setRootAliases($aliases)
    {
        $this->rootAliases = $aliases;

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
        $this->host = !empty($parts['host']) ? $parts['host'] : "";
        $this->port = !empty($parts['port']) ? $parts['port'] : "";
        $this->basePath = !empty($parts['path']) ? $parts['path'] : "/";

        if (substr($this->basePath, -1) != "/") {
            $this->basePath .= "/";
        }

        return $this;
    }
    // }}}

    // {{{ parseRequestUri()
    /**
     * @brief parseRequestUri
     *
     * @param mixed $
     * @return void
     **/
    protected function parseRequestUri($requestUri)
    {
        if ($this->basePath != "/") {
            // remove basePath from request
            // @todo throw error if request does not start with basePath?
            $request = substr($requestUri, strlen($this->basePath) - 1);
        } else {
            $request = $requestUri;
        }

        $request = explode("/", $request);

        if (isset($request[1]) && strlen($request[1]) == 2) {
            // assume its a lang identifier if strlen is 2
            $this->lang = array_splice($request, 1, 1)[0];
        }
        if (!in_array($this->lang, $this->languages)) {
            $this->lang = "";
        }


        return implode("/", $request);
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

        foreach ($browserLanguages as $l) {
            $l = explode(';', $l);
            $l = explode('-', $l[0]);
            $l = trim($l[0]);
            if (in_array($l, $this->languages)) {
                $language = $l;
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

        if (isset($this->alternatePages[$request])) {
            return new Result($this->alternatePages[$request], $isFallback);
        }

        $request = explode("/", $request);

        //search for pages
        while ($altPage == "" && count($request) > 1) {
            $tempUrl = implode("/", $request) . "/";
            foreach ($this->pages as $page) {
                if (substr($page . "/", 0, strlen($tempUrl)) == $tempUrl) {
                    $altPage = $page;

                    break;
                }
            }
            array_pop($request);
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
        reset($this->pages);

        return new Result(current($this->pages), true);
    }
    // }}}
    // {{{ getBasePath()
    /**
     * @brief getBasePath
     *
     * @return void
     **/
    public function getBasePath()
    {
        return $this->basePath;
    }
    // }}}

    // {{{ testAliases()
    /**
     * @brief testAliases
     *
     * @param mixed
     * @return void
     **/
    public function testAliases($requestUri, $acceptLanguage = "")
    {
        $request = $this->parseRequestUri($requestUri);

        foreach ($this->rootAliases as $regex => $repl) {
            $regex = "/" . str_replace("/", "\/", $regex) . "/";
            $url = preg_replace($regex, $repl, $request);

            if ($url != $request) return "." . $url;
        }

        if ($this->lang != "") {
            $lang = $this->lang;
        } else if (!empty($this->languages)) {
            $lang = $this->getLanguageByBrowser($acceptLanguage);
        }

        foreach ($this->aliases as $regex => $repl) {
            $regex = "/" . str_replace("/", "\/", $regex) . "/";
            $url = preg_replace($regex, $repl, $request);

            if ($url != $request) return "./" . $lang . $url;
        }

        return "";
    }
    // }}}
    // {{{ redirectToAlternativePage()
    /**
     * @brief redirectToAlternativePage
     *
     * @param $request
     * @return void
     **/
    public function redirectToAlternativePage($requestUri, $acceptLanguage = "")
    {
        $url = $this->scheme . "://" . $this->host . $this->basePath;
        $request = $this->parseRequestUri($requestUri);

        if ($this->lang != "") {
            $url .= $this->lang;
        } else if (!empty($this->languages)) {
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
    public function redirectToIndex($requestUri = "/", $acceptLanguage = "")
    {
        $url = $this->scheme . "://" . $this->host . $this->basePath;
        $request = $this->parseRequestUri($requestUri);

        if ($this->lang != "") {
            $url .= $this->lang;
        } else if (!empty($this->languages)) {
            $url .= $this->getLanguageByBrowser($acceptLanguage);
        }
        $url .= $this->getIndexPage();

        header("Location: $url");
    }
    // }}}
}

}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
