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
    /**
     * @brief handlers
     **/
    protected $handlers = [];

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

    // {{{ addHandler()
    /**
     * @brief addHandler
     *
     * @param mixed $
     * @return void
     **/
    public function addHandler($handler, $route = 'default')
    {
        $this->handlers[$route] = $handler;

        return $this;
    }
    // }}}
    // {{{ route()
    /**
     * @brief route
     *
     * @param mixed
     * @return void
     **/
    public function route($url = null)
    {
        if (is_null($url)) {
            $url = $this->analyzeCurrentUrl();
        } else {
            $url = $this->analyzeUrl();
        }
        $action = $url->getPart(1);
        $params = $url->getParts(2);

        $handler = $this->handlers['default'];
        $handler->baseUrl = $this->baseUrl;

        // @todo add support for using index-method if not default is set
        // @todo add support for handling all methods through one function

        if (empty($action)) {
            $action = "index";
        }
        if (is_callable([$handler, $action])) {
            $response = call_user_func_array([$handler, $action], $params);
        } else {
            $response = $handler->notFound($action, $params);
        }

        if (method_exists($response, 'sendHeaders')) {
            $response->sendHeaders();
        }

        return $response;
    }
    // }}}

    // {{{ redirect
    public static function redirect($url)
    {
        header('Location: ' . $url);
        die( "Tried to redirect you to <a href=\"$url\">$url</a>");
    }
    // }}}
}


// vim:set ft=php sw=4 sts=4 fdm=marker et :
