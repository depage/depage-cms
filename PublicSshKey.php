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
        $this->key = $this->details($data);
        $this->tmpDir = $tmpDir;

        if ($this->key === false) {
            throw new Exceptions\FsException('Invalid SSH key "' . $data . '".');
        } elseif (is_file($data)) {
            $this->path = $data;
        } elseif (is_dir($tmpDir)) {
            if (is_writable($tmpDir)) {
                $this->path = tempnam($tmpDir, 'depage-fs');
                $this->temporary = true;
                $bytesWritten = file_put_contents($this->path, $data);
                if ($bytesWritten === false) {
                    throw new Exceptions\FsException('Cannot create temporary key file "' . $this->path . '".');
                }
            } else {
                throw new Exceptions\FsException('Cannot write to temporary key directory "' . $tmpDir . '".');
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

    // {{{ details
    protected function details($path)
    {
        // @todo do proper check
        return file_get_contents($path);
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
