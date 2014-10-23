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
    public function __construct($param = array()) {
        if (isset($param['chmod'])) {
            $this->chmod = $param['chmod'];
        }
        //umask($this->chmod ^ 0777);
        $this->setDirChmod();
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
    public function ls($path) {
        $flist = array(
            'dirs'  => array(),
            'files' => array(),
        );

        if ($path == '') {
            $path = '.';
        }

        if ($this->exists($path) && @is_dir($path)) {
            $currentDir = opendir($path);
            while ($entryName = readdir($currentDir)) {
                if ($entryName != '.' && $entryName != '..') {
                    if (@is_dir($path . '/' . $entryName)) {
                        $flist['dirs'][] = $entryName;
                    } elseif (is_file($path . '/' . $entryName)) {
                        $flist['files'][] = $entryName;
                    }
                }
            }
            closedir($currentDir);
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
    public function cd($path) {
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
    public function mkdir($path) {
        global $log;

        $paths = explode('/', $path);
        $actual_path = $paths[0];
        foreach ($paths as $dir) {
            $actual_path .= '/' . $dir;
            if (!file_exists($actual_path)) {
                mkdir($actual_path, $this->dirChmod);
                $this->chmod($actual_path);
            }
        }
    }
    // }}}
    // {{{ chmod
    /**
     * changes the chmodding of a file or a directory
     */
    public function chmod($path, $mod = null) {
        if ($mod == null) {
            if (is_dir($path)) {
                $mod = $this->dirChmod;
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
     * @param   $path (string) path to file or directory
     *
     * @return  $success (bool) true on success, false on error
     */
    public function rm($path) {
        if (file_exists($path) && @is_dir($path)) {
            $currentDir = opendir($path);
            while ($entryName = readdir($currentDir)) {
                if ($entryName != '.' && $entryName!='..') {
                    $this->rm($path . '/' . $entryName);
                }
            }
            closedir($currentDir);
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
     * @param   $source (string) name of source file or directory
     * @param   $target (string) target
     *
     * @return  $success (bool) true on success, false on error
     */
    public function mv($source, $target) {
        if (file_exists($source)) {
            if (!($value = rename($source, $target))) {
                trigger_error("could not rename '$source' to '$target'");
            }
            return $value;
        } else {
            trigger_error("could not rename '$source' to '$target' - source doesn't exist");
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
     * @param   $source (string) name of sourcefile or -directory
     * @param   $target (string) name of targetfile or -directory
     *
     * @return  $success (bool) true on success, false on error
     */
    public function cp($source, $target) {
        if (!file_exists($target)) {
            if (is_dir($source)) {
                if (substr($source, -1) != '/') {
                    $source .= '/';
                }
                if (substr($target, -1) != '/') {
                    $target .= '/';
                }
                $this->mkdir($target);
                $flist = $this->ls($source);
                foreach ($flist['dirs'] as $dir) {
                    $this->cp($source . $dir, $target . $dir);
                }
                foreach ($flist['files'] as $file) {
                    $this->cp($source . $file, $target . $file);
                }
            } else if (is_file($source)) {
                copy($source, $target);
            }
        } else {
            trigger_error("could not copy. target exists:\n$target");
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
    public function exists($path) {
        return file_exists($path);
    }
    // }}}
    // {{{ fileInfo
    public function fileInfo($path) {
        return new \SplFileInfo($path);
    }
    // }}}

    // {{{ readString
    public function readString($path) {
        // @todo stub
    }
    // }}}
    // {{{ writeString
    public function writeString($path, $string) {
        // @todo stub
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
