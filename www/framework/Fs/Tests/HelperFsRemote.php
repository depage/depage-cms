<?php

namespace Depage\Fs\Tests;

class HelperFsRemote extends HelperFsLocal
{
    protected static $sshConnection;

    // {{{ constructor
    public function __construct($path)
    {
        parent::__construct('/home/testuser' . $path);
    }
    // }}}

    // {{{ createFile
    public function createFile($path = 'testFile', $contents = 'testString')
    {
        $absolutePath = $this->translatePath($path);

        $this->sshExec("echo -n \"$contents\" > $absolutePath");

        return $this->checkFile($path, $contents);
    }
    // }}}
    // {{{ checkFile
    public function checkFile($path = 'testFile', $contents = 'testString')
    {
        $file = $this->sshExec('cat ' . $this->translatePath($path));

        return $file === $contents;
    }
    // }}}
    // {{{ rm
    public function rm($path)
    {
        $this->sshExec('rm -r ' . $this->translatePath($path));

        return !$this->file_exists($path);
    }
    // }}}
    // {{{ mkdir
    public function mkdir($path, $mode = 0777, $recursive = true)
    {
        $absolutePath = $this->translatePath($path);

        $parents = ($recursive) ? '-p ' : '';
        $decMode = decoct($mode);
        $command = 'mkdir ' . $parents . '-m ' . $decMode . ' ' . $absolutePath;
        $this->sshExec($command);

        return $this->is_dir($path);
    }
    // }}}
    // {{{ file_exists
    public function file_exists($path)
    {
        return $this->is_dir($path) || $this->is_file($path);
    }
    // }}}
    // {{{ touch
    public function touch($path, $mode = 0777, $time = null)
    {
        $absolutePath = $this->translatePath($path);
        $decMode = decoct($mode);
        $mtime = ($time) ? ' -t ' . date('YmdHi.s', $time) : '';

        $this->sshExec('touch ' . $absolutePath . $mtime);
        $this->sshExec('chmod ' . $decMode . ' ' . $absolutePath);

        return $this->is_file($path);
    }
    // }}}
    // {{{ is_dir
    public function is_dir($path)
    {
        $result = $this->sshExec('if [ -d "' . $this->translatePath($path) . '" ]; then echo 1; else echo 0; fi');

        return (bool) trim($result);
    }
    // }}}
    // {{{ is_file
    public function is_file($path)
    {
        $result = $this->sshExec('if [ -f "' . $this->translatePath($path) . '" ]; then echo 1; else echo 0; fi');

        return (bool) trim($result);
    }
    // }}}
    // {{{ sha1_file
    public function sha1_file($path)
    {
        $result = explode(' ', $this->sshExec('sha1sum ' . $this->translatePath($path)));

        return $result[0];
    }
    // }}}

    // {{{ sshConnection
    protected function sshConnection()
    {
        if (!isset(static::$sshConnection)) {
            static::$sshConnection = ssh2_connect($GLOBALS['REMOTE_HOST'], 22);
            ssh2_auth_password(static::$sshConnection, $GLOBALS['REMOTE_USER'], $GLOBALS['REMOTE_PASS']);
        }

        return static::$sshConnection;
    }
    // }}}
    // {{{ sshExec
    protected function sshExec($cmd)
    {
        $stream = ssh2_exec($this->sshConnection(), $cmd);
        stream_set_blocking($stream, true);
        $streamResult = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);

        return stream_get_contents($streamResult);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
