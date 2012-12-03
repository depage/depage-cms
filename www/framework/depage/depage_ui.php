<?php
/**
 * @file    framework/depage/depage_ui.php
 *
 * depage main module
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

abstract class depage_ui {
    // {{{ default config
    public $defaults = array(
        'auth' => null,
        'db' => array(
            'dsn' => "mysql:dbname=;host=localhost",
            'user' => "root",
            'password' => "",
            'prefix' => "tt",
        ),
        'env' => "development",
        'lang' => array(
            'domain' => 'messages',
        ),
        'urlHasLocale' => false
    );
    protected $options = array();
    // }}}

    protected $urlPath = null;
    
    protected $urlSubArgs = array();

    // {{{ constructor
    /**
     * automatically loads classes from the framework or the private modules
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    protected function __construct($options = NULL) {
        $conf = new config($options);
        $this->options = $conf->getDefaultsFromClass($this);
        
        if (!defined("DEPAGE_URL_HAS_LOCALE")) {
            define("DEPAGE_URL_HAS_LOCALE", $this->options->urlHasLocale);
        }
        
        $this->log = new log(array(
            'file' => DEPAGE_PATH . "/logs/" . str_replace("\\", "_", get_class($this)) . ".log",
        ));
    }
    // }}}
    // {{{ _init()
    /**
     * initialize needed objects like pdo or auth-objects
     *
     * @return  null
     */
    public function _init(Array $importVariables = array()) {
        foreach ($importVariables as $name => $value) {
            $this->$name = $value;
        }
    }
    // }}}
    
    // {{{ _run
    /**
     * default function to call if no function is given in handler
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     *
     * @todo split this function apart for better readability
     */
    public function _run($parent = "") {
        // starting time
        $time_start = microtime(true);

        // set protocol
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") {
            $protocol = "https://";
        } else {
            $protocol = "http://";
        }

        // get depage specific query string
        // @todo use parseurl?
        $dp_request_uri = (string) substr($protocol . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'], strlen(DEPAGE_BASE . $parent));

        // remove get parameters
        if (strpos($dp_request_uri, '?') !== false) {
            list($dp_request_path, $dp_query_string) = @explode("?", $dp_request_uri, 2);
        } else {
            $dp_request_path = $dp_request_uri;
            $dp_query_string = '';
        }

        // get parameters from url
        list($dp_lang, $dp_params, $dp_subhandler, $dp_parent) = $this->getParams($dp_request_path);

        // set language
        depage::setLanguage($this->options->lang->domain, $dp_lang);
        
        // save path (without localization)
        if ($parent == "") {
            if (DEPAGE_URL_HAS_LOCALE && $dp_lang != '') {
                $this->urlPath = (string) substr($dp_request_path, 3);
            } else {
                $this->urlPath = $dp_request_path;
            }
        }
        
        if ($parent == "" && DEPAGE_URL_HAS_LOCALE && DEPAGE_LANG != $dp_lang) {
            // redirect to page with lang-identifier if is not set correctly, but only if it is not a subhandler
            depage::redirect(html::link($this->urlPath, "auto", DEPAGE_LANG));
        }
        
        if ($dp_subhandler != "") {
            // forward handling of request to a subhandler
            $handler = $dp_subhandler::_factory($this->options);
            $handler->urlSubArgs = $this->urlSubArgs;
            $handler->urlPath = $this->urlPath;

            if (DEPAGE_URL_HAS_LOCALE) {
                return $handler->_run($dp_lang . "/" . $dp_parent . "/");
            } else {
                return $handler->_run($dp_parent . "/");
            }
        }
        
        // first paramater is function
        $dp_func = str_replace("-", "_", array_shift($dp_params));
        
        try {
            $this->_init();
            if ($dp_func == "") {
                // show index page
                $content = $this->index();
            } else if ($dp_func[0] != "_" && is_callable(array($this, $dp_func))) {
                // call function
                $content = call_user_func_array(array($this, $dp_func), $dp_params);
            } else {
                // show error for notfound
                $content = $this->notfound($this->urlPath);
            }
            $content = $this->_package($content);
        } catch (Exception $e) {
            // show error page for exceptions
            $error = (object) array(
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'msg' => $e->getMessage(),
                'backtrace' => debug_backtrace(),
            );
            $content = $this->error($error, $this->options->env);
            $content = $this->_package($content);
        }
        
        depage::sendContent($content);
        
        // finishing time
        $time = microtime(true) - $time_start;
        $this->_send_time($time);
    }
    // }}}
    // {{{ _factory
    /**
     * gets a new instance for the current class
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    static public function _factory($options) {
        $class = get_called_class();

        return new $class($options);
    }
    // }}}
    // {{{ _factoryAndInit
    /**
     * gets a new instance for the current class and
     * calls the _init method on it
     *
     * @param   $options (array) named options for base class
     * @param   $importVariables (array) named options for base class
     *
     * @return  null
     */
    static public function _factoryAndInit($options, $importVariables) {
        $instance = self::_factory($options);
        $instance->_init($importVariables);

        return $instance;
    }
    // }}}
    
    // getParams{{{
    private function getParams($dp_request_path){
        $dp_lang = "";
        $dp_parent = "";
        $dp_params = explode("/", $dp_request_path);
        $dp_subhandler = "";

        // strip locale, if it is part of url
        if (DEPAGE_URL_HAS_LOCALE && strlen($dp_params[0]) == 2) {
            $dp_lang = array_shift($dp_params);
        }

        $dp_request_path = implode("/", $dp_params);

        // test for subhandlers
        $subhandlerMethod = get_class($this) . "::_getSubHandler";
        if (is_callable($subhandlerMethod)) {
            $subHandler = call_user_func($subhandlerMethod);
            $simplepatterns = \config::getSimplePatterns();
            foreach ($subHandler as $name => $class) {
                $pattern = "/^(" . str_replace(array_keys($simplepatterns), array_values($simplepatterns), $name) . ")/";
                if (preg_match($pattern, $dp_request_path, $matches)) {
                    $dp_parent = $matches[1];
                    $i = 2;
                    $this->urlSubArgs = array();
                    // test for non-empty subArgs
                    while (isset($matches[$i]) && !empty($matches[$i])) {
                        $this->urlSubArgs[] = $matches[$i];
                        $i++;
                    }
                    for ($i = 0; $i < count($this->urlSubArgs); $i++) {
                        $this->urlSubArgs[$i] = rawurldecode($this->urlSubArgs[$i]);
                    }
                    if (count($matches)){
                        array_splice($dp_params, 1, count($this->urlSubArgs));
                    }
                    $dp_subhandler = $class;
                }
            }
        }
        
        // ignore trailing '/', so that params are equal with or without the trailing '/'
        if (end($dp_params) === "") {
            array_pop($dp_params);
        }

        return array($dp_lang, $dp_params, $dp_subhandler, $dp_parent);
    }
    //}}}
    
    // {{{ _send_time
    protected function _send_time($time) {
        if (!(isset($_POST['ajax']) && $_POST['ajax'] == true) && $this->options->env == "development") {
            echo("<!-- $time sec -->");
        }
    }
    // }}}

    // {{{ _package
    /**
     * default function to call if no function is given in handler
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    protected function _package($output) {
        return $output;
    }
    // }}}
    
    // {{{ index
    /**
     * default function to call if no function is given in handler
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    public function index() {
    }
    // }}}
    // {{{ notfound
    /**
     * default function to call if no function is given in handler
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    public function notfound($function = "") {
        header('HTTP/1.1 404 Not Found');
    }
    // }}}
    // {{{ error
    /**
     * automatically loads classes from the framework or the private modules
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    public function error($error, $env) {
        $h = "";

        $h .= "<h1>Error</h1>";
        if (is_string($error)) {
            $h .= "<p>{$error}</p>";
        } elseif ($env == "production") {
            $h .= "<p>{$error->msg}</p>";
        } elseif ($env == "development") {
            $h .= "<p>{$error->msg}";
                if (isset($error->no)) {
                    $h .= " ({$error->no})";
                }
            $h .= "</p>";
            $h .= "<p>in '{$error->file}' on line {$error->line} </p>";

            $h .= "<ol>";
                foreach ($error->backtrace as $call) {
                    $h .= "<li>";
                        $h .= "<details>";
                            $h .= "<dt>function: {$call['class']}{$call['type']}{$call['function']}</dt>";
                            $h .= "<dd>in {$call['file']} on line {$call['line']}</dd>";
                        $h .= "</details>";
                    $h .= "</li>";
                }
            $h .= "</ol>";
        }

        return $h;
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
