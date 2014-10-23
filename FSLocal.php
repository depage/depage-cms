<?php

namespace Depage\FS;

/**
 * Implements file system functions on local file system
 */
class FSLocal extends FS implements FSInterface {
    // {{{ constructor
    /**
     * Constructor, sets umask to default value on unix-system
     */
    function __construct($param = array()) {
        if (isset($param['chmod'])) {
            $this->chmod = $param['chmod'];
        }
        //umask($this->chmod ^ 0777);
        $this->set_dirchmod();
    }
    // }}}

    // {{{ ls
    /**
     * Gets directroy listing
     *
     * @public
     *
     * @param   $path (string) path of directory. if not given, the function
     *          lists the content of the actual directory '.'.
     *
     * @return  $flist (array) contains 2 subarrays 'dirs' and 'files'
     */
    function ls($path) {
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
    function cd($path) {
        global $log;
        if (!@chdir($path)) {
            $log->add_entry("could not change directory to '$path'");
        }

        return true;
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
    function mkdir($path) {
        global $log;

        $paths = explode('/', $path);
        $actual_path = $paths[0];
        foreach ($paths as $dir) {
            $actual_path .= '/' . $dir;
            if (!file_exists($actual_path)) {
                mkdir($actual_path, $this->dirchmod);
                $this->chmod($actual_path);
            }
        }
    }
    // }}}
    // {{{ chmod
    /**
     * changes the chmodding of a file or a directory
     */
    function chmod($path, $mod = null) {
        if ($mod == null) {
            if (is_dir($path)) {
                $mod = $this->dirchmod;
            } else if (is_file($path)) {
                $mod = $this->chmod;
            }
        }
        return \chmod($path, $mod);
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
    function mv($oldname, $newname) {
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
    // {{{ cp
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
    function cp($sourcename, $targetname) {
        if (!file_exists($targetname)) {
            if (is_dir($sourcename)) {
                if (substr($sourcename, -1) != '/') {
                    $sourcename .= '/';
                }
                if (substr($targetname, -1) != '/') {
                    $targetname .= '/';
                }
                $this->mkdir($targetname);
                $flist = $this->ls($sourcename);
                foreach ($flist['dirs'] as $dir) {
                    $this->cp($sourcename . $dir, $targetname . $dir);
                }
                foreach ($flist['files'] as $file) {
                    $this->cp($sourcename . $file, $targetname . $file);
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
    function exists($path) {
        return file_exists($path);
    }
    // }}}
    // {{{ fileInfo
    function fileInfo($path) {
        return new \SplFileInfo($path);
    }
    // }}}

    // {{{ readString
    function readString($path) {
        // @todo stub
    }
    // }}}
    // {{{ writeString
    function writeString($path, $string) {
        // @todo stub
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
