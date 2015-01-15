<?php

namespace Depage\Fs;

class FsFile extends Fs
{
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
        $cleanUrl = $this->cleanUrl($url);
        if (preg_match('/^' . preg_quote($cleanUrl, '/') . '\/?$/', $this->pwd())) {
            throw new Exceptions\FsException('Cannot delete current directory ' . $this->pwd());
        }

        $success = false;
        if (is_dir($cleanUrl)) {
            foreach ($this->scanDir($cleanUrl, true) as $nested) {
                $this->rm($cleanUrl . '/' .  $nested);
            }

            // workaround, rmdir does not support file stream wrappers
            $cleanUrl = preg_replace(';^file://;', '', $cleanUrl);

            $success = rmdir($cleanUrl);
        } else if (is_file($cleanUrl)) {
            $success = unlink($cleanUrl);
        }

        return $success;
    }
    // }}}
    // {{{ setBase
    protected function setBase($path)
    {
        $path = realpath($path);

        if ($path === false) {
            throw new Exceptions\FsException('Invalid path: ' . $path);
        }

        return parent::setBase($path);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
