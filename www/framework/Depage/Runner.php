<?php
/**
 * @file    framework/depage/depage.php
 *
 * depage main module
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
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

namespace Depage\Depage;

define("DEPAGE_FM_PATH", Runner::getDepageFrameworkPath()) ;
define("DEPAGE_PATH", Runner::getDepagePath()) ;
define("DEPAGE_CACHE_PATH", Runner::getDepageCachePath()) ;

// register autoload function
spl_autoload_register('Depage\Depage\Runner::autoload');

class Runner {
    const name = 'depage-cms';

    public $conf;
    public $log;

    protected $configFile;
    protected $handler;

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
        $this->setEncoding();

        /* @todo check include path
            ;include_path = ".:/usr/local/lib/php"
            include_path = "/usr/local/lib/php:."
         */

        $this->log = new \Depage\Log\Log();

        set_error_handler(array($this, "handlePhpError"));

        if ($configFile != '') {
            $this->configFile = $configFile;
        } else {
            $this->configFile = DEPAGE_PATH . "conf/dpconf.php";
        }

        $this->conf = new \Depage\Config\Config();

        // read config file
        if (file_exists($this->configFile)) {
            $this->conf->readConfig($this->configFile);
        }

        $this->options = $this->conf->getFromDefaults($this->defaults);

        // set default timezone from config
        date_default_timezone_set($this->options->timezone);

        //$this->log = new \Depage\Log\Log($this->options->log);
    }
    // }}}
    // {{{ destructor
    /**
     * @brief __destruct
     *
     * @return void
     **/
    public function __destruct()
    {
        session_write_close();

        $_SESSION = NULL;
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
        $php_file = "";
        $namespaces = explode("\\", $class);

        if (count($namespaces) > 1) {
            // adjust search path for namespaces
            $file = array_pop($namespaces) . ".php";

            if (strtolower($namespaces[0]) === "depage") {
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

        //searching for class in global modules with lower string filename
        } elseif (file_exists(DEPAGE_FM_PATH . strtolower($file))) {
            $php_file = DEPAGE_FM_PATH . strtolower($file);

        //searching for class in local modules with lower string filename
        } elseif (file_exists(DEPAGE_PATH . "modules/" . strtolower($file))) {
            $php_file = DEPAGE_PATH . "modules/" . strtolower($file);
        }

        //error_log("class: $class - file: $file - php_file: $php_file<br>\n");

        if ($php_file != "") {
            require_once($php_file);
        }
    }
    // }}}

    // {{{ getCliOptions()
    /**
     * gets the default options when called from cli
     *
     * @return  path
     */
    static function getCliOptions() {
        static $options;
        static $files;

        if (!isset($options)) {
            if (php_sapi_name() == 'cli') {
                $printHelp = false;
                $errorMsg = "";

                list($options, $files) = self::parseArgv();

                if (isset($options['h']) || isset($options['help'])) {
                    $printHelp = true;
                } else {
                    // get path paramater
                    if (empty($options['dp-path']) || !is_dir($options['dp-path'])) {
                        $printHelp = true;
                        $errorMsg .= "You must a set a valid path as root directory\n";
                        $errorMsg .= "    (See --dp-path)\n";
                    }

                    // conf-url paramater
                    if (empty($options['conf-url'])) {
                        $options['conf-url'] = "/";
                    }
                }
                if ($printHelp) {
                    if ($errorMsg != "") {
                        echo("ERROR:\n");
                        echo($errorMsg);
                        echo("\n");
                    }

                    echo("Usage: " . $_SERVER['argv'][0] . " <option>\n");
                    echo("\n");
                    echo("PARAMETERS:\n");
                    echo("--dp-path        path to the root directory of the current depage installation\n");
                    echo("--conf-url       url which is used to select current configuration\n");
                    echo("                 if you don't set one the default configuration will be used\n");
                    die();
                }

                define("DEPAGE_CLI_URL", $options['conf-url']) ;
                define("DEPAGE_BASE", $options['conf-url']) ;
            }
        }

        return [$options, $files];
    }
    // }}}
    // {{{ parseArgv()
    /**
     * @brief parseArgv
     *
     * @param mixed $param
     * @return void
     **/
    public static function parseArgv($argv = null)
    {
        $argv = !empty($argv) ? $argv : $_SERVER['argv'];
        array_shift($argv);

        $named = [];
        $other = [];

        for ($i = 0, $j = count($argv); $i < $j; $i++) {
            $arg = $argv[$i];

            if (substr($arg, 0, 2) == '--') {
                $pos = strpos($arg, '=');
                if ($pos !== false) {
                    $named[substr($arg, 2, $pos - 2)] = substr($arg, $pos + 1);
                } else {
                    $k = substr($arg, 2);

                    if ($i + 1 < $j && $argv[$i + 1][0] !== '-') {
                        $named[$k] = $argv[$i + 1];
                        $i++;
                    } else if (!isset($o[$k])) {
                        $named[$k] = true;
                    }
                }
            } else if (substr($arg, 0, 1) == '-') {
                foreach (str_split(substr($arg, 1)) as $k) {
                    $named[$k] = true;
                }
            } else {
                $other[] = $arg;
            }
        }

        return [
            $named,
            $other,
        ];
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
            if (php_sapi_name() == 'cli') {
                list($options, $files) = self::getCliOptions();
                $path = $options['dp-path'] . "/";
            } else {
                // http
                if (getcwd() == "") {
                    $path = dirname($_SERVER['SCRIPT_FILENAME']) . "/";
                } else {
                    $path = getcwd() . "/";
                }
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
            $path = self::getDepagePath() . "cache/";
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
        return self::name;
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

        $txt = __DIR__ . "/version.txt";

        if (!isset($version) && file_exists($txt)) {
            $version = trim(file_get_contents($txt));
        }

        return $version;
    }
    // }}}

    // {{{ redirect
    static public function redirect($url) {
        header('Location: ' . $url);
        die("Tried to redirect you to <a href=\"$url\">$url</a>");
    }
    // }}}
    // {{{ sendContent
    /**
     * sends out headers
     */
    static public function sendContent($content) {
        self::sendHeaders($content);

        echo($content);
    }
    // }}}
    // {{{ sendHeaders
    /**
     * sends out headers
     */
    static public function sendHeaders($content) {
        if (is_object($content)) {
            if (isset($content->contentType) && isset($content->charset)) {
                header("Content-type: {$content->contentType}; charset={$content->charset}");
            } else if (isset($content->contentType)) {
                header("Content-type: $content->contentType");
            }
        }
    }
    // }}}

    // {{{ setEncoding
    /**
     * sets the defaults for multibyte encodings
     *
     * @param   $encoding (string) encoding to set
     *
     * @return  null
     */
    public function setEncoding($encoding = "utf-8") {
        if (is_callable("mb_internal_encoding")) {
            mb_internal_encoding($encoding);
            mb_http_output($encoding);
        }
        if (is_callable("iconv_set_encoding") && version_compare(PHP_VERSION, '5.6.0', '<')) {
            iconv_set_encoding("internal_encoding", $encoding);
            iconv_set_encoding("input_encoding", $encoding);
            iconv_set_encoding("output_encoding", $encoding);
        }
    }
    // }}}
    // {{{ setLanguage
    /**
     * set language and prepare gettext functionality
     * by default language is infered by HTTP_ACCEPT_LANGUAGE
     * overwrite this method to change this
     */
    static public function setLanguage($textdomain, $locale = null, $availableLocales = array()) {
        if (defined("DEPAGE_LANG")) {
            return DEPAGE_LANG;
        }

        if (!is_array($availableLocales) || count($availableLocales) == 0) {
            $availableLocales = self::getAvailableLocales();
        }

        $availableLocales = array_keys($availableLocales);

        // test if locale-parameter is in available_locale
        if (empty($locale)) {
            $locale = self::localeLookup($availableLocales, $locale);
        }

        if (!$locale) {
            // test locales from browser header
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $browserLocales = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

                foreach ($browserLocales as $lang) {
                    list($lang) = explode(';', $lang);

                    if ($locale = self::localeLookup($availableLocales, $lang)) {
                        break;
                    }
                }
            }

            if ($locale == "") {
                // if not locale is found, take the first of all available locales
                $locale = $availableLocales[0];
            }
        }

        putenv('LANGUAGE=' . $locale . ".UTF-8");
        putenv('LC_ALL=' . $locale . ".UTF-8");
        setlocale(LC_ALL, $locale . ".UTF-8");

        // Specify location of translation tables
        bindtextdomain($textdomain, DEPAGE_FM_PATH . "/locale");
        bind_textdomain_codeset($textdomain, 'UTF-8');

        // Choose domain
        textdomain($textdomain);

        // set LANG and LOCALE constants
        define("DEPAGE_LOCALE", $locale);
        define("DEPAGE_LANG", \Locale::getPrimaryLanguage($locale));

        return DEPAGE_LANG;
    }
    // }}}
    // {{{ localeLookup
    static protected function localeLookup($availableLocales, $lang) {
        $locale = "";

        if (strlen($lang) == 2) {
            // this is a hack when Locale::lookup does not return a valid value
            // for simple locales like "de", "fr" or "en"
            foreach ($availableLocales as $fallback) {
                if (\Locale::getPrimaryLanguage($fallback) == $lang) {
                    $locale = $fallback;

                    break;
                }
            }
        } else if ($lang) {
            $locale = \Locale::lookup($availableLocales, $lang, false, "");
        }

        return $locale;
    }
    // }}}
    // {{{ getAvailableLocales
    /**
     * gets all available locales
     */
    static public function getAvailableLocales() {
        static $availableLocales;

        if (!$availableLocales) {
            $availableLocales = array();

            // test for locales in main path
            $dirs = glob(DEPAGE_FM_PATH . "/locale/*", GLOB_ONLYDIR);

            foreach ($dirs as $dir) {
                $locale = basename($dir);
                $availableLocales[$locale] = \Locale::getDisplayLanguage($locale, $locale);
            }

            if (count($availableLocales) == 0) {
                // have en_US as fallback
                $availableLocales['en_US'] = \Locale::getDisplayLanguage("en_US", "en_US");
            }
        }

        return $availableLocales;
    }
    // }}}

    // {{{ handleRequest()
    /**
     * analyses request and decides what to do
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
            $this->handler = $handler::_factory($this->conf);
            $this->handler->_run();
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
            $this->handler->error($error, $this->options->env);
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
            $this->handler->error($error, $this->options->env);
        }
    }

    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
