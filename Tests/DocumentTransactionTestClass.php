<?php

namespace Depage\XmlDb\Tests;

class DocumentTransactionTestClass extends DocumentTestClass
{
    public $cacheCleared = 0;

    // {{{ constructor
    public function __construct($xmlDb, $doc_id)
    {
        parent::__construct($xmlDb, $doc_id);

        $dth = new DoctypeHandlerTransactionTestClass($this->xmlDb, $this);
        $this->setDoctypeHandler($dth);
    }
    // }}}

    // {{{ inTransaction
    public function isInTransaction()
    {
        return ($this->xmlDb->transactions > 0);
    }
    // }}}
    // {{{ clearCache
    public function clearCache()
    {
        if ($this->isInTransaction()) {
            throw new \Exception("clearCache triggered during transaction.");
        }

        $result = parent::clearCache();
        $this->cacheCleared++;

        return $result;
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
