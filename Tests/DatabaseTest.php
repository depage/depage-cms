<?php

use depage\DB\Schema;
use depage\DB\Exceptions;

class SchemaDatabaseTest extends Generic_Tests_DatabaseTestCase
{
    // {{{ dropTestTable
    public function dropTestTable()
    {
        $preparedStatement = $this->pdo->prepare('DROP TABLE test');
        $preparedStatement->execute();
    }
    // }}}
    // {{{ setUp
    public function setUp()
    {
        parent::setUp();
        $this->pdo      = self::$pdo;
        $this->schema   = new Schema($this->pdo);
    }
    // }}}
    // {{{ tearDown
    public function tearDown()
    {
        $this->dropTestTable();
    }
    // }}}

    // {{{ testUpdateAndExecute
    public function testUpdateAndExecute()
    {
        $this->schema->load('Fixtures/TestFile.sql');

        $statement  = $this->pdo->query('SHOW CREATE TABLE test');
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
    // {{{ testVersionIdentifierMissingException
    public function testVersionIdentifierMissingException()
    {
        // create table without version comment
        $preparedStatement = $this->pdo->prepare("CREATE TABLE test (uid int(10) unsigned NOT NULL DEFAULT '0') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $preparedStatement->execute();

        // check if it's really there
        $statement  = $this->pdo->query('SHOW CREATE TABLE test');
        $statement->execute();
        $row        = $statement->fetch();

        $expected   = "CREATE TABLE `test` (\n  `uid` int(10) unsigned NOT NULL DEFAULT '0'\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->assertEquals($expected, $row['Create Table']);

        // trigger exception
        try {
            $this->schema->load('Fixtures/TestFile.sql');
        } catch (Exceptions\VersionIdentifierMissingException $expeceted) {
            return;
        }
        $this->fail('Expected VersionIdentifierMissingException');
    }
    // }}}
}
