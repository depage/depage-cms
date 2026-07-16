<?php

namespace Depage\XmlDb\Tests;

class SchemaTest extends \PHPUnit\Framework\TestCase
{
    protected $pdo = null;

    // {{{ setUp
    protected function setUp(): void
    {
        parent::setUp();

        self::getConnection();
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
        return $this->pdo;
    }
    // }}}

    // {{{ tableExists
    protected function tableExists($tableName)
    {
        $exists = false;

        try {
            $this->pdo->query('SELECT 1 FROM ' . $tableName);
            $exists = true;
        } catch (\PDOException $e) {
            // only catch "table doesn't exist" exception
            if (!preg_match("/SQLSTATE\\[42S02\\]/", $e->getMessage())) {
                throw $e;
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
        foreach ($tableNames as $tableName) {
            $this->dropTable($tableName);
        }
    }
    // }}}

    // {{{ setForeignKeyChecks
    protected function setForeignKeyChecks($enable)
    {
        $setString = 'SET FOREIGN_KEY_CHECKS=';
        $setString .= ($enable) ? '1;' : '0;';

        $this->pdo->exec($setString);
    }
    // }}}

    // {{{ testUpdateSchema
    public function testUpdateSchema()
    {
        $tables = [
            'xmldb_proj_schema_test_xmldocs',
            'xmldb_proj_schema_test_xmltree',
            'xmldb_proj_schema_test_history',
            'xmldb_proj_schema_test_xmldeltaupdates',
        ];

        $this->dropTables($tables);

        $cache = \Depage\Cache\Cache::factory('xmldb', ['disposition' => 'uncached']);
        $xmlDb = new \Depage\XmlDb\XmlDb($this->pdo->prefix . '_proj_schema_test', $this->pdo, $cache);
        $xmlDb->updateSchema();

        foreach ($tables as $table) {
            $this->assertTrue($this->tableExists($table));
        }

        $this->dropTables($tables);
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
