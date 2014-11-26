<?php

use depage\DB\Schema;

/* {{{ SchemaTestClass */
class SchemaTestClass extends Schema
{
    public $executedStatements = array();
    public $currentTableVersion;
    public $updateTableName;
    public $updateVersion;
    public $tableExists;

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

    protected function tableExists($tableName)
    {
        return $this->tableExists;
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
        $this->setExpectedException('depage\DB\Exceptions\FileNotFoundException');
        $this->schema->loadFile('fileDoesntExist.sql');
    }
    /* }}} */
    /* {{{ testLoadBatchFail */
    public function testLoadBatchFail()
    {
        $this->setExpectedException('PHPUnit_Framework_Error_Warning');
        $this->schema->loadGlob('fileDoesntExist.sql');
    }
    /* }}} */
    /* {{{ testLoadNoTableName */
    public function testLoadNoTableName()
    {
        $this->setExpectedException('depage\DB\Exceptions\TableNameMissingException');
        $this->schema->loadGlob('Fixtures/TestNoTableName.sql');
    }
    /* }}} */
    /* {{{ testLoadMultipleTableNames */
    public function testLoadMultipleTableNames()
    {
        $this->setExpectedException('depage\DB\Exceptions\MultipleTableNamesException');
        $this->schema->loadGlob('Fixtures/TestMultipleTableNames.sql');
    }
    /* }}} */
    /* {{{ testLoadUnversionedCode */
    public function testLoadUnversionedCode()
    {
        $this->setExpectedException('depage\DB\Exceptions\UnversionedCodeException');
        $this->schema->loadGlob('Fixtures/TestUnversionedCode.sql');
    }
    /* }}} */
    /* {{{ testLoadIncompleteFile */
    public function testLoadIncompleteFile()
    {
        $this->setExpectedException('depage\DB\Exceptions\SyntaxErrorException');
        $this->schema->loadGlob('Fixtures/TestIncompleteFile.sql');
    }
    /* }}} */

    /* {{{ testProcessNewestVersion */
    public function testProcessNewestVersion()
    {
        $this->schema->tableExists          = true;
        $this->schema->currentTableVersion  = 'version 0.2';
        $this->schema->loadGlob('Fixtures/TestFile.sql');

        $expected = array();
        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    /* }}} */
    /* {{{ testProcessUpdate */
    public function testProcessUpdate()
    {
        $this->schema->tableExists          = true;
        $this->schema->currentTableVersion  = 'version 0.1';
        $this->schema->loadGlob('Fixtures/TestFile.sql');

        $expected = array(
            11 => array("ALTER TABLE test ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid"),
        );
        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    /* }}} */
    /* {{{ testProcessEntireFile */
    public function testProcessEntireFile()
    {
        $this->schema->tableExists = false;
        $this->schema->loadGlob('Fixtures/TestFile.sql');

        $expected = array(
            7   => array("CREATE TABLE test ( uid int(10) unsigned NOT NULL DEFAULT '0', pid int(10) unsigned NOT NULL DEFAULT '0' ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"),
            11  => array("ALTER TABLE test ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid",),
        );

        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    /* }}} */
    /* {{{ testProcessUnknownVersion */
    public function testProcessUnknownVersion()
    {
        $this->schema->tableExists          = true;
        $this->schema->currentTableVersion  = 'bogus version';
        $this->schema->loadGlob('Fixtures/TestFile.sql');

        $expected = array();

        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    /* }}} */
    /* {{{ testProcessConnections */
    public function testProcessConnections()
    {
        $this->schema->currentTableVersion = '';
        $this->schema->loadGlob('Fixtures/TestConnections.sql');

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
        $this->schema->loadGlob('Fixtures/TestConnections.sql');

        $expected = array(
            9   => array("CREATE TABLE testPrefix_testTable ( uid int(10) unsigned NOT NULL DEFAULT '0', pid int(10) unsigned NOT NULL DEFAULT '0' ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"),
            15  => array("CREATE VIEW testPrefix_testView AS SELECT id, name FROM testPrefix_testConnection WHERE someCondition=TRUE"),
        );

        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    /* }}} */
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
