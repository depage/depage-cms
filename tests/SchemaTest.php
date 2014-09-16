<?php

use depage\DB\Schema;

// {{{ SchemaTestClass
class SchemaTestClass extends Schema
{
    public $executedStatements = array();
    public $currentTableVersion;

    public function getSql()
    {
        return $this->sql;
    }

    protected function run($statement, $lineNumber)
    {
        $this->executedStatements[] = $lineNumber . ":" . $statement;
    }

    protected function currentTableVersion($tableName)
    {
        return $this->currentTableVersion;
    }

    public function execute($line, $number)
    {
        parent::execute($line, $number);
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
        $contents = "# Version: version 0.1\n" .
            "\tCREATE TABLE test (\n" .
            "\t\tuid int(10) unsigned NOT NULL DEFAULT '0',\n" .
            "\t) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.1';\n" .
            "# Version: version 0.2\n" .
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
        $this->schema->load(array('testFile'));

        $testArray = array(
            'testFile' => array(
                '0.1' => array(
                    1 => "# Version: version 0.1\n" ,
                    2 => "\tCREATE TABLE test (\n",
                    3 => "\t\tuid int(10) unsigned NOT NULL DEFAULT '0',\n",
                    4 => "\t) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.1';\n",
                ),
                '0.2' => array(
                    5 => "# Version: version 0.2\n",
                    6 => "\tALTER TABLE test\n",
                    7 => "\tADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid;\n",
                    8 => "\tALTER TABLE test\n",
                    9 => "\tCOMMENT 'version 0.2';\n",
                ),
            ),
        );
        $this->assertEquals($testArray, $this->schema->getSql());
    }
    // }}}
    // {{{ testPreperation1
    public function testPreperation1()
    {
        $this->schema->currentTableVersion = '0.2';
        $this->schema->load(array('testFile'));
        $this->schema->update();

        $testArray = array();
        $this->assertEquals($testArray, $this->schema->executedStatements);
    }
    // }}}
    // {{{ testPreperation2
    public function testPreperation2()
    {
        $this->schema->currentTableVersion = '0.1';
        $this->schema->load(array('testFile'));
        $this->schema->update();

        $testArray = array(
            "7:ALTER TABLE test ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid",
            "9:ALTER TABLE test COMMENT 'version 0.2'",
        );
        $this->assertEquals($testArray, $this->schema->executedStatements);
    }
    // }}}
    // {{{ testPreperation3
    public function testPreperation3()
    {
        $this->schema->currentTableVersion = false;
        $this->schema->load(array('testFile'));
        $this->schema->update();

        $testArray = array(
            "4:CREATE TABLE test ( uid int(10) unsigned NOT NULL DEFAULT '0', ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.1'",
            "7:ALTER TABLE test ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid",
            "9:ALTER TABLE test COMMENT 'version 0.2'",
        );

        $this->assertEquals($testArray, $this->schema->executedStatements);
    }
    // }}}
    // {{{ testSqlParsing
    public function testSqlParsing()
    {
        // simple incomplete statement
        $this->schema->execute("ALTER TABLE", 1);
        $this->assertEquals(array(), $this->schema->executedStatements);

        // completed...
        $this->schema->execute("test COMMENT 'version 0.2';", 2);
        $this->assertEquals("2:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->executedStatements[0]);
        $this->schema->executedStatements = array();

        // simple complete statement
        $this->schema->execute("ALTER TABLE test COMMENT 'version 0.2';", 1);
        $this->assertEquals("1:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->executedStatements[0]);
        $this->schema->executedStatements = array();

        // two complete statements in one line
        $this->schema->execute("ALTER TABLE test COMMENT 'version 0.2'; ALTER TABLE test COMMENT 'version 0.3';", 1);
        $this->assertEquals("1:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->executedStatements[0]);
        $this->assertEquals("1:ALTER TABLE test COMMENT 'version 0.3'", $this->schema->executedStatements[1]);
        $this->schema->executedStatements = array();

        // remove comments
        // simple incomplete statement with hash comment
        $this->schema->execute('ALTER TABLE # comment', 1);
        $this->assertEquals(array(), $this->schema->executedStatements);

        // simple incomplete statement with double dash comment
        $this->schema->execute('test -- comment', 2);
        $this->assertEquals(array(), $this->schema->executedStatements);

        // completed with multiline comment in single line
        $this->schema->execute("COMMENT  /* comment */ 'version 0.2';", 3);
        $this->assertEquals("3:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->executedStatements[0]);
        $this->schema->executedStatements = array();

        $this->schema->execute("ALTER TABLE", 1);
        $this->schema->execute("/* comment", 2);
        $this->schema->execute("comment", 3);
        $this->schema->execute("comment", 4);
        $this->schema->execute("comment */ test", 5);
        $this->schema->execute("COMMENT 'version 0.2\';", 6);
        $this->assertEquals("6:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->executedStatements[0]);
    }
    // }}}

    // {{{ tearDown
    public function tearDown()
    {
        unlink('testFile.sql');
    }
    // }}}
}
