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
    public $log;

    protected $configFile = "conf/dpconf.php";
    
    // {{{ default config
    protected $defaults = array(
        'handlers' => array(
            '*' => "setup",
        ),
        'env' => "development",
    );
    protected $options = array();
    // }}}

    // {{{ constructor
    /**
     * instatiates base class
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    public function __construct($configFile = '') {
        $this->log = new log();

        set_error_handler(array($this, "handlePhpError"));
        set_exception_handler(array($this, "handleException"));

        if ($configFile != '') {
            $this->configFile = $configFile;
        }

        $this->conf = new config();

        // read config file
        if (file_exists($this->configFile)) {
            $this->conf->readConfig($this->configFile);
        }

        $this->options = $this->conf->toOptions($this->defaults);

        $this->log = new log($this->conf->log);
    }
    // }}}
    
    // {{{ autoload
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
        $php_file = "";

        $file = "$class.php";

        if ($pos = strrpos($class, "_")) {
            $module = substr($class, 0, $pos);
        } else {
            $module = $class;
        }
        
        //searching for class in global modules
        if (file_exists("$fm_path/$module/$file")) {
            $php_file = "$fm_path/$module/$file";

        //searching for class in local modules
        } elseif (file_exists("$dp_path/modules/$module/$file")) {
            $php_file = "$dp_path/modules/$module/$file";
        }

        if ($php_file != "") {
            require_once($php_file);
        } else {
            trigger_error("failed to load $class");
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
    
    // {{{ handleRequest()
    /**
     * analyses request and decieds what to do
     *
     * @return  framework path
     */
    public function handleRequest($handler = "") {
        if ($handler == "") {
            // get handler based on configuration/domain/path
            $handler = $this->conf->handler;
        }

        // setup handler class
        $this->handler = new $handler();
        $this->handler->run();
    }
    // }}}
    // {{{ handlePhpError()
    /**
     * analyses request and decieds what to do
     *
     * @return  framework path
     */
    public function handlePhpError($errno, $errstr, $errfile, $errline) {
        $error = (object) array(
            'no' => $errno,
            'msg' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'backtrace' => debug_backtrace(),
        );

        $this->log->log("Error: {$error->msg} in '{$error->file}' on line {$error->line}");

            $this->handler->showError($error, $this->options['env']);
        if (is_callable($this->handler, "showError")) {
            $this->handler->showError($error, $this->options['env']);
        }

        /* Don't execute PHP internal error handler */
        return true;
    }
    
    // }}}
    // {{{ handleException()
    /**
     * analyses request and decieds what to do
     *
     * @return  framework path
     */
    public function handleException($exception) {
        $error = (object) array(
            'exception' => $exception,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'msg' => $exception->getMessage(),
            'backtrace' => debug_backtrace(),
        );

        $this->log->log("Unhandled Exception: {$error->msg} in '{$error->file}' on line {$error->line}");

        if (is_callable($this->handler, "showError")) {
            $this->handler->showError($error, $this->options['env']);
        }
    }
    
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
