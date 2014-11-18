<?php

namespace Depage\FS;

class FS
{
    // {{{ variables
        protected $currentPath;
        protected $base;
        protected $url;
    // }}}
    // {{{ constructor
    public function __construct($url, $params = array())
    {
        $parsed = parse_url($url);
        $path = isset($parsed['path']) ? $parsed['path'] : '';
        unset($parsed['path']);

        $this->url = $parsed;
        if (isset($params['scheme']))   $this->url['scheme']    = $params['scheme'];
        if (isset($params['user']))     $this->url['user']      = $params['user'];
        if (isset($params['pass']))     $this->url['pass']      = $params['pass'];
        if (isset($params['host']))     $this->url['host']      = $params['host'];
        if (isset($params['port']))     $this->url['port']      = $params['port'];

        if (!isset($this->url['scheme'])) {
            $this->url['scheme'] = 'file';
            // @todo handle failed realpath
            $path = realpath($path);
        }

        $this->base = $this->cleanPath($path);
    }
    // }}}

    // {{{ pwd
    public function pwd()
    {
        $url = $this->url;
        $url['path'] = $this->base . $this->currentPath;

        return $this->buildUrl($url);
    }
    // }}}
    // {{{ ls
    public function ls($path)
    {
        return $this->lsRecursive($path, '');
    }
    // }}}
    // {{{ lsDir
    public function lsDir($path = '')
    {
        return $this->lsFilter($path, 'is_dir');
    }
    // }}}
    // {{{ lsFiles
    public function lsFiles($path = '')
    {
        return $this->lsFilter($path, 'is_file');
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

        if (isset($parsed['scheme'])) {
            $newUrl = $parsed;
        } else {
            $newUrl = $this->url;
            if ($url[0] == '/') {
                $newUrl['path'] = $url;
            } else {
                $newUrl['path'] = $this->base;
                $newUrl['path'] .= ($parsed['path'][0] == '/') ? $this->currentPath . '/' : '';
                $newUrl['path'] .= $parsed['path'];
            }
        }

        $newUrl['path'] = $this->cleanPath($newUrl['path']);
        $urlString = $this->buildUrl($newUrl);

        if (is_dir($urlString) && is_readable($urlString . '.')) {
            if (preg_match(';^' . preg_quote($this->base) . '(.*)$;', $newUrl['path'], $matches)) {
                $this->currentPath = $matches[1];
                return true;
            } else {
                throw new Exceptions\FSException('Cannot leave base directory ' . $this->base);
            }
        } else {
            throw new Exceptions\FSException('Directory not accessible ' . $newUrl['path']);
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
        return mkdir($this->pwd() . $path, 0777, true);
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
        $success = false;

        if (is_dir($remote)) {
            foreach ($this->scanDir($path) as $nested) {
                $this->rm($path . '/' .  $nested);
            }

            // workaround, rmdir does not support file stream wrappers
            if ($this->url['scheme'] == 'file') {
                $remote = preg_replace(';^file://;', '', $remote);
            }

            $success = rmdir($remote);
        } else if (is_file($remote)) {
            $success = unlink($remote);
        }

        return $success;
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
                throw new Exceptions\FSException("could not move '$source' to '$target'");
            }
            return $value;
        } else {
            throw new Exceptions\FSException("could not move '$source' to '$target' - source doesn't exist");
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
        if ($local === null) {
            $pathInfo   = pathinfo($remote);
            $fileName   = $pathInfo['filename'];
            $extension  = $pathInfo['extension'];

            $local = $fileName . '.' . $extension;
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
    protected function cleanPath($path)
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

        $newPath = (isset($path[0]) && $path[0] == '/') ? '/' : '';
        $newPath .= implode('/', $newDirs) . '/';

        return $newPath;
    }
    // }}}
    // {{{ buildUrl
    protected function buildUrl($parsed)
    {
        $path = $parsed['scheme'] . '://';
        $path .= isset($parsed['user']) ? $parsed['user']       : '';
        $path .= isset($parsed['pass']) ? ':' . $parsed['pass'] : '';
        $path .= isset($parsed['user']) ? '@'                   : '';
        $path .= isset($parsed['host']) ? $parsed['host']       : '';
        $path .= isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $path .= isset($parsed['path']) ? $parsed['path']       : '/';

        return $path;
    }
    // }}}
    // {{{ lsFilter
    protected function lsFilter($path = '', $function)
    {
        // @todo slow
        $ls         = $this->ls($path);
        $lsFiles    = array_filter(
            $ls,
            function ($element) use ($function) {
                return $function($element);
            }
        );
        natcasesort($lsFiles);
        $sorted = array_values($lsFiles);

        return $sorted;
    }
    // }}}
    // {{{ lsRecursive
    protected function lsRecursive($path, $current)
    {
        $result = array();
        $patterns = explode('/', $path);
        $count = count($patterns);

        if ($count) {
            $pattern = array_shift($patterns);
            $matches = array_filter(
                $this->scanDir($current),
                function ($node) use ($pattern) { return fnmatch($pattern, $node); }
            );

            foreach ($matches as $match) {
                $next = ($current) ? $current . '/' . $match : $match;

                if ($count == 1) {
                    $result[] = $next;
                } elseif (is_dir($this->pwd() . $next)) {
                    $result = array_merge(
                        $result,
                        $this->lsRecursive(implode('/', $patterns), $next)
                    );
                }
            }
        }

        return $result;
    }
    // }}}
    // {{{ scanDir
    protected function scanDir($path = '')
    {
        $scanDir = scandir($this->pwd() . $path);
        $filtered = array_diff($scanDir, array('.', '..'));

        natcasesort($filtered);
        $sorted = array_values($filtered);

        return $sorted;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
