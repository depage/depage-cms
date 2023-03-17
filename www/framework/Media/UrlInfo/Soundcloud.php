<?php

namespace Depage\Media\UrlInfo;

class Soundcloud extends \Depage\Media\UrlInfo
{
    // {{{ variables
    public $audioId = null;
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

        $this->platform = "soundcloud";
        $this->isAudio = true;
    }
    // }}}

    // {{{ toXml()
    /**
     * @brief toXml
     **/
    public function toXml()
    {
        $fields = [
            'audioId',
        ];
        $doc = parent::toXml();

        foreach ($fields as $field) {
            $doc->documentElement->setAttribute($field, $this->$field);
        }

        return $doc;
    }
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
