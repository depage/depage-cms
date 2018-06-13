<?php
/**
 * @file    framework/json/json.php
 *
 * depage json module
 *
 *
 * copyright (c) 2011 Lion Vollnhals [lion.vollnhals@googlemail.com]
 *
 * @author    Lion Vollnhals [lion.vollnhals@googlemail.com]
 *
 */

namespace Depage\Json;

class Json
{
    private $param;
    private $content;

    public $contentType = "application/json";
    public $charset = "UTF-8";

    // {{{ __construct()
    /**
     * initializes json object
     *
     * @param $content (mixed) json_encode compatible object
     * @param $param (array) params how to use json
     */
    public function __construct($content, $param = 0) {
        $this->content = $content;
        $this->param = $param;
    }
    // }}}

    // {{{ __toString()
    /**
     * renders template
     *
     * @return
     */
    public function __toString() {
        $json = "";
        try {
            $json = json_encode($this->content, $this->param);
        } catch (Exception $e) {
            echo($e);
        }

        return $json;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
