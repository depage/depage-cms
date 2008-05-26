<?php
/**
 * @file    lib_funccache.php
 *
 * Function Caching Library
 *
 * This defines a class to cache function results and give 
 * result from cache if available. This is mainly used if
 * there is database content, that is only available on the
 * depage server, so that content can be cached for the live
 * server without the need to duplicate the database.
 *
 *
 * copyright (c) 2008-2008 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

/* {{{ file_put_contents() */
if (!function_exists("file_put_contents")) {
    function file_put_contents($file, $data) {
        $fh = fopen($file, "w");
        fwrite($fh, $data);
        fclose($fh);
    }
}
/* }}} */

class func_cache {
    var $cache_path = ".";
    var $force_call = false;

    /* {{{ constructor */
    function func_cache($cache_path = ".", $force_call = false) {
        $this->cache_path = $cache_path;
        $this->force_call = $force_call;
    }
    /* }}} */
    /* {{{ call() */
    function call($func, $args = array()) {
        global $log;

        if (is_array($func)) {
            $func_name = $func[0] . "::" . $func[1];
        } else {
            $func_name = $func;
        }

        $args_serialized = serialize($args);
        $args_digest = sha1($args_serialized);

        $cache_file = "{$this->cache_path}/${func}_${args_digest}.ser";

        if (!$this->force_call) {
            if (file_exists($cache_file)) {
                $value = unserialize(file_get_contents($cache_file));

                return $value;
            }
        } 

        $log->add_entry("call uncached: $func_name");
        $log->add_varinfo($args);
        $value = call_user_func_array($func, $args);

        file_put_contents($cache_file, serialize($value));

        return $value;
    }
    /* }}} */
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
