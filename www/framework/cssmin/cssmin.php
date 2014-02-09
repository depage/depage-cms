<?php
/**
 * @file    cssmin.php
 * @brief   cssmin class
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 **/

namespace depage\cssmin;

/**
 * @brief Main cssmin class
 **/
abstract class cssmin {
    // {{{ variables
    private $cache = null;
    // }}}
    
    // {{{ factory()
    /**
     * @brief   cssmin object factory
     * 
     * Generates minify object
     *
     * @param   $options (array) cssmin processing parameters
     * @return  (object) cssmin object
     **/
    public static function factory($options = array()) {
        $extension = (isset($options['extension'])) ? $options['extension'] : 'cssminLocal';

        return new providers\cssminLocal($options);
    }
    // }}}
    // {{{ __construct()
    /**
     * @brief graphics class constructor
     *
     * @param $options (array) image processing parameters
     **/
    public function __construct($options = array()) {
        $this->cache = \depage\cache\cache::factory("css");
    }
    // }}}
    
    // {{{ minifySrc()
    /**
     * @brief minifies css-source
     *
     * @param $src javascript source code
     **/
    abstract public function minifySrc($src);
    // }}}
    // {{{ minifyFiles()
    /**
     * @brief minifies css-source from files
     *
     * @param $src javascript source code
     **/
    public function minifyFiles($name, $files) {
        $src = "";
        $identifier = "{$name}_" . sha1(serialize($files)) . ".css";
        
        $regenerate = false;

        if (($age = $this->cache->age($identifier)) !== false) {
            foreach ($files as $file) {
                $fage = filemtime($file);
                
                // regenerate cache if one file is newer then the cached file
                $regenerate = $regenerate || $age < $fage;
            }
        } else {
            //regenerate if cache file does not exist
            $regenerate = true;
        }
        if ($regenerate || !($src = $this->cache->getFile($identifier))) {
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
     * @brief minifies css-source from file
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
            $log = new \depage\log\log();
            $log->log("cssmin: minifying '$file'");

            $src = $this->minifySrc(file_get_contents($file));
            $this->cache->setFile($file, $src, true);
        }

        return $src;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
