<?php

use depage\DB\Schema;

// {{{ SchemaTestClass
class SchemaTestClass extends Schema
{
    public $committedStatements = array();
    public $currentTableVersion;

    public function getSql()
    {
        return $this->sql;
    }

    protected function execute($statement, $lineNumber)
    {
        $this->committedStatements[] = $lineNumber . ":" . $statement;
    }

    protected function currentTableVersion($tableName)
    {
        return $this->currentTableVersion;
    }

    public function commit($line, $number)
    {
        parent::commit($line, $number);
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

        $testArray = array(
            'test' => array(
                'version 0.1' => array(
                    3   => "\tCREATE TABLE test (\n",
                    4   => "\t\tuid int(10) unsigned NOT NULL DEFAULT '0',\n",
                    5   => "\t) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.1';\n",
                ),
                'version 0.2' => array(
                    7   => "\tALTER TABLE test\n",
                    8   => "\tADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid;\n",
                    9   => "\tALTER TABLE test\n",
                    10  => "\tCOMMENT 'version 0.2';\n",
                ),
            ),
        );
        $this->assertEquals($testArray, $this->schema->getSql());
    }
    // }}}
    // {{{ testPreperation1
    public function testPreperation1()
    {
        $this->schema->currentTableVersion = 'version 0.2';
        $this->schema->load('testFile.sql');
        $this->schema->update();

        $testArray = array();
        $this->assertEquals($testArray, $this->schema->committedStatements);
    }
    // }}}
    // {{{ testPreperation2
    public function testPreperation2()
    {
        $this->schema->currentTableVersion = 'version 0.1';
        $this->schema->load('testFile.sql');
        $this->schema->update();

        $testArray = array(
            "8:ALTER TABLE test ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid",
            "10:ALTER TABLE test COMMENT 'version 0.2'",
        );
        $this->assertEquals($testArray, $this->schema->committedStatements);
    }
    // }}}
    // {{{ testPreperation3
    public function testPreperation3()
    {
        $this->schema->currentTableVersion = false;
        $this->schema->load('testFile.sql');
        $this->schema->update();

        $testArray = array(
            "5:CREATE TABLE test ( uid int(10) unsigned NOT NULL DEFAULT '0', ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.1'",
            "8:ALTER TABLE test ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid",
            "10:ALTER TABLE test COMMENT 'version 0.2'",
        );

        $this->assertEquals($testArray, $this->schema->committedStatements);
    }
    // }}}
    // {{{ testSqlParsing
    public function testSqlParsing()
    {
        // incomplete statement
        $this->schema->commit("ALTER TABLE", 1);
        $this->assertEquals(array(), $this->schema->committedStatements);

        // completed...
        $this->schema->commit("test COMMENT 'version 0.2';", 2);
        $this->assertEquals("2:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->committedStatements[0]);
        $this->schema->committedStatements = array();

        // complete statement
        $this->schema->commit("ALTER TABLE test COMMENT 'version 0.2';", 1);
        $this->assertEquals("1:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->committedStatements[0]);
        $this->schema->committedStatements = array();

        // two complete statements in one line
        $this->schema->commit("ALTER TABLE test COMMENT 'version 0.2'; ALTER TABLE test COMMENT 'version 0.3';", 1);
        $this->assertEquals("1:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->committedStatements[0]);
        $this->assertEquals("1:ALTER TABLE test COMMENT 'version 0.3'", $this->schema->committedStatements[1]);
        $this->schema->committedStatements = array();

        // remove comments
        // incomplete statement with hash comment
        $this->schema->commit('ALTER TABLE # comment', 1);
        $this->assertEquals(array(), $this->schema->committedStatements);

        // incomplete statement with double dash comment
        $this->schema->commit('test -- comment', 2);
        $this->assertEquals(array(), $this->schema->committedStatements);

        // completed with multiline comment in single line
        $this->schema->commit("COMMENT  /* comment */ 'version 0.2';", 3);
        $this->assertEquals("3:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->committedStatements[0]);
        $this->schema->committedStatements = array();

        // multiline comment
        $this->schema->commit("ALTER TABLE", 1);
        $this->schema->commit("/* comment", 2);
        $this->schema->commit("comment", 3);
        $this->schema->commit("comment", 4);
        $this->schema->commit("comment */ test", 5);
        $this->schema->commit("COMMENT 'version 0.2';", 6);
        $this->assertEquals("6:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->committedStatements[0]);
        $this->schema->committedStatements = array();

        // multiple multiline comments
        $this->schema->commit("ALTER /* comment", 1);
        $this->schema->commit("comment", 2);
        $this->schema->commit("comment */ TABLE /* comment", 3);
        $this->schema->commit("comment", 4);
        $this->schema->commit("comment */ test", 5);
        $this->schema->commit("COMMENT 'version 0.2';", 6);
        $this->assertEquals("6:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->committedStatements[0]);
        $this->schema->committedStatements = array();

        // complete statement with semicolon in single quoted string
        $this->schema->commit("ALTER TABLE test COMMENT 'vers;ion 0.2';", 1);
        $this->assertEquals("1:ALTER TABLE test COMMENT 'vers;ion 0.2'", $this->schema->committedStatements[0]);
        $this->schema->committedStatements = array();

        // complete statement with semicolon in double quoted string
        $this->schema->commit('ALTER TABLE test COMMENT "vers;ion 0.2";', 1);
        $this->assertEquals('1:ALTER TABLE test COMMENT "vers;ion 0.2"', $this->schema->committedStatements[0]);
        $this->schema->committedStatements = array();

        // incomplete statement with semicolon in hash comment
        $this->schema->commit('ALTER TABLE # ;', 1);
        $this->assertEquals(array(), $this->schema->committedStatements);

        // incomplete statement with semicolon in double dash comment
        $this->schema->commit('test -- ;', 2);
        $this->assertEquals(array(), $this->schema->committedStatements);

        // incomplete statement with semicolon in multiline comment
        $this->schema->commit('COMMENT /* ; */', 3);
        $this->assertEquals(array(), $this->schema->committedStatements);

        // ...completed
        $this->schema->commit("'version 0.2';", 4);
        $this->assertEquals("4:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->committedStatements[0]);
        $this->schema->committedStatements = array();
    }
    // }}}

    // {{{ tearDown
    public function tearDown()
    {
        unlink('testFile.sql');
    }
    // }}}
}
