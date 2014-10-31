<?php

namespace Depage\FS;

class FSWrapper extends FS implements FSInterface
{
    // {{{ constructor
    public function __construct($url, $params = array()) {
        parent::__construct($params);

        if (preg_match(';^[a-z0-9]*://;', $url)) {
            $this->root = $url;
        } else {
            $this->root = 'file://' . realpath($url);
        }

        $this->url = (substr($url, -1) == '/') ? $this->root : $this->root . '/';
    }
    // }}}

    // {{{ ls
    public function ls($path = '') {
        $scanDir    = scandir($this->url . $path);
        $ls         = array_diff($scanDir, array('.', '..'));

        natcasesort($ls);
        $sorted = array_values($ls);

        return $sorted;
    }
    // }}}
    // {{{ lsDir
    public function lsDir($path = '') {
        // @todo slow
        $ls     = $this->ls($path);
        $lsDir  = array_filter(
            $ls,
            function ($element) use ($path) {
                return is_dir($this->url . $path . '/' . $element);
            }
        );
        natcasesort($lsDir);
        $sorted = array_values($lsDir);

        return $sorted;
    }
    // }}}
    // {{{ lsFiles
    public function lsFiles($path = '') {
        // @todo slow
        $ls         = $this->ls($path);
        $lsFiles    = array_filter(
            $ls,
            function ($element) use ($path) {
                return is_file($this->url . $path . '/' . $element);
            }
        );
        natcasesort($lsFiles);
        $sorted = array_values($lsFiles);

        return $sorted;
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
    public function chmod($path, $mod = null) {
        // won't work on remote files
        if ($mod == null) {
            if (is_dir($this->url . $path)) {
                $mod = $this->dirChmod;
            } else if (is_file($this->url . $path)) {
                $mod = $this->chmod;
            }
        }
        return chmod($this->url . $path, $mod);
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
        $remote = $this->url . $path;

        if (file_exists($remote)) {
            if (is_dir($remote)) {
                foreach (scandir($remote) as $nested) {
                    $this->rm($path . '/' .  $nested);
                }
                return rmdir($remote);
            } else if (is_file($remote)) {
                return unlink($remote);
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
     * @param    $source (string) name of source file or directory
     * @param    $target (string) target
     *
     * @return    $success (bool) true on success, false on error
     */
    public function mv($source, $target) {
        $source = $this->url . $source;
        $target = $this->url . $target;

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
     * Writes content of a local file to targetfile
     *
     * @public
     *
     * @param    $filepath (string) name of targetfile
     * @param    $sourcefile (string) path to sourcefile
     *
     * @return    $success (bool) true on success, false on error
     */
    public function get($remote, $local = null) {
        $pathInfo = pathinfo($remote);
        $fileName = $pathInfo['filename'];

        if ($local === null) {
            $local = $fileName;
        }

        return copy($this->url . $remote, $local);
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
    public function put($local, $remote) {
        return copy($local, $this->url . $remote);
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
        return file_exists($this->url . $path);
    }
    // }}}
    // {{{ fileInfo
    public function fileInfo($path) {
        return new \SplFileInfo($this->url . $path);
    }
    // }}}

    // {{{ getString
    public function getString($path) {
        return file_get_contents($this->url . $path);
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
    public function putString($path, $string) {
        return file_put_contents($this->url . $path, $string);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
