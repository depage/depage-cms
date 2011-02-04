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
    private $args;
    private $param = null;
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

        if ($this->param !== null) {
            $this->set_html_options($this->param);
        }
    }
    // }}}
    // {{{ set_child_options()
    /**
     * gives the options to child-templates
     */
    public function set_html_options($param) {
        $this->param = $param;

        foreach ($this->args as $arg) {
            // set to parent params to params if not set
            if (is_object($arg) and get_class($arg) == "html") {
                if ($arg->param === null) {
                    $arg->set_html_options($param);
                }
            } else if (is_array($arg)) {
                // set to parent params to params for subarray if not set
                foreach ($arg as $a) {
                    if (is_object($a) and get_class($a) == "html") {
                        if ($a->param === null) {
                            $a->set_html_options($param);
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

            try {
                require($this->param["template_path"] . $this->template);
            } catch (Exception $e) {
                echo($e);
                //echo("exception thrown");
            }

            $html = ob_get_contents();
            ob_end_clean();
        } else {
            $html = html::e($this->content);
        }

        if (isset($this->param["clean"])) {
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
    // {{{ include_js()
    /**
     * includes javascript files into html
     */
    public function include_js($name, $files = array()) {
        if ($this->param['env'] === "production") {
            // production environement
            $identifier = "js/{$name}_" . sha1(serialize($files)) . ".js";
            $cache = new cache(DEPAGE_CACHE_PATH, DEPAGE_BASE);
            $regenerate = false;

            if (($age = $cache->age($identifier)) !== false) {
                foreach ($files as $file) {
                    $fage = filemtime($file);
                    
                    // regenerate cache if one file is newer then the cached file
                    $regenerate = $regenerate || $age < $fage;
                }
            } else {
                //regenerate if cache file does not exist
                $regenerate = true;
            }
            if ($regenerate) {
                $src = "";

                foreach ($files as $file) {
                    $src .= file_get_contents($file);
                }

                $src = JSMin::minify($src);

                // save cache file
                $cache->put($identifier, $src);
            }

            echo("<script type=\"text/javascript\" src=\"" . $cache->geturl($identifier) . "\"></script>\n");
        } else {
            // development environement
            foreach ($files as $file) {
                echo("<script type=\"text/javascript\" src=\"$file\"></script>\n");
            }
        }
    }
    // }}}
    // {{{ include_css()
    /**
     * includes css files into html
     */
    public function include_css($name, $files = array(), $for = "") {
        if ($for != "") {
            $media = "media=\"$for\"";
        } else {
            $media = "";
        }

        if ($this->param['env'] === "production") {
            // production environement
            $identifier = "css/{$name}_" . sha1(serialize($files)) . ".css";
            $cache = new cache(DEPAGE_CACHE_PATH, DEPAGE_BASE);
            $regenerate = false;

            if (($age = $cache->age($identifier)) !== false) {
                foreach ($files as $file) {
                    $fage = filemtime($file);
                    
                    // regenerate cache if one file is newer then the cached file
                    $regenerate = $regenerate || $age < $fage;
                }
            } else {
                //regenerate if cache file does not exist
                $regenerate = true;
            }
            if ($regenerate) {
                $src = "";

                foreach ($files as $file) {
                    $src .= file_get_contents($file);
                }

                $src = CssMin::minify($src);

                // save cache file
                $cache->put($identifier, $src);
            }

            echo("<link rel=\"stylesheet\" type=\"text/css\" $media href=\"" . $cache->geturl($identifier) . "\">\n");
        } else {
            // development environement
            foreach ($files as $file) {
                echo("<link rel=\"stylesheet\" type=\"text/css\" $media href=\"$file\">\n");
            }
        }
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
    static function t($text = "", $linebreaks = false) {
        if ($linebreaks) {
            $lines = explode("\n", $text);
            foreach ($lines as $line) {
                html::t($line);
                echo("<br>");
            }
        } else {
            echo(htmlspecialchars($text));
        }
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

    // {{{ markdown()
    /**
     * outputs html by parsing markdown syntax
     *
     * @param   $param (string) text to parse
     * @return  void
     */
    static function markdown($param) {
        require_once('custom_markdown.php');

        echo(Markdown(htmlspecialchars($param)));
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
