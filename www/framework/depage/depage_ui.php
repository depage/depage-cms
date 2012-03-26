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

class depage_ui {
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
        'urlHasLocale' => false,
    );
    protected $options = array();
    // }}}

    protected $urlpath = null;

    // {{{ constructor
    /**
     * automatically loads classes from the framework or the private modules
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    public function __construct($options = NULL) {
        $conf = new config($options);
        $this->options = $conf->getDefaultsFromClass($this);

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
    public function _init() {
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
        $dp_request_uri =  substr($protocol . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'], strlen(DEPAGE_BASE . $parent));

        if (strpos('?', $dp_request_uri)) {
            list($dp_request_path, $dp_query_string) = explode("?", $dp_request_uri, 2);
        } else {
            $dp_request_path = $dp_request_uri;
            $dp_query_string = '';
        }
        $dp_params = explode("/", $dp_request_path);

        // ignore trailing '/', so that params are equal with or without the trailing '/'
        if ($dp_request_path[strlen($dp_request_path) - 1] == '/') {
            array_pop($dp_params);
        }

        define("DEPAGE_URL_HAS_LOCALE", $this->options->urlHasLocale);

        // strip locale
        if (DEPAGE_URL_HAS_LOCALE) {
            if (strlen($dp_params[0]) == 2) {
                $dp_lang = array_shift($dp_params);
                depage::setLanguage($this->options->lang->domain, $dp_lang);
            } else {
                $dp_lang = "";
                depage::setLanguage($this->options->lang->domain);
            }
        } else {
            $dp_lang = "";
            depage::setLanguage($this->options->lang->domain);
        }

        // save path (without localization)
        $this->urlpath = implode($dp_params, "/");
        if ($this->urlpath != "") {
            $this->urlpath .= "/";
        }

        if ($parent == "" && DEPAGE_URL_HAS_LOCALE && DEPAGE_LANG != $dp_lang) {
            // redirect to page with lang-identifier if is not set correctly, but only if it is not a subhandler
            depage::redirect(html::link($this->urlpath, DEPAGE_LANG, "auto"));
        }

        // first is function
        $dp_func = str_replace("-", "_", array_shift($dp_params));

        if (is_callable(array($this, "_getSubHandler"))) {
            $subHandler = $this::_getSubHandler();
            foreach ($subHandler as $name => $class) {
                $subsub = explode("/", $name);
                $test = $dp_func;

                if (count($subsub) > 1) {
                    for ($i = 0; $i < count($subsub) - 1; $i++) {
                        if (isset($dp_params[$i])) {
                            $test .= "/" . $dp_params[$i];
                        }
                    }
                }
                if ($name == $test && class_exists($class, true)) {
                    // has a valid subhandler, so use this instead of $this
                    $handler = new $class($this->options);
                    if (DEPAGE_URL_HAS_LOCALE) {
                        $handler->_run($dp_lang . "/" . $name . "/");
                    } else {
                        $handler->_run($name . "/");
                    }

                    return;
                }
            }
        }

        try {
            $this->_init();

            if ($dp_func == "") {
                // show index page
                $content = $this->index();
            } else if (is_callable(array($this, $dp_func))) {
                // call function
                $content = call_user_func_array(array($this, $dp_func), $dp_params);
            } else {
                // show error for notfound
                $content = $this->notfound($this->urlpath);
            }
            $content = $this->_package($content);
        } catch (Exception $e) {
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

        depage::sendHeaders($content);

        if (is_callable(array($content, 'clean'))) {
            echo($content->clean($content));
        } else {
            echo($content);
        }

        // finishing time
        $time = microtime(true) - $time_start;
        $this->_send_time($time);
    }
    // }}}

    // {{{ _send_time
    protected function _send_time($time) {
        echo("<!-- $time sec -->");
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
        if ($env == "production") {
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
