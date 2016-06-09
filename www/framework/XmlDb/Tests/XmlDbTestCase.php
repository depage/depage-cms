<?php

namespace Depage\XmlDb\Tests;

class XmlDbTestCase extends \PHPUnit_Extensions_Database_TestCase
{
    // {{{ variables
    protected $pdo = null;
    protected $conn = null;
    protected $namespaces = 'xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page"';
    // }}}

    // {{{ setUp
    protected function setUp() {
        $this->getConnection();
        $this->setForeignKeyChecks(false);
        parent::setUp();
        $this->setForeignKeyChecks(true);
    }
    // }}}
    // {{{ getSetUpOperation
    /**
     * From https://gist.github.com/mlively/1319731
     */
    public function getSetUpOperation()
    {
        return new \PHPUnit_Extensions_Database_Operation_Composite(
            [
                new PHPUnit_Extensions_Database_Operation_MySQL55Truncate(false),
                \PHPUnit_Extensions_Database_Operation_Factory::INSERT(),
            ]
        );
    }
    // }}}

    // {{{ getConnection
    final public function getConnection()
    {
        $this->pdo = new \Depage\Db\Pdo(
            $GLOBALS['DB_DSN'],
            $GLOBALS['DB_USER'],
            $GLOBALS['DB_PASSWD'],
            [
                'prefix' => 'xmldb',
                \PDO::ATTR_PERSISTENT => true,
            ]
        );
        $this->conn = $this->createDefaultDBConnection($this->pdo->getPdoObject(), $GLOBALS['DB_DBNAME']);

        return $this->conn;
    }
    // }}}
    // {{{ getDataSet
    protected function getDataSet() {
        return $this->createXMLDataSet(__DIR__.'/dataset.xml');
    }
    // }}}

    // {{{ setForeignKeyChecks
    protected function setForeignKeyChecks($enable) {
        $setString = 'SET FOREIGN_KEY_CHECKS=';
        $setString .= ($enable) ? '1;' : '0;';

        $this->pdo->exec($setString);
    }
    // }}}

    // {{{ prepareDatabase
    public static function prepareDatabase()
    {
        $pdo = new \Depage\Db\Pdo(
            $GLOBALS['DB_DSN'],
            $GLOBALS['DB_USER'],
            $GLOBALS['DB_PASSWD']
        );

        $schema = new \Depage\Db\Schema($pdo);
        $schema->setReplace(
            function ($name)
            {
                return 'xmldb_proj_test' . $name;
            }
        );
        $schema->loadGlob(__DIR__ . '/../Sql/*.sql');

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
        $schema->update();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
    }
    // }}}

    // {{{ tableExists
    protected function tableExists($tableName)
    {
        $exists = false;

        try {
            $this->pdo->query('SELECT 1 FROM ' . $tableName);
            $exists = true;
        } catch (\PDOException $expected) {
            // only catch "table doesn't exist" exception
            if ($expected->getCode() != '42S02') {
                throw $expected;
            }
        }

        return $exists;
    }
    // }}}
    // {{{ dropTable
    protected function dropTable($tableName)
    {
        $this->setForeignKeyChecks(false);
        $this->pdo->query('DROP TABLE IF EXISTS ' . $tableName);
        $this->setForeignKeyChecks(true);
        $this->assertFalse($this->tableExists($tableName));
    }
    // }}}
    // {{{ dropTables
    protected function dropTables($tableNames)
    {
        foreach($tableNames as $tableName) {
            $this->dropTable($tableName);
        }
    }
    // }}}
    // {{{ insertDummyDataIntoTable
    protected function insertDummyDataIntoTable($tableName)
    {
        $statement = $this->pdo->query('DESCRIBE ' . $tableName . ';');
        $statement->execute();
        while ($row = $statement->fetch()) {
            $values[] = '" "';
        }

        $this->setForeignKeyChecks(false);
        $rows = $this->pdo->exec('INSERT INTO ' . $tableName . ' VALUES (' . implode(',', $values) . ');');
        $this->assertEquals(1, $rows);
        $this->setForeignKeyChecks(true);
    }
    // }}}

    // {{{ assertTableEmpty
    protected function assertTableEmpty($tableName)
    {
        $statement = $this->pdo->query('SELECT COUNT(*) FROM ' . $tableName . ';');
        $statement->execute();
        $result = $statement->fetch();

        $this->assertEquals(0, $result['COUNT(*)']);
    }
    // }}}

    // {{{ removeAttribute
    protected function removeAttribute($attribute, $xmlString)
    {
        $regex = ' ' . preg_quote($attribute .'=') . '"[^"]*"';
        $result = preg_replace('#' . $regex . '#', '', $xmlString);

        return $result;
    }
    // }}}
    // {{{ removeAttributes
    protected function removeAttributes($attributes, $xmlString)
    {
        foreach ($attributes as $attribute) {
            $xmlString = $this->removeAttribute($attribute, $xmlString);
        }

        return $xmlString;
    }
    // }}}
    // {{{ assertEqualsIgnoreAttributes
    protected function assertEqualsIgnoreAttributes($expected, $actual, $attributes = [], $message = '')
    {
        $expectedWithoutAttributes = $this->removeAttributes($attributes, $expected);
        $actualWithoutAttributes = $this->removeAttributes($attributes, $actual);

        return $this->assertEquals($expectedWithoutAttributes, $actualWithoutAttributes, $message);
    }
    // }}}
    // {{{ assertEqualsIgnoreLastchange
    protected function assertEqualsIgnoreLastchange($expected, $actual, $message = '')
    {
        return $this->assertEqualsIgnoreAttributes(
            $expected,
            $actual,
            [
                'db:lastchange',
                'db:lastchangeUid',
            ],
            $message
        );
    }
    // }}}
    // {{{ assertXmlStringEqualsXmlStringIgnoreAttributes
    protected function assertXmlStringEqualsXmlStringIgnoreAttributes($expected, $actual, $attributes = [], $message = '')
    {
        $expectedWithoutAttributes = $this->removeAttributes($attributes, $expected);
        $actualWithoutAttributes = $this->removeAttributes($attributes, $actual);

        return $this->assertXmlStringEqualsXmlString($expectedWithoutAttributes, $actualWithoutAttributes, $message);
    }
    // }}}
    // {{{ assertXmlStringEqualsXmlStringIgnoreLastchange
    protected function assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $actual, $message = '')
    {
        return $this->assertXmlStringEqualsXmlStringIgnoreAttributes(
            $expected,
            $actual,
            [
                'db:lastchange',
                'db:lastchangeUid',
            ],
            $message
        );
    }
    // }}}

    // {{{ generateDomDocument
    protected function generateDomDocument($xml)
    {
        $doc = new \DomDocument();
        $doc->loadXml($xml);

        return $doc;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
