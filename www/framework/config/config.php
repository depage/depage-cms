<?php
/**
 * @file    framework/config/config.php
 *
 * depage config module
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

class config implements Iterator {
    protected $data = array();

    // {{{ constructor
    /**
     * instatiates config class
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    public function __construct($values = array()) {
        $this->setConfig($values);
    }
    // }}}
    // {{{ readConfig
    /**
     * reads configuration from a file
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    public function readConfig($configFile) {
        $values = include $configFile;

        $urls = array_keys($values);
        $acturl = $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"];
        foreach ($urls as $url) {
            $pattern = "/" . str_replace(array("?", "*", "/"), array("(.)", "(.*)", "\/"), $url) . "/";
            if (preg_match($pattern, $acturl)) {
                // url fits into pattern
                $this->setConfig($values[$url]);
            }
        }
    }
    // }}}
    // {{{ setConfig
    /**
     * sets configuration options as array
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    public function setConfig($values) {
        if (count($values) > 0) {
            foreach ($values as $key => $value) {
                if (is_array($value)) {
                    $this->data[$key] = new self($value);
                } else {
                    $this->data[$key] = $value;
                }
            }
        }
    }
    // }}}
    // {{{ toArray
    /**
     * returns options as array
     *
     * @return  options as array
     */
    public function toArray() {
        $data = array();

        foreach ($this->data as $key => $value) {
            if (is_object($value)) {
                $data[$key] = $value->toArray();
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }
    // }}}
    // {{{ toOptions
    /**
     * returns options based on defaults as array
     *
     * @param $defaults (array) default options from class
     *
     * @return  options as array
     */
    public function toOptions($defaults) {
        $data = array();

        if (count($defaults) > 0) {
            foreach ($defaults as $key => $value) {
                if (isset($this->data[$key]) && !is_null($this->data[$key])) {
                    $data[$key] = $this->data[$key];
                } else {
                    $data[$key] = $value;
                }
            }
        }

        return $data;
    }
    // }}}
    
    // {{{ __get
    /**
     * gets a value from configuration
     *
     * @param   $name (string) name of option
     *
     * @return  null
     */
    public function __get($name) {
        if (array_key_exists($name, $this->data)) {
            if (is_array($this->data[$name])) {
                return "sub $name";
            } else {
                return $this->data[$name];
            }
        }
    }
    // }}}
    // {{{ __isset
    /**
     * checks, if value exists
     *
     * @param   $name (string) name of option
     *
     * @return  null
     */
    public function __isset($name) {
        return isset($this->data[$name]);
    }
    // }}}
     
    // {{{ rewind()
    public function rewind() {
        reset($this->data);
    }
    // }}}
    // {{{ current()
    public function current() {
        return current($this->data);
    }
    // }}}
    // {{{ key()
    public function key() {
        return key($this->data);
    }
    // }}}
    // {{{ next()
    public function next() {
        return next($this->data);
    }
    // }}}
    // {{{ valid()
    public function valid() {
        return $this->current() !== false;
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */

