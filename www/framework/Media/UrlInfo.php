<?php
/**
 * @file    UrlInfo.php
 *
 * description
 *
 * copyright (c) 2021 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Media;

class UrlInfo
{
    public $url = null;
    public $valid = false;
    public $scheme = null;
    public $host = null;
    public $port = null;
    public $user = null;
    public $pass = null;
    public $path = null;
    public $query = null;
    public $fragment = null;
    public $platform = null;
    public $isVideo = false;
    public $isAudio = false;
    public $isImage = false;

    // {{{ construct()
    /**
     * @brief __construct
     *
     * @param mixed $url
     * @return void
     **/
    public function __construct($url)
    {
        $this->url = $url;
        $url = filter_var($url, \FILTER_VALIDATE_URL);

        if (!$url) {
            return;
        }

        $info = parse_url($url);
        $this->valid = true;

        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    }
    // }}}
    // {{{ factory()
    /**
     * @brief factory
     *
     * @param mixed $url
     * @return void
     **/
    public static function factory($url)
    {
        $u = filter_var($url, \FILTER_VALIDATE_URL);
        $host = parse_url($u, \PHP_URL_HOST);

        if (preg_match("/^soundcloud\.com$/", $host)) {
            return new UrlInfo\Soundcloud($url);
        } else if (preg_match("/^(www\.)?youtube\.com$/", $host)) {
            return new UrlInfo\Youtube($url);
        } else if (preg_match("/^(www.)?vimeo\.com$/", $host)) {
            return new UrlInfo\Vimeo($url);
        }

        return new static($url);
    }
    // }}}

    // {{{ toXml()
    /**
     * @brief toXml
     *
     * @param mixed
     * @return void
     **/
    public function toXml()
    {
        $fields = [
            'url',
            'valid',
            'scheme',
            'host',
            'port',
            'user',
            'pass',
            'path',
            'query',
            'fragment',
            'platform',
            'isVideo',
            'isAudio',
            'isImage',
        ];

        $doc = new \DOMDocument();
        $node = $doc->createElement("url");

        foreach ($fields as $key) {
            $node->setAttribute($key, $this->$key);
        }

        $doc->appendChild($node);

        return $doc;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
