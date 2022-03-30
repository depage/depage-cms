<?php
/**
 * @file    framework/html/html.php
 *
 * depage html module
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 *
 * thanks for ideas from:
 * http://stackoverflow.com/questions/62617/whats-the-best-way-to-separate-php-code-and-html
 */

namespace Depage\Html;

class Html {
    public $args;
    private $param = null;
    private $template;

    public $contentType = "text/html";
    public $charset = "UTF-8";
    public $templatePath = "";
    public $clean = false;

    // {{{ substitutes
    protected static $substitutes = array(
        // Latin
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'AE', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'OE', 'Ő' => 'O',
        'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'UE', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
        'ß' => 'ss',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'ae', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe', 'ő' => 'o',
        'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'ue', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
        'ÿ' => 'y',

        // Latin symbols
        '©' => 'c', '®' => 'r', '℗' => 'p', '™' => 'tm',
        '@' => 'at', '%' => 'percent',

        // Currency symbols
        '¥' => 'yen', '¢' => 'cent', '€' => 'eur', '$' => 'dollar', '₤' => 'pound',

        // Greek
        'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
        'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
        'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
        'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
        'Ϋ' => 'Y',
        'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
        'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
        'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
        'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
        'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',

        // Turkish
        'Ş' => 'S', 'İ' => 'I', 'Ç' => 'C', 'Ü' => 'UE', 'Ö' => 'OE', 'Ğ' => 'G',
        'ş' => 's', 'ı' => 'i', 'ç' => 'c', 'ü' => 'ue', 'ö' => 'oe', 'ğ' => 'g',

        // Russian
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
        'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
        'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
        'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
        'Я' => 'Ya',
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
        'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
        'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
        'я' => 'ya',

        // Ukrainian
        'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
        'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',

        // Czech
        'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U',
        'Ž' => 'Z',
        'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
        'ž' => 'z',

        // Polish
        'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ó' => 'o', 'Ś' => 'S', 'Ź' => 'Z',
        'Ż' => 'Z',
        'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
        'ż' => 'z',

        // Latvian
        'Ā' => 'A', 'Č' => 'C', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N',
        'Š' => 'S', 'Ū' => 'u', 'Ž' => 'Z',
        'ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
        'š' => 's', 'ū' => 'u', 'ž' => 'z',
    );
    // }}}

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
            // allow template to be empty -> pull args and parameters up
            $this->template = null;
            $this->args = $template;
            $this->param = $args;
        } else {
            // template not empty
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
        $this->templatePath = $param['template_path'] ?? "";
        $this->clean = $param['clean'] ?? false;

        foreach ($this->args as $arg) {
            // set to parent params to params if not set
            if (is_object($arg) and get_class($arg) == "Depage\Html\Html") {
                if ($arg->param === null) {
                    $arg->setHtmlOptions($param);
                }
            } else if (is_array($arg)) {
                // set to parent params to params for subarray if not set
                foreach ($arg as $a) {
                    if (is_object($a) && get_class($a) == "Depage\Html\Html") {
                        if ($a->param === null) {
                            $a->setHtmlOptions($param);
                        }
                    }
                }
            }
        }
    }
    // }}}
    // {{{ addArg()
    /**
     * @brief addArg
     *
     * @param mixed $name, $value
     * @return void
     **/
    public function addArg($name, $value)
    {
        $this->args[$name] = $value;
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
    // {{{ __set()
    /**
     * set parameter in args array
     *
     * @param $name (string) name of parameter
     * @param $value () value of parameter
     *
     * @return void
     */
    public function __set($name, $value) {
        $this->args[$name] = $value;
    }
    // }}}
    // {{{ __isset()
    /**
     * test args array if argument is set
     *
     * @param $name (string) name of parameter
     *
     * @return bool
     */
    public function __isset($name) {
        return isset($this->args[$name]);
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
        try {
            $contents = [];
            if ($this->template !== null) {
                require($this->templatePath . $this->template);
            } else {
                if (isset($this->content)) {
                    $contents[] = $this->content;
                } else if (is_array($this->args)) {
                    $contents = $this->args;
                }

                foreach($contents as $c) {
                    self::e($c);
                }
            }
        } catch (Exception $e) {
            var_dump($e);
        }
        $html = ob_get_contents();
        ob_end_clean();

        return $this->clean($html);
    }
    // }}}
    // {{{ clean()
    /**
     * clean html output
     *
     * @return output
     */
    public function clean($html) {
        if ($this->clean) {
            $cleaner = new \Depage\Html\Cleaner();
            $html = $cleaner->clean($html);
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
    // {{{ includeJs()
    /**
     * includes javascript files into html
     */
    public function includeJs($name, $files = array(), $attr = "") {
        // get file-dependencies that are required from javascript header
        $files = $this->includeJsGetDependencies($files);
        $useCached = false;

        if ($this->param['env'] === "production") {
            // production environement
            $mtimes = $this->getFileModTimes($files);
            $identifier = "{$name}_" . sha1(serialize(array($files, $mtimes))) . ".js";
            $useCached = true;

            // get cache instance
            $src = false;
            $jsmin = \Depage\JsMin\JsMin::factory(array(
                'extension' => isset($this->param['jsmin']->extension) ? $this->param['jsmin']->extension : "",
                'jar' => isset($this->param['jsmin']->jar) ? $this->param['jsmin']->jar : "",
                'java' => isset($this->param['jsmin']->java) ? $this->param['jsmin']->java : "",
            ));
            try {
                $src = $jsmin->minifyFiles($name, $files);
            } catch (\Depage\JsMin\Exceptions\JsminException $e) {
                $log = new \Depage\Log\Log();
                $log->log("closure compiler: " . $e->getMessage());
                $src = false;
            }
            if ($src === false) {
                // could not minify -> use unminified version
                $useCached = false;
            }
        }
        if ($useCached) {
            $cache = \Depage\Cache\Cache::factory("js");
            $d = date ("YmdHis", $cache->age($identifier));
            echo("<script src=\"" . $cache->getUrl($identifier) . "?$d\" $attr></script>\n");
        } else {
            // development environement
            foreach ($files as $file) {
                $d = date ("YmdHis", filemtime($file));
                echo("<script src=\"$file?$d\" $attr></script>\n");
            }
        }
    }
    // }}}
    // {{{ includeJsGetDependencies()
    /**
     * gets dependencies from filename
     */
    protected function includeJsGetDependencies($files = array()) {
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
                                $sub_files = $this->includeJsGetDependencies(array($matches[1]));
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
    // {{{ includeCss()
    /**
     * includes css files into html
     */
    public function includeCss($name, $files = array(), $for = "", $inline = false) {
        if ($for != "") {
            $media = "media=\"$for\"";
        } else {
            $media = "";
        }

        // development environement
        if (!$inline) {
            foreach ($files as $file) {
                $d = date ("YmdHis", filemtime($file));
                echo("<link rel=\"stylesheet\" type=\"text/css\" $media href=\"$file?$d\">\n");
            }
        } else {
            echo("<style type=\"text/css\" $media>\n");
                foreach ($files as $file) {
                    readfile($file);
                }
            echo("</style>\n");
        }
    }
    // }}}
    // {{{ getFileModTimes()
    /**
     * @brief gets modification times for files
     *
     * @param $files array of filenames
     **/
    protected function getFileModTimes($files) {
        $mtimes = array();
        foreach ($files as $i => $file) {
            $mtimes[$i] = filemtime($file);
        }

        return $mtimes;
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
                self::t($line);
                echo("<br>");
            }
        } else {
            echo(htmlspecialchars($text));
        }
    }
    // }}}
    // {{{ sp()
    /**
     * outputs escaped text for use in html and html-attributes
     *
     * @param   $text (string) text to escape
     *
     * @return  void
     */
    static function sp($text = "", ...$params) {
        echo(htmlspecialchars(sprintf($text, ...$params)));
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
                self::e($p);
            }
        } else {
            if (is_object($param) && get_class($param) == "Depage\Html\Html") {
                $param->clean = false;
            }
            if (!empty($param)) {
                echo($param);
            }
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
        self::t(self::link($link, $protocol, $locale));
    }
    // }}}
    // {{{ attr()
    /**
     * @brief attr
     *
     * @param mixed $name, $value
     * @return void
     **/
    static function attr($name, $value = "")
    {
        if (is_array($name)) {
            foreach($name as $attr => $val) {
                self::attr($attr, $val);
            }
        } else if (!empty($value) || is_numeric($value)) {
            echo(" $name=\"");
            echo(trim(htmlspecialchars($value, \ENT_COMPAT, 'UTF-8')));
            echo("\"");
        }
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
        return htmlspecialchars(self::getEscapedUrl($text));
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
        return new Link($link, $protocol, $locale);
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

    // {{{ truncate()
    static function truncate($string, $max = 50, $rep = "") {
        if (strlen($string) <= $max) {
            $rep = "";
        }
        $leave = $max - strlen($rep);

        return trim(substr_replace($string, "", $leave)) . $rep;
    }
    // }}}
    // {{{ textContent()
    /**
     * @brief textContent
     *
     * @param mixed $htmlString
     * @return void
     **/
    public static function textContent($htmlString)
    {
        $t = $htmlString;

        $t = preg_replace("/[\r\n\t ]+/", " ", $t);
        $t = str_replace("</p>", "\n</p>", $t);
        $t = strip_tags($t);

        return trim(html_entity_decode($t));
    }
    // }}}
    // {{{ isEmpty()
    /**
     * @brief isEmpty
     *
     * @param mixed $htmlString
     * @return void
     **/
    public static function isEmpty($htmlString)
    {
        return empty(self::textContent($htmlString));
    }
    // }}}

    // {{{ formatDate()
    /**
     * formats date parameter based on current locale
     * @param   $date (DateTime | int) either a DateTime object or an integer timestamp
     * @return  string
     *
     * @todo move into Depage\Formatters Namespace
     */
    static function formatDate($date, $date_format = \IntlDateFormatter::LONG, $time_format = \IntlDateFormatter::SHORT, $pattern = null) {
        if (!is_integer($date_format)) {
            $pattern = $date_format;
            $date_format = \IntlDateFormatter::LONG;
        }

        $fmt = self::getDateFormatter($date_format, $time_format, $pattern);

        if ($date instanceof \DateTime) {
            $timestamp = $date->getTimestamp();
        } else if (is_string($date)) {
            $timestamp = strtotime($date);
        } else {
            $timestamp = $date;
        }

        return $fmt->format($timestamp);
    }
    // }}}
    // {{{ formatDateNatural()
    /**
     * formats date parameter based on current locale
     * @param   $date (DateTime | int) either a DateTime object or an integer timestamp
     * @return  string
     *
     * @todo move into Depage\Formatters Namespace
     */
    static function formatDateNatural($date, $addTime = false) {
        $fmt = new \Depage\Formatters\DateNatural();
        if ($date instanceof \DateTime) {
            $timestamp = $date->getTimestamp();
        } else if (is_string($date)) {
            $timestamp = strtotime($date);
        } else {
            $timestamp = $date;
        }

        return $fmt->format($timestamp, $addTime);
    }
    // }}}
    // {{{ formatNumber()
    /*
     * @todo move into Depage\Formatters Namespace
     */
    static function formatNumber($number, $format = \NumberFormatter::DECIMAL) {
        // there is not getlocale, so use setlocale with null
        $current_locale = setlocale(LC_ALL, null);
        $fmt = new \NumberFormatter($current_locale, $format);

        return $fmt->format($number);
    }
    // }}}

    // {{{ getDateFormatter()
    /**
     * @brief getDateFormatter
     *
     * @param mixed $locale, $dateFormat, $timeFormat, $pattern
     * @return void
     **/
    static function getDateFormatter($dateFormat, $timeFormat, $pattern)
    {
        // there is not getlocale, so use setlocale with null
        $locale = setlocale(LC_ALL, null);
        $hash = "$dateFormat#$timeFormat#$pattern";

        static $fmts = [];

        if (!isset($fmts[$hash])) {
            $fmts[$hash] = new \IntlDateFormatter($locale, $dateFormat, $timeFormat, null, null, $pattern);
        }


        return $fmts[$hash];
    }
    // }}}

    // {{{ getEscapedUrl()
    public static function getEscapedUrl($text, $repl = "-", $limit = -1) {
        $origText = $text;
        $qRepl = preg_quote($repl);

        // transliterate
        $text = str_replace(array_keys(self::$substitutes), array_values(self::$substitutes), $text);

        //$text = mb_ereg_replace('[^\d\w]+', $repl, $text);
        //$text = preg_replace('/[^0-9a-zA-Z]+/', $repl, $text);
        //$text = preg_replace('/[^\p{L}\p{Nd}_\-\.' . $qRepl . ']+/u', $repl, $text);
        $text = preg_replace('/[^\d\w_\-\.' . $qRepl . ']+/u', $repl, $text);

        // replace double placeholders
        $text = preg_replace("/($qRepl){2,}/", '$1', $text);

        $text = rtrim($text, $repl);
        if ($limit > 0 && mb_strlen($text) > $limit) {
            $title = mb_strcut($text, 0, $limit);
        }

        $text = rawurlencode($text);

        return $text;
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
