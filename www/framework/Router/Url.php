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
        $path = trim($path, "/");

        $parts = explode("/", $path);
        array_walk($parts, function(&$value, $key) {
            $value = rawurldecode($value);
        });

        if (isset($parts[0]) && strlen($parts[0]) == 2) {
            // assume its a lang identifier if strlen is 2
            $result->lang = array_splice($parts, 0, 1)[0];
        }
        if (!in_array($result->lang, $languages)) {
            $result->lang = "";
        }
        // @todo set default language?

        $result->parts = $parts;
        $result->path = "/" . implode("/", $parts) . "/";

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
    // {{{ fromRelativeUrl()
    /**
     * @brief fromRelativeUrl
     *
     * @param mixed $baseUrl = "", $languages = []
     * @return void
     **/
    public static function fromRelativeUrl($url, $baseUrl = "", $languages = [])
    {
        return self::fromUrl($url, $baseUrl, $languages);
    }
    // }}}

    // {{{ getParts()
    /**
     * @brief getParts
     *
     * @param mixed
     * @return void
     **/
    public function getParts($offset = 0, $length = null)
    {
        if (!is_null($length) && $length <= 0) {
            return [];
        } else if ($length > 0) {
            // initialize array with empty string up to length
            $parts = array_fill(0, $length, "");
            $parts = array_replace($parts, array_slice($this->parts, $offset, $length));
        } else {
            // otherwise get all the rest
            $parts = array_slice($this->parts, $offset);
        }

        return $parts;
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
        return $this->parts[$offset] ?? null;
    }
    // }}}
    // {{{ setPart()
    /**
     * @brief setPart
     *
     * @param mixed $
     * @return void
     **/
    public function setPart($offset, $val)
    {
        $this->parts[$offset] = $val;
        $this->path = "/" . trim(implode("/", $this->parts), "/") . "/";

        return $this;
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
        return $this->baseUrl . $this->lang . $this->path;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
