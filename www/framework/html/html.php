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
    public $args;
    private $param = null;
    private $template;

    public $content_type = "text/html";
    public $charset = "UTF-8";

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
            $this->setHtmlOptions($this->param);
        }
    }
    // }}}
    // {{{ setHtmlOptions()
    /**
     * gives the options to child-templates
     */
    public function setHtmlOptions($param) {
        $this->param = $param;

        foreach ($this->args as $arg) {
            // set to parent params to params if not set
            if (is_object($arg) and get_class($arg) == "html") {
                if ($arg->param === null) {
                    $arg->setHtmlOptions($param);
                }
            } else if (is_array($arg)) {
                // set to parent params to params for subarray if not set
                foreach ($arg as $a) {
                    if (is_object($a) and get_class($a) == "html") {
                        if ($a->param === null) {
                            $a->setHtmlOptions($param);
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
        $html = "";

        ob_start();
        if ($this->template !== null) {
            //require($this->param["template_path"] . $this->template);
            if(!@include($this->param["template_path"] . $this->template)) {
                echo("<h1>Template error</h1>");
                echo("<p>Could not load template '$this->template'<p>");
            }
        } else {
            html::e($this->content);
        }

        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }
    // }}}
    // {{{ clean()
    /**
     * clean html output
     *
     * @return output
     */
    public function clean($html) {
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

            $dont_clean_tags = array("pre", "textarea");
            $dont_clean = 0;

            foreach ($html_lines as $i => $line) {
                // check for opening tags
                if ($m = preg_match_all("/<" . implode("|<", $dont_clean_tags) . "/", $line, $matches)) {
                    $dont_clean += $m;
                }

                if ($dont_clean > 0) {
                    // just copy the whole line
                    $html .= $line . "\n";
                } else {
                    // trim line
                    $line = trim($line); 
                    // replace multiple spaces with only one space
                    $line = preg_replace("/( )+/", " ", $line);
                    // throw away empty lines
                    if ($line != "") {
                        $html .= $line . "\n";
                    }
                }

                // check for closing tags
                if ($m = preg_match_all("/<\/" . implode("|<\/", $dont_clean_tags) . "/", $line, $matches)) {
                    $dont_clean -= $m;
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
        // get file-dependencies that are required from javascript header
        $files = $this->include_js_get_dependencies($files);
        
        if ($this->param['env'] === "production") {
            // production environement
            $identifier = "{$name}_" . sha1(serialize($files)) . ".js";
            
            // get cache instance
            $cache = depage\cache\cache::factory("js");

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
                $cache->setFile($identifier, $src, true);
            }

            echo("<script src=\"" . $cache->getUrl($identifier) . "\"></script>\n");
        } else {
            // development environement
            foreach ($files as $file) {
                echo("<script src=\"$file\"></script>\n");
            }
        }
    }
    // }}}
    // {{{ include_js_get_dependecies()
    /**
     * gets dependencies from filename
     */
    protected function include_js_get_dependencies($files = array()) {
        $all_files = array();
        $max_test_lines = 10; // maximum lines to test without a match

        foreach($files as $file) {
            if (strpos($file, "http://") !== 0 && file_exists($file)) {
                $fh = @fopen($file, "r");
                $n = 0;

                if ($fh) {
                    while (($line = fgets($fh)) !== false && $n <= $max_test_lines) {
                        if (preg_match("/@require (.*)/", $line, $matches)) {
                            // add required files to included files
                            if (!in_array($matches[1], $all_files)) {
                                // check for subdependecies
                                $sub_files = $this->include_js_get_dependencies(array($matches[1]));
                                $all_files = array_merge($all_files, $sub_files);

                                $all_files[] = $matches[1];
                            }
                        } else {
                            $n++;
                        }
                    }
                    fclose($fh);
                }
                
                // add actual file to uncluded files
                $all_files[] = $file;
            } else {
                $all_files[] = $file;
            }
        }
        // only include files once
        $all_files = array_unique($all_files);

        // @todo added version to libaries like jquery with min and/or max version to include

        return $all_files;
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
            $identifier = "{$name}_" . sha1(serialize($files)) . ".css";
            
            // get cache instance
            $cache = depage\cache\cache::factory("css");

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
                    $css = file_get_contents($file);

                    // replace relative path with new path relative to cache
                    $css = str_replace("url(../", "url(../../" . dirname(dirname($file)) . "/", $css);
                    $css = str_replace("url('../", "url('../../" . dirname(dirname($file)) . "/", $css);
                    $css = str_replace("url(\"../", "url(\"../../" . dirname(dirname($file)) . "/", $css);

                    $src .= $css;
                }

                $src = CssMin::minify($src);

                // save cache file
                $cache->setFile($identifier, $src, true);
            }

            echo("<link rel=\"stylesheet\" type=\"text/css\" $media href=\"" . $cache->getUrl($identifier) . "\">\n");
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
    // {{{ a()
    /**
     * outputs the url to a localized link
     *
     * @param   $link (string) page to link to 
     * @param   $protocol (string) protocol to use for the link
     * @param   $locale (string) locale to link to 
     *
     * @return  void
     */
    static function a($link, $protocol = null, $locale = null) {
        html::t(html::link($link, $protocol, $locale));
    }
    // }}}
    // {{{ hash()
    /**
     * creates hash tag for locations from text
     *
     * @param   $text (string) text to escape for used in url hash
     *
     * @return  (string) escaped string
     */
    static function hash($text = "") {
        return htmlspecialchars(self::get_url_escaped($text));
    }
    // }}}
    
    // {{{ link()
    /**
     * builds a localized link
     *
     * @param   $link (string) page to link to 
     * @param   $protocol (string) protocol to use for the link
     * @param   $locale (string) locale to link to 
     *
     * @return  url
     */
    static function link($link, $protocol = null, $locale = null) {
        return new \depage\html\link($link, $protocol, $locale);
    }
    // }}}

    // {{{ markdown()
    /**
     * outputs html by parsing markdown syntax
     *
     * @param   $param (string) text to parse
     * @return  void
     */
    static function markdown($param, $nofollow = '', $gamut_filter = array()) {
        require_once('custom_markdown.php');

        echo(Markdown(htmlspecialchars($param), $nofollow, $gamut_filter));
    }
    // }}}

    // {{{ format_date()
    /**
     * formats date parameter based on current locale
     * @param   $date (DateTime | int) either a DateTime object or an integer timestamp
     * @return  string
     */
    static function format_date($date, $date_format = IntlDateFormatter::LONG, $time_format = IntlDateFormatter::SHORT, $pattern = null) {
        if (!is_integer($date_format)) {
            $pattern = $date_format;
            $date_format = IntlDateFormatter::LONG;
        }
        // there is not getlocale, so use setlocale with null
        $current_locale = setlocale(LC_ALL, null);
        $fmt = new IntlDateFormatter($current_locale, $date_format, $time_format, null, null, $pattern); 
        
        if ($date instanceof DateTime) {
            $timestamp = $date->getTimestamp();
        } else {
            $timestamp = $date;
        }

        return $fmt->format($timestamp);
    }
    // }}}
    // {{{ format_number()
    static function format_number($number, $format = NumberFormatter::DECIMAL) {
        // there is not getlocale, so use setlocale with null
        $current_locale = setlocale(LC_ALL, null);
        $fmt = new NumberFormatter($current_locale, $format);
        
        return $fmt->format($number);
    }
    // }}}
    
    // {{{ get_url_escaped()
    public static function get_url_escaped ($text, $limit = 100) {
        // {{{ substitutes
        $substitutes = array(
            'Š'=>'S',
            'š'=>'s',
            'Ð'=>'Dj',
            'Ž'=>'Z',
            'ž'=>'z',
            'À'=>'A',
            'Á'=>'A',
            'Â'=>'A',
            'Ã'=>'A',
            'Ä'=>'AE',
            'Å'=>'A',
            'Æ'=>'A',
            'Ç'=>'C',
            'È'=>'E',
            'É'=>'E',
            'Ê'=>'E',
            'Ë'=>'E',
            'Ì'=>'I',
            'Í'=>'I',
            'Î'=>'I',
            'Ï'=>'I',
            'Ñ'=>'N',
            'Ò'=>'O',
            'Ó'=>'O',
            'Ô'=>'O',
            'Õ'=>'O',
            'Ö'=>'OE',
            'Ø'=>'O',
            'Ù'=>'U',
            'Ú'=>'U',
            'Û'=>'U',
            'Ü'=>'UE',
            'Ý'=>'Y',
            'Þ'=>'B',
            'ß'=>'ss',
            'à'=>'a',
            'á'=>'a',
            'â'=>'a',
            'ã'=>'a',
            'ä'=>'ae',
            'å'=>'a',
            'æ'=>'a',
            'ç'=>'c',
            'è'=>'e',
            'é'=>'e',
            'ê'=>'e',
            'ë'=>'e',
            'ì'=>'i',
            'í'=>'i',
            'î'=>'i',
            'ï'=>'i',
            'ð'=>'o',
            'ñ'=>'n',
            'ò'=>'o',
            'ó'=>'o',
            'ô'=>'o',
            'õ'=>'o',
            'ö'=>'oe',
            'ø'=>'o',
            'ù'=>'u',
            'ú'=>'u',
            'û'=>'u',
            'ü'=>'ue',
            'ý'=>'y',
            'ý'=>'y',
            'þ'=>'b',
            'ÿ'=>'y',
            'ƒ'=>'f',
            '§'=>'-',
            '°'=>'-',
        );
        // }}}
        
        $text = trim($text);
        
        foreach ($substitutes as $o => $s) {
            $text = mb_ereg_replace($o, $s, $text);
        }
        
        $text = mb_ereg_replace('[^\d\w]+', '-', $text);
        $text = trim($text, "-");
        if (mb_strlen($text) > $limit) {
            $title = mb_strcut($text, 0, $limit);
        }
        $text = rawurlencode($text);
        
        return $text;
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
