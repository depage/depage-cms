<?php

require_once('../SQLParser.php');
require_once('../Schema.php');

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

