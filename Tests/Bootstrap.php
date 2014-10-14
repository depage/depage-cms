<?php

require_once('../SQLParser.php');
require_once('../Schema.php');
require_once('../Exceptions/SchemaException.php');


// {{{ PDOTestClass
class PDOTestClass extends PDO
{
    public $queryFail = '';

    public function query($query) {
        if ($query == $this->queryFail) {
            throw new PDOException();
        } else {
            return parent::query($query);
        }
    }
}
// }}}
// {{{ Generic_Tests_DatabaseTestCase
class Generic_Tests_DatabaseTestCase extends PHPUnit_Extensions_Database_TestCase
{
    protected $pdo  = null;
    protected $conn = null;

    final public function getConnection()
    {
        $this->pdo = new PDOTestClass(
            $GLOBALS['DB_DSN'],
            $GLOBALS['DB_USER'],
            $GLOBALS['DB_PASSWD']
        );
        $this->conn = $this->createDefaultDBConnection($this->pdo, $GLOBALS['DB_DBNAME']);

        return $this->conn;
    }

    protected function getDataSet()
    {
        return new PHPUnit_Extensions_Database_DataSet_QueryDataSet($this->getConnection());
    }

    protected function setUp() {
        $this->getConnection();
    }
}
// }}}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
