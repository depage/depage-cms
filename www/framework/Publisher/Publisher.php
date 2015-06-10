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
     * @param mixed $
     * @return void
     **/
    public function publishFile($source, $target)
    {

    }
    // }}}
    // {{{ publishString()
    /**
     * @brief publishes string content directly to target
     *
     * @param mixed $
     * @return void
     **/
    public function publishString($content, $target)
    {

    }
    // }}}
    // {{{ unpublishFile()
    /**
     * @brief removes a file from target
     *
     * @param mixed $
     * @return void
     **/
    public function unpublishFile($target)
    {

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
