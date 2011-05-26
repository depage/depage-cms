<?php

namespace depage\cache; 

class cache {
    // {{{ variables
    protected $prefix;
    protected $cachepath;
    protected $baseurl;
    protected $defaults = array(
        'cachepath' => DEPAGE_CACHE_PATH,
        'baseurl' => DEPAGE_BASE,
        'disposition' => "file",
    );
    // }}}

    // {{{ factory
    public static function factory($prefix, $options = array()) {
        if ($options['disposition'] == "memory" && extension_loaded("memcached")) {
            return new \depage\cache\cache_memcached($prefix, $options);
        } elseif ($options['disposition'] == "memory" && extension_loaded("memcache")) {
            return new \depage\cache\cache_memcache($prefix, $options);
        } else {
            return new \depage\cache\cache($prefix, $options);
        }
    }
    // }}}

    // {{{ constructor
    protected function __construct($prefix, $options = array()) {
        $class_vars = get_class_vars('\depage\cache\cache');
        $options = array_merge($class_vars['defaults'], $options);

        $this->prefix = $prefix;
        $this->cachepath = "{$options['cachepath']}/{$this->prefix}/";
        $this->baseurl = "{$options['baseurl']}cache/{$this->prefix}/";
    }
    // }}}
    // {{{ exist
    /**
     * @brief return if a cache-item with $key exists
     *
     * @return      (bool) true if cache for $key exists, false if not
     */
    private function exist($key) {
        return file_exists($this->get_cache_path($key));
    }
    // }}}
    // {{{ age */
    /**
     * @brief returns age of cache-item with key $key
     *
     * @param       $key (string) key of cache item
     *
     * @return      (int) age as unix timestamp
     */
    public function age($key) {
        if ($this->exist($key)) {
            return filemtime($this->get_cache_path($key));
        } else {
            return false;
        }
    }
    // }}}
    // {{{ set_file */
    /**
     * @brief saves cache data for key $key to a file
     *
     * @param   $key (string) key to save data in, may include namespaces divided by a forward slash '/'
     * @param   $data (string) data to save in file
     * @param   $save_gzipped_content (bool) if true, it saves a gzip file additional to plain string, defaults to false
     *
     * @return  (bool) true if saved successfully
     */
    public function set_file($key, $data, $save_gzipped_content = false) {
        $path = $this->get_cache_path($key);

        $success = file_put_contents($path, $data);
        if (!$success) {
            mkdir(dirname($path), 0777, true);
            $success = file_put_contents($path, $data);
        }
        if ($save_gzipped_content) {
            $success = $success && file_put_contents($path . ".gz", gzencode($data));
        }

        return $success;
    }
    // }}}
    // {{{ get_file */
    /**
     * @brief gets content of cache item by key $key from a file
     *
     * @param   $key (string) key of item to get
     *
     * @return  (string) content of cache item, false if the cache item does not exist
     */
    public function get_file($key) {
        if ($this->exist($key)) {
            $path = $this->get_cache_path($key);

            return file_get_contents($path);
        } else {
            return false;
        }
    }
    // }}}
    // {{{ set */
    /**
     * @brief sets data ob a cache item
     *
     * @param   $key (string) key to save under
     * @param   $data (object) object to save. $data must be serializable
     *
     * @return  (bool) true on success, false on failure
     */
    public function set($key, $data) {
        $str = serialize($data);

        return $this->set_file($key, $str);
    }
    // }}}
    // {{{ get */
    /**
     * @brief gets a cached object
     *
     * @param   $key (string) key of item to get
     *
     * @return  (object) unserialized content of cache item, false if the cache item does not exist
     */
    public function get($key) {
        $value = $this->get_file($key);

        return unserialize($value);
    }
    // }}}
    // {{{ geturl */
    /**
     * @brief returns cache-url of cache-item for direct access through http
     *
     * @param   $key (string) key of cache item
     *
     * @return  (string) url of cache-item
     */
    public function geturl($key) {
        if ($this->baseurl !== null) {
            return $this->baseurl . $key;
        }
    }
    // }}}
    // {{{ delete */
    /**
     * @brief deletes a cache-item by key or by namespace
     *
     * If key ends on a slash, all items in this namespace will be deleted.
     *
     * @param   $key (string) key of item
     *
     * @return  void
     */
    public function delete($key) {
        // @todo throw error if there are wildcards in identifier to be compatioble with memcached
        
        if ($key[strlen($key) - 1] == "/") {
            $dir = $key;
            $key .= "*";
        }
        $files = array_merge(
            (array) glob($this->cachepath . $key),
            (array) glob($this->cachepath . $key . ".gz")
        );

        foreach ($files as $file) {
            unlink($file);
        }

        if (isset($dir)) {
            rmdir($this->cachepath . $dir);
        }
    }
    // }}}

    // {{{ get_cache_path */
    /**
     * @brief gets file-path for a cache-item by key
     *
     * @param   key (string) key of item 
     *
     * @return  (string) file path to cache-item
     */
    private function get_cache_path($key) {
        return $this->cachepath . $key;
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
