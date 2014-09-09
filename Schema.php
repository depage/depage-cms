<?php
/**
 * @file    framework/DB/Schema.php
 *
 * depage database module
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 * @author    Sebastian Reinhold [sebastian@bitbernd.de]
 */

namespace depage\DB;

class Schema
{
    /* {{{ variables */
        private $filenames  = array();
        private $sql        = array();
    /* }}} */

    /* {{{ constructor */
    /**
     *
     * @return void
     */
    public function __construct($filenames)
    {
            $this->filenames = $filenames;

    }
    /* }}} */

    /* {{{ load */
    public function load()
    {
        foreach($this->filenames as $filename) {
            $handle = @fopen($filename, "r");
            if ($handle) {
                while (($buffer = fgets($handle, 4096)) !== false) {
                    $sql[] = trim($buffer);
                }
                if (!feof($handle)) {
                    #TODO exception
                }
                fclose($handle);
            }
        }
    }
    /* }}} */
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
