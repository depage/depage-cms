<?php
/**
 * Implements file system functions on local file system
 */
class fs_local extends fs {
    // {{{ fs_local
    /**
     * Constructor, sets umask to default value on unix-system
     */
    function fs_local($param = array()) {
        if (isset($param['chmod'])) {
            $this->chmod = $param['chmod'];
        }
        //umask($this->chmod ^ 0777);
        $this->set_dirchmod();
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

        if ($path == '') {
            $path = '.';
        }
        
        if (file_exists($path) && @is_dir($path)) {
            $current_dir = opendir($path);
            while ($entryname = readdir($current_dir)) {
                if ($entryname != '.' && $entryname!='..') {
                    if (@is_dir($path . '/' . $entryname)) {
                        $flist['dirs'][] = $entryname;
                    } elseif (is_file($path . '/' . $entryname)) {
                        $flist['files'][] = $entryname;
                    }
                }
            }
            closedir($current_dir);
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
        global $log;

        $paths = explode('/', $path);
        $actual_path = $paths[0];
        foreach ($paths as $dir) {
            $actual_path .= '/' . $dir;
            if (!file_exists($actual_path)) {
                mkdir($actual_path, $this->dirchmod);
                $this->ch_mod($actual_path);
            }
        }
    }
    // }}}
    // {{{ ch_mod
    /**
     * changes the chmodding of a file or a directory
     */
    function ch_mod($path, $mod = null) {
        if ($mod == null) {
            if (is_dir($path)) {
                $mod = $this->dirchmod;
            } else if (is_file($path)) {
                $mod = $this->chmod;
            }
        }
        return chmod($path, $mod);
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
        if (file_exists($path) && @is_dir($path)) {
            $current_dir = opendir($path);
            while ($entryname = readdir($current_dir)) {
                if ($entryname != '.' && $entryname!='..') {
                    $this->rm($path . '/' . $entryname);
                }
            }
            closedir($current_dir);
            return rmdir($path);
        } else if (file_exists($path)) {
            return unlink($path);
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
        global $log;
        if (!@chdir($path)) {
            $log->add_entry("could not change directory to '$path'");
        } 

        return true;
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
        return file_exists($path);
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
        return filesize($path);
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
        return filemtime($path);
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
        if (file_exists($oldname)) {
            if (!($value = rename($oldname, $newname))) {
                trigger_error("could not rename '$oldname' to '$newname'");
            }
            return $value;
        } else {
            trigger_error("could not rename '$oldname' to '$newname' - source don't exist");
            return false;
        }
    }
    // }}}
    // {{{ f_copy
    /**
     * Copies file or directory
     *
     * @public
     *
     * @param    $sourcename (string) name of sourcefile or -directory
     * @param    $targetname (string) name of targetfile or -directory
     *
     * @return    $success (bool) true on success, false on error
     */
    function f_copy($sourcename, $targetname) {
        if (!file_exists($targetname)) {
            if (is_dir($sourcename)) {
                if (substr($sourcename, -1) != '/') {
                    $sourcename .= '/';
                }
                if (substr($targetname, -1) != '/') {
                    $targetname .= '/';
                }
                $this->mk_dir($targetname);
                $flist = $this->list_dir($sourcename);
                foreach ($flist['dirs'] as $dir) {
                    $this->f_copy($sourcename . $dir, $targetname . $dir);
                }
                foreach ($flist['files'] as $file) {
                    $this->f_copy($sourcename . $file, $targetname . $file);
                }
            } else if (is_file($sourcename)) {
                copy($sourcename, $targetname);
            }
        } else {
            trigger_error("could not copy. target exists:\n$targetname");
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
        $path = pathinfo($filepath);
        
        $this->mk_dir($path['dirname']);
        $fp = fopen($filepath, 'w');
        if ($fp) {
            fwrite($fp, $str);
            fclose($fp);
            
            return true;
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
        if (file_exists($sourcefile)) {
            $path = pathinfo($filepath);
            
            $this->mk_dir($path['dirname']);
            return copy($sourcefile, $filepath);
        }
    }
    // }}}
}

