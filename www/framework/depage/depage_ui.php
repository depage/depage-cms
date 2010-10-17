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
        $this->options = $conf->toOptions($this->defaults);
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
        // get depage specific query string
        $dp_request_uri =  substr("http://" . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'], strlen(DEPAGE_BASE));

        list($dp_request_path, $dp_query_string) = explode("?", $dp_request_uri, 2);
        $dp_params = explode("/", $dp_request_path);
        $dp_func = array_shift($dp_params);

        if ($dp_func == "") {
            // show index page
            $this->index();
        } else if (is_callable(array($this, $dp_func))) {
            // call function
            $this->$dp_func($dp_params);
        } else {
            // show error for notfound
            $this->notfound();
        }
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
    // {{{ showError
    /**
     * automatically loads classes from the framework or the private modules
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    public function showError($error, $env) {
        echo("<h1>Error</h1>");
        if ($env == "production") {
            echo("<p>error in production environement</p>");
        } elseif ($env == "development") {
            echo("<p>{$error->no}: {$error->msg}</p>");
            echo("<p>in '{$error->file}' on line {$error->line} </p>");

            echo("<ol>");
                foreach ($error->backtrace as $call) {
                    echo("<li>");
                        echo("<details>");
                            echo("<dt>function: {$call['class']}{$call['type']}{$call['function']}</dt>");
                            echo("<dd>in {$call['file']} on line {$call['line']}</dd>");
                        echo("</details>");
                    echo("</li>");
                }
            echo("</ol>");
        }
    }
    // }}}
}
/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
