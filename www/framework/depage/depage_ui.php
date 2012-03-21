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
            'subdomain_to_locale' => array(
                'en' => 'en_US',
            ),
        ),
        'urlHasLocale' => false,
    );
    protected $options = array();
    // }}}

    protected $urlpath = null;
    public $locale = null;

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

        // strip locale
        if ($this->options->urlHasLocale) {
            if (strlen($dp_params[0]) == 2) {
                $dp_lang = array_shift($dp_params);
                $this->_setLanguage($this->options->lang->subdomain_to_locale->$dp_lang);
            } else {
                $dp_lang = "";
                $this->_setLanguage();
            }
        } else {
            $this->_setLanguage();
            $dp_lang = Locale::getPrimaryLanguage($this->locale);
        }

        // save path (without localization)
        $this->urlpath = implode($dp_params, "/");
        if ($this->urlpath != "") {
            $this->urlpath .= "/";
        }

        if ($parent == "" && Locale::getPrimaryLanguage($this->locale) != $dp_lang) {
            // redirect to page with lang-identifier if is not set correctly, but only if it is not a subhandler
            depage::redirect(html::link($this->urlpath, Locale::getPrimaryLanguage($this->locale), "auto"));
        }

        // first is function
        $dp_func = array_shift($dp_params);
        $dp_func = str_replace("-", "_", $dp_func);

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
                    $handler->locale = $this->locale;
                    if ($this->options->urlHasLocale) {
                        $handler->run($dp_lang . "/" . $name . "/");
                    } else {
                        $handler->run($name . "/");
                    }

                    return;
                }
            }
        }

        try {
            $this->_init();

            if ($dp_func == "") {
                // show index page
                $content = $this->_index();
            } else if (is_callable(array($this, $dp_func))) {
                // call function
                $content = call_user_func_array(array($this, $dp_func), $dp_params);
            } else {
                // show error for notfound
                $content = $this->_notfound($this->urlpath);
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
            $content = $this->_error($error, $this->options->env);
        }

        $this->_sendHeaders($content);
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

    // {{{ _sendHeaders
    /**
     * sends out headers
     */
    protected function _sendHeaders($content) {
        if (is_object($content)) {
            if (isset($content->content_type) && isset($content->charset)) {
                header("Content-type: {$content->content_type}; charset={$content->charset}");
            } else if (isset($content->content_type)) {
                header("Content-type: $content->content_type");
            }
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
    
    // {{{ _index
    /**
     * default function to call if no function is given in handler
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    public function _index() {
    }
    // }}}
    // {{{ _notfound
    /**
     * default function to call if no function is given in handler
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    public function _notfound($function = "") {
        header('HTTP/1.1 404 Not Found');
    }
    // }}}
    // {{{ _error
    /**
     * automatically loads classes from the framework or the private modules
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    public function _error($error, $env) {
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

    // {{{ _setLanguage
    /**
     * set language and prepare gettext functionality
     * by default language is infered by HTTP_ACCEPT_LANGUAGE
     * overwrite this method to change this
     */
    protected function _setLanguage($locale = null) {
        if (defined("DEPAGE_LANG")) {
            return;
        }
        define("DEPAGE_URL_HAS_LOCALE", $this->options->urlHasLocale);

        $availableLocales = array_keys($this->_getAvailableLocales());

        if (!in_array($locale, $availableLocales)) {
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $browserLocales = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);    
            } else {
                $browserLocales = array();
            }

            foreach ($browserLocales as $lang) {
                list($lang) = explode(';', $lang);

                if (strlen($lang) == 2) {
                    // this is a hack when Locale::lookup does not return a valid value
                    // for simple locales like "de", "fr" or "en"
                    foreach ($availableLocales as $fallback) {
                        if (Locale::getPrimaryLanguage($fallback) == $lang) {
                            $locale = $fallback;

                            break;
                        }
                    }
                } else {
                    $locale = Locale::lookup($availableLocales, $lang, false, "");
                }
                
                if ($locale != "") {
                    // locale found
                    break;
                }
            }
            if ($locale == "") {
                // if not locale is found, take the first of all available locales
                $locale = $availableLocales[0];
            }
        }

        putenv('LC_ALL=' . $locale);
        setlocale(LC_ALL, $locale);

        // Specify location of translation tables
        bindtextdomain($this->options->lang->domain, "./locale");
        bind_textdomain_codeset($this->options->lang->domain, 'UTF-8'); 

        // Choose domain
        textdomain($this->options->lang->domain);

        $this->locale = $locale;
        define("DEPAGE_LANG", Locale::getPrimaryLanguage($this->locale));
    } 
    // }}}
    // {{{ _getAvailableLocales
    /**
     * gets all available locales
     */
    protected function _getAvailableLocales() {
        // @todo add available locales and descriptions automatically
        $availableLocales = array(
            "en_US" => _("english"),
            "de_DE" => _("deutsch"),
        );

        return $availableLocales;
    } 
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
