<?php

namespace Depage\Fs;

class Fs
{
    // {{{ variables
    protected $currentPath;
    protected $base;
    protected $url;
    protected $hidden;
    protected $streamContextOptions = array();
    protected $streamContext;
    protected $path;
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

        $this->streamContext = stream_context_create($this->streamContextOptions);
    }
    // }}}
    // {{{ factory
    public static function factory($url, $params = array())
    {
        $parsed = self::parseUrl($url);
        if (is_array($parsed)) {
            $params = array_merge($parsed, $params);
        }
        $scheme = isset($params['scheme']) ? $params['scheme'] : null;
        $alias = self::schemeAlias($scheme);

        $schemeClass = '\Depage\Fs\Fs' . ucfirst($alias['class']);
        $params['scheme'] = $alias['scheme'];

        return new $schemeClass($params);
    }
    // }}}
    // {{{ schemeAlias
    protected static function schemeAlias($alias = '')
    {
        $aliases = array(
            ''          => array('class' => 'file', 'scheme' => 'file'),
            'file'      => array('class' => 'file', 'scheme' => 'file'),
            'ftp'       => array('class' => 'ftp',  'scheme' => 'ftp'),
            'ftps'      => array('class' => 'ftp',  'scheme' => 'ftps'),
            'ssh2.sftp' => array('class' => 'ssh',  'scheme' => 'ssh2.sftp'),
            'ssh'       => array('class' => 'ssh',  'scheme' => 'ssh2.sftp'),
            'sftp'      => array('class' => 'ssh',  'scheme' => 'ssh2.sftp'),
        );

        if (array_key_exists($alias, $aliases)) {
            $translation = $aliases[$alias];
        } else {
            $translation = array('class' => '', 'scheme' => $alias);
        }

        return $translation;
    }
    // }}}

    // {{{ pwd
    public function pwd()
    {
        $this->preCommandHook();

        $url = $this->url;
        $url['path'] = $this->base . $this->currentPath;
        $pwd = $this->buildUrl($url);

        $this->postCommandHook();
        return $pwd;
    }
    // }}}
    // {{{ ls
    public function ls($url)
    {
        $this->preCommandHook();

        $cleanUrl = $this->cleanUrl($url);
        $path = str_replace($this->pwd(), '', $cleanUrl);
        $ls = $this->lsRecursive($path, '');

        $this->postCommandHook();
        return $ls;
    }
    // }}}
    // {{{ lsDir
    public function lsDir($path = '')
    {
        $this->preCommandHook();

        $lsDir = $this->lsFilter($path, 'is_dir');

        $this->postCommandHook();
        return $lsDir;
    }
    // }}}
    // {{{ lsFiles
    public function lsFiles($path = '')
    {
        $this->preCommandHook();

        $lsFiles = $this->lsFilter($path, 'is_file');

        $this->postCommandHook();
        return $lsFiles;
    }
    // }}}
    // {{{ exists
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

    // {{{ cd
    public function cd($url)
    {
        $this->preCommandHook();

        $cleanUrl = $this->cleanUrl($url);

        if (is_dir($cleanUrl) && is_readable($cleanUrl . '/.')) {
            $this->currentPath = str_replace($this->pwd(), '', $cleanUrl) . '/';
        } else {
            throw new Exceptions\FsException('Directory not accessible "' . $this->cleanUrl($url, false) . '".');
        }

        $this->postCommandHook();
    }
    // }}}
    // {{{ mkdir
    public function mkdir($pathName, $mode = 0777, $recursive = true)
    {
        $this->preCommandHook();

        $cleanUrl = $this->cleanUrl($pathName);
        if (!is_dir($cleanUrl)) {
            $success = mkdir($cleanUrl, $mode, $recursive, $this->streamContext);

            if (!$success && !is_dir($cleanUrl)) {
                throw new Exceptions\FsException('Error while creating directory "' . $pathName . '".');
            }
        }

        $this->postCommandHook();
    }
    // }}}
    // {{{ rm
    public function rm($url)
    {
        $this->preCommandHook();

        $cleanUrl = $this->cleanUrl($url);
        $pwd = $this->pwd();

        if (preg_match('/^' . preg_quote($cleanUrl, '/') . '\//', $pwd . '/')) {
            throw new Exceptions\FsException('Cannot delete current or parent directory "' . $this->cleanUrl($pwd, false) . '".');
        }
        $this->rmRecursive($cleanUrl);

        $this->postCommandHook();
    }
    // }}}
    // {{{ copy
    public function copy($sourcePath, $targetPath)
    {
        $this->preCommandHook();

        $source = $this->cleanUrl($sourcePath);
        $target = $this->cleanUrl($targetPath);

        if (file_exists($source)) {
            if(file_exists($target) && is_dir($target)) {
                $target .= '/' . $this->extractFileName($source);
            }
            \copy($source, $target, $this->streamContext);
        } else {
            throw new Exceptions\FsException('Cannot copy "' . $this->cleanUrl($sourcePath, false) . '" to "' . $this->cleanUrl($targetPath, false) . '" - source doesn\'t exist.');
        }

        $this->postCommandHook();
    }
    // }}}
    // {{{ mv
    public function mv($sourcePath, $targetPath)
    {
        $this->preCommandHook();

        $source = $this->cleanUrl($sourcePath);
        $target = $this->cleanUrl($targetPath);

        if (file_exists($source)) {
            if(file_exists($target) && is_dir($target)) {
                $target .= '/' . $this->extractFileName($source);
            }
            $this->rename($source, $target);
        } else {
            throw new Exceptions\FsException('Cannot move "' . $this->cleanUrl($sourcePath, false) . '" to "' . $this->cleanUrl($targetPath, false) . '" - source doesn\'t exist.');
        }

        $this->postCommandHook();
    }
    // }}}

    // {{{ get
    public function get($remotePath, $local = null)
    {
        $this->preCommandHook();

        if ($local === null) {
            $local = $this->extractFileName($remotePath);
        }

        $remote = $this->cleanUrl($remotePath);
        copy($remote, $local, $this->streamContext);

        $this->postCommandHook();
    }
    // }}}
    // {{{ put
    public function put($local, $remotePath)
    {
        $this->preCommandHook();

        $remote = $this->cleanUrl($remotePath);
        copy($local, $remote, $this->streamContext);

        $this->postCommandHook();
    }
    // }}}
    // {{{ getString
    public function getString($remotePath)
    {
        $this->preCommandHook();

        $remote = $this->cleanUrl($remotePath);
        $string = file_get_contents($remote, false, $this->streamContext);

        $this->postCommandHook();
        return $string;
    }
    // }}}
    // {{{ putString
    public function putString($remotePath, $string)
    {
        $this->preCommandHook();

        $remote = $this->cleanUrl($remotePath);
        $this->file_put_contents($remote, $string, 0, $this->streamContext);

        $this->postCommandHook();
    }
    // }}}

    // {{{ test
    public function test(&$error = null)
    {
        $testFile = 'depage-fs-test-file.tmp';
        $testString = 'depage-fs-test-string';
        $success = false;

        try {
            if (!$this->exists($testFile)) {
                $this->putString($testFile, $testString);
                if ($this->getString($testFile) === $testString) {
                    $this->rm($testFile);
                    $success = !$this->exists($testFile);
                }
            }
        } catch (Exceptions\FsException $exception) {
            $error = $exception->getMessage();
        }

        return $success;
    }
    // }}}

    // {{{ preCommandHook
    protected function preCommandHook()
    {
        $this->lateConnect();
        $this->setErrorHandler(true);
    }
    // }}}
    // {{{ postCommandHook
    protected function postCommandHook()
    {
        $this->setErrorHandler(false);
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
        $cleanPath = $this->cleanPath('/' . $path);
        $this->base = (substr($cleanPath, -1) == '/') ? $cleanPath : $cleanPath . '/';
    }
    // }}}

    // {{{ depageFsErrorHandler
    public function depageFsErrorHandler($errno, $errstr, $errfile, $errline)
    {
        restore_error_handler();
        throw new Exceptions\FsException($errstr);
    }
    // }}}
    // {{{ setErrorHandler
    protected function setErrorHandler($start)
    {
        if ($start) {
            set_error_handler(array($this, 'depageFsErrorHandler'));
        } else {
            restore_error_handler();
        }
    }
    // }}}

    // {{{ parseUrl
    public static function parseUrl($url)
    {
        $parsed = parse_url($url);

        // hack, parse_url (PHP 5.6.29) won't handle resource name strings
        if (isset($parsed['fragment'])) {
            $urlParts = explode('/', $url);

            if (isset($urlParts[2]) && preg_match('/^Resource id \#([0-9]+)$/', $urlParts[2], $matches)) {
                $urlParts[2] = $matches[1];
                $parsed = parse_url(implode('/', $urlParts));
                $parsed['host'] = 'Resource id #' . $parsed['host'];
            }
        }

        // hack, parse_url matches anything after the first question mark as "query"
        $path = (isset($parsed['path'])) ? $parsed['path'] : '';
        $query = (isset($parsed['query'])) ? $parsed['query'] : '';
        if (!empty($query) || preg_match('/\?$/', $url)) {
            $parsed['path'] = $path . '?' . $query;
            unset($parsed['query']);
        }

        return $parsed;
    }
    // }}}
    // {{{ cleanUrl
    protected function cleanUrl($url, $showPass = true)
    {
        $parsed = self::parseUrl($url);
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
                $newPath = $this->base . $this->currentPath;
                $newPath .= (substr($path, 0, 1) == '/') ? '' : '/';
                $newPath .= $path;
            }
        }

        $newUrl['path'] = $this->cleanPath($newPath);

        if (!preg_match(';^' . preg_quote($this->cleanPath($this->base)) . '(.*)$;',  $newUrl['path'])) {
            throw new Exceptions\FsException('Cannot leave base directory "' . $this->base . '".');
        }

        return $this->buildUrl($newUrl, $showPass);
    }
    // }}}
    // {{{ cleanPath
    protected function cleanPath($path)
    {
        $dirs = explode('/', $path);
        $newDirs = array();

        foreach ($dirs as $dir) {
            if ($dir == '..') {
                array_pop($newDirs);
            } elseif ($dir != '.' && $dir != '') {
                $newDirs[] = $dir;
            }
        }

        $newPath = (substr($path, 0, 1) == '/') ? '/' : '';
        $newPath .= implode('/', $newDirs);

        return $newPath;
    }
    // }}}
    // {{{ buildUrl
    protected function buildUrl($parsed, $showPass = true)
    {
        $path = $parsed['scheme'] . '://';
        $path .= !empty($parsed['user']) ? $parsed['user'] : '';

        if (!empty($parsed['pass'])) {
            $path .= ($showPass) ? ':' . $parsed['pass'] : ':...';
        }

        $path .= !empty($parsed['user']) ? '@'                   : '';
        $path .= !empty($parsed['host']) ? $parsed['host']       : '';
        $path .= !empty($parsed['port']) ? ':' . $parsed['port'] : '';
        $path .= !empty($parsed['path']) ? $parsed['path']       : '/';

        return $path;
    }
    // }}}
    // {{{ extractFileName
    protected function extractFileName($path)
    {
        $pathInfo = pathinfo($path);
        $fileName = $pathInfo['filename'];

        if (isset($pathInfo['extension'])) {
            $fileName .= '.' . $pathInfo['extension'];
        }

        return $fileName;
    }
    // }}}

    // {{{ lsFilter
    protected function lsFilter($path, $function)
    {
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
    // {{{ matchNodesInPath
    protected function matchNodesInPath($path, $pattern)
    {
        if (preg_match('/[' . preg_quote('*?[]') . ']/', $pattern)) {
            $matches = array_filter(
                $this->scandir($path),
                function ($node) use ($pattern) { return fnmatch($pattern, $node); }
            );
        } else {
            $matches = array($pattern);
        }
        return $matches;
    }
    // }}}
    // {{{ lsRecursive
    protected function lsRecursive($path, $current)
    {
        $nodes = array();
        $patterns = explode('/', $path);
        $count = count($patterns);
        $pwd = $this->pwd();

        if ($count) {
            $pattern = array_shift($patterns);
            $matches = $this->matchNodesInPath($pwd . $current, $pattern);

            foreach ($matches as $match) {
                $next = ($current) ? $current . '/' . $match : $match;

                if ($count === 1) {
                    $nodes[] = $next;
                } elseif (is_dir($pwd . $next)) {
                    $nodes = array_merge(
                        $nodes,
                        $this->lsRecursive(implode('/', $patterns), $next)
                    );
                }
            }
        }

        return $nodes;
    }
    // }}}
    // {{{ rmRecursive
    protected function rmRecursive($cleanUrl)
    {
        if (!file_exists($cleanUrl)) {
            throw new Exceptions\FsException('"' . $this->cleanUrl($cleanUrl, false) . '" doesn\'t exist.');
        } elseif (is_dir($cleanUrl)) {
            foreach ($this->scandir($cleanUrl, true) as $nested) {
                $this->rmRecursive($cleanUrl . '/' .  $nested);
            }
            $this->rmdir($cleanUrl);
        } elseif (is_file($cleanUrl)) {
            unlink($cleanUrl, $this->streamContext);
        }

        clearstatcache(true, $cleanUrl);
    }
    // }}}

    // {{{ scandir
    protected function scandir($cleanUrl = '', $hidden = null)
    {
        if ($hidden === null) {
            $hidden = $this->hidden;
        }

        $scanDir = \scandir($cleanUrl, 0, $this->streamContext);
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
    // {{{ rmdir
    /**
     * Hook, allows overriding of rmdir function
     */
    protected function rmdir($url)
    {
        return \rmdir($url, $this->streamContext);
    }
    // }}}
    // {{{ rename
    /**
     * Hook, allows overriding of rename function
     */
    protected function rename($source, $target)
    {
        return \rename($source, $target, $this->streamContext);
    }
    // }}}
    // {{{ file_put_contents
    /**
     * Hook, allows overriding of file_put_contents function
     */
    public function file_put_contents($filename, $data, $flags = 0, $context = null)
    {
        return \file_put_contents($filename, $data, $flags, $context);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
