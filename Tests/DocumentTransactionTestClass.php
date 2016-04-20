<?php

namespace Depage\XmlDb\Tests;

class DocumentTransactionTestClass extends DocumentTestClass
{
    public $cacheCleared = 0;

    // {{{ constructor
    public function __construct($xmldb, $doc_id)
    {
        parent::__construct($xmldb, $doc_id);

        $this->doctypeHandlers[$this->doc_id] = new DoctypeHandlerTransactionTestClass($this->xmldb, $this);
    }
    // }}}

    public function isInTransaction()
    {
        return ($this->xmldb->transactions > 0);
    }
    public function clearCache()
    {
        if ($this->isInTransaction()) {
            throw new \Exception("clearCache triggered during transaction.");
        }

        $result = parent::clearCache();
        $this->cacheCleared++;

        return $result;
    }
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
