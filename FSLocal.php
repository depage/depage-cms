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

        $flist['dirs']  = array_values($flist['dirs']);
        $flist['files'] = array_values($flist['files']);

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
        return file_get_contents($path);
    }
    // }}}
    // {{{ writeString
    public function writeString($path, $string) {
        return file_put_contents($path, $string);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
