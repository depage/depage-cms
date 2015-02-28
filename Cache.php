<?php

/**
 * @todo change the modification time to a date in the future to be able to
 *       have ttl in the setter instead of the getter
 *
 * @todo add increment/decrement ?
 *
 * @todo add provider for redis ?
 */

namespace Depage\Cache;

// {{{ autoloader
/**
 * @brief PHP autoloader
 *
 * Autoloads classes by namespace. (requires PHP >= 5.3)
 **/
function autoload($class)
{
    $class = str_replace('\\', '/', str_replace(__NAMESPACE__ . '\\', '', $class));
    $file = __DIR__ . '/' .  $class . '.php';

    if (file_exists($file)) {
        require_once($file);
    }
}

spl_autoload_register(__NAMESPACE__ . '\autoload');
// }}}

abstract class Cache
{
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
    public static function factory($prefix, $options = array())
    {
        if (!isset($options['disposition'])) {
            $options['disposition'] = "file";
        }
        if (in_array($options['disposition'], array("memcached", "memory")) && extension_loaded("memcached")) {
            return new \Depage\Cache\Providers\Memcached($prefix, $options);
        } elseif (in_array($options['disposition'], array("memcache", "memory")) && extension_loaded("memcache")) {
            return new \Depage\Cache\Providers\Memcache($prefix, $options);
        } elseif (in_array($options['disposition'], array("redis", "memory")) && extension_loaded("redis")) {
            return new \Depage\Cache\Providers\Redis($prefix, $options);
        } elseif ($options['disposition'] == "uncached") {
            return new \Depage\Cache\Providers\Uncached($prefix, $options);
        }
        return new \Depage\Cache\Providers\File($prefix, $options);
    }
    // }}}

    // {{{ constructor
    protected function __construct($prefix, $options = array())
    {
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
     * @return (bool) true if cache for $key exists, false if not
     */
    abstract public function exist($key);
    // }}}
    // {{{ age */
    /**
     * @brief returns age of cache-item with key $key
     *
     * @param   $key (string) key of cache item
     *
     * @return (int) age as unix timestamp
     */
    abstract public function age($key);
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
    abstract public function set($key, $data);
    // }}}
    // {{{ get */
    /**
     * @brief gets a cached object
     *
     * @param   $key (string) key of item to get
     *
     * @return (object) unserialized content of cache item, false if the cache item does not exist
     */
    abstract public function get($key);
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
    abstract public function delete($key);
    // }}}
    // {{{ clear */
    /**
     * @brief clears all items from current cache
     *
     * @return void
     */
    abstract public function clear();
    // }}}

    // {{{ rmr */
    /**
     * @brief deletes files and direcories recursively
     *
     * @param   $path (string) path to file or directory
     *
     * @return void
     */
    public function rmr($path)
    {
        if (!is_link($path) && is_dir($path)) {
            $files = glob($path . "/*");
            foreach ($files as $file) {
                $this->rmr($file);
            }
            rmdir($path);
        } else {
            unlink($path);
        }
    }
    // }}}
    // {{{ getCachePath */
    /**
     * @brief gets file-path for a cache-item by key
     *
     * @param   key (string) key of item
     *
     * @return (string) file path to cache-item
     */
    protected function getCachePath($key)
    {
        return $this->cachepath . $key;
    }
    // }}}
}

/* vim:set ft=php sts=4 fdm=marker et : */
