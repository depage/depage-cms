<?php

namespace Depage\FS;

class FSWrapper extends FS implements FSInterface
{
    // {{{ variables
        protected $current;
    // }}}
    // {{{ constructor
    public function __construct($url, $params = array())
    {
        parent::__construct($params);

        if ($parsed = parse_url($url)) {
            if (isset($parsed['scheme'])) {
                $this->scheme   = $parsed['scheme'];
                $this->base     = isset($parsed['path']) ? $parsed['path'] : null;
            } else {
                $this->scheme   = 'file';
                $this->base     = realpath($url);
            }

            $this->user = isset($parsed['user']) ? $parsed['user'] : null;
            $this->pass = isset($parsed['pass']) ? $parsed['pass'] : null;
            $this->host = isset($parsed['host']) ? $parsed['host'] : null;
            $this->port = isset($parsed['port']) ? $parsed['port'] : null;

            // @todo clean up
            $this->base = (substr($url, -1) == '/') ? $this->base : $this->base . '/';
        }
    }
    // }}}

    // {{{ pwd
    public function pwd()
    {
        $url = array(
            'scheme'    => $this->scheme,
            'user'      => $this->user,
            'pass'      => $this->pass,
            'host'      => $this->host,
            'path'      => $this->base . $this->current,
        );

        return $this->buildUrl($url);
    }
    // }}}
    // {{{ ls
    public function ls($path = '')
    {
        $scanDir    = scandir($this->pwd() . $path);
        $ls         = array_diff($scanDir, array('.', '..'));

        natcasesort($ls);
        $sorted = array_values($ls);

        return $sorted;
    }
    // }}}
    // {{{ lsDir
    public function lsDir($path = '')
    {
        // @todo slow
        $ls     = $this->ls($path);
        $lsDir  = array_filter(
            $ls,
            function ($element) use ($path) {
                return is_dir($this->pwd() . $path . '/' . $element);
            }
        );
        natcasesort($lsDir);
        $sorted = array_values($lsDir);

        return $sorted;
    }
    // }}}
    // {{{ lsFiles
    public function lsFiles($path = '')
    {
        // @todo slow
        $ls         = $this->ls($path);
        $lsFiles    = array_filter(
            $ls,
            function ($element) use ($path) {
                return is_file($this->pwd() . $path . '/' . $element);
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
    public function cd($url)
    {
        $parsed = parse_url($url);

        $scheme = isset($parsed['scheme'])  ? $parsed['scheme'] : null;
        $path   = isset($parsed['path'])    ? $parsed['path']   : null;

        if ($scheme) {
            $newUrl = $url;
        } else {
            $newUrl = array(
                'scheme'    => $this->scheme,
                'user'      => $this->user,
                'pass'      => $this->pass,
                'host'      => $this->host,
            );

            if ($path) {
                if ($path[0] == '/') {
                    $newUrl['path'] = $path;
                } else {
                    $newUrl['path'] = $this->base . $this->current . '/' . $path;
                }
            }
        }

        $newUrl['path'] = $this->cleanPath($newUrl['path']);

        if (is_dir($this->buildUrl($newUrl))) {
            if (preg_match(';^' . preg_quote($this->base) . '(.*)$;', $newUrl['path'], $matches)) {
                $this->current = $matches[1];
                return true;
            }
        }
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
    public function mkdir($path)
    {
        return mkdir($this->pwd() . $path, $this->dirChmod, true);
    }
    // }}}
    // {{{ chmod
    /**
     * changes the chmodding of a file or a directory
     */
    public function chmod($path, $mod = null)
    {
        // won't work on remote files
        if ($mod == null) {
            if (is_dir($this->pwd() . $path)) {
                $mod = $this->dirChmod;
            } else if (is_file($this->pwd() . $path)) {
                $mod = $this->chmod;
            }
        }
        return chmod($this->pwd() . $path, $mod);
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
    public function rm($path)
    {
        $remote = $this->pwd() . $path;

        if (is_dir($remote)) {
            foreach ($this->ls($path) as $nested) {
                $this->rm($path . '/' .  $nested);
            }

            if ($this->scheme == 'file') {
                // php bug hack
                $remote = preg_replace(';^file://;', '', $remote);
            }

            return rmdir($remote);
        } else if (is_file($remote)) {
            return unlink($remote);
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
    public function mv($source, $target)
    {
        $source = $this->pwd() . $source;
        $target = $this->pwd() . $target;

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
    public function get($remote, $local = null)
    {
        $pathInfo = pathinfo($remote);
        $fileName = $pathInfo['filename'];

        if ($local === null) {
            $local = $fileName;
        }

        return copy($this->pwd() . $remote, $local);
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
    public function put($local, $remote)
    {
        return copy($local, $this->pwd() . $remote);
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
    public function exists($path)
    {
        return file_exists($this->pwd() . $path);
    }
    // }}}
    // {{{ fileInfo
    public function fileInfo($path)
    {
        return new \SplFileInfo($this->pwd() . $path);
    }
    // }}}

    // {{{ getString
    public function getString($path)
    {
        return file_get_contents($this->pwd() . $path);
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
    public function putString($path, $string)
    {
        return file_put_contents($this->pwd() . $path, $string);
    }
    // }}}

    // {{{ cleanPath
    public function cleanPath($path)
    {
        // @todo handle backslashes
        $dirs       = explode('/', $path);
        $newDirs    = array();

        foreach ($dirs as $dir) {
            if ($dir == '..') {
                array_pop($newDirs);
            } else if ($dir != '.' && $dir != '') {
                $newDirs[] = $dir;
            }
        }

        $newPath = ($path[0] == '/') ? '/' : '';
        $newPath .= implode('/', $newDirs);

        return $newPath;
    }
    // }}}
    // {{{ pwd
    public function buildUrl($parsed)
    {
        $path = $parsed['scheme'] . '://';
        $path .= isset($parsed['user']) ? $parsed['user']       : '';
        $path .= isset($parsed['pass']) ? ':' . $parsed['pass'] : '';
        $path .= isset($parsed['user']) ? '@'                   : '';
        $path .= isset($parsed['host']) ? $parsed['host']       : '';
        $path .= isset($parsed['port']) ? ':' . $this->port     : '';
        $path .= isset($parsed['path']) ? $parsed['path']       : '/';

        return $path;
    }
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
