<?php

use depage\DB\Schema;
use depage\DB\Exceptions;

// {{{ SchemaTestClass
class SchemaTestClass extends Schema
{
    public $executedStatements = array();
    public $currentTableVersion;

    public function getSql()
    {
        return $this->sql;
    }

    protected function execute($number, $statements)
    {
        $this->executedStatements[$number] = $statements;
    }

    protected function currentTableVersion($tableName)
    {
        return $this->currentTableVersion;
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

    // {{{ testLoad
    public function testLoad()
    {
        $this->schema->load('Fixtures/TestFile.sql');

        $expected = array(
            'Fixtures/TestFile.sql' => array(
                'version 0.1' => array(
                    3   => "# @version version 0.1\n",
                    4   => "    CREATE TABLE test (\n",
                    5   => "        uid int(10) unsigned NOT NULL DEFAULT '0',\n",
                    6   => "        pid int(10) unsigned NOT NULL DEFAULT '0'\n",
                    7   => "    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.1';\n",
                    8   => "\n",
                ),
                'version 0.2' => array(
                    9   => "# @version version 0.2\n",
                    10  => "    ALTER TABLE test\n",
                    11  => "    ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid;\n",
                    12  => "\n",
                    13  => "    ALTER TABLE test\n",
                    14  => "    COMMENT 'version 0.2';\n",
                ),
            ),
        );
        $this->assertEquals($expected, $this->schema->getSql());
    }
    // }}}
    // {{{ testLoadMultipleFiles
    public function testLoadMultipleFiles()
    {
        $this->schema->load('Fixtures/TestFile*.sql');

        $expected = array(
            'Fixtures/TestFile.sql' => array(
                'version 0.1' => array(
                    3   => "# @version version 0.1\n",
                    4   => "    CREATE TABLE test (\n",
                    5   => "        uid int(10) unsigned NOT NULL DEFAULT '0',\n",
                    6   => "        pid int(10) unsigned NOT NULL DEFAULT '0'\n",
                    7   => "    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.1';\n",
                    8   => "\n",
                ),
                'version 0.2' => array(
                    9   => "# @version version 0.2\n",
                    10  => "    ALTER TABLE test\n",
                    11  => "    ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid;\n",
                    12  => "\n",
                    13  => "    ALTER TABLE test\n",
                    14  => "    COMMENT 'version 0.2';\n",
                ),
            ),
            'Fixtures/TestFile2.sql' => array(
                'version 0.1' => array(
                    3   => "# @version version 0.1\n",
                    4   => "    CREATE TABLE test2 (\n",
                    5   => "        uid int(10) unsigned NOT NULL DEFAULT '0',\n",
                    6   => "        pid int(10) unsigned NOT NULL DEFAULT '0'\n",
                    7   => "    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.1';\n",
                    8   => "\n",
                ),
                'version 0.2' => array(
                    9   => "# @version version 0.2\n",
                    10  => "    ALTER TABLE test2\n",
                    11  => "    ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid;\n",
                    12  => "\n",
                    13  => "    ALTER TABLE test2\n",
                    14  => "    COMMENT 'version 0.2';\n",
                ),
            ),
        );
        $this->assertEquals($expected, $this->schema->getSql());
    }
    // }}}
    // {{{ testLoadNoFile
    public function testLoadNoFile()
    {
        try {
            $this->schema->load('fileDoesntExist.sql');
        } catch (Exceptions\FileNotFoundException $expeceted) {
            return;
        }
        $this->fail('Expected FileNotFoundException');
    }
    // }}}
    // {{{ testLoadNoTableName
    public function testLoadNoTableName()
    {
        try {
            $this->schema->load('Fixtures/TestNoTableName.sql');
        } catch (Exceptions\TableNameMissingException $expeceted) {
            return;
        }
        $this->fail('Expected TableNameMissingException');
    }
    // }}}
    // {{{ testLoadMultipleTableNames
    public function testLoadMultipleTableNames()
    {
        try {
            $this->schema->load('Fixtures/TestMultipleTableNames.sql');
        } catch (Exceptions\MultipleTableNamesException $expeceted) {
            return;
        }
        $this->fail('Expected MultipleTableNamesException');
    }
    // }}}
    // {{{ testLoadUnversionedCode
    public function testLoadUnversionedCode()
    {
        try {
            $this->schema->load('Fixtures/TestUnversionedCode.sql');
        } catch (Exceptions\UnversionedCodeException $expeceted) {
            return;
        }
        $this->fail('Expected UnversionedCodeException');
    }
    // }}}

    // {{{ testProcessNewestVersion
    public function testProcessNewestVersion()
    {
        $this->schema->currentTableVersion = 'version 0.2';
        $this->schema->load('Fixtures/TestFile.sql');
        $this->schema->update();

        $expected = array();
        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    // }}}
    // {{{ testProcessUpdate
    public function testProcessUpdate()
    {
        $this->schema->currentTableVersion = 'version 0.1';
        $this->schema->load('Fixtures/TestFile.sql');
        $this->schema->update();

        $expected = array(
            11 => array("ALTER TABLE test ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid"),
            14 => array("ALTER TABLE test COMMENT 'version 0.2'",),
        );
        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    // }}}
    // {{{ testProcessEntireFile
    public function testProcessEntireFile()
    {
        $this->schema->currentTableVersion = '';
        $this->schema->load('Fixtures/TestFile.sql');
        $this->schema->update();

        $expected = array(
            7   => array("CREATE TABLE test ( uid int(10) unsigned NOT NULL DEFAULT '0', pid int(10) unsigned NOT NULL DEFAULT '0' ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.1'"),
            11  => array("ALTER TABLE test ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid",),
            14  => array("ALTER TABLE test COMMENT 'version 0.2'",),
        );

        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    // }}}
    // {{{ testProcessConnections
    public function testProcessConnections()
    {
        $this->schema->currentTableVersion = '';
        $this->schema->load('Fixtures/TestConnections.sql');
        $this->schema->update();

        $expected = array(
            9   => array("CREATE TABLE testTable ( uid int(10) unsigned NOT NULL DEFAULT '0', pid int(10) unsigned NOT NULL DEFAULT '0' ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.1'"),
            15  => array("CREATE VIEW testView AS SELECT id, name FROM testConnection WHERE someCondition=TRUE"),
        );

        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    // }}}
    // {{{ testProcessPrefixes
    public function testProcessPrefixes()
    {
        $this->schema->currentTableVersion = '';
        $this->schema->load('Fixtures/TestConnections.sql');
        $this->schema->setReplace(
            function ($tableName) {
                return 'testPrefix_' . $tableName;
            }
        );

        $this->schema->update();

        $expected = array(
            9   => array("CREATE TABLE testPrefix_testTable ( uid int(10) unsigned NOT NULL DEFAULT '0', pid int(10) unsigned NOT NULL DEFAULT '0' ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.1'"),
            15  => array("CREATE VIEW testPrefix_testView AS SELECT id, name FROM testPrefix_testConnection WHERE someCondition=TRUE"),
        );

        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    // }}}
}
