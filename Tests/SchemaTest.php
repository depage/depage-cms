<?php

use depage\DB\Schema;

// {{{ SchemaTestClass
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

    public function extractTag($split) {
        return parent::extractTag($split);
    }
}
// }}}

class SchemaTest extends PHPUnit_Framework_TestCase
{
    // {{{ setUp
    public function setUp()
    {
        $this->schema = new SchemaTestClass('');
    }
    // }}}

    // {{{ testLoadSpecificFileFail
    /**
     * @expectedException        depage\DB\Exceptions\SchemaException
     * @expectedExceptionMessage File "fileDoesntExist.sql" doesn't exist or isn't readable.
     */
    public function testLoadSpecificFileFail()
    {
        $this->schema->loadFile('fileDoesntExist.sql');
    }
    // }}}
    // {{{ testLoadGlobFailWarning
    /**
     * @expectedException        PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage No file found matching "fileDoesntExist.sql".
     */
    public function testLoadGlobFailWarning()
    {
        $this->schema->loadGlob('fileDoesntExist.sql');
    }
    // }}}
    // {{{ testLoadGlobFailExecution
    public function testLoadGlobFailExecution()
    {
        @$this->schema->loadGlob('fileDoesntExist.sql');

        $this->assertEquals(array(), $this->schema->executedStatements);
    }
    // }}}
    // {{{ testLoadNoTableName
    /**
     * @expectedException        depage\DB\Exceptions\SchemaException
     * @expectedExceptionMessage Tablename tag missing in "Fixtures/TestNoTableName.sql".
     */
    public function testLoadNoTableName()
    {
        $this->schema->loadGlob('Fixtures/TestNoTableName.sql');
    }
    // }}}
    // {{{ testLoadMultipleTableNames
    /**
     * @expectedException        depage\DB\Exceptions\SchemaException
     * @expectedExceptionMessage More than one tablename tags in "Fixtures/TestMultipleTableNames.sql".
     */
    public function testLoadMultipleTableNames()
    {
        $this->schema->loadGlob('Fixtures/TestMultipleTableNames.sql');
    }
    // }}}
    // {{{ testLoadUnversionedCode
    /**
     * @expectedException        depage\DB\Exceptions\SchemaException
     * @expectedExceptionMessage There is code without version tags in "Fixtures/TestUnversionedCode.sql" at line 4.
     */
    public function testLoadUnversionedCode()
    {
        $this->schema->loadGlob('Fixtures/TestUnversionedCode.sql');
    }
    // }}}
    // {{{ testLoadIncompleteFile
    /**
     * @expectedException        depage\DB\Exceptions\SchemaException
     * @expectedExceptionMessage Incomplete statement at the end of "Fixtures/TestIncompleteFile.sql".
     */
    public function testLoadIncompleteFile()
    {
        $this->schema->loadGlob('Fixtures/TestIncompleteFile.sql');
    }
    // }}}

