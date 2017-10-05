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
     * @brief urlPath
     **/
    public $urlPath = "";

    /**
     * @brief urlSubArgs
     **/
    public $urlSubArgs = [];

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
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
