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
    protected $_data = array();

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
        include $configFile;

        $values = get_defined_vars();

        $this->setConfig($values);
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
                    $this->_data[$key] = new self($value);
                } else {
                    $this->_data[$key] = $value;
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

        foreach ($this->_data as $key => $value) {
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
     * returns options as array
     *
     * @return  options as array
     */
    public function toOptions($defaults) {
        $data = array();

        foreach ($defaults as $key => $value) {
            if (isset($this->_data[$key]) && !is_null($this->_data[$key])) {
                $data[$key] = $this->_data[$key];
            } else {
                $data[$key] = $value;
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
        if (array_key_exists($name, $this->_data)) {
            if (is_array($this->_data[$name])) {
                return "sub $name";
            } else {
                return $this->_data[$name];
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
        return isset($this->_data[$name]);
    }
    // }}}
     
    // {{{ rewind()
    public function rewind() {
        reset($this->_data);
    }
    // }}}
    // {{{ current()
    public function current() {
        return current($this->_data);
    }
    // }}}
    // {{{ key()
    public function key() {
        return key($this->_data);
    }
    // }}}
    // {{{ next()
    public function next() {
        return next($this->_data);
    }
    // }}}
    // {{{ valid()
    public function valid() {
        return $this->current() !== false;
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */

