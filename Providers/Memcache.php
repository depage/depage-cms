<?php

// @note implement delete by wildcard as the following:
// http://stackoverflow.com/questions/1595904/memcache-and-wildcards

namespace Depage\Cache\Providers;

class Memcache extends \Depage\Cache\Cache
{
    // {{{ variables
    protected $defaults = array(
        'host' => 'localhost:11211',
    );
    private $memc;
    protected $keyNs = "~~namespace";
    // }}}
    
    // {{{ constructor
    protected function __construct($prefix, $options = array())
    {
        parent::__construct($prefix, $options);

        $options = array_merge($this->defaults, $options);
        $this->host = $options['host'];

        $this->memc = $this->init();

        if (!is_array($this->host)) {
            $this->host = array($this->host);
        }
        foreach ($this->host as $server) {
            $parts = explode(":", $server);
            $host = $parts[0];
            if (count($parts) == 2) {
                $port = $parts[1];
            } else {
                $port = "11211";
            }

            $this->memc->addServer($host, $port);
        }
    }
    // }}}
    // {{{ init
    protected function init()
    {
        return new \Memcache();
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
        $val = $this->get($key);
        return ($val !== false);
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
        // because we don't know the age in memcached we always return false
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
        $k = $this->getMemcKey($key);

        return $this->memc->set($k, $data);
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
        $k = $this->getMemcKey($key);

        return $this->memc->get($k);
    }
    // }}}

    // {{{ getMemcKey */
    protected function getMemcKey($key)
    {
        $keyNs = $this->keyNs;
        $keyCacheItem = "";

        $namespaces = explode("/", $key);
        $last = array_pop($namespaces);

        foreach ($namespaces as $namespace) {
            $keyNs .= "/" . $namespace;

            $counter = $this->memc->get($keyNs);
            if ($counter === false) {
                $counter = mt_rand(1, 10000);
                $this->memc->set($keyNs, $counter);
            }

            $keyCacheItem .= "$namespace~$counter/";

        }
        $keyCacheItem .= $last;

        return $keyCacheItem;
    }
    // }}}
    // {{{ delete */
    public function delete($key)
    {
        $keyNs = $this->keyNs;

        $namespaces = explode("/", $key);
        $last = array_pop($namespaces);

        if ($last != "") {
            // it is just one item - delete directly
            $this->memc->delete($key);
        } else {
            // invalidate namespace with key
            foreach ($namespaces as $namespace) {
                $keyNs .= "/" . $namespace;
            }
            $this->memc->increment($keyNs);
        }
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
        $this->memc->flush();
    }
    // }}}
}

/* vim:set ft=php sts=4 fdm=marker et : */
