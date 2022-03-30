<?php

namespace Depage\Html;

class Cleaner
{
    public $dontCleanTags = array();

    // {{{ constructor
    /*
     * constructor for cleaner, defines the tags of lines that should not
     * be cleaned of whitespace e.g. pre and textarea
     */
    public function __construct()
    {
        $this->dontCleanTags = implode("|<", array(
            "pre",
            "textarea",
        ));
    }
    // }}}

    // {{{ clean()
    /*
     * Cleans the html of unnecessary spaces and empty lines
     *
     * @param $html html source to clean up
     *
     * @return $html cleaned html
     */
    public function clean($html)
    {
        $htmlLines = explode("\n", $html);
        $html = "";

        $dontClean = 0;

        foreach ($htmlLines as $i => $line) {
            // check for opening tags
            if ($m = preg_match_all("/<{$this->dontCleanTags}/", $line, $matches)) {
                $dontClean += $m;
            }

            if ($dontClean > 0) {
                // just copy the whole line
                $html .= $line . "\n";
            } else {
                // trim line
                $line = trim($line);
                // replace multiple spaces with only one space
                $line = preg_replace("/\"[^\"]*\"(*SKIP)(*FAIL)|'[^']*'(*SKIP)(*FAIL)|( )+/", " ", $line);
                // throw away empty lines
                if ($line != "") {
                    $html .= $line . "\n";
                }
            }

            // check for closing tags
            if ($m = preg_match_all("/<\/{$this->dontCleanTags}/", $line, $matches)) {
                $dontClean -= $m;
            }
        }

        return $html;
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
