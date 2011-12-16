<?php
/**
 * @file    framework/db/db_pdo.php
 *
 * depage database module
 *
 *
 * copyright (c) 2002-2010 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

class db_pdo {
    /* {{{ variables*/
    public $prefix;
    private $pdo = null;
    private $dsn;
    private $username;
    private $password;
    private $driver_options;
    /* }}} */

    /* {{{ constructor */
    /**
     * constructor for PDO object with an additional prefix-parameter in driver-options
     *
     * @param   string  dsn                 dsn for pdo-object
     * @param   string  username            username for database
     * @param   string  password            password for database
     * @param   array   $driver_options     database-driver options with additional prefix-entry
     *
     * @return  void
     */
    public function __construct($dsn, $username = '', $password = '', $driver_options = array()) {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;

        if (isset($driver_options['prefix'])) {
            $this->prefix = $driver_options['prefix'];
            unset($driver_options['prefix']);
        }
        $this->driver_options = $driver_options;
    }
    /* }}} */
    /* {{{ late_initialize */
    /**
     */
    private function late_initialize() {
        $this->pdo = new pdo($this->dsn, $this->username, $this->password, $this->driver_options);

        // set error mode to exception by default
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    /* }}} */

    /* {{{ __set */
    /**
     */
    public function __set($name, $value) {
        if (is_null($this->pdo)) {
            $this->late_initialize();
        }
        $this->$name = $value;
    }
    /* }}} */
    /* {{{ __get */
    /**
     */
    public function __get($name) {
        if (is_null($this->pdo)) {
            $this->late_initialize();
        }
        return $this->$name;
    }
    /* }}} */
    /* {{{ __call */
    /**
     */
    public function __call($name, $arguments) {
        if (is_null($this->pdo)) {
            $this->late_initialize();
        }
        return call_user_func_array(array($this->pdo, $name), $arguments);
    }
    /* }}} */
    /* {{{ __callStatic */
    /**
     */
    public static function __callStatic($name, $arguments) {
        return call_user_func_array("pdo::$name", $arguments);
    }
    /* }}} */
    
    /* {{{ dsn_parts */
    /**
     * parses dsn intro its parts
     *
     * @param   string  dsn                 dsn for pdo-object
     *
     * @return  array of options
     */
    static function parse_dsn($dsn) {
        $info = array();

        list($info['protocol'], $rest) = explode(":", $dsn, 2);

        $parts = explode(";", $rest);

        foreach ($parts as $part) {
            list($name, $value) = explode("=", $part, 2);
            $info[$name] = $value;
        }

        return $info;
    }
    /* }}} */
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
