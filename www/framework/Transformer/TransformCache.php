<?php

namespace Depage\Transformer;

class TransformCache
{
    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $
     * @return void
     **/
    public function __construct($pdo, $projectName, $templateName)
    {
        $this->pdo = $pdo;
        $this->projectName = $projectName;
        $this->templateName = $templateName;
        $this->cache = \Depage\Cache\Cache::factory("transform");
    }
    // }}}
    // {{{ exists()
    /**
     * @brief exists
     *
     * @param mixed $docId
     * @return void
     **/
    public function exist($docId)
    {
        //$cachePath = $this->projectName . "/" . $this->template . "/" . $this->lang . $this->currentPath;
    }
    // }}}
    // {{{ get()
    /**
     * @brief get
     *
     * @param mixed $docId
     * @return void
     **/
    public function get($docId)
    {
        return false;
    }
    // }}}
    // {{{ set()
    /**
     * @brief set
     *
     * @param mixed
     * @return void
     **/
    public function set($docId, $usedDocuments, $content)
    {
        return false;
    }
    // }}}
    // {{{ delete()
    /**
     * @brief delete
     *
     * @param mixed $
     * @return void
     **/
    protected function delete($docId)
    {
        return false;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
