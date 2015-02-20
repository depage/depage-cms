<?php
/**
 * @file    jsmin.php
 * @brief   jsmin class
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 **/

namespace Depage\JsMin;

/**
 * @brief Main jsmin class
 **/
abstract class JsMin
{
    // {{{ variables
    private $cache = null;
    // }}}

    // {{{ factory()
    /**
     * @brief   jsmin object factory
     *
     * Generates minify object
     *
     * @param   $options (array) jsmin processing parameters
     * @return  (object) jsmin object
     **/
    public static function factory($options = array()) {
        $extension = (isset($options['extension'])) ? $options['extension'] : 'closureApi';

        if ( strtolower($extension) == 'closurelocal' ) {
            return new Providers\ClosureLocal($options);
        } else {
            return new Providers\ClosureApi($options);
        }
    }
    // }}}
    // {{{ __construct()
    /**
     * @brief graphics class constructor
     *
     * @param $options (array) image processing parameters
     **/
    public function __construct($options = array()) {
        $this->cache = \Depage\Cache\Cache::factory("js");
    }
    // }}}

    // {{{ minifySrc()
    /**
     * @brief minifies js-source
     *
     * @param $src javascript source code
     **/
    abstract public function minifySrc($src);
    // }}}
    // {{{ minifyFiles()
    /**
     * @brief minifies js-source from files
     *
     * @param $src javascript source code
     **/
    public function minifyFiles($name, $files) {
        $src = "";
        $mtimes = $this->getFileModTimes($files);
        $identifier = "{$name}_" . sha1(serialize(array($files, $mtimes))) . ".js";

        if (!($src = $this->cache->getFile($identifier))) {
            foreach ($files as $file) {
                $src .= $this->minifyFile($file);
            }
            $this->cache->setFile($identifier, $src, true);
        }

        return $src;
    }
    // }}}
    // {{{ minifyFile()
    /**
     * @brief minifies js-source from file
     *
     * @param $src javascript source code
     **/
    public function minifyFile($file) {
        $regenerate = false;

        if (($age = $this->cache->age($file)) !== false) {
            $fage = filemtime($file);

            // regenerate cache if one file is newer then the cached file
            $regenerate = $regenerate || $age < $fage;
        } else {
            //regenerate if cache file does not exist
            $regenerate = true;
        }
        if ($regenerate || !($src = $this->cache->getFile($file))) {
            $log = new \Depage\Log\Log();
            $log->log("jsmin: minifying '$file'");
            if (php_sapi_name() == 'cli') {
                fwrite(STDERR, "jsmin: minifying '$file'\n");
            }

            if (preg_match("/\.min\.js$/", $file)) {
                // dont minify already minified files
                $src = file_get_contents($file);
            } else {
                $src = $this->minifySrc(file_get_contents($file));
            }
            $this->cache->setFile($file, $src, false);
        }

        return $src;
    }
    // }}}

    // {{{ getFileModTimes()
    /**
     * @brief gets modification times for files
     *
     * @param $files array of filenames
     **/
    protected function getFileModTimes($files) {
        $mtimes = array();
        foreach ($files as $i => $file) {
            $mtimes[$i] = filemtime($file);
        }

        return $mtimes;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
