<?php

namespace Depage\Publisher;

/**
 * brief Publisher
 * Class Publisher
 */
class Publisher
{
    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $pdo
     * @return void
     **/
    public function __construct($pdo, \Depage\Fs\Fs $fs, $publishId)
    {
        $this->pdo = $pdo;
        $this->tableFiles = $this->pdo->prefix . "_published_files";
        $this->fs = $fs;
        $this->publishId = $publishId;
    }
    // }}}

    // {{{ testConnection()
    /**
     * @brief testConnection
     *
     * @param mixed
     * @return void
     **/
    public function testConnection($baseUrl = null)
    {
        $id = sha1(rand(0, 1000));
        $value = false;
        $filename = "_test_connection_" . $id;

        try {
            $this->fs->putString($filename, $id);

            // @todo add testing of content of url directly if base url is set

            $value = $this->fs->getString($filename);
        } catch (\Depage\Fs\Exceptions\FsException $e) {
            // suppress exception -> we will return false when values don't match
        }

        // @todo throw exception with error message when test fails for taskrunner?

        return $id === $value;
    }
    // }}}
    // {{{ publishFile()
    /**
     * @brief publihes a local file to target
     *
     * @param string $source
     * @param string $target
     * @return void
     **/
    public function publishFile($source, $target, &$updated = false)
    {
        $updated = false;
        $hash = sha1_file($source);
        if ($this->fileNeedsUpdate($target, $hash)) {
            $this->mkdirForTarget($target);
            $this->fs->put($source, $target);

            $this->fileUpdated($target, $hash);
            $updated = true;
        }
    }
    // }}}
    // {{{ publishString()
    /**
     * @brief publishes string content directly to target
     *
     * @param string $content
     * @param string $target
     * @return void
     **/
    public function publishString($content, $target, &$updated = false)
    {
        $updated = false;
        $hash = sha1($content);
        if ($this->fileNeedsUpdate($target, $hash)) {
            $this->mkdirForTarget($target);
            $this->fs->putString($target, $content);

            $this->fileUpdated($target, $hash);
            $updated = true;
        }
    }
    // }}}
    // {{{ fileNeedsUpdate()
    /**
     * @brief fileNeedsUpdate
     *
     * @param string $filename
     * @param string $hash
     * @return void
     **/
    protected function fileNeedsUpdate($filename, $hash)
    {
        $query = $this->pdo->prepare("SELECT hash FROM {$this->tableFiles} WHERE filename = :filename");
        $query->execute(array(
            'filename' => $filename,
        ));
        $data = $query->fetchObject();

        if ($data) {
            return $data->hash != $hash;
        }
        return true;
    }
    // }}}
    // {{{ unpublishFile()
    /**
     * @brief removes a file from target
     *
     * @param string $target
     * @return void
     **/
    public function unpublishFile($target)
    {
        try {
            $this->fs->rm($target);
        } catch (\Depage\Fs\Exceptions\FsException $e) {
            // @todo ignore exceptions only when file does not exist -> not when it is not deletable
        }

        $this->fileDeleted($target);
    }
    // }}}

    // {{{ fileUpdated()
    /**
     * @brief fileUpdated
     *
     * @param string $filename
     * @param string $hash
     * @return void
     **/
    protected function fileUpdated($filename, $hash)
    {
        $query = $this->pdo->prepare("DELETE FROM {$this->tableFiles} WHERE filename = :filename");
        $query->execute(array(
            'filename' => $filename,
        ));
        $query = $this->pdo->prepare("INSERT {$this->tableFiles}
            SET
                pid = :pid,
                filename = :filename,
                hash = :hash,
                lastmod = NOW(),
                exist = 1;
        ");
        $query->execute(array(
            'pid' => $this->publishId,
            'filename' => $filename,
            'hash' => $hash,
        ));
    }
    // }}}
    // {{{ fileDeleted()
    /**
     * @brief fileDeleted
     *
     * @param string $filename
     * @param string $hash
     * @return void
     **/
    protected function fileDeleted($filename)
    {
        $query = $this->pdo->prepare("DELETE FROM {$this->tableFiles} WHERE filename = :filename");
        $query->execute(array(
            'filename' => $filename,
        ));
    }
    // }}}

    // {{{ resetPublishedState()
    /**
     * @brief resetPublishedState
     *
     * @param mixed $
     * @return void
     **/
    public function resetPublishedState($target)
    {

    }
    // }}}
    // {{{ getDeletedFiles()
    /**
     * @brief getDeletedFiles
     *
     * @param mixed
     * @return void
     **/
    public function getDeletedFiles()
    {

    }
    // }}}

    // {{{ mkdirForTarget()
    /**
     * @brief mkdirForTarget
     *
     * @param mixed $
     * @return void
     **/
    protected function mkdirForTarget($target)
    {
        $dir = dirname($target);
        if ($dir != ".") {
            $this->fs->mkdir($dir);
        }
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
