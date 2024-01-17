<?php
/**
 * @file    Handler.php
 *
 * description
 *
 * copyright (c) 2017 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Router;

/**
 * @brief Handler
 * Class Handler
 */
class Handler
{
    /**
     * @brief baseUrl
     **/
    public $baseUrl = "";

    /**
     * @brief options
     **/
    public $options = [];

    /**
     * @brief url
     **/
    public $url = "";

    /**
     * @brief urlSubArgs
     **/
    public $urlSubArgs = [];

    /**
     * @brief lang
     **/
    public $lang = null;

    // {{{ error()
    /**
     * @brief error
     *
     * @param mixed
     * @return void
     **/
    public function error($message = "Internal Server Error")
    {
        $message = htmlentities($message);

        return new \Depage\Http\Response(
            $message,
            ["HTTP/1.0 500 Internal Server Error"]
        );
    }
    // }}}
    // {{{ notFound()
    /**
     * @brief notFound
     *
     * @param mixed
     * @return void
     **/
    public function notFound($action = "", $params = "")
    {
        $action = htmlentities($action);

        return new \Depage\Http\Response(
            "Not Found '$action'",
            ["HTTP/1.0 404 Not Found"]
        );
    }
    // }}}
    // {{{ notAllowed()
    /**
     * @brief notAllowed
     *
     * @param mixed
     * @return void
     **/
    public function notAllowed($action = "", $params = "")
    {
        $action = htmlentities($action);

        return new \Depage\Http\Response(
            "Not Allowed '$action'",
            ["HTTP/1.0 403 Forbidden"]
        );
    }
    // }}}

    // {{{ redirect()
    /**
     * @brief redirect
     *
     * @param mixed $url
     * @return void
     **/
    protected function redirect($url)
    {
        if (preg_match("/^\/[^\/].*/", $url)) {
            // interal link
            $url = "{$this->baseUrl}{$this->lang}{$url}";
        }

        \Depage\Router\Router::redirect($url);
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
