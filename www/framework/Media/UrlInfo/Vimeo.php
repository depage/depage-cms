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

        $this->videoId = explode('/', $this->path)[1] ?? null;
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
