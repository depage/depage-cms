<?php

use depage\DB\Schema;

class SchemaTestClass extends Schema {
    public function getSql() {
        return $this->sql;
    }
}

class SchemaTest extends PHPUnit_Framework_TestCase {
    // {{{ setUp
    public function setUp() {
        $this->schema = new SchemaTestClass('');
    }
    // }}}
    // {{{ testLoad
    public function testLoad() {
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
        unlink('testFile.sql');
    }
    // }}}
}
