<?php

use depage\DB\Schema;
use depage\DB\Exceptions;

class SchemaDatabaseTest extends Generic_Tests_DatabaseTestCase
{
    // {{{ setUp
    public function setUp()
    {
        parent::setUp();
        $this->pdo      = self::$pdo;
        $this->schema   = new Schema($this->pdo);
    }
    // }}}

    // {{{ testUpdateAndExecute
    public function testUpdateAndExecute()
    {
        $this->schema->load('Fixtures/TestFile.sql');
        $this->schema->update();

        $query      = 'SHOW CREATE TABLE test';
        $statement  = $this->pdo->query($query);
        $statement->execute();
        $row        = $statement->fetch();

        $expected   = "CREATE TABLE `test` (\n" .
        "  `uid` int(10) unsigned NOT NULL DEFAULT '0',\n" .
        "  `pid` int(10) unsigned NOT NULL DEFAULT '0',\n" .
        "  `did` int(10) unsigned NOT NULL DEFAULT '0'\n" .
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.2'";

        $this->assertEquals($expected, $row['Create Table']);
    }
    // }}}
}
