<?php

namespace Depage\Media\UrlInfo;

class Vimeo extends \Depage\Media\UrlInfo
{
    // {{{ variables
    public $videoId = null;
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

        $this->platform = "vimeo";
        $this->isVideo = true;

        $parts = explode("/", $this->path);
        $this->videoId = $parts[1] ?? null;
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
        return "https://player.vimeo.com/video/" . $this->videoId;
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
