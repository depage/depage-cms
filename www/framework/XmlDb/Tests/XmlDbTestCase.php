<?php

namespace Depage\XmlDb\Tests;

class XmlDbTestCase extends \PHPUnit\Framework\TestCase
{
    // {{{ variables
    protected $pdo = null;
    protected $conn = null;
    protected $namespaces = 'xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:pg="http://www.depagecms.net/ns/page"';
    // }}}

    // {{{ setUp
    protected function setUp(): void
    {
        parent::setUp();
        //self::prepareDatabase();

        $this->pdo = null;

        self::getConnection();

        $this->pdo->beginTransaction();
    }
    // }}}
    // {{{ tearDown
    protected function tearDown(): void
    {
        $this->pdo->rollBack();

        parent::tearDown();
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

    // {{{ setForeignKeyChecks
    protected function setForeignKeyChecks($enable)
    {
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
            $GLOBALS['DB_PASSWD'],
            [
                'prefix' => 'xmldb',
                \PDO::ATTR_PERSISTENT => true,
            ]
        );
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');

        $tablesToDrop = [
            '_auth_user',
            '_proj_test_xmldocs',
            '_proj_test_xmltree',
            '_proj_test_history',
            '_proj_test_xmldeltaupdates',
        ];
        foreach ($tablesToDrop as $tableName) {
            $pdo->exec('DROP TABLE IF EXISTS ' . $pdo->prefix . $tableName . ';');
        }

        $schema = new \Depage\Db\Schema($pdo);

        $schema->setReplace(
            function ($name) {
                return 'xmldb_proj_test' . $name;
            }
        );
        $schema->loadGlob(__DIR__ . '/../Sql/*.sql');
        $schema->update();

        $schema->setReplace(
            function ($name) {
                return 'xmldb' . $name;
            }
        );
        $schema->loadGlob(__DIR__ . '/*.sql');
        $schema->update();

        $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
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
        $regex = ' ' . preg_quote($attribute . '=') . '"[^"]*"';
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
    // {{{ assertXmlStringEqualsXmlStringIgnoreAllDbAttributes
    protected function assertXmlStringEqualsXmlStringIgnoreAllDbAttributes($expected, $actual, $message = '')
    {
        return $this->assertXmlStringEqualsXmlStringIgnoreAttributes(
            $expected,
            $actual,
            [
                'db:lastchange',
                'db:lastchangeUid',
                'db:docid',
                'db:id',
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
