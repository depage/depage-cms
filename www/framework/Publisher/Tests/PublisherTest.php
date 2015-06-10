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
    }
    // }}}

    // {{{ testPublishFile()
    /**
     * @brief testPublishFile
     **/
    public function testPublishFile()
    {
        $content = "abcd";
        file_put_contents($this->source . "test.txt");
        $this->publisher->testPublishFile($this->source . "test.txt", "testFile.txt");

        $this->assertFileEquals($content, $this->target . "testFile.txt");
    }
    // }}}
    // {{{ testPublishString()
    /**
     * @brief testPublishString
     **/
    public function testPublishString()
    {
        $this->publisher->publishString("testcontent", "testString.txt");

    }
    // }}}
}

/* vim:set ft=php sts=4 fdm=marker et : */
