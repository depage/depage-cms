<?php

/**
 * Implements file system functions on remote ftp filesystem
 */
class fs_ftp extends fs {
    // {{{ variables
    var $chmod = 0644;
    var $num_errors_max = 3; 
    var $login_errors = 0;
    var $connected = false;
    // }}}

    // {{{ fs_ftp
    /**
     * Constructor, sets parameter needed for connection
     *
     * @param    $server (string) name of ftp-server
     * @param    $port (string) port of ftp-server
     * @param    $user (string) authentication user
     * @param    $pass (string) authenticaion
     */
    function fs_ftp($param) {
        $this->server = $param['host'];
        $this->port = $param['port'];
        $this->user = $param['user'];
        $this->pass = $param['pass'];
        if (isset($param['chmod'])) {
            $this->chmod = $param['chmod'];
        }
        $this->set_dirchmod();
    }
    // }}}

    // {{{ _connect
    /**
     * connects to ftp server if connection isnt established
     *
     * @private
     *
     * @return    $success (bool) true on success, false on error.
     */
    function _connect() {
        if (!$this->connected) {
            while (!$this->connected && $this->login_errors <= $this->num_errors_max) {
                $this->ftpp = @ftp_connect($this->server);
                if (!$this->ftpp) {
                    $this->login_errors++;
                    sleep(2);
                    continue;
                }
                if (!@ftp_login($this->ftpp, $this->user, $this->pass)) {
                    ftp_close($this->ftpp);
                    if ($this->login_errors <= $this->num_errors_max) {
                        $this->login_errors++;
                        sleep(2);
                        continue;
                    } else {
                        trigger_error("%error_ftp%%error_ftp_login% '$this->user@$this->server'.", E_USER_ERROR);
                    }
                }
                @ftp_pasv($this->ftpp, true);
                register_shutdown_function(array(&$this, '_disconnect'));
                
                $this->connected = true;
            }
            if (!$this->connected) {
                trigger_error("%error_ftp%%error_ftp_connect% '$this->server'.", E_USER_ERROR);
            }
            
            return $this->connected;
        } else {
            return true;
        }
    }
    // }}}
    // {{{ _disconnect
    /**
     * disconnects from ftp server, if is connected
     * registered for shutdown by function _connect
     *
     * @private
     */
    function _disconnect() {
        if ($this->connected) {
            ftp_close($this->ftpp);
            $this->connected = false;
        }
    }
    // }}}
    // {{{ _reconnect
    /**
     * reconnects to ftp-server after 3 second sleep
     *
     * @private
     */
    function _reconnect() {
        $this->_disconnect();
        sleep(3);
        $this->_connect();
    }
    // }}}
    // {{{ _getTransferType
    /**
     * get type of transfer (ascii | binary) by extension of file
     *
     * @private
     *
     * @param    $filename (string) name of file
     *
     * @return    $type (int) FTP_ASCII for ascii and FTP_BINARY for binary
     */
    function _getTransferType($filename) {
        $textTypes = array(
            'txt',
            'htm', 'html',
            'css',
            'js',
            
            'cgi', 'shtml', 
            'php', 'php3', 'php4', 'phtm', 'phtml', 'phps', 'inc',
            'pl', 'pm', 
            
            'xml', 'xsl', 'dtd',
            'c', 'h',
            'conf', 'ini',
            'sql', 'csv', 
            'htaccess', 'htpasswd',
            'log',
            'nfo',
        );
        
        if (in_array(strtolower(substr($filename, strrpos($filename, '.') + 1)), $textTypes)) {
            return FTP_ASCII;
        } else {
            return FTP_BINARY;
        }
    }
    // }}}

    // {{{ _get_filelist
    /**
     * gets files in a directory
     *
     * @private
     *
     * @param    $path (string) path to file
     *
     * @return    $filelist (array) which contains to other array 'dirs' and 'files'
     */
    function _get_filelist($path) {
        global $log;

        $dirs_list = array();
        $files_list = array();
        $dir_list = ftp_rawlist($this->ftpp, $path . "/");
        foreach ($dir_list as $entry) {
            // ([1] = directory?, [2] = rights, [3] = files below, [4] = user,
            //  [5] = group, [6] = size, [7] = date, [8]  = name)
            $res_1 = @ereg("([-dl])([rwx-]{9})[ ]*([0-9]*)[ ]*([a-zA-Z0-9_-]*)[ ]*([a-zA-Z0-9_-]*)[ ]*([0-9]*)[ ]*([A-Za-z]+ [0-9: ]*) (.+)", $entry, $eregs);
            if (!$res_1) {
                trigger_error("Raw directory-list in wrong format.");
            }
            $is_dir = (@trim($eregs[1]) == "d");
            // snip link-locations (have to clean that up later)
            if (@trim($eregs[1]) == "l") {
                preg_match("/(.*) -> (.*)/", $eregs[8], $matches);
                $eregs[8] = $matches[1];
            }
            
            $date = $this->_parse_date($eregs[7]);
            // $date = $eregs[7];
            if (!$date) {
                trigger_error("Can not parse date from raw directory-list on '$dir'.");
            }
            if ($eregs[8] != '.' && $eregs[8] != '..') {
                if ($is_dir) {
                    $dirs_list[] = array("name"         =>  $eregs[8],
                                        "rights"        =>  $eregs[2],
                                        "user"          =>  $eregs[4],
                                        "group"         =>  $eregs[5],
                                        "files_inside"  =>  $eregs[3],
                                        "date"          =>  $date,
                                        "is_dir"        =>  $is_dir);
                } else if ($eregs[8] != null) {
                    $files_list[] = array("name"        =>  $eregs[8],
                                         "size"         =>  (int)$eregs[6],
                                         "rights"       =>  $eregs[2],
                                         "user"         =>  $eregs[4],
                                         "group"        =>  $eregs[5],
                                         "date"         =>  $date,
                                         "is_dir"       =>  $is_dir);
                }
            }
        }
        usort($dirs_list, array($this, "compare_ftp_listing"));
        usort($files_list, array($this, "compare_ftp_listing"));
        $res["dirs"] = $dirs_list;
        $res["files"] = $files_list;
        
        return $res;
    }
    // }}}
    // {{{ compare_ftp_listing
    function compare_ftp_listing($a, $b) {
        return strcmp($a["name"], $b["name"]);
    }
    // }}}
    // {{{ _parse_date
    /**
     * parses a date out of a filelisting by a unixlike ftp server
     *
     * @private
     *
     * @param    $date (string) datestring
     *
     * @return    $date (int) date in a unix timestamp
     */
    function _parse_date($date) {
        // Sep 10 22:06 => Sep 10, <year> 22:06
        if (preg_match("/([A-Za-z]+)[ ]+([0-9]+)[ ]+([0-9]+):([0-9]+)/", $date, $res)) {
            $year = date("Y");
            $month = $res[1];
            $day = $res[2];
            $hour = $res[3];
            $minute = $res[4];
            $date = "$month $day, $year $hour:$minute";
        }
        $res = strtotime($date);
        if (!$res) {
            trigger_error("Dateconversion failed.");
        }
        return $res;
    }
    // }}}

