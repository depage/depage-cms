<?php

// @note implement delete by wildcard as the following:
// http://stackoverflow.com/questions/1595904/memcache-and-wildcards

namespace Depage\Cache\Providers;

class Memcached extends Memcache
{
    // {{{ init
    protected function init()
    {
        return new \Memcached();
    }
    // }}}
}

/* vim:set ft=php sts=4 fdm=marker et : */
