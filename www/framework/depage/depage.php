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

/**
 * @mainpage
 *
 * @intro
 * @image html icon_depage-cms.png
 * @htmlinclude main-intro.html
 * @endintro
 *
 * @htmlinclude main-extended.html
 **/

define("DEPAGE_FM_PATH", depage::getDepageFrameworkPath()) ;
define("DEPAGE_PATH", depage::getDepagePath()) ;
define("DEPAGE_CACHE_PATH", depage::getDepageCachePath()) ;

// register autoload function
spl_autoload_register("depage::autoload");

class depage {
    const name = 'depage-cms';

    public $conf;
    public $log;

    protected $configFile = "conf/dpconf.php";
    
    // {{{ default config
    protected $defaults = array(
        'handlers' => array(
            '*' => "setup",
        ),
        'env' => "development",
        'timezone' => "UST",
    );
    protected $options;
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

        /* @todo check include path
            ;include_path = ".:/usr/local/lib/php"
            include_path = "/usr/local/lib/php:."

            If you use REST techniques - so that POST requests do work and then send the browser a 303 redirect to GET to view the results, you quickly achieve two things:
         */
        
        $this->log = new log();

        set_error_handler(array($this, "handlePhpError"));

        if ($configFile != '') {
            $this->configFile = $configFile;
        }

        $this->conf = new config();

        // read config file
        if (file_exists($this->configFile)) {
            $this->conf->readConfig($this->configFile);
        }

        $this->options = $this->conf->getFromDefaults($this->defaults);

        // set default timezone from config
        date_default_timezone_set($this->options->timezone);

        //$this->log = new log($this->options->log);
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
        $class = strtolower($class);
        $php_file = "";
        $namespaces = explode("\\", $class);

        if (count($namespaces) > 1) {
            // adjust search path for namespaces
            $file = array_pop($namespaces) . ".php";

            if ($namespaces[0] === "depage") {
                // local depage-namespace -> remove it from searchpath
                array_shift($namespaces);
            }

            $file = implode("/", $namespaces) . "/$file";
        } else {
            // no namespace
            $file = "$class.php";

            if ($pos = strpos($class, "_")) {
                $file = substr($class, 0, $pos) . "/" . $file;
            } else {
                $file = $class . "/" . $file;
            }
        }
            
        //searching for class in global modules
        if (file_exists(DEPAGE_FM_PATH . $file)) {
            $php_file = DEPAGE_FM_PATH . $file;

        //searching for class in local modules
        } elseif (file_exists(DEPAGE_PATH . "modules/" . $file)) {
            $php_file = DEPAGE_PATH . "modules/" . $file;
        }
        
	//echo("class: $class - file: $file - php_file: $php_file<br>");

        if ($php_file != "") {
            require_once($php_file);
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
                $path = getcwd() . "/";
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
            $path = substr(__DIR__, 0, -6);
        }

        return $path;
    }
    // }}}
    // {{{ getDepageCachePath()
    /**
     * gets path of depage framework
     *
     * @return  framework path
     */
    static function getDepageCachePath() {
        static $path;
               
        if (!isset($path)) {
            $path = depage::getDepagePath() . "/cache/";
        }

        return $path;
    }
    // }}}
    // {{{ getName()
    /**
     * gets name of depage framework
     *
     * @return  name
     */
    static function getName() {
        return depage::name;
    }
    // }}}
    // {{{ getVersion()
    /**
     * gets version number of depage framework
     *
     * @return  version number
     */
    static function getVersion() {
        static $version;

        if (!isset($version)) {
            $version = file_get_contents(__DIR__ . "/version.txt");
        }

        return $version;
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

        // enable output-compression
        ini_set("zlib.output_compression", "On");

        // setup handler class
        if (class_exists($handler, true)) {
            $this->handler = new $handler($this->conf);
            $this->handler->run();
        } else {
            // no config -> setup/config?
            die("This url is not configured");
        }
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

        $this->log->log("Error{$error->no}: {$error->msg} in '{$error->file}' on line {$error->line}");

        if (isset($this->handler) && is_callable($this->handler, "error")) {
            $this->handler->error($error, $this->options['env']);
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

        if (isset($this->handler) && is_callable($this->handler, "error")) {
            $this->handler->error($error, $this->options['env']);
        }
    }
    
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
