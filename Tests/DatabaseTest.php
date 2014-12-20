<?php

use depage\DB\Schema;

// {{{ DatabaseSchemaTestClassddd
class DatabaseSchemaTestClass extends Schema
{
    public function currentTableVersion($tableName)
    {
        return parent::currentTableVersion($tableName);
    }
}
// }}}

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
        $this->schema = new DatabaseSchemaTestClass($this->pdo);
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
        $statement = $this->pdo->query('SHOW CREATE TABLE test');
        $statement->execute();
        $row = $statement->fetch();

        return $row['Create Table'];
    }
    // }}}

    // {{{ testCompleteUpdate
    public function testCompleteUpdate()
    {
        $this->schema->loadFile(__DIR__ . '/Fixtures/TestFile.sql');
        $this->schema->update();
        $this->assertEquals($this->finalShowCreate, $this->showCreateTestTable());
    }
    // }}}
    // {{{ testIncrementalUpdates
    public function testIncrementalUpdates()
    {
        $this->schema->loadFile(__DIR__ . '/Fixtures/TestFilePart.sql');
        $this->schema->update();

        $firstVersion = "CREATE TABLE `test` (\n" .
        "  `uid` int(10) unsigned NOT NULL DEFAULT '0',\n" .
        "  `pid` int(10) unsigned NOT NULL DEFAULT '0'\n" .
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.1'";

        $this->assertEquals($firstVersion, $this->showCreateTestTable());

        $this->schema->loadFile(__DIR__ . '/Fixtures/TestFile.sql');
        $this->schema->update();
        $this->assertEquals($this->finalShowCreate, $this->showCreateTestTable());
    }
    // }}}
    // {{{ testUpToDate
    public function testUpToDate()
    {
        $this->schema->loadFile(__DIR__ . '/Fixtures/TestFile.sql');
        $this->schema->update();
        $this->assertEquals($this->finalShowCreate, $this->showCreateTestTable());

        $this->schema->loadFile(__DIR__ . '/Fixtures/TestFile.sql');
        $this->schema->update();
        $this->assertEquals($this->finalShowCreate, $this->showCreateTestTable());
    }
    // }}}
    // {{{ testDryRun
    public function testDryRun()
    {
        $this->schema->loadFile('Fixtures/TestFile.sql');

        $expected = array(
            'CREATE TABLE test ( uid int(10) unsigned NOT NULL DEFAULT \'0\', pid int(10) unsigned NOT NULL DEFAULT \'0\' ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'ALTER TABLE test ADD COLUMN did int(10) unsigned NOT NULL DEFAULT \'0\' AFTER pid',
            'ALTER TABLE test COMMENT \'version 0.2\''
        );

        $this->assertEquals($expected, $this->schema->dryRun());

        $statement = $this->pdo->query('SHOW TABLES LIKE \'test\'');
        $statement->execute();
        $row = $statement->fetch();

        $this->assertFalse($row);
    }
    // }}}

    // {{{ testPDOException
    public function testPDOException()
    {
        $expectedMessage =  'SQLSTATE[42000]: Syntax error or access violation: ' .
                            '1064 You have an error in your SQL syntax; ' .
                            'check the manual that corresponds to your MySQL server version ' .
                            'for the right syntax to use near \'=InnoDB DEFAULT CHARSET=utf8mb4\' at line 7';

        $this->schema->loadFile(__DIR__ . '/Fixtures/TestSyntaxError.sql');

        try {
            $this->schema->update();
        } catch (PDOException $e) {
            $this->assertEquals($expectedMessage, $e->getMessage());
            $this->assertEquals(7, $e->getLine());

            return;
        }

        $this->fail();
    }
    // }}}
    // {{{ testVersionIdentifierMissingException
    /**
     * @expectedException        depage\DB\Exceptions\SchemaException
     * @expectedExceptionMessage Missing version identifier in table "test".
     */
    public function testVersionIdentifierMissingException()
    {
        // create table without version comment
        $preparedStatement = $this->pdo->prepare("CREATE TABLE test (uid int(10) unsigned NOT NULL DEFAULT '0') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $preparedStatement->execute();

        // check if it's really there
        $expected = "CREATE TABLE `test` (\n  `uid` int(10) unsigned NOT NULL DEFAULT '0'\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->assertEquals($expected, $this->showCreateTestTable());

        // trigger exception
        $this->schema->loadFile(__DIR__ . '/Fixtures/TestFile.sql');
        $this->schema->update();
    }
    // }}}
    // {{{ testCurrentTableVersion
    public function testCurrentTableVersion()
    {
        $this->schema->loadFile(__DIR__ . '/Fixtures/TestFile.sql');
        $this->schema->update();
        $this->assertEquals('version 0.2', $this->schema->currentTableVersion('test'));
    }
    // }}}
    // {{{ testCurrentTableVersionFallback
    public function testCurrentTableVersionFallback()
    {
        $this->pdo->queryFail = 'SELECT TABLE_COMMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = "test" LIMIT 1';
        $this->schema->loadFile(__DIR__ . '/Fixtures/TestFile.sql');
        $this->schema->update();
        $this->assertEquals('version 0.2', $this->schema->currentTableVersion('test'));
    }
    // }}}
    // {{{ testVersionIdentifierMissingFallbackException
    /**
     * @expectedException        depage\DB\Exceptions\SchemaException
     * @expectedExceptionMessage Missing version identifier in table "test".
     */
    public function testVersionIdentifierMissingFallbackException()
    {
        // create table without version comment
        $preparedStatement = $this->pdo->prepare("CREATE TABLE test (uid int(10) unsigned NOT NULL DEFAULT '0') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $preparedStatement->execute();

        // check if it's really there
        $expected = "CREATE TABLE `test` (\n  `uid` int(10) unsigned NOT NULL DEFAULT '0'\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->assertEquals($expected, $this->showCreateTestTable());

        // make information_schema unavailable
        $this->pdo->queryFail = 'SELECT TABLE_COMMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = "test" LIMIT 1';

        // trigger exception
        $this->schema->loadFile(__DIR__ . '/Fixtures/TestFile.sql');
        $this->schema->update();
    }
    // }}}
    // {{{ testTableExistsPDOException
    /**
     * @expectedException        PDOException
     */
    public function testTableExistsPDOException()
    {
        // make select statement fail
        $this->pdo->queryFail = 'SELECT 1 FROM test';

        // trigger exception
        $this->schema->loadFile(__DIR__ . '/Fixtures/TestFile.sql');
        $this->schema->update();
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
