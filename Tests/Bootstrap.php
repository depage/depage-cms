<?php

require_once('../SqlParser.php');
require_once('../Schema.php');
require_once('../Exceptions/FileNotFoundException.php');
require_once('../Exceptions/TableNameMissingException.php');
require_once('../Exceptions/UnversionedCodeException.php');
require_once('../Exceptions/MultipleTableNamesException.php');
require_once('../Exceptions/VersionIdentifierMissingException.php');
require_once('../Exceptions/SQLExecutionException.php');
require_once('../Exceptions/SyntaxErrorException.php');

/* {{{ Generic_Tests_DatabaseTestCase */
class Generic_Tests_DatabaseTestCase extends PHPUnit_Extensions_Database_TestCase
{
    protected $pdo  = null;
    protected $conn = null;

    final public function getConnection()
    {
        $this->pdo = new PDO(
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
/* }}} */

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
