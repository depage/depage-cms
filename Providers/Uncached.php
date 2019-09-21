<?php

namespace Depage\Cache\Providers;

class Uncached extends \Depage\Cache\Cache
{
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
        return false;
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
        return false;
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
        return false;
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
        return false;
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
        return false;
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
        return false;
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
        return false;
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
    }
    // }}}
}

/* vim:set ft=php sts=4 fdm=marker et : */
