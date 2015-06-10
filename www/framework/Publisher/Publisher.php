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
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
