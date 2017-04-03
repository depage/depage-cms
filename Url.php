<?php
/**
 * @file    Url.php
 *
 * description
 *
 * copyright (c) 2017 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Router;

/**
 * @brief Url
 * Class Url
 */
class Url
{
    /**
     * @brief scheme
     **/
    public $scheme = "";

    /**
     * @brief host
     **/
    public $host = "";

    /**
     * @brief port
     **/
    public $port = "";

    /**
     * @brief lang
     **/
    public $lang = "";

    /**
     * @brief baseUrl
     **/
    public $baseUrl = "";

    /**
     * @brief path
     **/
    public $path = "";

    /**
     * @brief parts
     **/
    public $parts = [];

    // {{{ fromUrl()
    /**
     * @brief fromUrl
     *
     * @param mixed $url, $baseUrl = "", $languages = []
     * @return void
     **/
    public static function fromUrl($url, $baseUrl = "", $languages = [])
    {
        $result = new self();

        // parse baseUrl
        $parts = parse_url($baseUrl);

        $result->scheme = !empty($parts['scheme']) ? $parts['scheme'] : "";
        $result->host = !empty($parts['host']) ? $parts['host'] : "";
        $result->port = !empty($parts['port']) ? $parts['port'] : "";
        $result->basePath = !empty($parts['path']) ? $parts['path'] : "/";
        $result->baseUrl = $baseUrl;

        if (empty($result->port)) {
            $result->port = $result->scheme == "https" ? 443 : 80;
        }
        if (substr($result->basePath, -1) != "/") {
            $result->basePath .= "/";
        }

        // parse and split url
        $path = parse_url($url, PHP_URL_PATH);

        if ($result->basePath != "/") {
            // remove basePath from request
            // @todo throw error if request does not start with basePath?
            $path = substr($path, strlen($result->basePath) - 1);
        } else {
            $path = $path;
        }
        $result->path = $path;
        $path = trim($path, "/");

        $parts = explode("/", $path);

        if (isset($parts[1]) && strlen($parts[1]) == 2) {
            // assume its a lang identifier if strlen is 2
            $result->lang = array_splice($parts, 1, 1)[0];
        }
        if (!in_array($result->lang, $languages)) {
            $result->lang = "";
        }
        // @todo set default language?

        $result->parts = $parts;

        return $result;
    }
    // }}}
    // {{{ fromRequestUri()
    /**
     * @brief fromRequestUri
     *
     * @param mixed $baseUrl = "", $languages = []
     * @return void
     **/
    public static function fromRequestUri($baseUrl = "", $languages = [])
    {
        return self::fromUrl($_SERVER['REQUEST_URI'], $baseUrl, $languages);
    }
    // }}}

    // {{{ getParts()
    /**
     * @brief getParts
     *
     * @param mixed
     * @return void
     **/
    public function getParts($offset, $length = null)
    {
        return array_slice($this->parts, $offset, $length);
    }
    // }}}
    // {{{ getPart()
    /**
     * @brief getPart
     *
     * @param mixed
     * @return void
     **/
    public function getPart($offset)
    {
        return $this->parts[$offset];
    }
    // }}}

    // {{{ toString()
    /**
     * @brief toString
     *
     * @param mixed
     * @return void
     **/
    public function __toString()
    {
        return $this->path;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
