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
    protected $chmod = 0664;

    // {{{ constructor
    /**
     * Constructor, sets umask to default value on unix-system
     */
    public function __construct($param = array()) {
        if (isset($param['chmod'])) {
            $this->chmod = $param['chmod'];
        }

        $this->setDirChmod();
    }
    // }}}
    // {{{ factory
    /**
     * creates new filesystem object
     *
     * @public
     *
     * @param    $driver (string) type of fs object (local or ftp)
     * @param    $param (array) array of parameter
     */
    public function factory($driver, $param = array()) {
        $class = "Depage\FS\FS{$driver}";

        return new $class($param);
    }
    // }}}

    // {{{ getMaxUploadFileSize
    public function getMaxUploadFileSize() {
        $post_max = fs::getSizeInBytes(ini_get('post_max_size'));
        $file_max = fs::getSizeInBytes(ini_get('upload_max_filesize'));

        return fs::formatFilesize($post_max < $file_max ? $post_max : $filemax);
    }
    // }}}
    // {{{ setDirChmod
    protected function setDirChmod() {
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
