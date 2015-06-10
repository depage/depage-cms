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
        $this->tableFiles = $this->pdo->prefix . "_publish_files";
        $this->fs = $fs;
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
    public function publishFile($source, $target)
    {
        $this->mkdirForTarget($target);
        $this->fs->put($source, $target);
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
    public function publishString($content, $target)
    {
        $this->mkdirForTarget($target);
        $this->fs->putString($target, $content);
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
    }
    // }}}
    // {{{ getFilesToUnpublish()
    /**
     * @brief getFilesToUnpublish
     *
     * @param mixed
     * @return void
     **/
    public function getFilesToUnpublish()
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
