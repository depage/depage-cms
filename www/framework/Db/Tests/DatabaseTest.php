<?php

use depage\DB\Schema;

class SchemaDatabaseTest extends Generic_Tests_DatabaseTestCase
{
    // {{{ dropTestTable
    public function dropTestTable()
    {
        // table might not exist. so we catch the exception
        try {
            $preparedStatement = $this->pdo->prepare('DROP TABLE test');
            $preparedStatement->execute();
        } catch (\PDOException $expected) {}
    }
    // }}}
    // {{{ setUp
    public function setUp()
    {
        parent::setUp();
        $this->schema = new Schema($this->pdo);
        $this->dropTestTable();

        $this->finalShowCreate = "CREATE TABLE `test` (\n" .
        "  `uid` int(10) unsigned NOT NULL DEFAULT '0',\n" .
        "  `pid` int(10) unsigned NOT NULL DEFAULT '0',\n" .
        "  `did` int(10) unsigned NOT NULL DEFAULT '0'\n" .
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.2'";

    }
    // }}}
    // {{{ tearDown
    public function tearDown()
    {
        $this->dropTestTable();
    }
    // }}}
    // {{{ showCreateTestTable
    public function showCreateTestTable()
    {
        $statement  = $this->pdo->query('SHOW CREATE TABLE test');
        $statement->execute();
        $row        = $statement->fetch();

        return $row['Create Table'];
    }
    // }}}

    // {{{ testCompleteUpdate
    public function testCompleteUpdate()
    {
        $this->schema->loadFile('Fixtures/TestFile.sql');
        $this->assertEquals($this->finalShowCreate, $this->showCreateTestTable());
    }
    // }}}
    // {{{ testUpToDate
    public function testUpToDate()
    {
        $this->schema->loadFile('Fixtures/TestFile.sql');
        $this->assertEquals($this->finalShowCreate, $this->showCreateTestTable());

        $this->schema->loadFile('Fixtures/TestFile.sql');
        $this->assertEquals($this->finalShowCreate, $this->showCreateTestTable());
    }
    // }}}

    // {{{ testVersionIdentifierMissingException
    public function testVersionIdentifierMissingException()
    {
        // create table without version comment
        $preparedStatement = $this->pdo->prepare("CREATE TABLE test (uid int(10) unsigned NOT NULL DEFAULT '0') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $preparedStatement->execute();

        // check if it's really there
        $expected   = "CREATE TABLE `test` (\n  `uid` int(10) unsigned NOT NULL DEFAULT '0'\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->assertEquals($expected, $this->showCreateTestTable());

        // trigger exception
        $this->setExpectedException('depage\DB\Exceptions\VersionIdentifierMissingException');
        $this->schema->loadFile('Fixtures/TestFile.sql');
    }
    // }}}
    // {{{ testSQLExecutionException
    public function testSQLExecutionException()
    {
        // trigger exception
        $this->setExpectedException('depage\DB\Exceptions\SQLExecutionException');
        $this->schema->loadFile('Fixtures/TestSyntaxErrorFile.sql');
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
