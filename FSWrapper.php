<?php

namespace Depage\FS;

class FSWrapper extends FS implements FSInterface {
    // {{{ constructor
    public function __construct($url, $params = array()) {
        parent::__construct($params);

        $this->url = (substr($url, -1) == '/') ? $url : $url . '/';
    }
    // }}}

    // {{{ ls
    public function ls($path) {
        $ls = scandir($this->url . $path);
        natcasesort($ls);

        return $ls;
    }
    // }}}
    // {{{ lsDir
    public function lsDir($path) {
        // @todo slow
        $ls     = scandir($this->url . $path);
        $lsDir  = array_filter(
            $ls,
            function ($element) {
                return is_dir($this->url . $element);
            }
        );
        natcasesort($lsDir);

        return $lsDir;
    }
    // }}}
    // {{{ lsFiles
    public function lsFiles($path) {
        // @todo slow
        $ls         = scandir($this->url . $path);
        $lsFiles    = array_filter(
            $ls,
            function ($element) {
                return is_file($this->url . $element);
            }
        );
        natcasesort($lsFiles);

        return $lsFiles;
    }
    // }}}
    // {{{ cd
    /**
     * Changes current directory
     *
     * @public
     *
     * @param $path (string) path of directory to change to
     *
     * @return $success (bool) true on success, false on error
     */
    public function cd($path) {
        // @todo fix
        return chdir($this->url . $path);
    }
    // }}}
    // {{{ mkdir
    /**
     * Creates new directory recursive if it doesn't exist
     *
     * @public
     *
     * @param $path (string) path of new directory
     */
    public function mkdir($path) {
        return mkdir($this->url . $path, $this->dirChmod, true);
    }
    // }}}
    // {{{ chmod
    /**
     * changes the chmodding of a file or a directory
     */
    public function chmod($path, $mod) {
        if ($this->_connect()) {
            $mod = sprintf("%04o", $mod);
            return ftp_site($this->ftpp, "CHMOD $mod $path");
        }
    }
    // }}}
    // {{{ rm
    /**
     * Removes files and directories recursive
     *
     * @public
     *
     * @param $path (string) path to file or directory
     *
     * @return $success (bool) true on success, false on error
     */
    public function rm($path) {
        if ($this->_connect()) {
            if (ftp_size($this->ftpp, $path) == -1) {
                $flist = $this->_get_filelist($path);
                if ($path != '' && $path != '/') {
                    foreach ($flist['dirs'] as $dir) {
                        $this->rm($path . '/' . $dir['name']);
                    }
                    foreach ($flist['files'] as $file) {
                        $this->rm($path . '/' . $file['name']);
                    }
                    if (!($value = @ftp_rmdir($this->ftpp, $path))) {
                        trigger_error("ftp: could not remove '$path'");
                    }
                }
                return $value;
            } else {
                if (!($value = @ftp_delete($this->ftpp, $path))) {
                    trigger_error("ftp: could not remove '$path'");
                }
                return $value;
            }
        } else {
            return false;
        }
    }
    // }}}

    // {{{ mv
    /**
     * Renames or moves file or directory
     *
     * @public
     *
     * @param    $oldname (string) name of source file or directory
     * @param    $newname (string) target
     *
     * @return    $success (bool) true on success, false on error
     */
    public function mv($oldname, $newname) {
        if ($this->_connect()) {
            if (!($value = @ftp_rename($this->ftpp, $oldname, $newname))) {
                trigger_error("ftp: could not rename '$oldname' to '$newname'");
            }
            return $value;
        } else {
            return false;
        }
    }
    // }}}
    // {{{ get
    /**
     * Writes content of a local file to targetfile
     *
     * @public
     *
     * @param    $filepath (string) name of targetfile
     * @param    $sourcefile (string) path to sourcefile
     *
     * @return    $success (bool) true on success, false on error
     */
    public function get($filepath, $sourcefile) {
        // @todo implement both ways
        $errors = 0;

        if ($this->_connect()) {
            $path = pathinfo($filepath);

            $this->mkdir($path['dirname']);

            while ($errors <= $this->num_errors_max) {
                if (!ftp_put($this->ftpp, $filepath, $sourcefile, $this->_getTransferType($filepath))) {
                    $errors++;
                    if ($errors > $this->num_errors_max) {
                        trigger_error("%error_ftp%%error_ftp_write% '$filepath'", E_USER_ERROR);

                        return false;
                    } else {
                        trigger_error("%error_ftp%%error_ftp_write% '$filepath' - retrying $errors", E_USER_NOTICE);

                        $this->_reconnect();
                    }
                } else {
                    $this->chmod($filepath, $this->chmod);

                    return true;
                }
            }
        } else {
            return false;
        }
    }
    // }}}
    // {{{ put
    /**
     * Writes content of a local file to targetfile
     *
     * @public
     *
     * @param    $filepath (string) name of targetfile
     * @param    $sourcefile (string) path to sourcefile
     *
     * @return    $success (bool) true on success, false on error
     */
    public function put($filepath, $sourcefile) {
    }
    // }}}

    // {{{ exists
    /**
     * Checks if file exists
     *
     * @public
     *
     * @param $path (string) path to file to check
     *
     * @return $exist (bool) true if file exists, false otherwise
     */
    public function exists($path) {
        if ($this->_connect()) {
            return (ftp_size($this->ftpp, $path) > -1);
        } else {
            return false;
        }
    }
    // }}}
    // {{{ fileInfo
    public function fileInfo($path) {
        // @todo stub
    }
    // }}}

    // {{{ getString
    public function getString($path) {
        // @todo stub
    }
    // }}}
    // {{{ putString
    /**
     * Writes a String directly to a file
     *
     * @public
     *
     * @param    $filepath (string) name of targetfile
     * @param    $str (string) content to write to file
     *
     * @return    $success (bool) true on success, false on error
     */
    public function putString($filepath, $str) {
        $errors = 0;

        if ($this->_connect()) {
            $path = pathinfo($filepath);

            $this->mkdir($path['dirname']);

            $tempfile = tempnam("", "publ");
            $fp = fopen($tempfile, 'w');
            fwrite($fp, $str);
            fclose($fp);

            while ($errors <= $this->num_errors_max) {
                if (!ftp_put($this->ftpp, $filepath, $tempfile, $this->_getTransferType($filepath))) {
                    $errors++;
                    if ($errors > $this->num_errors_max) {
                        trigger_error("%error_ftp%%error_ftp_write% '$filepath'", E_USER_ERROR);
                        unlink($tempfile);

                        return false;
                    } else {
                        trigger_error("%error_ftp%%error_ftp_write% '$filepath' - retrying", E_USER_NOTICE);

                        $this->_reconnect();
                    }
                } else {
                    $this->chmod($filepath, $this->chmod);
                    unlink($tempfile);

                    return true;
                }
            }
        } else {
            return false;
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
