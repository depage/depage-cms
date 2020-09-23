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

    /**
     * @brief options
     **/
    protected $options = [
        'urlSubArgs' => -1,
    ];

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
            $url = $this->analyzeUrl($url);
        }
        $handler = $this->handlers['default'];
        $handler->baseUrl = $this->baseUrl;
        $options = (object) array_replace($this->options, $handler->options);

        // @todo add option to handler to get offset of first element
        $handler->url = $url;
        $handler->urlSubArgs = $url->getParts(0, $options->urlSubArgs);
        $handler->lang = $url->lang;
        $action = $url->getPart($options->urlSubArgs);
        $args = $url->getParts($options->urlSubArgs + 1);

        $action = str_replace("-", "_", $action);
        $action = preg_replace("/\.(html|php)$/", "", $action);

        if (empty($action)) {
            $action = "index";
        }
        $handler->action = $action;

        if (is_callable([$handler, '_init'])) {
            $handler->_init();
        }
        if (is_callable([$handler, $action])) {
            $response = call_user_func_array([$handler, $action], $args);
        } else {
            $response = $handler->notFound($action, $args);
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
