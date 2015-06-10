<?php

/**
 * Tests Publisher Class
 **/
class PublisherTest extends \PHPUnit_Framework_TestCase
{
    // {{{ setUp()
    /**
     * setup function
     **/
    public function setUp()
    {
        $this->source = __DIR__ . "/tmp/src/";
        $this->target = __DIR__ . "/tmp/target/";

        if (!is_dir($this->source)) {
            mkdir($this->source, 0777, true);
        }
        if (!is_dir($this->target)) {
            mkdir($this->target, 0777, true);
        }

        $fs = Depage\Fs\Fs::factory($this->target);
        $pdo = new Depage\Db\Pdo(
            "mysql:dbname=depage_phpunit;host=localhost",
            "root",
            ""
        );

        $this->publisher = new Depage\Publisher\Publisher($pdo, $fs, 1);
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

    // {{{ testPublishFile()
    /**
     * @brief testPublishFile
     **/
    public function testPublishFile()
    {
        $content = "abcd";
        file_put_contents($this->source . "test.txt", $content);
        $this->publisher->publishFile($this->source . "test.txt", "testFile.txt");

        $this->assertFileEquals($this->source . "test.txt", $this->target . "testFile.txt");
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

        $this->assertFileEquals($this->source . "test.txt", $this->target . "testString.txt");
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
