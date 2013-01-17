<?php
/**
 * @file    cssmin.php
 * @brief   cssmin class
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 **/

namespace depage\cssmin\providers;

/**
 * @brief Main cssmin class
 **/
class cssminLocal extends \depage\cssmin\cssmin {
    
    // {{{ minifySrc()
    /**
     * @brief minifies css-source
     *
     * @param $src javascript source code
     **/
    public function minifySrc($src) {
        require_once(__DIR__ . "/local/cssmin.php");

        return \CssMin::minify($src);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
