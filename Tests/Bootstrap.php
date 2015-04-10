<?php

// @todo reenable once composer installs are available
//require_once(__DIR__ . '/../vendor/autoload.php');

// @todo delete once composer installs are available
require_once(__DIR__ . '/../vendor/depage-cache/Cache.php');
require_once(__DIR__ . '/../vendor/depage-cache/Providers/Uncached.php');

require_once(__DIR__ . '/../XmlGetter.php');
require_once(__DIR__ . '/../Document.php');
require_once(__DIR__ . '/../XmlDb.php');

// {{{ Generic_Tests_DatabaseTestCase
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

    // {{{ getDataSet
    protected function getDataSet() {
        return $this->createXMLDataSet(__DIR__.'/xmldb_dataset.xml');
    }
    // }}}

    protected function setUp() {
        $this->getConnection();
    }
}
// }}}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
