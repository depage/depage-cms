<?php

namespace Depage\Fs;

class PublicSshKey
{
    // {{{ variables
    protected $key = null;
    protected $path = null;
    protected $temporary = false;
    // }}}
    // {{{ constructor
    public function __construct($data, $tmpDir = false)
    {
        if ($tmpDir) {
            $this->key = $this->parse($data);
            $this->path = $this->createTmpFile($tmpDir, $data);
        } else {
            $this->path = $data;
            if (is_file($data) && is_readable($data)) {
                $this->key = $this->parse(file_get_contents($data));
            } else {
                throw new Exceptions\FsException('SSH key file not accessible: "' . $data . '".');
            }
        }
    }
    // }}}
    // {{{ destructor
    public function __destruct()
    {
        $this->clean();
    }
    // }}}

    // {{{ createTmpFile
    protected function createTmpFile($tmpDir, $data)
    {
        $this->temporary = true;

        if (is_dir($tmpDir) && is_writable($tmpDir)) {
            $path = tempnam($tmpDir, 'depage-fs');
            $bytesWritten = Fs::file_put_contents($path, $data);

            if ($bytesWritten === false) {
                throw new Exceptions\FsException('Cannot create temporary key file "' . $path . '".');
            }
        } else {
            throw new Exceptions\FsException('Cannot write to temporary key file directory "' . $tmpDir . '".');
        }

        return $path;
    }
    // }}}

    // {{{ parse
    protected function parse($keyString)
    {
        // @todo do proper check
        return $keyString;
    }
    // }}}
    // {{{ toString
    public function __toString()
    {
        return $this->path;
    }
    // }}}
    // {{{ clean
    public function clean()
    {
        unset($this->key);
        if ($this->temporary) {
            if (is_file($this->path) && is_writable($this->path)) {
                unlink($this->path);
                $this->temporary = false;
            } else {
                throw new Exceptions\FsException('Cannot delete temporary key file "' . $this->path . '".');
            }
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
