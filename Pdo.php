<?php
/**
 * @file    framework/DB/Pdo.php
 *
 * depage database module
 *
 *
 * copyright (c) 2002-2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Db;

class Pdo
{
    /* {{{ variables*/
    public $prefix = "";
    private $pdo = null;
    private $dsn;
    private $username;
    private $password;
    private $driver_options;
    // }}}

    // {{{ constructor
    /**
     * constructor for PDO object with an additional prefix-parameter in driver-options
     *
     * @param   string  dsn                 dsn for pdo-object
     * @param   string  username            username for database
     * @param   string  password            password for database
     * @param array $driver_options database-driver options with additional prefix-entry
     *
     * @return void
     */
    public function __construct($dsn, $username = '', $password = '', $driver_options = array())
    {
        $this->dsn = $dsn . ";charset=utf8mb4";
        $this->username = $username;
        $this->password = $password;

        if (isset($driver_options['prefix'])) {
            $this->prefix = $driver_options['prefix'];
            unset($driver_options['prefix']);
        }
        $this->driver_options = $driver_options;
    }
    // }}}
    // {{{ destructor
    /**
     * removes the pdo object which closes the connection to the database
     *
     * @return  void
     */
    public function __destruct()
    {
        $this->pdo = null;
    }
    // }}}
    // {{{ lateInitialize
    /**
     */
    private function lateInitialize()
    {
        $this->pdo = new \PDO($this->dsn, $this->username, $this->password, $this->driver_options);

        // set error mode to exception by default
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // disable emulated prepares
        // @todo check why this does not work with some queries
        $this->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    }
    // }}}

    // {{{ __set
    /**
     */
    public function __set($name, $value)
    {
        if (is_null($this->pdo)) {
            $this->lateInitialize();
        }
        $this->$name = $value;
    }
    // }}}
    // {{{ __get
    /**
     */
    public function __get($name)
    {
        if (is_null($this->pdo)) {
            $this->lateInitialize();
        }

        return $this->$name;
    }
    // }}}
    // {{{ __call
    /**
     */
    public function __call($name, $arguments)
    {
        if (is_null($this->pdo)) {
            $this->lateInitialize();
        }

        return call_user_func_array(array($this->pdo, $name), $arguments);
    }
    // }}}
    // {{{ __callStatic
    /**
     */
    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array("pdo::$name", $arguments);
    }
    // }}}

    // {{{ __sleep()
    /**
     * allows Depage\Db\Pdo-object to be serialized
     */
    public function __sleep()
    {
        return array(
            'dsn',
            'username',
            'password',
            'driver_options',
            'prefix',
        );
    }
    // }}}
    // {{{ __wakeup()
    /**
     * allows Depage\Db\Pdo-object to be unserialized
     *
     * We don't need to initialize the connection because we are already initializing them late.
     */
    public function __wakeup()
    {
    }
    // }}}

    // {{{ dsn_parts
    /**
     * parses dsn intro its parts
     *
     * @param   string  dsn                 dsn for pdo-object
     *
     * @return array of options
     */
    public static function parse_dsn($dsn)
    {
        $info = array();

        list($info['protocol'], $rest) = explode(":", $dsn, 2);

        $parts = explode(";", $rest);

        foreach ($parts as $part) {
            list($name, $value) = explode("=", $part, 2);
            $info[$name] = $value;
        }

        return $info;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
