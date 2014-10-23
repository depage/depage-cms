<?php

namespace Depage\FS;

/**
 * @file    FS.php
 *
 * File System Library
 *
 * This file defines Classes for accessing different file
 * systems like the local file system or an ftp filesystem
 * with same function calls.
 *
 *
 * copyright (c) 2002-2010 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 * @author    Sebastian Reinhold [sebastian@bitbernd.de]
 */

/**
 * Parent class for all other fs_classes
 */
abstract class FS {
    var $chmod = 0664;

    // {{{ factory
    /**
     * creates new filesystem object
     *
     * @public
     *
     * @param    $driver (string) type of fs object (local or ftp)
     * @param    $param (array) array of parameter
     */
    function factory($driver, $param = array()) {
        $class = "Depage\FS\FS{$driver}";

        return new $class($param);
    }
    // }}}

    // {{{ formatFilesize
    /**
     * Formats the size of a file in B/KB/MB/GB
     *
     * @public
     *
     * @param    $size (int) size of file to format
     *
     * @return    $size (string) formatted size string
     */
    function formatFilesize($size) {
        $kb = 1024;         // Kilobyte
        $mb = 1024 * $kb;   // Megabyte
        $gb = 1024 * $mb;   // Gigabyte
        $tb = 1024 * $gb;   // Terabyte
           
        if($size < $kb) {
            return $size . ' B';
        } else if($size < $mb) {
            return round($size/$kb, 0) . ' KB';
        } else if($size < $gb) {
            return round($size/$mb, 1) . ' MB';
        } else if($size < $tb) {
            return round($size/$gb, 1) . ' GB';
        } else {
            return round($size/$tb, 1) . ' TB';
        }
    }
    // }}}
    // {{{ getSizeInBytes
    function getSizeInBytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        switch($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
    // }}}
    // {{{ getMaxUploadFileSize
    function getMaxUploadFileSize() {
        $post_max = fs::getSizeInBytes(ini_get('post_max_size'));
        $file_max = fs::getSizeInBytes(ini_get('upload_max_filesize'));

        return fs::formatFilesize($post_max < $file_max ? $post_max : $filemax);
    }
    // }}}
    // {{{ f_size_format
    /**
     * Gets size of a file in B/KB/MB/GB
     *
     * @public
     *
     * @param    $path (string) path to file
     *
     * @return    $size (string) filesize string
     */
    function f_size_format($path) {
        return $this->formatFilesize($this->f_size($path));
    }
    // }}}
    // {{{ setDirChmod
    function setDirChmod() {
        global $log;

        $this->dirChmod = $this->chmod;
        if (($this->chmod & 0400) == 0400) {
            $this->dirChmod = 0100 | $this->dirChmod;
        } 
           if (($this->chmod & 0040) == 0040) {
            $this->dirChmod = 0010 | $this->dirChmod;
        } 
           if (($this->chmod & 0004) == 0004) {
            $this->dirChmod = 0001 | $this->dirChmod;
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
