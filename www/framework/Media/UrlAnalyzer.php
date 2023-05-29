<?php
/**
 * @file    UrlAnalyzer.php
 * @brief   url analyzer class
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 */

namespace Depage\Media;

class UrlAnalyzer
{
    // {{{ variables
    protected $cache = null;
    // }}}

    // {{{ construct()
    /**
     * @brief __construct
     *
     * @param cache
     * @return void
     **/
    public function __construct($cache = null) {
        $this->cache = $cache;
    }
    // }}}

    // {{{ analyze()
    /**
     * @brief analyze
     *
     * @param mixed $url
     * @return void
     **/
    public function analyze($url)
    {
        $info = UrlInfo::factory($url);

        return $info;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