    // {{{ testProcessNewestVersion
    public function testProcessNewestVersion()
    {
        $this->schema->tableExists          = true;
        $this->schema->currentTableVersion  = 'version 0.2';
        $this->schema->loadGlob('Fixtures/TestFile.sql');

        $expected = array();
        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    // }}}
    // {{{ testProcessUpdate
    public function testProcessUpdate()
    {
        $this->schema->tableExists          = true;
        $this->schema->currentTableVersion  = 'version 0.1';
        $this->schema->loadGlob('Fixtures/TestFile.sql');
        $this->schema->update();

        $expected = array(
            11 => array("ALTER TABLE test ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid"),
        );
        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    // }}}
    // {{{ testProcessEntireFile
    public function testProcessEntireFile()
    {
        $this->schema->tableExists = false;
        $this->schema->loadGlob('Fixtures/TestFile.sql');
        $this->schema->update();

        $expected = array(
            7   => array("CREATE TABLE test ( uid int(10) unsigned NOT NULL DEFAULT '0', pid int(10) unsigned NOT NULL DEFAULT '0' ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"),
            11  => array("ALTER TABLE test ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid",),
        );

        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    // }}}
    // {{{ testProcessUnknownVersionWarning
    /**
     * @expectedException        PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Current table version (bogus version) not in schema file.
     */
    public function testProcessUnknownVersionWarning()
    {
        $this->schema->tableExists = true;
        $this->schema->currentTableVersion = 'bogus version';
        $this->schema->loadGlob('Fixtures/TestFile.sql');
        $this->schema->update();
    }
    // }}}
    // {{{ testProcessUnknownVersionExecution
    public function testProcessUnknownVersionExecution()
    {
        $this->schema->tableExists          = true;
        $this->schema->currentTableVersion  = 'bogus version';
        @$this->schema->loadGlob('Fixtures/TestFile.sql');

        $this->assertEquals(array(), $this->schema->executedStatements);
    }
    // }}}
    // {{{ testProcessConnections
    public function testProcessConnections()
    {
        $this->schema->currentTableVersion = '';
        $this->schema->loadGlob('Fixtures/TestConnections.sql');
        $this->schema->update();

        $expected = array(
            9   => array("CREATE TABLE testTable ( uid int(10) unsigned NOT NULL DEFAULT '0', pid int(10) unsigned NOT NULL DEFAULT '0' ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"),
            15  => array("CREATE VIEW testView AS SELECT id, name FROM testConnection WHERE someCondition=TRUE"),
        );

        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    // }}}
    // {{{ testProcessPrefixes
    public function testProcessPrefixes()
    {
        $this->schema->currentTableVersion = '';
        $this->schema->setReplace(
            function ($tableName) {
                return 'testPrefix_' . $tableName;
            }
        );
        $this->schema->loadGlob('Fixtures/TestConnections.sql');
        $this->schema->update();

        $expected = array(
            9   => array("CREATE TABLE testPrefix_testTable ( uid int(10) unsigned NOT NULL DEFAULT '0', pid int(10) unsigned NOT NULL DEFAULT '0' ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"),
            15  => array("CREATE VIEW testPrefix_testView AS SELECT id, name FROM testPrefix_testConnection WHERE someCondition=TRUE"),
        );

        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    // }}}
    // {{{ testProcessBackticks
    public function testProcessBackticks()
    {
        $this->schema->currentTableVersion = '';
        $this->schema->loadGlob('Fixtures/TestBackticks.sql');
        $this->schema->update();

        $expected = array(
            9   => array("CREATE TABLE `table backticks` ( uid int(10) unsigned NOT NULL DEFAULT '0', pid int(10) unsigned NOT NULL DEFAULT '0' ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"),
            15  => array("CREATE VIEW `view backticks` AS SELECT id, name FROM `connection backticks` WHERE someCondition=TRUE"),
        );

        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    // }}}

    // {{{ testExtractTagNoTag
    public function testExtractTagNoTag()
    {
        $noTag = array(
            '@version'      => false,
            '@tablename'    => false,
            '@connection'   => false,
        );

        // empty
        $this->assertEquals($noTag, $this->schema->extractTag(array()));

        // unknown tag
        $testUnknown = array(
            array(
                'type'      => 'comment',
                'string'    => '# @bogusTag imakenosense',
            ),
        );
        $this->assertEquals($noTag, $this->schema->extractTag($testUnknown));

        // tag outside comments
        $testOutsideComments = array(
            array(
                'type'      => 'code',
                'string'    => '# @connection testConnection',
            ),
        );
        $this->assertEquals($noTag, $this->schema->extractTag($testOutsideComments));

        // empty tag
        $testEmpty = array(
            array(
                'type'      => 'comment',
                'string'    => '# @version ',
            ),
        );
        $this->assertEquals($noTag, $this->schema->extractTag($testEmpty));

        // empty tag
        $testEmpty = array(
            array(
                'type'      => 'comment',
                'string'    => '/* @version */',
            ),
        );
        $this->assertEquals($noTag, $this->schema->extractTag($testEmpty));
    }
    // }}}
    // {{{ testExtractTagSimple
    public function testExtractTagSimple()
    {
        // simple version tag
        $testVersion = array(
            array(
                'type'      => 'comment',
                'string'    => '# @version 42',
            ),
        );
        $expectedVersion = array(
            '@version'      => '42',
            '@tablename'    => false,
            '@connection'   => false,
        );
        $this->assertEquals($expectedVersion, $this->schema->extractTag($testVersion));

        // simple tablename tag
        $testTablename = array(
            array(
                'type'      => 'comment',
                'string'    => '# @tablename testTable',
            ),
        );
        $expectedTablename = array(
            '@version'      => false,
            '@tablename'    => 'testTable',
            '@connection'   => false,
        );
        $this->assertEquals($expectedTablename, $this->schema->extractTag($testTablename));

        // simple connection tag
        $testConnection = array(
            array(
                'type'      => 'comment',
                'string'    => '# @connection testConnection',
            ),
        );
        $expectedConnection = array(
            '@version'      => false,
            '@tablename'    => false,
            '@connection'   => 'testConnection',
        );
        $this->assertEquals($expectedConnection, $this->schema->extractTag($testConnection));
    }
    // }}}
    // {{{ testExtractTagFilter
    public function testExtractTagFilter()
    {
        $testFilter = array(
            array(
                'type'      => 'string',
                'string'    => '"# @version 1"',
            ),
            array(
                'type'      => 'break',
                'string'    => ';',
            ),
            array(
                'type'      => 'code',
                'string'    => '\n',
            ),
            array(
                'type'      => 'comment',
                'string'    => '/* @version 42 */',
            ),
            array(
                'type'      => 'code',
                'string'    => ' @version 2',
            ),
        );
        $expectedFilter = array(
            '@version'      => '42',
            '@tablename'    => false,
            '@connection'   => false,
        );
        $this->assertEquals($expectedFilter, $this->schema->extractTag($testFilter));
    }
    // }}}
    // {{{ testTagSubstringException
    /**
     * @expectedException        depage\DB\Exceptions\SchemaException
     * @expectedExceptionMessage Tags cannot be substrings of each other
     */
    public function testTagSubstringException()
    {
        $this->schema->loadFile('Fixtures/TestTagSubstring.sql');
    }
    // }}}

}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
