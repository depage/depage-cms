<?php
/**
 * @file    framework/html/html.php
 *
 * depage html module
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 *
 * thanks for ideas from:
 * http://stackoverflow.com/questions/62617/whats-the-best-way-to-separate-php-code-and-html
 */

class html {
    public $param;

    private $args;
    private $template;

    // {{{ __construct()
    /**
     * initializes html template
     *
     * @param $template (string) file path to template file
     * @param $args (array) arguments which can be used in template file
     * @param $param (array) params how to use template
     */
    public function __construct($template, $args = array(), $param = null) {
        if (is_array($template)) {
            $this->template = null;
            $this->args = $template;
            $this->param = $param;
        } else {
            $this->template = $template;
            $this->args = $args;
            $this->param = $param;
        }

        foreach ($this->args as $arg) {
            // set to parent params to params if not set
            if (is_object($arg) and get_class($arg) == "html") {
                if ($arg->param === null) {
                    $arg->param = &$this->param;
                }
            } else if (is_array($arg)) {
                // set to parent params to params for subarray if not set
                foreach ($arg as $a) {
                    if (is_object($a) and get_class($a) == "html") {
                        if ($a->param === null) {
                            $a->param = &$this->param;
                        }
                    }
                }
            }
        }
    }
    // }}}
    // {{{ __get()
    /**
     * returns parameter from args array
     *
     * @param $name (string) name of parameter
     *
     * @return value of parameter
     */
    public function __get($name) {
        if (isset($this->args[$name])) {
            return $this->args[$name];
        } else {
            // if parameter is not set
            return null;
        }
    }
    // }}}
    // {{{ __toString()
    /**
     * renders template 
     *
     * @return
     */
    public function __toString() {
        if ($this->template !== null) {
            ob_start();

            require($this->param["template_path"] . $this->template);

            $html = ob_get_contents();
            ob_end_clean();
        } else {
            $html = html::e($this->content);
        }

        if ($this->param["clean"] == "tidy") {
            // clean html up
            $tidy = new tidy();
            $html = $tidy->repairString($html, array(
                'indent' => false,
                'output-xhtml' => false,
                'wrap' => 0,
                'doctype' => "html5",
            ));
        } else if ($this->param["clean"] == "space") {
            $html_lines = explode("\n", $html);
            $html = "";

            foreach ($html_lines as $i => $line) {
                $line = trim($line);
                if ($line != "") {
                    $html .= trim($line) . "\n";
                }
            }
        }

        return $html;
    }
    // }}}
    
    // {{{ base()
    /**
     * outputs base for refs
     */
    static function base() {
        echo(DEPAGE_BASE);
    }
    // }}}
    // {{{ t()
    /**
     * outputs escaped text for use in html and html-attributes
     *
     * @param   $text (string) text to escape
     *
     * @return  void
     */
    static function t($text = "") {
        echo(htmlspecialchars($text));
    }
    // }}}
    // {{{ e()
    /**
     * outputs all given parameters
     *
     * @param   $ (string) text to escape
     *
     * @return  void
     */
    static function e($param = "") {
        if (is_array($param)) {
            foreach ($param as $p) {
                echo($p);
            }
        } else {
            echo($param);
        }
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
