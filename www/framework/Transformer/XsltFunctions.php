<?php
/**
 * @file    XsltFunctions.php
 *
 * description
 *
 * copyright (c) 2024 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Transformer;

class XsltFunctions
{
    // {{{ variables
    /**
     * @brief transformer
     **/
    protected $transformer;

    /**
     * @brief fl
     **/
    protected $fl;
    // }}}
    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $transformer
     * @param mixed $fl
     * @return void
     **/
    public function __construct($transformer, $fl)
    {
        $this->transformer = $transformer;
        $this->fl = $fl;
    }
    // }}}

    // {{{ getLibRef()
    /**
     * @brief getLibRef
     *
     * @param mixed $path
     * @return void
     **/
    public function getLibRef($path, $absolute = false)
    {
        $p = $this->fl->toLibref($path);

        if ($p) $path = $p;

        $url = parse_url($path);

        $path = "lib/" . ($url['host'] ?? '') . ($url['path'] ?? '');

        if ($absolute != "relative" && !empty($this->transformer->baseUrlStatic) && $this->transformer->baseUrl != $this->transformer->baseUrlStatic) {
            $path = $this->transformer->baseUrlStatic . $path;
        } else if ($absolute == "absolute" || $this->transformer->useAbsolutePaths) {
            $path = $this->transformer->baseUrl . $path;
        } else if ($absolute != "relative" && $this->transformer->useBaseUrl) {
            $path = $path;
        } else {
            $url = new \Depage\Http\Url($this->transformer->currentPath);
            $path = $url->getRelativePathTo($path);
        }

        return $path;
    }
    // }}}
    // {{{ getPageRef()
    /**
     * @brief getPageRefgetPageRef
     *
     * @param mixed $pageId, $lang, $absolute
     * @return void
     **/
    public function getPageRef($pageId, $lang = null, $absolute = false)
    {
        if ($lang === null) {
            $lang = $this->transformer->lang;
        }
        $path = "";

        $xmlnav = $this->transformer->getXmlNav();
        $this->transformer->addToUsedDocuments("1");

        if ($url = $xmlnav->getUrl($pageId)) {
            $path = $lang . $url;
        }

        if ($absolute == "absolute" || $this->transformer->useAbsolutePaths) {
            $path = $this->transformer->baseUrl . $path;
        } else if ($this->transformer->useBaseUrl) {
            $path = $path;
        } else {
            $url = new \Depage\Http\Url($this->transformer->currentPath);
            $path = $url->getRelativePathTo($path);
        }
        if ($this->transformer->routeHtmlThroughPhp) {
            //$path = preg_replace("/\.php$/", ".html", $path);
        }

        return $path;
    }
    // }}}
    // {{{ fileinfo
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function fileinfo($path, $extended = "true") {
        $info = $this->fl->getFileInfoByRef($path);

        if (!$info) {
            $xml = "<file exists=\"false\" />";
            $doc = new \DOMDocument();
            $doc->loadXML($xml);

            return $doc;
        }

        return $info->toXml();
    }
    // }}}
    // {{{ urlinfo
    /**
     * gets urlinfo for url
     *
     * @public
     *
     * @param    $path (string) url to get info about
     *
     * @return    $xml (xml) url info as xml string
     */
    public function urlinfo($url) {
        $analyzer = new \Depage\Media\UrlAnalyzer();
        $info = $analyzer->analyze($url);

        return $info->toXml();
    }
    // }}}
    // {{{ filesInFolder
    /**
     * gets fileinfo for all files in a specific folder
     *
     * @public
     *
     * @param    $id (int) id of folder
     *
     * @return    $xml (xml) file infos of files
     */
    public function filesInFolder($folderId) {
        $files = $this->fl->getFilesInFolder($folderId);

        $doc = new \DOMDocument();
        $doc->loadXML("<files />");

        foreach ($files as $f) {
            $node = $doc->importNode($f->toXML()->documentElement, true);
            $doc->documentElement->appendChild($node);
        }

        return $doc;
    }
    // }}}
    // {{{ includeUnparsed
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function includeUnparsed($path) {
        $xml = "";
        $path = "projects/" . $this->transformer->project->name . "/lib" . substr($path, 8);

        $xml = "<text>";
        if (file_exists($path)) {
            $xml .= htmlspecialchars(file_get_contents($path),
                \ENT_COMPAT | \ENT_XML1 | \ENT_DISALLOWED, "utf-8");
        }
        $xml .= "</text>";

        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        return $doc;
    }
    // }}}
    // {{{ setUseAbsolutePaths()
    /**
     * @brief setUseAbsolutePaths
     *
     * @param mixed
     * @return void
     **/
    public function setUseAbsolutePaths()
    {
        $this->transformer->useAbsolutePaths = true;
        $this->transformer->useBaseUrl = false;

        return "<true />";
    }
    // }}}
    // {{{ getUseAbsolutePaths()
    /**
     * @brief getUseAbsolutePaths
     *
     * @param mixed
     * @return void
     **/
    public function getUseAbsolutePaths()
    {
        return $this->transformer->useAbsolutePaths;
    }
    // }}}
    // {{{ setUseBaseUrl()
    /**
     * @brief setUseBaseUrl
     *
     * @param mixed
     * @return void
     **/
    public function setUseBaseUrl()
    {
        $this->transformer->useBaseUrl = true;
        $this->transformer->useAbsolutePaths = false;

        return "<true />";
    }
    // }}}
    // {{{ getUseBaseUrl()
    /**
     * @brief getUseBaseUrl
     *
     * @param mixed
     * @return void
     **/
    public function getUseBaseUrl()
    {
        return $this->transformer->useBaseUrl;
    }
    // }}}
    // {{{ changeSrc()
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function changeSrc($source) {
        $url = new \Depage\Http\Url($this->transformer->currentPath);
        $newSource = "";
        $posOffset = 0;
        // @todo check libref:/(/)
        while (($startPos = strpos($source, '"libref://', $posOffset)) !== false) {
            $newSource .= substr($source, $posOffset, $startPos - $posOffset) . '"';
            $posOffset = $startPos + strlen("libref://") + 3;
            $endPos = strpos($source, "\"", $posOffset);
            $newSource .= $url->getRelativePathTo('/lib' . substr($source, $startPos + 9, $endPos - ($startPos + 9)));
            $posOffset = $endPos;
        }
        $newSource .= substr($source, $posOffset);

        return $newSource;
    }
    // }}}
    // {{{ replaceEmailChars()
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function replaceEmailChars($email) {
        $original = array(
            "@",
            ".",
            "-",
            "_",
        );
        if ($this->transformer->lang == "de") {
            $repl = array(
                " *at* ",
                " *punkt* ",
                " *minus* ",
                " *unterstrich* ",
            );
        } else {
            $repl = array(
                " *at* ",
                " *dot* ",
                " *minus* ",
                " *underscore* ",
            );
        }
        $value = str_replace($original, $repl, $email);

        return $value;
    }
    // }}}
    // {{{ atomizeText()
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function atomizeText($text) {
        $xml = "<spans><span>" . str_replace(" ", " </span><span>", htmlspecialchars(trim($text))) . " </span></spans>";

        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        return $doc;
    }
    // }}}
    // {{{ phpEscape()
    /**
     * escapes string for use as php code in xsl
     *
     * @public
     *
     * @param    $string
     *
     * @return    escaped string
     */
    public function phpEscape($string) {
        $value = var_export($string, true);

        return $value;
    }
    // }}}
    // {{{ jsEscape()
    /**
     * escapes string for use in javascript code in xsl
     *
     * @public
     *
     * @param    $string
     *
     * @return    escaped string
     */
    public function jsEscape($string) {
        $value = json_encode($string, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_NUMERIC_CHECK);

        return $value;
    }
    // }}}
    // {{{ cssEscape()
    /**
     * escapes css identifier for use in xsl
     *
     * @public
     *
     * @param    $string
     *
     * @return    escaped string
     */
    public function cssEscape($string) {
        $value = \Depage\Html\Html::getEscapedUrl($string);

        return $value;
    }
    // }}}
    // {{{ formatDate()
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function formatDate($date = '', $format = '') {
        if ($format == '') {
            $format = "c";
        }
        if (empty($date)) {
            $date = date($format);
        } else {
            $date = date($format, strtotime($date));
        }

        return $date;
    }
    // }}}
    // {{{ autokeywords()
    /**
     * @brief autokeywords
     *
     * @param mixed $keywords, $content
     * @return void
     **/
    public function autokeywords($keys, $content)
    {
        // @todo add keyword aliases?
        $val = "";
        $keywords = [];
        $originalKeywords = $this->extractWords($keys);
        foreach ($originalKeywords as $key => $value) {
            $keywords[$key] = mb_strtolower($value);
        }
        $contentWords = $this->extractWords($content, true);

        $found = array_intersect($contentWords, $keywords);

        foreach ($found as $word) {
            $val .= $originalKeywords[array_search($word, $keywords)] . ", ";
        }
        /*
        var_dump($keys);
        var_dump($keywords);
        var_dump($contentWords);
        var_dump($val);
        die();
         */
        return trim($val, ", ");
    }
    // }}}

    // {{{ extractWords()
    /**
     * @brief extractWords
     *
     * @param mixed $
     * @return void
     **/
    private function extractWords($string, $normalize = false)
    {
        preg_match_all("/\w+(-\w+)?/u", $string, $matches);
        if (!isset($matches[0])) {
            return [];
        }

        if ($normalize) {
            foreach ($matches[0] as &$value) {
                $value = mb_strtolower($value);
            }

            return array_unique($matches[0]);
        } else {
            return $matches[0];
        }

    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
