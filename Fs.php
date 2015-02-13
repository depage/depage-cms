<?php

namespace Depage\Fs;

class Fs
{
    // {{{ variables
        protected $currentPath;
        protected $base;
        protected $url;
        protected $hidden = false;
    // }}}
    // {{{ constructor
    public function __construct($params = array())
    {
        if (isset($params['scheme']))   $this->url['scheme']    = $params['scheme'];
        if (isset($params['user']))     $this->url['user']      = $params['user'];
        if (isset($params['pass']))     $this->url['pass']      = $params['pass'];
        if (isset($params['host']))     $this->url['host']      = $params['host'];
        if (isset($params['port']))     $this->url['port']      = $params['port'];

        $this->hidden   = (isset($params['hidden']))    ? $params['hidden'] : false;
        $this->path     = (isset($params['path']))      ? $params['path']   : '.';
    }
    // }}}
    // {{{ factory
    public static function factory($url, $params = array())
    {
        $parsed = self::parseUrl($url);
        $params['path'] = isset($parsed['path']) ? $parsed['path'] : '';
        $params = array_merge($parsed, $params);
        if (!isset($params['scheme'])) {
            $params['scheme'] = 'file';
        }
        $schemeClass = '\Depage\Fs\Fs' . ucfirst($params['scheme']);

        return new $schemeClass($params);
    }
    // }}}

