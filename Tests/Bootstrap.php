<?php

require_once('../SQLParser.php');
require_once('../Schema.php');
require_once('../Exceptions/FileNotFoundException.php');
require_once('../Exceptions/TableNameMissingException.php');
require_once('../Exceptions/UnversionedCodeException.php');
require_once('../Exceptions/MultipleTableNamesException.php');
require_once('../Exceptions/VersionIdentifierMissingException.php');

/* {{{ Generic_Tests_DatabaseTestCase */
abstract class Generic_Tests_DatabaseTestCase extends PHPUnit_Extensions_Database_TestCase
{
    static  protected $pdo  = null;
            protected $conn = null;

    final public function getConnection()
    {
        if ($this->conn === null) {
            if (self::$pdo == null) {
                self::$pdo = new PDO(
                    $GLOBALS['DB_DSN'],
                    $GLOBALS['DB_USER'],
                    $GLOBALS['DB_PASSWD']
                );
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
        }

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
