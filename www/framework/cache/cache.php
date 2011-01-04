<?php

class cache {
    /* {{{ constructor */
    public function __construct($cachepath, $baseurl) {
        $this->cachepath = $cachepath;
        $this->baseurl = $baseurl . "cache/";
    }
    /* }}} */
    /* {{{ exist */
    public function exist($identifier) {
        return file_exists($this->get_cache_path($identifier));
    }
    /* }}} */
    /* {{{ age */
    public function age($identifier) {
        if ($this->exist($identifier)) {
            return filemtime($this->get_cache_path($identifier));
        } else {
            return false;
        }
    }
    /* }}} */
    /* {{{ get */
    public function get($identifier) {
        if ($this->exist($identifier)) {
            $path = $this->get_cache_path($identifier);

            return file_get_contents($path);
        } else {
            return false;
        }
    }
    /* }}} */
    /* {{{ geturl */
    public function geturl($identifier) {
        return $this->baseurl . $identifier;
    }
    /* }}} */
    /* {{{ put */
    public function put($identifier, $data) {
        $path = $this->get_cache_path($identifier);

        file_put_contents($path, $data);
        file_put_contents($path . ".gz", gzencode($data));
    }
    /* }}} */
    /* {{{ call */
    public function call($func, $args) {
    }
    /* }}} */

    /* {{{ get_cache_path */
    protected function get_cache_path($identifier) {
        return $this->cachepath . $identifier;
    }
    /* }}} */
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
?>
