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
            "", array(
                'prefix' => 'publisher_test',
            )
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
                    'publishId' => 1,
                    'filename' => "testfile.txt",
                    'hash' => hash("sha256", "test"),
                    'lastmod' => "2015-04-24 17:15:23",
                    'exist' => 1,
                ),
                array(
                    'id' => 2,
                    'publishId' => 1,
                    'filename' => "testfile2.txt",
                    'hash' => hash("sha256", "test2"),
                    'lastmod' => "2015-04-20 10:15:23",
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

        try {
            $value = $this->publisher->testConnection();
        } catch (\Depage\Publisher\Exceptions\PublisherException $e) {
            return;
        }

        $this->fail("Publisher Exception expected.");
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

        $this->publisher->publishFile($this->source . "test.txt", "testFile.txt", $updated);
        $this->assertFileEquals($this->source . "test.txt", $this->target . "testFile.txt");
        $this->assertTrue($updated);

        $this->publisher->publishFile($this->source . "test.txt", "subdir/testFile.txt", $updated);
        $this->assertFileEquals($this->source . "test.txt", $this->target . "subdir/testFile.txt");
        $this->assertTrue($updated);
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

        $this->publisher->publishString($content, "testString.txt", $updated);
        $this->assertFileEquals($this->source . "test.txt", $this->target . "testString.txt");
        $this->assertTrue($updated);

        $this->publisher->publishString($content, "subdir/testString.txt", $updated);
        $this->assertFileEquals($this->source . "test.txt", $this->target . "subdir/testString.txt");
        $this->assertTrue($updated);
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

    // {{{ testPublishFileNoUpdateNeeded()
    /**
     * @brief testPublishFileNoUpdateNeeded
     **/
    public function testPublishFileNoUpdateNeeded()
    {
        $content = "old";
        file_put_contents($this->source . "test.txt", $content);

        $this->publisher->publishFile($this->source . "test.txt", "test.txt", $updated);
        $this->assertFileEquals($this->source . "test.txt", $this->target . "test.txt");
        $this->assertTrue($updated);

        // file did not change
        $this->publisher->publishFile($this->source . "test.txt", "test.txt", $updated);
        $this->assertFileEquals($this->source . "test.txt", $this->target . "test.txt");
        $this->assertFalse($updated);
    }
    // }}}
    // {{{ testPublishFileUpdateNeeded()
    /**
     * @brief testPublishFileUpdateNeeded
     **/
    public function testPublishFileUpdateNeeded()
    {
        $content = "old";
        file_put_contents($this->source . "test.txt", $content);

        $this->publisher->publishFile($this->source . "test.txt", "test.txt", $updated);
        $this->assertFileEquals($this->source . "test.txt", $this->target . "test.txt");
        $this->assertTrue($updated);

        // file changed
        $content = "new";
        file_put_contents($this->source . "test.txt", $content);

        $this->publisher->publishFile($this->source . "test.txt", "test.txt", $updated);
        $this->assertFileEquals($this->source . "test.txt", $this->target . "test.txt");
        $this->assertTrue($updated);
    }
    // }}}
    // {{{ testPublishStringNoUpdateNeeded()
    /**
     * @brief testPublishStringNoUpdateNeeded
     **/
    public function testPublishStringNoUpdateNeeded()
    {
        $content = "old";
        file_put_contents($this->source . "test.txt", $content);

        $this->publisher->publishString($content, "test.txt", $updated);
        $this->assertFileEquals($this->source . "test.txt", $this->target . "test.txt");
        $this->assertTrue($updated);

        // content did not change
        $this->publisher->publishString($content, "test.txt", $updated);
        $this->assertFileEquals($this->source . "test.txt", $this->target . "test.txt");
        $this->assertFalse($updated);
    }
    // }}}
    // {{{ testPublishStringUpdateNeeded()
    /**
     * @brief testPublishStringUpdateNeeded
     **/
    public function testPublishStringUpdateNeeded()
    {
        $content = "old";
        file_put_contents($this->source . "test.txt", $content);

        $this->publisher->publishString($content, "test.txt", $updated);
        $this->assertFileEquals($this->source . "test.txt", $this->target . "test.txt");
        $this->assertTrue($updated);

        // content changed
        $content = "new";
        file_put_contents($this->source . "test.txt", $content);

        $this->publisher->publishString($content, "test.txt", $updated);
        $this->assertFileEquals($this->source . "test.txt", $this->target . "test.txt");
        $this->assertTrue($updated);
    }
    // }}}

    // {{{ testClearPublishedState()
    /**
     * @brief testClearPublishedState
     **/
    public function testClearPublishedState()
    {
        $content = "old";
        file_put_contents($this->source . "test.txt", $content);

        $this->publisher->publishFile($this->source . "test.txt", "test.txt", $updated);
        $this->assertFileEquals($this->source . "test.txt", $this->target . "test.txt");
        $this->assertTrue($updated);

        $this->publisher->clearPublishedState();

        // published state for file got reset -> so publishing file updates target
        $this->publisher->publishFile($this->source . "test.txt", "test.txt", $updated);
        $this->assertFileEquals($this->source . "test.txt", $this->target . "test.txt");
        $this->assertTrue($updated);
    }
    // }}}
    // {{{ testGetDeletedFiles()
    /**
     * @brief testGetDeletedFiles
     **/
    public function testGetDeletedFiles()
    {
        $content = "old";
        file_put_contents($this->source . "test.txt", $content);

        $this->publisher->publishFile($this->source . "test.txt", "test1.txt");
        $this->publisher->publishFile($this->source . "test.txt", "test2.txt");

        $this->publisher->resetPublishedState();

        $this->publisher->publishFile($this->source . "test.txt", "test1.txt");

        $filesToDelete = $this->publisher->getFilesToDelete();

        $this->assertNotContains("test1.txt", $filesToDelete);
        $this->assertContains("test2.txt", $filesToDelete);
        $this->assertContains("testfile.txt", $filesToDelete);
    }
    // }}}
    // {{{ testGetPublishedFiles()
    /**
     * @brief testGetPublishedFiles
     **/
    public function testGetPublishedFiles()
    {
        $publishedFiles = $this->publisher->getPublishedFiles();

        $this->assertNotContains("not-existant-file.txt", $publishedFiles);
        $this->assertContains("testfile.txt", $publishedFiles);
    }
    // }}}
    // {{{ testGetLastPublishDate()
    /**
     * @brief testGetLastPublishDate
     **/
    public function testGetLastPublishDate()
    {
        $date = $this->publisher->getLastPublishDate();

        $this->assertEquals($date, new \DateTime("2015-04-24 17:15:23"));
    }
    // }}}
    // {{{ testUnpublishRemovedFiles()
    /**
     * @brief testUnpublishRemovedFiles
     *
     * @param mixed
     * @return void
     **/
    public function testUnpublishRemovedFiles()
    {
        $content = "old";
        file_put_contents($this->source . "test.txt", $content);

        $this->publisher->publishFile($this->source . "test.txt", "test1.txt");
        $this->publisher->publishFile($this->source . "test.txt", "test2.txt");

        $this->publisher->resetPublishedState();

        $this->publisher->publishFile($this->source . "test.txt", "test1.txt");

        $this->publisher->unpublishRemovedFiles();

        $this->assertFileExists($this->target . "test1.txt");
        $this->assertFileNotExists($this->target . "test2.txt");

    }
    // }}}
}

/* vim:set ft=php sts=4 fdm=marker et : */
