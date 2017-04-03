<?php
/**
 * @file    Router.php
 *
 * description
 *
 * copyright (c) 2017 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Router;

/**
 * @brief Router
 * Class Router
 */
class Router
{
    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $baseUrl
     * @return void
     **/
    public function __construct($baseUrl, $languages = [])
    {
        $this->baseUrl = $baseUrl;
        $this->languages = $languages;
    }
    // }}}
    // {{{ analyzeUrl()
    /**
     * @brief analyzeUrl
     *
     * @param mixed $url
     * @return void
     **/
    public function analyzeUrl($url)
    {
        return Url::fromUrl($url, $this->baseUrl, $this->languages);
    }
    // }}}
    // {{{ analyzeCurrentUrl()
    /**
     * @brief analyzeCurrentUrl
     *
     * @param mixed
     * @return void
     **/
    public function analyzeCurrentUrl()
    {
        return Url::fromRequestUri($this->baseUrl, $this->languages);
    }
    // }}}
}


// vim:set ft=php sw=4 sts=4 fdm=marker et :
