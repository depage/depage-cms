<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for graphics class
 **/
class optimizerTest extends TestCase
{
    protected $testJpeg;
    protected $testPng;

    function setUp():void
    {
        $this->testJpeg = __DIR__ . "/output/optim.jpg";
        $this->testPng = __DIR__ . "/output/optim.png";

        copy(__DIR__ . "/images/test.jpg", $this->testJpeg);
        copy(__DIR__ . "/images/test.png", $this->testPng);
    }

    function tearDown():void
    {
        unlink($this->testJpeg);
        unlink($this->testPng);
    }

    function testJpegtran()
    {
        $optimizer = new \Depage\Graphics\Optimizers\Jpegtran();

        clearstatcache();
        $sizeBefore = filesize($this->testJpeg);

        $success = $optimizer->optimize($this->testJpeg);

        clearstatcache();
        $sizeAfter = filesize($this->testJpeg);

        $this->assertTrue($success);
        $this->assertLessThanOrEqual($sizeBefore, $sizeAfter);
    }

    function testJpegoptim()
    {
        $optimizer = new \Depage\Graphics\Optimizers\Jpegoptim();

        clearstatcache();
        $sizeBefore = filesize($this->testJpeg);

        $success = $optimizer->optimize($this->testJpeg);

        clearstatcache();
        $sizeAfter = filesize($this->testJpeg);

        $this->assertTrue($success);
        $this->assertLessThanOrEqual($sizeBefore, $sizeAfter);
    }

    function testOptipng()
    {
        $optimizer = new \Depage\Graphics\Optimizers\Optipng();

        clearstatcache();
        $sizeBefore = filesize($this->testPng);

        $success = $optimizer->optimize($this->testPng);

        clearstatcache();
        $sizeAfter = filesize($this->testPng);

        $this->assertTrue($success);
        $this->assertLessThanOrEqual($sizeBefore, $sizeAfter);
    }

    function testPngcrush()
    {
        $optimizer = new \Depage\Graphics\Optimizers\Pngcrush();

        clearstatcache();
        $sizeBefore = filesize($this->testPng);

        $success = $optimizer->optimize($this->testPng);

        clearstatcache();
        $sizeAfter = filesize($this->testPng);

        $this->assertTrue($success);
        $this->assertLessThanOrEqual($sizeBefore, $sizeAfter);
    }
}
