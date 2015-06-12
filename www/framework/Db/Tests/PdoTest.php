<?php

class PdoTest extends PHPUnit_Framework_TestCase
{
    // {{{ setUp
    public function setUp()
    {
    }
    // }}}

    // {{{ testGetPdoObject
    public function testGetPdoObject()
    {
        $pdo = new \Depage\Db\Pdo($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
        $this->assertInstanceOf('PDO', $pdo->getPdoObject());
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
