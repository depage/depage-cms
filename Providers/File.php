<?php

namespace Depage\Cache\Providers;

class File extends \Depage\Cache\Cache
{
    // {{{ variables
    protected $prefix;
    protected $cachepath;
    protected $baseurl;
    protected $host;
    protected $defaults = [
        'cachepath' => DEPAGE_CACHE_PATH,
        'baseurl' => DEPAGE_BASE,
        'disposition' => "file",
    ];
    // }}}

    // {{{ constructor
    protected function __construct($prefix, $options = array())
    {
        $options = array_merge($this->defaults, $options);

        $this->prefix = $prefix;
        $this->cachepath = "{$options['cachepath']}/{$this->prefix}/";
        $this->baseurl = "{$options['baseurl']}cache/{$this->prefix}/";
    }
    // }}}
    // {{{ init()
    /**
     * @brief empty init function (called on wakeup)
     **/
    protected function init()
    {
    }
    // }}}
    // {{{ exist
    /**
     * @brief return if a cache-item with $key exists
     *
     * @return (bool) true if cache for $key exists, false if not
     */
    public function exist($key)
    {
        return file_exists($this->getCachePath($key));
    }
    // }}}
    // {{{ age */
    /**
     * @brief returns age of cache-item with key $key
     *
     * @param   $key (string) key of cache item
     *
     * @return (int) age as unix timestamp
     */
    public function age($key)
    {
        if ($this->exist($key)) {
            return filemtime($this->getCachePath($key));
        } else {
            return false;
        }
    }
    // }}}
    // {{{ setFile */
    /**
     * @brief saves cache data for key $key to a file
     *
     * @param   $key                (string) key to save data in, may include namespaces divided by a forward slash '/'
     * @param   $data               (string) data to save in file
     * @param   $saveGzippedContent (bool) if true, it saves a gzip file additional to plain string, defaults to false
     *
     * @return (bool) true if saved successfully
     */
    public function setFile($key, $data, $saveGzippedContent = false)
    {
        $path = $this->getCachePath($key);

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        $success = file_put_contents($path, $data, \LOCK_EX);

        if ($saveGzippedContent) {
            $success = $success && file_put_contents($path . ".gz", gzencode($data), \LOCK_EX);
        }

        return $success;
    }
    // }}}
    // {{{ getFile */
    /**
     * @brief gets content of cache item by key $key from a file
     *
     * @param   $key (string) key of item to get
     *
     * @return (string) content of cache item, false if the cache item does not exist
     */
    public function getFile($key)
    {
        if ($this->exist($key)) {
            $path = $this->getCachePath($key);

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
     * @param   $key  (string) key to save under
     * @param   $data (object) object to save. $data must be serializable
     *
     * @return (bool) true on success, false on failure
     */
    public function set($key, $data)
    {
        return $this->setFile($key, $this->serialize($key, $data));
    }
    // }}}
    // {{{ get */
    /**
     * @brief gets a cached object
     *
     * @param   $key (string) key of item to get
     *
     * @return (object) unserialized content of cache item, false if the cache item does not exist
     */
    public function get($key)
    {
        $value = $this->getFile($key);

        return $this->unserialize($key, $value);
    }
    // }}}
    // {{{ getUrl */
    /**
     * @brief returns cache-url of cache-item for direct access through http
     *
     * @param   $key (string) key of cache item
     *
     * @return (string) url of cache-item
     */
    public function getUrl($key)
    {
        if ($this->baseurl !== null) {
            return $this->baseurl . $key;
        }
    }
    // }}}
    // {{{ getPath */
    /**
     * @brief returns cache-url of cache-item for direct access through http
     *
     * @param   $key (string) key of cache item
     *
     * @return (string) url of cache-item
     */
    public function getPath($key)
    {
        if ($this->cachepath !== null) {
            return $this->cachepath . $key;
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
     * @return void
     */
    public function delete($key)
    {
        // @todo throw error if there are wildcards in identifier to be compatioble with memcached

        clearstatcache();
        $files = array_merge(
            (array) glob($this->cachepath . $key),
            (array) glob($this->cachepath . $key . ".gz")
        );

        foreach ($files as $file) {
            $this->rmr($file);
        }
        clearstatcache();
    }
    // }}}
    // {{{ clear */
    /**
     * @brief clears all items from current cache
     *
     * @return void
     */
    public function clear()
    {
        clearstatcache();
        $files = (array) glob($this->cachepath . "/*");

        foreach ($files as $file) {
            $this->rmr($file);
        }
        clearstatcache();
    }
    // }}}
}

/* vim:set ft=php sts=4 fdm=marker et : */
