<?php

namespace Depage\Media\UrlInfo;

class Youtube extends \Depage\Media\UrlInfo
{
    // {{{ variables
    public $videoId = null;

    public static $hostRegex = "/^youtu\.be$|^(www\.)?youtube\.com$/";
    // }}}

    // {{{ construct()
    /**
     * @brief __construct
     *
     * @param mixed $url
     * @return void
     **/
    public function __construct($url)
    {
        parent::__construct($url);

        $this->platform = "youtube";
        $this->isVideo = true;

        parse_str($this->query, $params);
        $parts = explode("/", $this->path);

        if (isset($params["v"])) {
            $this->videoId = $params["v"];
        } else if ($this->host == "youtu.be") {
            $this->videoId = $parts[1];
        } else if ($parts[1] == "shorts") {
            $this->videoId = $parts[2];
        } else if ($parts[1] == "embed") {
            $this->videoId = $parts[2];
        }
    }
    // }}}

    // {{{ getEmbedUrl()
    /**
     * @brief getEmbedUrl
     *
     * @return string
     **/
    public function getEmbedUrl()
    {
        return "https://www.youtube-nocookie.com/embed/" . $this->videoId;
    }
    // }}}

    // {{{ toXml()
    /**
     * @brief toXml
     *
     * @return void
     **/
    public function toXml()
    {
        $fields = [
            'videoId',
        ];
        $doc = parent::toXml();

        foreach ($fields as $field) {
            $doc->documentElement->setAttribute($field, $this->$field);
        }

        return $doc;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
