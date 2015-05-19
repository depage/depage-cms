depage-cache / simple caching class with file and memory backends
=================================================================

depage-cache is a simple caching class with different backends that are interchangable

Features
--------

- simple to use
- saves all serializable objects
- different interchangable backends:
  - File
  - Redis
  - Memcache
  - Memcached
- transparent fallback to file caching if memory caching is not available with the disposition "memory" option
- same calls with memcache and memcached extensions
- unit tested

[![Build Status](https://travis-ci.org/depage/depage-cache.png?branch=master)](https://travis-ci.org/depage/depage-cache)


Prerequisites
-------------

- PHP >= 5.3
- redis extension for redis provider
- Memcache extension for memcache provider
- Memcached extension for memcached provider

For more information
--------------------

- You can fork us at:
  <http://github.com/depage/depage-cache/>

License (dual)
--------------

- GPL2: <http://www.gnu.org/licenses/gpl-2.0.html>
- MIT: <http://www.opensource.org/licenses/mit-license.php>
