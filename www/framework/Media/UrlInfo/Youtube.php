<?php

namespace Depage\Media\UrlInfo;

class Youtube extends \Depage\Media\UrlInfo
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

        $this->platform = "youtube";
        $this->isVideo = true;

        parse_str($this->query, $params);
        if (isset($params["v"])) {
            $this->videoId = $params["v"];
        }
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