    // {{{ pwd
    public function pwd()
    {
        $this->preCommandHook();

        $url = $this->url;
        $url['path'] = $this->base . $this->currentPath;

        return $this->buildUrl($url);
    }
    // }}}
    // {{{ ls
    public function ls($url)
    {
        $this->preCommandHook();

        $cleanUrl = $this->cleanUrl($url);
        $path = str_replace($this->pwd(), '', $cleanUrl);

        return $this->lsRecursive($path, '');
    }
    // }}}
    // {{{ lsDir
    public function lsDir($path = '')
    {
        $this->preCommandHook();
        return $this->lsFilter($path, 'is_dir');
    }
    // }}}
    // {{{ lsFiles
    public function lsFiles($path = '')
    {
        $this->preCommandHook();
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
     */
    public function cd($url)
    {
        $this->preCommandHook();

        $cleanUrl = $this->cleanUrl($url);

        if (is_dir($cleanUrl) && is_readable($cleanUrl . '/.')) {
            $this->currentPath = str_replace($this->pwd(), '', $cleanUrl) . '/';
        } else {
            $parsedUrl = $this->parseUrl($cleanUrl);
            $path = $parsedUrl['path'];
            throw new Exceptions\FsException('Directory not accessible ' . $path);
        }

        $this->postCommandHook();
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
    public function mkdir($url)
    {
        $this->preCommandHook();

        $cleanUrl = $this->cleanUrl($url);
        $success = mkdir($cleanUrl, 0777, true);

        $this->postCommandHook();
        return $success;
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
    public function rm($url)
    {
        $this->preCommandHook();

        $cleanUrl = $this->cleanUrl($url);
        if (preg_match('/^' . preg_quote($cleanUrl, '/') . '\/?$/', $this->pwd())) {
            throw new Exceptions\FsException('Cannot delete current directory ' . $this->pwd());
        }

        $success = false;
        if (is_dir($cleanUrl)) {
            foreach ($this->scanDir($cleanUrl, true) as $nested) {
                $success = $this->rm($cleanUrl . '/' .  $nested) && $success;
            }

            $success = $this->rmdir($cleanUrl);
        } else if (is_file($cleanUrl)) {
            $success = unlink($cleanUrl);
        }

        if ($success) {
            clearstatcache (false, $cleanUrl);
        }

        $this->postCommandHook();
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
    public function mv($sourcePath, $targetPath)
    {
        $this->preCommandHook();

        $source = $this->cleanUrl($sourcePath);
        $target = $this->cleanUrl($targetPath);
        $success = false;

        if (file_exists($source)) {
            $success = rename($source, $target);
            if (!$success) {
                throw new Exceptions\FsException("could not move '$source' to '$target'");
            }
        } else {
            throw new Exceptions\FsException("could not move '$source' to '$target' - source doesn't exist");
        }

        $this->postCommandHook();
        return $success;
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
    public function get($remotePath, $local = null)
    {
        $this->preCommandHook();

        if ($local === null) {
            $pathInfo = pathinfo($remotePath);
            $fileName = $pathInfo['filename'];
            $extension = $pathInfo['extension'];

            $local = $fileName . '.' . $extension;
        }

        $remote = $this->cleanUrl($remotePath);
        $success = copy($remote, $local);

        $this->postCommandHook();
        return $success;
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
    public function put($local, $remotePath)
    {
        $this->preCommandHook();

        $remote = $this->cleanUrl($remotePath);
        $success = copy($local, $remote);

        $this->postCommandHook();
        return $success;
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
    public function exists($remotePath)
    {
        $this->preCommandHook();

        $remote = $this->cleanUrl($remotePath);
        $exists = file_exists($remote);

        $this->postCommandHook();
        return $exists;
    }
    // }}}
    // {{{ fileInfo
    public function fileInfo($remotePath)
    {
        $this->preCommandHook();

        $remote = $this->cleanUrl($remotePath);
        $fileInfo = new \SplFileInfo($remote);

        $this->postCommandHook();
        return $fileInfo;
    }
    // }}}
    // {{{ getString
    public function getString($remotePath)
    {
        $this->preCommandHook();

        $remote = $this->cleanUrl($remotePath);
        $string = file_get_contents($remote);

        $this->postCommandHook();
        return $string;
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
    public function putString($remotePath, $string)
    {
        $this->preCommandHook();

        $remote = $this->cleanUrl($remotePath);
        $bytes = file_put_contents($remote, $string);

        $this->postCommandHook();
        return $bytes;
    }
    // }}}

    // {{{ test
    public function test()
    {
        $testFile = 'depage-fs-test-file.tmp';
        $testString = 'depage-fs-test-string';

        try {
            $success = !$this->exists($testFile)
                && $this->putString($testFile, $testString)
                && $this->getString($testFile) === $testString
                && $this->rm($testFile)
                && !$this->exists($testFile);
        } catch (Exceptions\FsException $exception) {
            $success = false;
        }

        return $success;
    }
    // }}}

    // {{{ preCommandHook
    protected function preCommandHook()
    {
        $this->lateConnect();
        $this->errorHandler(true);
    }
    // }}}
    // {{{ postCommandHook
    protected function postCommandHook()
    {
        $this->errorHandler(false);
    }
    // }}}
    // {{{ lateConnect
    protected function lateConnect()
    {
        if (!isset($this->base)) {
            $this->setBase($this->path);
        }
    }
    // }}}
    // {{{ setBase
    protected function setBase($path)
    {
        $cleanPath = $this->cleanPath($path);
        $this->base = (substr($cleanPath, -1) == '/') ? $cleanPath : $cleanPath . '/';
    }
    // }}}
    // {{{ parseUrl
    protected function parseUrl($url)
    {
        $parsed = parse_url($url);

        // hack, parse_url matches anything after the first question mark as "query"
        $path = (isset($parsed['path'])) ? $parsed['path'] : null;
        $query = (isset($parsed['query'])) ? $parsed['query'] : null;
        if ($query !== null || preg_match('/\?$/', $url)) {
            $parsed['path'] = $path . '?' . $query;
            unset($parsed['query']);
        }

        return $parsed;
    }
    // }}}
    // {{{ cleanUrl
    protected function cleanUrl($url)
    {
        $parsed = $this->parseUrl($url);
        $scheme = (isset($parsed['scheme'])) ? $parsed['scheme'] : null;
        $path = (isset($parsed['path'])) ? $parsed['path'] : null;

        if ($scheme) {
            $newUrl = $parsed;
            $newPath = $path;
        } else {
            $newUrl = $this->url;
            if (substr($url, 0, 1) == '/') {
                $newPath = $url;
            } else {
                $newPath = $this->base;
                $newPath .= (substr($path, 0, 1) == '/') ? $this->currentPath . '/' : '';
                $newPath .= $path;
            }
        }

        $newUrl['path'] = $this->cleanPath($newPath);

        if (!preg_match(';^' . preg_quote($this->cleanPath($this->base)) . '(.*)$;',  $newUrl['path'])) {
            throw new Exceptions\FsException('Cannot leave base directory ' . $this->base);
        }

        return $this->buildUrl($newUrl);
    }
    // }}}
    // {{{ cleanPath
    protected function cleanPath($path)
    {
        // @todo handle backslashes
        $dirs = explode('/', $path);
        $newDirs = array();

        foreach ($dirs as $dir) {
            if ($dir == '..') {
                array_pop($newDirs);
            } else if ($dir != '.' && $dir != '') {
                $newDirs[] = $dir;
            }
        }

        $newPath = (substr($path, 0, 1) == '/') ? '/' : '';
        $newPath .= implode('/', $newDirs);

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
        $ls = $this->ls($path);
        $pwd = $this->pwd();
        $lsFiltered = array_filter(
            $ls,
            function ($element) use ($function, $pwd) {
                return $function($pwd . $element);
            }
        );
        natcasesort($lsFiltered);
        $sorted = array_values($lsFiltered);

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
            if (preg_match('/[\*\?\[\]]/', $pattern)) {
                $matches = array_filter(
                    $this->scanDir($current),
                    function ($node) use ($pattern) { return fnmatch($pattern, $node); }
                );
            } else {
                $matches = array($pattern);
            }

            foreach ($matches as $match) {
                $next = ($current) ? $current . '/' . $match : $match;

                if ($count == 1) {
                    $result[] = $next;
                } elseif (is_dir($this->cleanUrl($next))) {
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
    protected function scanDir($url = '', $hidden = null)
    {
        $cleanUrl = $this->cleanUrl($url);

        if ($hidden === null) {
            $hidden = $this->hidden;
        }

        $scanDir = scandir($cleanUrl);

        $filtered = array_diff($scanDir, array('.', '..'));

        if (!$hidden) {
            $filtered = array_filter(
                $filtered,
                function ($node) { return ($node[0] != '.'); }
            );
        }

        natcasesort($filtered);
        $sorted = array_values($filtered);

        return $sorted;
    }
    // }}}
    // {{{ errorHandler
    protected function errorHandler($start)
    {
        if ($start) {
            set_error_handler(
                function($errno, $errstr, $errfile, $errline, array $errcontext) {
                    throw new Exceptions\FsException($errstr);
                }
            );
        } else {
            restore_error_handler();
        }
    }
    // }}}
    // {{{ rmdir
    protected function rmdir($url)
    {
        return rmdir($url);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
