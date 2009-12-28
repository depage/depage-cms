<?php
/**
 * @file    framework/depage/depage.php
 *
 * depage main module
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

function __autoload($class) {
    depage::autoload($class);
}

class depage {
    public $version = '1.5';
    public $conf;

    protected $_configFile = "conf/dpconf.php";

    // {{{ constructor
    /**
     * instatiates base class
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    public function __construct($configFile = '') {
        if ($configFile != '') {
            $this->_configFile = $configFile;
        }

        $this->conf = new config();

        if (file_exists($this->_configFile)) {
            $this->conf->readConfig($this->_configFile);
        }
    }
    // }}}
    
    // {{{ __autoload
    /**
     * automatically loads classes from the framework or the private modules
     *
     * @param   $class (string) name of class to find the file for
     *
     * @return  null
     */
    static function autoload($class) {
        $fm_path = depage::getDepageFrameworkPath();
        $dp_path = depage::getDepagePath();

        $file = "$class.php";

        if ($pos = strrpos($class, "_")) {
            $module = substr($class, 0, $pos);
        } else {
            $module = "";
        }
        
        //searching for class in global modules
        if (file_exists("$fm_path/$module/$file")) {
            $php_file = "$fm_path/$module/$file";
        } elseif (file_exists("$fm_path/$class/$file")) {
            $php_file = "$fm_path/$class/$file";

        //searching for class in local modules
        } elseif (file_exists("$dp_path/modules/$module/$file")) {
            $php_file = "$dp_path/modules/$module/$file";
        } elseif (file_exists("$dp_path/modules/$class/$file")) {
            $php_file = "$dp_path/modules/$class/$file";
        }

        if ($php_file != "") {
            require_once($php_file);
        } else {
            echo("failed to load $class");
        }
    }
    // }}}
    // {{{ getDepagePath()
    /**
     * gets the path of the calles script
     *
     * @return  path
     */
    static function getDepagePath() {
        static $path;
               
        if (!isset($path)) {
            if (getcwd() == "") {
                $path = dirname($_SERVER['SCRIPT_FILENAME']) . "/";
            } else {
                $path = getcwd();
            }
        }

        return $path;
    }
    // }}}
    // {{{ getDepageFrameworkPath()
    /**
     * gets path of depage framework
     *
     * @return  framework path
     */
    static function getDepageFrameworkPath() {
        static $path;
               
        if (!isset($path)) {
            $path = substr(dirname(__FILE__), 0, -6);
        }

        return $path;
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
