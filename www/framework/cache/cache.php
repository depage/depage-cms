<?php

namespace depage\cache; 

class cache {
    /* {{{ variables */
    protected $prefix;
    protected $cachepath;
    protected $baseurl;
    /* }}} */

    /* {{{ constructor */
    public function __construct($prefix, $cachepath, $baseurl = null) {
        $this->prefix = $prefix;
        $this->cachepath = "{$cachepath}/{$this->prefix}/";
        $this->baseurl = "{$baseurl}cache/{$this->prefix}/";
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
    /* {{{ set_file */
    public function set_file($identifier, $data, $put_gzipped_content = false) {
        $path = $this->get_cache_path($identifier);

        $success = file_put_contents($path, $data);
        if (!$success) {
            mkdir(dirname($path), 0777, true);
            $success = file_put_contents($path, $data);
        }
        if ($put_gzipped_content) {
            $success = $success && file_put_contents($path . ".gz", gzencode($data));
        }

        return $success;
    }
    /* }}} */
    /* {{{ get_file */
    public function get_file($identifier) {
        if ($this->exist($identifier)) {
            $path = $this->get_cache_path($identifier);

            return file_get_contents($path);
        } else {
            return false;
        }
    }
    /* }}} */
    /* {{{ set */
    public function set($identifier, $data) {
        $str = serialize($data);

        return $this->set_file($identifier, $str);
    }
    /* }}} */
    /* {{{ get */
    public function get($identifier) {
        $value = $this->get_file($identifier);

        return unserialize($value);
    }
    /* }}} */
    /* {{{ geturl */
    public function geturl($identifier) {
        if ($this->baseurl !== null) {
            return $this->baseurl . $identifier;
        }
    }
    /* }}} */
    /* {{{ delete */
    public function delete($identifier) {
        // @todo throw error if there are wildcards in identifier to be compatioble with memcached
        
        if ($identifier[strlen($identifier) - 1] == "/") {
            $dir = $identifier;
            $identifier .= "*";
        }
        $files = array_merge(
            (array) glob($this->cachepath . $identifier),
            (array) glob($this->cachepath . $identifier . ".gz")
        );

        foreach ($files as $file) {
            unlink($file);
        }

        if (isset($dir)) {
            rmdir($this->cachepath . $dir);
        }
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
