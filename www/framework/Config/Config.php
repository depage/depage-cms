<?php
/**
 * @file    framework/Config/Config.php
 *
 * depage config module
 *
 *
 * copyright (c) 2002-2018 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Config;

class Config implements \Iterator, \ArrayAccess
{
    protected $data = array();

    // {{{ constructor
    /**
     * instatiates config class
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    public function __construct($defaults = []) {
        if (!empty($defaults)) {
            $this->setConfig($defaults);
        }
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

        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = "";
            $_SERVER['REQUEST_URI'] = "";
        }

        // test url against settings
        if (php_sapi_name() == 'cli') {
            $acturl = DEPAGE_CLI_URL;
        } else {
            $acturl = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }

        $this->setConfigForUrl($values, $acturl);
    }
    // }}}
    // {{{ setConfigForUrl()
    /**
     * @brief setConfigForUrl
     *
     * @param mixed $config, $url
     * @return void
     **/
    public function setConfigForUrl($values, $currentUrl)
    {
        $depage_base = "";
        $urls = array_keys($values);

        // sort that shorter urls with same beginning are tested first for a match
        // @todo change sort order to have the inherited always at the end
        usort($urls, function($a, $b) {
            $la = strlen($a);
            $lb = strlen($b);

            if ($la == $lb) {
                return 0;
            }
            return $la > $lb ? 1 : -1;
        });

        // remove url-parameters before matching
        list($currentUrl) = explode("?", $currentUrl, 2);

        $simplepatterns = self::getSimplePatterns();
        foreach ($urls as $url) {
            $pattern = "/(" . str_replace(array_keys($simplepatterns), array_values($simplepatterns), $url) . ")/";
            if (preg_match($pattern, $currentUrl, $matches)) {
                // url fits into pattern

                if (isset($values[$url]['base']) && $values[$url]['base'] == "inherit") {
                    // don't set the base when it is set to "inherit"
                } else {
                    $depage_base = $matches[0];
                }

                $this->setConfig($values[$url]);
            }
        }

        // set protocol
        if (($_SERVER['HTTPS'] ?? "off") != "off") {
            $protocol = "https://";
        } elseif (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? "") == "https") {
            $protocol = "https://";
        } else {
            $protocol = "http://";
        }

        // set base-url
        if (!defined("DEPAGE_BASE")) {
            if (!isset($depage_base[0])) {
                define("DEPAGE_BASE", "");
            } else if ($depage_base[0] != "*") {
                define("DEPAGE_BASE", $protocol . $depage_base);
            } else {
                define("DEPAGE_BASE", $protocol . $_SERVER['HTTP_HOST']);
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
        if (is_array($values) || $values instanceof \Iterator) {
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
        $data = [];

        foreach ($this->data as $key => $value) {
            if ($value instanceof self) {
                $data[$key] = $value->toArray();
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }
    // }}}
    // {{{ getFromDefaults
    /**
     * returns options based on defaults as array
     *
     * @param $defaults (array) default options from class
     *
     * @return  options as array
     */
    public function getFromDefaults($defaults) {
        $data = array();

        if (is_array($defaults) || $values instanceof \Iterator) {
            foreach ($defaults as $key => $value) {
                if (isset($this->data[$key]) && !is_null($this->data[$key])) {
                    $data[$key] = $this->data[$key];
                } else {
                    $data[$key] = $value;
                }
                if (is_array($data[$key])) {
                    $data[$key] = new self($data[$key]);
                }
            }
        }
        return new self($data);
    }
    // }}}
    // {{{ getDefaultsFromClass
    /**
     * returns options based on defaults as array
     *
     * @param $object (object) object to get defaults from
     *
     * @return  options as object
     */
    public function getDefaultsFromClass($object) {
        $defaults = [];

        $class = get_class($object);
        while ($class) {
            // go through class hierarchy for defaults and merge with parent's defaults
            $class_vars = get_class_vars($class);
            $defaults = array_merge($class_vars['defaults'], $defaults);

            $class = get_parent_class($class);
        }

        return $this->getFromDefaults($defaults);
    }
    // }}}

    // {{{ getSimplePatterns
    /**
     * returns array of simple patterns
     *
     * @return  array of replacements
     */
    static public function getSimplePatterns() {
        return array(
            "." => "\.",        // dot
            "/" => "\/",        // slash
            "?" => "([^\/])",    // single character
            "**" => "(.+)?",    // multiple characters including slash
            "*" => "([^\/]*)?",  // multiple character without slash
        );
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
    // {{{ __set
    /**
     * gets a value from configuration
     *
     * @param   $name (string) name of option
     *
     * @return  null
     */
    public function __set($name, $value) {
        // make readonly
        if (php_sapi_name() != 'cli') {
            error_log("cannot set '$name': config objects are read only");
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
    public function rewind():void
    {
        reset($this->data);
    }
    // }}}
    // {{{ current()
    public function current():mixed
    {
        return current($this->data);
    }
    // }}}
    // {{{ key()
    public function key():int|string|null
    {
        return key($this->data);
    }
    // }}}
    // {{{ next()
    public function next():void
    {
        next($this->data);
    }
    // }}}
    // {{{ valid()
    public function valid():bool
    {
        return $this->current() !== false;
    }
    // }}}

    // {{{ offsetSet()
    public function offsetSet($offset, $value):void
    {
        // make readonly
        if (php_sapi_name() != 'cli') {
            error_log("cannot set '$offset': config objects are read only");
        }
    }
    // }}}
    // {{{ offsetExists()
    public function offsetExists($offset):bool
    {
        return isset($this->data[$offset]);
    }
    // }}}
    // {{{ offsetUnset()
    public function offsetUnset($offset):void
    {
        // make readonly
        if (php_sapi_name() != 'cli') {
            error_log("cannot unset '$offset': config objects are read only");
        }
    }
    // }}}
    // {{{ offsetGet()
    public function offsetGet($offset):mixed
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
