<?php

namespace Depage\Fs\Tests;

class HelperFsLocal
{
    // {{{ constructor
    public function __construct($root)
    {
        $this->root = $root;
    }
    // }}}
    // {{{ getRoot
    public function getRoot()
    {
        return $this->root;
    }
    // }}}
    // {{{ translatePath
    protected function translatePath($path)
    {
        return $this->root . '/' . $path;
    }
    // }}}

    // {{{ setUp
    public function setUp()
    {
        $result = true;

        if ($this->is_dir('')) {
            $result = $this->rm('');
        }

        $result = $result && $this->mkdir('', 0777);
        $result = $result && $this->is_dir('');

        return $result;
    }
    // }}}
    // {{{ tearDown
    public function tearDown()
    {
        return $this->rm('');
    }
    // }}}

    // {{{ createFile
    public function createFile($path = 'testFile', $contents = 'testString')
    {
        $newPath = $this->translatePath($path);

        $testFile = fopen($newPath, 'w');
        fwrite($testFile, $contents);
        fclose($testFile);

        return $this->checkFile($path, $contents);
    }
    // }}}
    // {{{ checkFile
    public function checkFile($path = 'testFile', $contents = 'testString')
    {
        $file = file($this->translatePath($path));

        return $file === [$contents];
    }
    // }}}
    // {{{ rm
    public function rm($path)
    {
        $result = true;

        if ($this->is_dir($path)) {
            $scanDir = array_diff($this->scandir($path), ['.', '..']);

            foreach ($scanDir as $nested) {
                $result = $result && $this->rm($path . '/' . $nested);
            }
            $result = $result && $this->rmdir($path);
        } elseif ($this->is_file($path)) {
            $result = $result && $this->unlink($path);
        }

        return $result && !$this->file_exists($path);
    }
    // }}}
    // {{{ mkdir
    public function mkdir($path)
    {
        return \mkdir($this->translatePath($path), 0777, true);
    }
    // }}}
    // {{{ file_exists
    public function file_exists($path)
    {
        return \file_exists($this->translatePath($path));
    }
    // }}}
    // {{{ touch
    public function touch($path, $mode = 0777, $time = null)
    {
        $path = $this->translatePath($path);

        if ($time) {
            $result = \touch($path, $mode, $time);
        } else {
            $result = \touch($path, $mode);
        }

        return $result && chmod($path, $mode);
    }
    // }}}
    // {{{ scandir
    public function scandir($path)
    {
        return \scandir($this->translatePath($path));
    }
    // }}}
    // {{{ is_dir
    public function is_dir($path)
    {
        return \is_dir($this->translatePath($path));
    }
    // }}}
    // {{{ is_file
    public function is_file($path)
    {
        return \is_file($this->translatePath($path));
    }
    // }}}
    // {{{ sha1_file
    public function sha1_file($path)
    {
        return \sha1_file($this->translatePath($path));
    }
    // }}}

    // {{{ rmdir
    private function rmdir($path)
    {
        return \rmdir($this->translatePath($path));
    }
    // }}}
    // {{{ unlink
    private function unlink($path)
    {
        return \unlink($this->translatePath($path));
    }
    // }}}
}
