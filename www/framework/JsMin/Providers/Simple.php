<?php
/**
 * @file    jsmin.php
 * @brief   jsmin class
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 **/

namespace Depage\JsMin\Providers;

/**
 * @brief Main jsmin class
 **/
class Simple extends \Depage\JsMin\JsMin {
    // {{{ minifySrc()
    /**
     * @brief minifies js-source
     *
     * @param $src javascript source code
     **/
    public function minifySrc($src) {
        // Remove a tab
        $js = str_replace("\t", " ", $src);

        $skipQuotes = "\/.*?\/(*SKIP)(*FAIL)|\'.*?\'(*SKIP)(*FAIL)|\".*?\"(*SKIP)(*FAIL)";
        $controlChars = preg_quote("<>,:;{}()|&+-=!?[]");
        $repl = [
            '/\n(\s+)?\/\/[^\n]*/u' => "", // Remove comments with "// "
            '!/\*[^*]*\*+([^/][^*]*\*+)*/!u' => "", // Remove other comments
            '/\/\*[^\/]*\*\//u' => "", // Remove single line comments
            '/\/\*\*((\r\n|\n) \*[^\n]*)+(\r\n|\n) \*\//u' => "", // Remove multiline comments
            '/^\h+|\h+$/mu' => '', // Remove leading/trailing whitespace;
            '/^\h*\v+/mu' => '', // Remove leading whitespace
            '/\h*\v+$/mu' => '', // Remove trailing whitespace
            '/ +/u' => ' ', // Remove multiple spaces
            '/' . $skipQuotes . '|\h*([' . $controlChars . '])\h*/u' => "\\1", // Remove unnecessary whitespace around operators
        ];

        foreach ($repl as $pattern => $replacement) {
            $js = preg_replace($pattern, $replacement, $js);
        }

        return $js;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
