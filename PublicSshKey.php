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
        $path = parse_url($data, PHP_URL_PATH);

        if ($path) {
            $this->path = $path;
            if (is_file($path) && is_readable($path)) {
                $this->key = $this->details(file_get_contents($path));
            } else {
                throw new Exceptions\FsException('SSH key file not accessible: "' . $path . '".');
            }
        } else {
            $this->key = $this->details($data);
            if (is_dir($tmpDir) && is_writable($tmpDir)) {
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
    protected function details($keyString)
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
