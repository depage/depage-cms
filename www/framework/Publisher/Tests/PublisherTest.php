<?php

/**
 * Tests Publisher Class
 **/
class PublisherTest extends \PHPUnit_Extensions_Database_TestCase
{
    // {{{ setUp()
    /**
     * setup function
     **/
    public function setUp()
    {
        parent::setUp();
        $this->source = __DIR__ . "/tmp/src/";
        $this->target = __DIR__ . "/tmp/target/";

        if (!is_dir($this->source)) {
            mkdir($this->source, 0777, true);
        }
        if (!is_dir($this->target)) {
            mkdir($this->target, 0777, true);
        }

        $this->fs = Depage\Fs\Fs::factory($this->target);
        $this->pdo = new Depage\Db\Pdo(
            "mysql:dbname=depage_phpunit;host=localhost",
            "root",
            ""
        );

        $this->publisher = new Depage\Publisher\Publisher($this->pdo, $this->fs, 1);
    }
    // }}}
    // {{{ tearDown()
    /**
     * setup function
     **/
    public function tearDown()
    {
        $this->rmr(__DIR__ . "/tmp/");
    }
    // }}}
    // {{{ rmr
    protected function rmr($path)
    {
        if (is_dir($path)) {
            $scanDir = array_diff(scandir($path), array('.', '..'));

            foreach ($scanDir as $nested) {
                $this->rmr($path . '/' . $nested);
            }
            rmdir($path);
        } elseif (is_file($path)) {
            unlink($path);
        }
    }
    // }}}
    // {{{ getConnection()
    /**
     * gets database connection
     */
    protected function getConnection() {
        $pdo = new \Pdo(
            "mysql:dbname=depage_phpunit;host=localhost",
            "root",
            ""
        );
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $schema = new \Depage\DB\Schema($pdo);

        $schema->setReplace(
            function ($tableName) {
                return str_replace("_proj_PROJECTNAME", "publisher_test", $tableName);
            }
        );
        $schema->loadGlob(__DIR__ . "/../Sql/*.sql");
        $schema->update();

        return $this->createDefaultDBConnection($pdo, 'testdb');
    }
    // }}}
    // {{{ getDataSet()
    /**
     * gets dataset
     */
    protected function getDataSet() {
        return $this->createArrayDataSet(array(
            'publisher_test_published_files' => array(
                array(
                    'id' => 1,
                    'pid' => 1,
                    'filename' => "testfile.txt",
                    'sha1' => sha1("test"),
                    'lastmod' => "2015-04-24 17:15:23",
                    'exist' => 1,
                ),
            ),
        ));
    }
    // }}}

    // {{{ testPublishConnection()
    /**
     * @brief testPublishConnection
     **/
    public function testPublishConnection()
    {
        $value = $this->publisher->testConnection();

        $this->assertTrue($value);
    }
    // }}}
    // {{{ testUnavailablePublishConnection()
    /**
     * @brief testUnavailablePublishConnection
     **/
    public function testUnavailablePublishConnection()
    {
        $fs = Depage\Fs\Fs::factory("ftp://unknown:unknown@unknownserver.unknowndomain/nodir/");

        $this->publisher = new Depage\Publisher\Publisher($this->pdo, $fs, 1);
        $value = $this->publisher->testConnection();

        $this->assertFalse($value);
    }
    // }}}
    // {{{ testPublishFile()
    /**
     * @brief testPublishFile
     **/
    public function testPublishFile()
    {
        $content = "abcd";
        file_put_contents($this->source . "test.txt", $content);
        $this->publisher->publishFile($this->source . "test.txt", "testFile.txt");
        $this->publisher->publishFile($this->source . "test.txt", "subdir/testFile.txt");

        $this->assertFileEquals($this->source . "test.txt", $this->target . "testFile.txt");
        $this->assertFileEquals($this->source . "test.txt", $this->target . "subdir/testFile.txt");
    }
    // }}}
    // {{{ testPublishString()
    /**
     * @brief testPublishString
     **/
    public function testPublishString()
    {
        $content = "testcontent";
        file_put_contents($this->source . "test.txt", $content);

        $this->publisher->publishString($content, "testString.txt");
        $this->publisher->publishString($content, "subdir/testString.txt");

        $this->assertFileEquals($this->source . "test.txt", $this->target . "testString.txt");
        $this->assertFileEquals($this->source . "test.txt", $this->target . "subdir/testString.txt");
    }
    // }}}
    // {{{ testUnpublishFile()
    /**
     * @brief testUnpublishFile
     **/
    public function testUnpublishFile()
    {
        $content = "abcd";
        file_put_contents($this->source . "test.txt", $content);

        $this->publisher->publishFile($this->source . "test.txt", "testFileDeleted.txt");
        $this->publisher->unpublishFile("testFileDeleted.txt");

        $this->assertFileNotExists($this->target . "testFileDeleted.txt");
    }
    // }}}
}

/* vim:set ft=php sts=4 fdm=marker et : */
