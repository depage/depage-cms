<?php
/**
 * @file    framework/html/html.php
 *
 * depage html module
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

class html {
    protected $data = array();
    // {{{ default config
    protected $defaults = array(
    );
    protected $options = array();
    // }}}
    
    // {{{ constructor
    /**
     * automatically loads classes from the framework or the private modules
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    public function __construct($options = NULL) {
        $conf = new config($options);
        $this->options = $conf->toOptions($this->defaults);
    }
    // }}}
    // {{{ add
    /**
     * renders complete html text from data array
     *
     * @return  null
     */
    public function add($s) {
        array_push($this->data, $s);
    }
    // }}}
    // {{{ addTag
    /**
     * adds a html tag
     *
     * @param $tag (string) tag name
     *
     * @return  null
     */
    public function addTag($tag, $content, $attr = array()) {
        $s = "<$tag";
        foreach ($attr as $name => $value) {
            $s .= " $name=\"" . htmlentities($value) . "\"";
        }
        $s .= ">";

        $s .= htmlentities($content);

        $s .= "</$tag>";

        $this->add($s);
    }
    // }}}
    // {{{ render
    /**
     * renders complete html text from data array
     *
     * @return  null
     */
    public function render($complete = true) {
        if ($complete) {
        }

        if ($complete) {
        }
        return implode($this->data);
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
