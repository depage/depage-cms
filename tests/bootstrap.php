<?php

require_once('../Schema.php');

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
        parent::commit($line . "\n", $number);
    }
}
// }}}

