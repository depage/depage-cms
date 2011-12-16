<?php

// @note implement delete by wildcard as the following:
// http://stackoverflow.com/questions/1595904/memcache-and-wildcards

namespace depage\cache; 

class cache_memcached extends cache_memcache {
    // {{{ init
    protected function init() {
        return new \Memcached();
    }
    // }}}
}

/* vim:set ft=php sts=4 fdm=marker et : */
