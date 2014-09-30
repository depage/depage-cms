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

    protected function execute($statement)
    {
        $this->executedStatements[] = $statement;
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
        $this->schema->load('fixtures/TestFile.sql');

        $expected = array(
            'fixtures/TestFile.sql' => array(
                'version 0.1' => array(
                    3   => "# @version version 0.1\n",
                    4   => "    CREATE TABLE test (\n",
                    5   => "        uid int(10) unsigned NOT NULL DEFAULT '0',\n",
                    6   => "    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.1';\n",
                    7   => "\n",
                ),
                'version 0.2' => array(
                    8   => "# @version version 0.2\n",
                    9   => "    ALTER TABLE test\n",
                    10  => "    ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid;\n",
                    11  => "\n",
                    12  => "    ALTER TABLE test\n",
                    13  => "    COMMENT 'version 0.2';\n",
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
            $this->schema->load('fixtures/TestNoTableName.sql');
        } catch (Exceptions\TableNameMissingException $expeceted) {
            return;
        }
        $this->fail('Expected TableNameMissingException');
    }
    // }}}

    // {{{ testPreperation1
    public function testPreperation1()
    {
        $this->schema->currentTableVersion = 'version 0.2';
        $this->schema->load('fixtures/TestFile.sql');
        $this->schema->update();

        $expected = array();
        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    // }}}
    // {{{ testPreperation2
    public function testPreperation2()
    {
        $this->schema->currentTableVersion = 'version 0.1';
        $this->schema->load('fixtures/TestFile.sql');
        $this->schema->update();

        $expected = array(
            "ALTER TABLE test ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid",
            "ALTER TABLE test COMMENT 'version 0.2'",
        );
        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    // }}}
    // {{{ testPreperation3
    public function testPreperation3()
    {
        $this->schema->currentTableVersion = '';
        $this->schema->load('fixtures/TestFile.sql');
        $this->schema->update();

        $expected = array(
            "CREATE TABLE test ( uid int(10) unsigned NOT NULL DEFAULT '0', ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.1'",
            "ALTER TABLE test ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid",
            "ALTER TABLE test COMMENT 'version 0.2'",
        );

        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    // }}}
}
