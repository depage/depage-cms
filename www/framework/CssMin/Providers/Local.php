<?php
/**
 * @file    cssmin.php
 * @brief   cssmin class
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 **/

namespace Depage\CssMin\Providers;

/**
 * @brief Main cssmin class
 **/
class Local extends \Depage\CssMin\CssMin {

    // {{{ minifySrc()
    /**
     * @brief minifies css-source
     *
     * @param $src javascript source code
     **/
    public function minifySrc($src) {
        require_once(__DIR__ . "/Local/cssmin.php");

        return \CssMin::minify($src);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
