<?php

namespace Depage\XmlDb\Tests;

class XmlDbHistoryTest extends DatabaseTestCase
{
    // {{{ variables
    protected $xmlDbHistory;
    // }}}
    // {{{ setUp
    protected function setUp()
    {
        parent::setUp();

        $this->xmlDbHistory = new \Depage\XmlDb\XmlDbHistory($this->pdo->prefix . '_proj_test', $this->pdo);
    }
    // }}}

    // {{{ testDocExistsById
    public function testDocExistsById()
    {
        $this->assertSame(1, $this->xmlDbHistory->docExists(1));
    }
    // }}}
    // {{{ testDocExistsByIdFail
    public function testDocExistsByIdFail()
    {
        $this->assertFalse($this->xmlDbHistory->docExists(2));
    }
    // }}}
    // {{{ testDocExistsByName
    public function testDocExistsByName()
    {
        $this->assertSame(1, $this->xmlDbHistory->docExists('pages'));
    }
    // }}}
    // {{{ testDocExistsByNameFail
    public function testDocExistsByNameFail()
    {
        $this->assertFalse($this->xmlDbHistory->docExists('noDocByThisName'));
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