    // {{{ list_dir
    /**
     * Gets directroy listing
     *
     * @public
     *
     * @param    $path (string) path of directory. if not given, the function
     *            lists the content of the actual directory '.'.    
     *
     * @return    $flist (array) contains 2 subarrays 'dirs' and 'files'
     */
    function list_dir($path) {
        $flist = array(
            'dirs' => array(),
            'files' => array(),
        );

        $temp_flist = $this->_get_filelist($path);
        foreach ($temp_flist['dirs'] as $dir) {
            if ($dir['name'] != '.' && $dir['name'] != '..') {
                $flist['dirs'][] = $dir['name'];
            }
        }
        foreach ($temp_flist['files'] as $file) {
            $flist['files'][] = $file['name'];
        }
        
        natcasesort($flist['dirs']);
        natcasesort($flist['files']);
        
        return $flist;
    }
    // }}}
    // {{{ mk_dir
    /**
     * Creates new directory recursive if it doesn't exist
     *
     * @public
     *
     * @param $path (string) path of new directory
     */
    function mk_dir($path) {
        if ($this->_connect()) {
            $paths = explode('/', $path);
            $actual_path = $paths[0];
            foreach ($paths as $dir) {
                $actual_path .= '/' . $dir;
                if (ftp_mkdir($this->ftpp, $actual_path)) {
                    $this->ch_mod($actual_path, $this->dirchmod);
                }
            }
        }
    }
    // }}}
    // {{{ ch_mod
    /**
     * changes the chmodding of a file or a directory
     */
    function ch_mod($path, $mod) {
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
    function rm($path) {
        global $conf, $log;
        
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
    // {{{ ch_dir
    /**
     * Changes current directory
     *
     * @public
     *
     * @param $path (string) path of directory to change to
     *
     * @return $success (bool) true on success, false on error
     */
    function ch_dir($path) {
        if ($this->_connect()) {
            if (!($value = @ftp_chdir($this->ftpp, $path))) {
                trigger_error("ftp: could not change dir to '$path'");
            }
            return $value;
        } else {
            return false;
        }
    }
    // }}}
    // {{{ f_exists
    /**
     * Checks if file exists
     *
     * @public
     *
     * @param $path (string) path to file to check
     *
     * @return $exist (bool) true if file exists, false otherwise
     */
    function f_exists($path) {
        if ($this->_connect()) {
            return (ftp_size($this->ftpp, $path) > -1);
        } else {
            return false;
        }
    }
    // }}}
    // {{{ f_size
    /**
     * Gets size of a file
     *
     * @public
     *
     * @param    $path (string) path to file
     *
     * @return    $size (int) size in bytes
     */
    function f_size($path) {
        if ($this->_connect()) {
            return ftp_size($this->ftpp, $path);
        } else {
            return -1;
        }
    }
    // }}}
    // {{{ f_mtime
    /**
     * Gets last modification date of file
     *
     * @public
     *
     * @param    $path (string) path to file
     *
     * @return    $date (int) unix timestamp of file modification date
     */
    function f_mtime($path) {
        if ($this->_connect()) {
            return ftp_mdtm($this->ftpp, $path);
        } else {
            return false;
        }
    }
    // }}}
    // {{{ f_rename
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
    function f_rename($oldname, $newname) {
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
    // {{{ f_write_string
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
    function f_write_string($filepath, $str) {
        $errors = 0;

        if ($this->_connect()) {
            $path = pathinfo($filepath);
            
            $this->mk_dir($path['dirname']);
            
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
                    $this->ch_mod($filepath, $this->chmod);
                    unlink($tempfile);

                    return true;
                }
            }
        } else {
            return false;
        }
    }
    // }}}
    // {{{ f_write_file
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
    function f_write_file($filepath, $sourcefile) {
        $errors = 0;

        if ($this->_connect()) {
            $path = pathinfo($filepath);
            
            $this->mk_dir($path['dirname']);
            
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
                    $this->ch_mod($filepath, $this->chmod);

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
