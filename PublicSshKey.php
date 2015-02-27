<?php

namespace Depage\Fs;

class PublicSshKey
{
    // {{{ variables
    protected $key = null;
    protected $path = null;
    protected $tmpDir = false;
    protected $temporary = false;
    // }}}
    // {{{ constructor
    public function __construct($data, $tmpDir = false)
    {
        $this->tmpDir = $tmpDir;

        if ($tmpDir) {
            $this->key = $this->parse($data);
            if (is_dir($tmpDir) && is_writable($tmpDir)) {
                $this->path = tempnam($tmpDir, 'depage-fs');
                $this->temporary = true;
                $bytesWritten = file_put_contents($this->path, $data);
                if ($bytesWritten === false) {
                    throw new Exceptions\FsException('Cannot create temporary key file "' . $this->path . '".');
                }
            } else {
                throw new Exceptions\FsException('Cannot write to temporary key file directory "' . $tmpDir . '".');
            }
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
        if ($this->temporary) {
            if (unlink($this->path)) {
                $this->temporary = false;
            } else {
                throw new Exceptions\FsException('Cannot delete temporary key file "' . $this->path . '".');
            }
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
