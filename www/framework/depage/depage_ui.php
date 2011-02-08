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
    protected $defaults = array(
    );
    protected $options = array();
    // }}}
    
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
        $this->options = $conf->getFromDefaults($this->defaults);

        $this->log = new log();
    }
    // }}}
    
    // {{{ run
    /**
     * default function to call if no function is given in handler
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    public function run() {
        $this->set_language();

        // get depage specific query string
        // @todo use parseurl?
        $dp_request_uri =  substr("http://" . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'], strlen(DEPAGE_BASE));

        if (strpos('?', $dp_request_uri)) {
            list($dp_request_path, $dp_query_string) = explode("?", $dp_request_uri, 2);
        } else {
            $dp_request_path = $dp_request_uri;
            $dp_query_string = '';
        }
        $dp_request_path = str_replace("-", "_", $dp_request_path);
        $dp_params = explode("/", $dp_request_path);
        
        $dp_func = array_shift($dp_params);

        try {
            if ($dp_func == "") {
                // show index page
                $content = $this->index();
            } else if (is_callable(array($this, $dp_func))) {
                // call function
                $content = call_user_func_array(array($this, $dp_func), $dp_params);
            } else {
                // show error for notfound
                $content = $this->notfound();
            }
        } catch (Exception $e) {
            $error = (object) array(
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'msg' => $e->getMessage(),
                'backtrace' => debug_backtrace(),
            );
            $content = $this->error($error, $this->options->env);
        }

        $content = $this->package($content);

        $this->send_headers($content);
        echo($content);
    }
    // }}}
    // {{{ send_headers
    /**
     * sends out headers
     */
    protected function send_headers($content) {
        if (is_object($content)) {
            if (isset($content->content_type) && isset($content->charset)) {
                header("Content-type: {$content->content_type}; charset={$content->charset}");
            } else if ($isset($content->content_type)) {
                header("Content-type: $content->content_type");
            }
        }
    }
    // }}}
    // {{{ package
    /**
     * default function to call if no function is given in handler
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    protected function package($output) {
        return $output;
    }
    // }}}
    // {{{ redirect
    public function redirect($url) {
        header('Location: ' . $url);
        die( "Tried to redirect you to " . $url);
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
    public function notfound() {
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
            $h .= "<p>error in production environement</p>";
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

    // {{{ set_language
    /**
     * set language and prepare gettext functionality
     * by default language is infered by HTTP_ACCEPT_LANGUAGE
     * overwrite this method to change this
     */
    protected function set_language($locale = null) {
        if (empty($locale)) 
            $locale = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']); 

        $this->log->log("set_language: setting locale to $locale");

        putenv('LC_ALL=' . $locale);
        setlocale(LC_ALL, $locale);

        // Specify location of translation tables
        bindtextdomain($this->options->lang->domain, "./locale");
        bind_textdomain_codeset($this->options->lang->domain, 'UTF-8'); 

        // Choose domain
        textdomain($this->options->lang->domain);
    } 
    // }}}
}
/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
