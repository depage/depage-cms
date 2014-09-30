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

        $testFile = fopen('testFile.sql', 'w');
        $contents = "# @tablename test\n" .
            "# @version version 0.1\n" .
            "\tCREATE TABLE test (\n" .
            "\t\tuid int(10) unsigned NOT NULL DEFAULT '0',\n" .
            "\t) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.1';\n" .
            "# @version version 0.2\n" .
            "\tALTER TABLE test\n" .
            "\tADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid;\n" .
            "\tALTER TABLE test\n" .
            "\tCOMMENT 'version 0.2';\n";

        fwrite($testFile, $contents);
        fclose($testFile);
    }
    // }}}

    // {{{ testLoad
    public function testLoad()
    {
        $this->schema->load('testFile.sql');

        $expected = array(
            'testFile.sql' => array(
                'version 0.1' => array(
                    2   => "# @version version 0.1\n",
                    3   => "\tCREATE TABLE test (\n",
                    4   => "\t\tuid int(10) unsigned NOT NULL DEFAULT '0',\n",
                    5   => "\t) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.1';\n",
                ),
                'version 0.2' => array(
                    6   => "# @version version 0.2\n",
                    7   => "\tALTER TABLE test\n",
                    8   => "\tADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid;\n",
                    9   => "\tALTER TABLE test\n",
                    10  => "\tCOMMENT 'version 0.2';\n",
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

    // {{{ testPreperation1
    public function testPreperation1()
    {
        $this->schema->currentTableVersion = 'version 0.2';
        $this->schema->load('testFile.sql');
        $this->schema->update();

        $expected = array();
        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    // }}}
    // {{{ testPreperation2
    public function testPreperation2()
    {
        $this->schema->currentTableVersion = 'version 0.1';
        $this->schema->load('testFile.sql');
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
        $this->schema->load('testFile.sql');
        $this->schema->update();

        $expected = array(
            "CREATE TABLE test ( uid int(10) unsigned NOT NULL DEFAULT '0', ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.1'",
            "ALTER TABLE test ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid",
            "ALTER TABLE test COMMENT 'version 0.2'",
        );

        $this->assertEquals($expected, $this->schema->executedStatements);
    }
    // }}}

    // {{{ tearDown
    public function tearDown()
    {
        unlink('testFile.sql');
    }
    // }}}
}
