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
            $this->fs->rm($filename);
        } catch (\Depage\Fs\Exceptions\FsException $e) {
            // suppress exception -> we will return false when values don't match
            throw new Exceptions\PublisherException($e->getMessage(), 1, $e);
        }

        if ($id !== $value) {
            throw new Exceptions\PublisherException("Could not publish: Written content is not equal.");
        }

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
        $hash = hash_file("sha256", $source);
        if ($this->fileNeedsUpdate($target, $hash)) {
            $this->mkdirForTarget($target);
            $this->fs->put($source, $target);

            $this->fileUpdated($target, $hash);
            $updated = true;
        } else {
            $this->fileKept($target);
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
        $hash = hash("sha256", $content);
        if ($this->fileNeedsUpdate($target, $hash)) {
            $this->mkdirForTarget($target);
            $this->fs->putString($target, $content);

            $this->fileUpdated($target, $hash);
            $updated = true;
        } else {
            $this->fileKept($target);
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
        $query = $this->pdo->prepare("SELECT hash FROM {$this->tableFiles} WHERE filenamehash = SHA1(:filename) AND publishId = :publishId");
        $query->execute(array(
            'filename' => $filename,
            'publishId' => $this->publishId,
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
    // {{{ unpublishRemovedFiles()
    /**
     * @brief deletes file that did not get published after resetPublishedState call
     *
     * @return void
     **/
    public function unpublishRemovedFiles()
    {
        $files = $this->getFilesToDelete();

        foreach ($files as $file) {
            $this->unpublishFile($file);
        }

        return $files;
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
        $query = $this->pdo->prepare("DELETE FROM {$this->tableFiles} WHERE filenamehash = SHA1(:filename) AND publishId = :publishId");
        $query->execute(array(
            'filename' => $filename,
            'publishId' => $this->publishId,
        ));
        $query = $this->pdo->prepare("INSERT {$this->tableFiles}
            SET
                publishId = :publishId,
                filename = :filename1,
                filenamehash = SHA1(:filename2),
                hash = :hash,
                lastmod = NOW(),
                exist = 1;
        ");
        $query->execute(array(
            'publishId' => $this->publishId,
            'filename1' => $filename,
            'filename2' => $filename,
            'hash' => $hash,
        ));
    }
    // }}}
    // {{{ fileKept()
    /**
     * @brief fileKept
     *
     * @param string $filename
     * @param string $hash
     * @return void
     **/
    protected function fileKept($filename)
    {
        $query = $this->pdo->prepare("UPDATE {$this->tableFiles}
            SET
                exist = 1
            WHERE filenamehash = SHA1(:filename) AND publishId = :publishId;
        ");
        $query->execute(array(
            'publishId' => $this->publishId,
            'filename' => $filename,
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
        $query = $this->pdo->prepare("DELETE FROM {$this->tableFiles} WHERE filenamehash = SHA1(:filename) AND publishId = :publishId");
        $query->execute(array(
            'filename' => $filename,
            'publishId' => $this->publishId,
        ));
    }
    // }}}

    // {{{ getFilesToDelete()
    /**
     * @brief getFilesToDelete
     *
     * @param mixed
     * @return void
     **/
    public function getFilesToDelete()
    {
        $deletedFiles = array();
        $query = $this->pdo->prepare("SELECT filename FROM {$this->tableFiles} WHERE exist = 0 AND publishId = :publishId");
        $query->execute(array(
            'publishId' => $this->publishId,
        ));

        $data = $query->fetchObject();
        while ($data) {
            array_push($deletedFiles, $data->filename);

            $data = $query->fetchObject();
        };

        return $deletedFiles;
    }
    // }}}
    // {{{ getPublishedFiles()
    /**
     * @brief getPublishedFiles
     *
     * @param mixed
     * @return void
     **/
    public function getPublishedFiles()
    {
        $publishedFiles = array();
        $query = $this->pdo->prepare("SELECT filename FROM {$this->tableFiles} WHERE publishId = :publishId");
        $query->execute(array(
            'publishId' => $this->publishId,
        ));

        $data = $query->fetchObject();
        while ($data) {
            array_push($publishedFiles, $data->filename);

            $data = $query->fetchObject();
        };

        return $publishedFiles;
    }
    // }}}
    // {{{ getLastPublishDate()
    /**
     * @brief getLastPublishDate
     *
     * @param mixed
     * @return void
     **/
    public function getLastPublishDate()
    {
        $date = false;
        $query = $this->pdo->prepare(
            "SELECT lastmod FROM {$this->tableFiles}
            WHERE publishId = :publishId
            ORDER BY lastmod DESC
            LIMIT 1"
        );
        $query->execute(array(
            'publishId' => $this->publishId,
        ));

        $data = $query->fetchObject();
        if ($data) {
            $date = new \DateTime($data->lastmod);
        } else {
            // for projects that have never been published
            $date = new \DateTime();
            $date->modify("-100 years");
        }

        return $date;
    }
    // }}}
    // {{{ getFileInfo()
    /**
     * @brief getFileInfo
     *
     * @param mixed $filename
     * @return void
     **/
    public function getFileInfo($filename)
    {
        $query = $this->pdo->prepare("SELECT filename, hash, lastmod, exist FROM {$this->tableFiles} WHERE filenamehash = SHA1(:filename) AND publishId = :publishId");
        $query->execute(array(
            'filename' => $filename,
            'publishId' => $this->publishId,
        ));

        $data = $query->fetchObject();
        if ($data) {
            $data->lastmod = new \DateTime($data->lastmod);
        }

        return $data;
    }
    // }}}

    // {{{ resetPublishedState()
    /**
     * @brief resetPublishedState
     *
     * @param mixed $
     * @return void
     **/
    public function resetPublishedState()
    {
        $query = $this->pdo->prepare("UPDATE {$this->tableFiles} SET exist=0 WHERE publishId = :publishId");
        $query->execute(array(
            'publishId' => $this->publishId,
        ));
    }
    // }}}
    // {{{ clearPublishedState()
    /**
     * @brief clearPublishedState
     *
     * @return void
     **/
    public function clearPublishedState()
    {
        $query = $this->pdo->prepare("DELETE FROM {$this->tableFiles} WHERE publishId = :publishId");
        $query->execute(array(
            'publishId' => $this->publishId,
        ));
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
            $this->fs->mkdir($dir, 0777, true);
        }
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
