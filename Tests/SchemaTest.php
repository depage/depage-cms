<?php

use depage\DB\Schema;
use depage\DB\Exceptions;

/* {{{ SchemaTestClass */
class SchemaTestClass extends Schema
{
    public $executedStatements = array();
    public $currentTableVersion;
    public $updateTableName;
    public $updateVersion;

    protected function execute($number, $statements)
    {
        $this->executedStatements[$number] = $statements;
    }

    protected function updateTableVersion($tableName, $version) {
        $this->updateTableName  = $tableName;
        $this->updateVersion    = $version;
    }

    protected function currentTableVersion($tableName)
    {
        return $this->currentTableVersion;
    }
}
/* }}} */

class SchemaTest extends PHPUnit_Framework_TestCase
{
    /* {{{ setUp */
    public function setUp()
    {
        $this->schema = new SchemaTestClass('');
    }
    /* }}} */

    /* {{{ testLoadSpecificFileFail */
    public function testLoadSpecificFileFail()
    {
        try {
            $this->schema->loadFile('fileDoesntExist.sql');
        } catch (Exceptions\FileNotFoundException $expeceted) {
            return;
        }
        $this->fail('Expected FileNotFoundException');
    }
    /* }}} */
    /* {{{ testLoadBatchFail */
    public function testLoadBatchFail()
    {
        $this->setExpectedException('PHPUnit_Framework_Error_Warning');
        $this->schema->load('fileDoesntExist.sql');
    }
    /* }}} */
    /* {{{ testLoadNoTableName */
    public function testLoadNoTableName()
    {
        try {
            $this->schema->load('Fixtures/TestNoTableName.sql');
        } catch (Exceptions\TableNameMissingException $expeceted) {
            return;
        }
        $this->fail('Expected TableNameMissingException');
    }
    /* }}} */
    /* {{{ testLoadMultipleTableNames */
    public function testLoadMultipleTableNames()
    {
        try {
            $this->schema->load('Fixtures/TestMultipleTableNames.sql');
        } catch (Exceptions\MultipleTableNamesException $expeceted) {
            return;
        }
        $this->fail('Expected MultipleTableNamesException');
    }
    /* }}} */
    /* {{{ testLoadUnversionedCode */
    public function testLoadUnversionedCode()
    {
        try {
            $this->schema->load('Fixtures/TestUnversionedCode.sql');
        } catch (Exceptions\UnversionedCodeException $expeceted) {
            return;
        }
        $this->fail('Expected UnversionedCodeException');
    }
    /* }}} */
    /* {{{ testLoadIncompleteFile */
    public function testLoadIncompleteFile()
    {
        try {
            $this->schema->load('Fixtures/TestIncompleteFile.sql');
        } catch (Exceptions\SyntaxErrorException $expeceted) {
            return;
        }
        $this->fail('Expected SyntaxErrorException');
    }
    /* }}} */

    /* {{{ testProcessNewestVersion */
    public function testProcessNewestVersion()
    {
        $this->schema->currentTableVersion = 'version 0.2';
        $this->schema->load('Fixtures/TestFile.sql');

        $expected = array();
        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    /* }}} */
    /* {{{ testProcessUpdate */
    public function testProcessUpdate()
    {
        $this->schema->currentTableVersion = 'version 0.1';
        $this->schema->load('Fixtures/TestFile.sql');

        $expected = array(
            11 => array("ALTER TABLE test ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid"),
        );
        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    /* }}} */
    /* {{{ testProcessEntireFile */
    public function testProcessEntireFile()
    {
        $this->schema->currentTableVersion = '';
        $this->schema->load('Fixtures/TestFile.sql');

        $expected = array(
            7   => array("CREATE TABLE test ( uid int(10) unsigned NOT NULL DEFAULT '0', pid int(10) unsigned NOT NULL DEFAULT '0' ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"),
            11  => array("ALTER TABLE test ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid",),
        );

        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    /* }}} */
    /* {{{ testProcessConnections */
    public function testProcessConnections()
    {
        $this->schema->currentTableVersion = '';
        $this->schema->load('Fixtures/TestConnections.sql');

        $expected = array(
            9   => array("CREATE TABLE testTable ( uid int(10) unsigned NOT NULL DEFAULT '0', pid int(10) unsigned NOT NULL DEFAULT '0' ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"),
            15  => array("CREATE VIEW testView AS SELECT id, name FROM testConnection WHERE someCondition=TRUE"),
        );

        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    /* }}} */
    /* {{{ testProcessPrefixes */
    public function testProcessPrefixes()
    {
        $this->schema->currentTableVersion = '';
        $this->schema->setReplace(
            function ($tableName) {
                return 'testPrefix_' . $tableName;
            }
        );
        $this->schema->load('Fixtures/TestConnections.sql');

        $expected = array(
            9   => array("CREATE TABLE testPrefix_testTable ( uid int(10) unsigned NOT NULL DEFAULT '0', pid int(10) unsigned NOT NULL DEFAULT '0' ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"),
            15  => array("CREATE VIEW testPrefix_testView AS SELECT id, name FROM testPrefix_testConnection WHERE someCondition=TRUE"),
        );

        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    /* }}} */
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
