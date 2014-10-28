<?php

namespace Depage\FS;

/**
 * Implements file system functions on local file system
 */
class FSLocal extends FS implements FSInterface {
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
        $flist = array();

        if ($path == '') {
            $path = '.';
        }

        if ($this->exists($path) && @is_dir($path)) {
            $currentDir = opendir($path);
            while ($entryName = readdir($currentDir)) {
                if ($entryName != '.' && $entryName != '..') {
                    $flist[] = $entryName;
                }
            }
            closedir($currentDir);
        }
        natcasesort($flist);

        $flist = array_values($flist);

        return $flist;
    }
    // }}}
    // {{{ lsDir
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
    public function lsDir($path) {
        $flist = array();

        if ($path == '') {
            $path = '.';
        }

        if ($this->exists($path) && @is_dir($path)) {
            $currentDir = opendir($path);
            while ($entryName = readdir($currentDir)) {
                if ($entryName != '.' && $entryName != '..') {
                    if (@is_dir($path . '/' . $entryName)) {
                        $flist[] = $entryName;
                    }
                }
            }
            closedir($currentDir);
        }
        natcasesort($flist);

        $flist = array_values($flist);

        return $flist;
    }
    // }}}
    // {{{ lsFiles
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
    public function lsFiles($path) {
        $flist = array();

        if ($path == '') {
            $path = '.';
        }

        if ($this->exists($path) && @is_dir($path)) {
            $currentDir = opendir($path);
            while ($entryName = readdir($currentDir)) {
                if ($entryName != '.' && $entryName != '..') {
                    if (is_file($path . '/' . $entryName)) {
                        $flist[] = $entryName;
                    }
                }
            }
            closedir($currentDir);
        }
        natcasesort($flist);

        $flist = array_values($flist);

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
        return @chdir($path);
    }
    // }}}
    // {{{ mkdir
    /**
     * Creates new directory recursively if it doesn't exist
     *
     * @public
     *
     * @param $path (string) path of new directory
     */
    public function mkdir($path) {
        return mkdir($path, $this->dirChmod, true);
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
     * Recursively removes files and directories
     *
     * @public
     *
     * @param   $path (string) path to file or directory
     *
     * @return  $success (bool) true on success, false on error
     */
    public function rm($path) {
        if (file_exists($path)) {
            if (is_dir($path)) {
                foreach (glob($path . '/*') as $nested) {
                    $this->rm($nested);
                }
                return rmdir($path);
            } else if (is_file($path)) {
                return unlink($path);
            }
        }
        return false;
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
    // {{{ get
    /**
     * Copies file or directory
     *
     * @public
     *
     * @param   $remote (string) name of sourcefile or -directory
     * @param   $local (string) name of targetfile or -directory
     *
     * @return  $success (bool) true on success, false on error
     */
    public function get($remote, $local) {
        if (!file_exists($local)) {
            if (is_dir($remote)) {
                if (substr($remote, -1) != '/') {
                    $remote .= '/';
                }
                if (substr($local, -1) != '/') {
                    $local .= '/';
                }
                $this->mkdir($local);
                $flist = $this->ls($remote);
                foreach ($flist['dirs'] as $dir) {
                    $this->cp($remote . $dir, $local . $dir);
                }
                foreach ($flist['files'] as $file) {
                    $this->cp($remote . $file, $local . $file);
                }
            } else if (is_file($remote)) {
                copy($remote, $local);
            }
        } else {
            trigger_error("could not copy. target exists:\n$local");
            return false;
        }
    }
    // }}}
    // {{{ put
    /**
     * Copies file or directory
     *
     * @public
     *
     * @param   $local (string) name of sourcefile or -directory
     * @param   $remote (string) name of targetfile or -directory
     *
     * @return  $success (bool) true on success, false on error
     */
    public function put($local, $remote) {
        if (!file_exists($remote)) {
            if (is_dir($local)) {
                if (substr($local, -1) != '/') {
                    $local .= '/';
                }
                if (substr($remote, -1) != '/') {
                    $remote .= '/';
                }
                $this->mkdir($remote);
                $flist = $this->ls($local);
                foreach ($flist['dirs'] as $dir) {
                    $this->cp($local . $dir, $remote . $dir);
                }
                foreach ($flist['files'] as $file) {
                    $this->cp($local . $file, $remote . $file);
                }
            } else if (is_file($local)) {
                copy($local, $remote);
            }
        } else {
            trigger_error("could not copy. target exists:\n$remote");
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

    // {{{ getString
    public function getString($path) {
        return file_get_contents($path);
    }
    // }}}
    // {{{ putString
    public function putString($path, $string) {
        return file_put_contents($path, $string);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
