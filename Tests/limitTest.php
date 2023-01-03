<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for imagesmagick class
 **/
class limitTest extends TestCase
{
    // {{{ testLimitImageMagickDefaults()
    /**
     * Test default limits for ImageMagick
     **/
    public function testLimitImageMagickDefaults()
    {
        $graphics = new graphics_imagemagickTestClass();

        $this->assertSame("64MiB", $graphics->getLimit('memory'));
        $this->assertSame("128MiB", $graphics->getLimit('map'));
        $this->assertSame("64MiB", getenv("MAGICK_MEMORY_LIMIT"));
        $this->assertSame("128MiB", getenv("MAGICK_MAP_LIMIT"));
    }
    // }}}
    // {{{ testLimitImageMagick()
    /**
     * Test limits for ImageMagick
     **/
    public function testLimitImageMagick()
    {
        $graphics = new graphics_imagemagickTestClass([
            'limits' => [
                'memory' => "128MiB",
                'map' => "256MiB",
            ]
        ]);

        $this->assertSame("128MiB", $graphics->getLimit('memory'));
        $this->assertSame("256MiB", $graphics->getLimit('map'));
        $this->assertSame("128MiB", getenv("MAGICK_MEMORY_LIMIT"));
        $this->assertSame("256MiB", getenv("MAGICK_MAP_LIMIT"));
    }
    // }}}
    // {{{ testLimitImageMagickExecutable()
    /**
     * Test default limits for graphicsmagick
     **/
    public function testLimitImageMagickExecutable()
    {
        $graphics = new graphics_imagemagickTestClass();

        $command = "convert -list resource";
        exec($command . ' 2>&1', $results, $returnVal);
        $resultString = implode($results);

        $this->assertMatchesRegularExpression("/Memory:\s*64MiB/", $resultString);
        $this->assertMatchesRegularExpression("/Map:\s*128MiB/", $resultString);
    }
    // }}}
    // {{{ testLimitGraphicsMagickDefaults()
    /**
     * Test default limits for graphicsmagick
     **/
    public function testLimitGraphicsMagickDefaults()
    {
        $graphics = new graphics_graphicsmagickTestClass();

        $this->assertSame("64MiB", $graphics->getLimit('memory'));
        $this->assertSame("128MiB", $graphics->getLimit('map'));
        $this->assertSame("64MiB", getenv("MAGICK_LIMIT_MEMORY"));
        $this->assertSame("128MiB", getenv("MAGICK_LIMIT_MAP"));
    }
    // }}}
    // {{{ testLimitGraphicsMagick()
    /**
     * Test limits for graphicsmagick
     **/
    public function testLimitGraphicsMagick()
    {
        $graphics = new graphics_graphicsmagickTestClass([
            'limits' => [
                'memory' => "128MiB",
                'map' => "256MiB",
            ]
        ]);

        $this->assertSame("128MiB", $graphics->getLimit('memory'));
        $this->assertSame("256MiB", $graphics->getLimit('map'));
        $this->assertSame("128MiB", getenv("MAGICK_LIMIT_MEMORY"));
        $this->assertSame("256MiB", getenv("MAGICK_LIMIT_MAP"));
    }
    // }}}
    // {{{ testLimitGraphicsMagickExecutable()
    /**
     * Test default limits for graphicsmagick
     **/
    public function testLimitGraphicsMagickExecutable()
    {
        $graphics = new graphics_imagemagickTestClass();

        $command = "gm convert -list resource";
        exec($command . ' 2>&1', $results, $returnVal);
        $resultString = implode($results);

        $this->assertMatchesRegularExpression("/Memory:\s*64.0MiB/", $resultString);
        $this->assertMatchesRegularExpression("/Map:\s*128.0MiB/", $resultString);
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
