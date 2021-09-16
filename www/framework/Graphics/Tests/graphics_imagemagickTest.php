<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for imagesmagick class
 **/
class graphics_imagemagickTest extends TestCase
{
    // {{{ setUp()
    /**
     * Prepares fresh test objects
     **/
    public function setUp():void
    {
        $this->graphics = new graphics_imagemagickTestClass([
            'executable' => 'bin',
        ]);
    }
    // }}}

    // {{{ testCropSimple()
    /**
     * Tests simple crop action
     **/
    public function testCropSimple()
    {
        $this->assertSame('', $this->graphics->getCommand(), 'Command string should be empty when queue is empty.');

        $this->graphics->crop(50, 50);
        $this->assertSame(' -gravity NorthWest -crop 50x50+0+0! -flatten', $this->graphics->getCommand(), 'Crop command error.');
        $this->assertSame(array(50, 50), $this->graphics->getSize(), 'Image size should have changed.');
    }
    // }}}
    // {{{ testCropOffset()
    /**
     * Tests crop action with offset
     **/
    public function testCropOffset()
    {
        $this->graphics->crop(50, 50, 20, 10);
        $this->assertSame(' -gravity NorthWest -crop 50x50+20+10! -flatten', $this->graphics->getCommand(), 'Crop command error.');
        $this->assertSame(array(50, 50), $this->graphics->getSize(), 'Image size should have changed.');
    }
    // }}}
    // {{{ testCropNegativeOffset()
    /**
     * Tests crop action with negative offset
     **/
    public function testCropNegativeOffset()
    {
        $this->graphics->crop(50, 50, -20, -10);
        $this->assertSame(' -gravity NorthWest -crop 50x50-20-10! -flatten', $this->graphics->getCommand(), 'Crop command error.');
        $this->assertSame(array(50, 50), $this->graphics->getSize(), 'Image size should have changed.');
    }
    // }}}

    // {{{ testResizeSimple()
    /**
     * Tests simple resize action
     **/
    public function testResizeSimple()
    {
        $this->graphics->resize(50, 50);
        $this->assertSame(' -thumbnail 50x50!', $this->graphics->getCommand(), 'Resize command error.');
        $this->assertSame(array(50, 50), $this->graphics->getSize(), 'Image size should have changed.');
    }
    // }}}
    // {{{ testResizeSimpleBig()
    /**
     * Tests simple resize action
     **/
    public function testResizeSimpleBig()
    {
        $this->graphics->resize(300, 300);
        $this->assertSame(' -resize 300x300!', $this->graphics->getCommand(), 'Resize command error.');
        $this->assertSame(array(300, 300), $this->graphics->getSize(), 'Image size should have changed.');
    }
    // }}}
    // {{{ testResizeScaleX()
    /**
     * Tests resize action with automatic width
     **/
    public function testResizeScaleX()
    {
        $this->graphics->resize('X', 60);
        $this->assertSame(' -thumbnail 60x60!', $this->graphics->getCommand(), 'Resize command error.');
        $this->assertEquals(array(60, 60), $this->graphics->getSize(), 'Image size should have changed.');
    }
    // }}}
    // {{{ testResizeScaleY()
    /**
     * Tests resize action with automatic height
     **/
    public function testResizeScaleY()
    {
        $this->graphics->resize(60, 'X');
        $this->assertSame(' -thumbnail 60x60!', $this->graphics->getCommand(), 'Resize command error.');
        $this->assertEquals(array(60, 60), $this->graphics->getSize(), 'Image size should have changed.');
    }
    // }}}

    // {{{ testThumbSimple()
    /**
     * Tests simple thumb action
     **/
    public function testThumbSimple()
    {
        $this->graphics->thumb(50, 50);
        $this->assertSame(' -gravity Center -thumbnail 50x50 -extent 50x50', $this->graphics->getCommand(), 'Thumb command error.');
        $this->assertSame(array(50, 50), $this->graphics->getSize(), 'Image size should have changed.');
    }
    // }}}

    // {{{ testActionChain()
    /**
     * Tests chaining of multiple actions
     **/
    public function testActionChain()
    {
        $this->graphics->crop(50, 50);
        $this->assertSame(array(50, 50), $this->graphics->getSize(), 'Image size should have changed.');
        $this->graphics->resize(60, 60);
        $this->assertSame(array(60, 60), $this->graphics->getSize(), 'Image size should have changed.');
        $this->graphics->thumb(70, 70);
        $this->assertSame(array(70, 70), $this->graphics->getSize(), 'Image size should have changed.');

        $expected = ' -gravity NorthWest -crop 50x50+0+0! -flatten -thumbnail 60x60! -gravity Center -thumbnail 70x70 -extent 70x70';
        $this->assertSame($expected, $this->graphics->getCommand(), 'Action chain error.');
    }
    // }}}

    // {{{ testRenderSimple()
    /**
     * Tests render method (process & execution)
     **/
    public function testRenderSimple()
    {
        $this->assertFalse($this->graphics->getExecuted(), 'Command has already been executed.');
        $this->graphics->render(__DIR__ . '/images/test.jpg', __DIR__ . '/output/test2.png');

        $this->assertSame("bin -size 100x100 -background none ( '" . __DIR__ . "/images/test.jpg' ) -colorspace sRGB -flatten -quality 95 -strip -define png:format=png00 png:'" . __DIR__ . "/output/test2.png'", $this->graphics->getCommand(), 'Error in command string.');
        $this->assertTrue($this->graphics->getExecuted(), 'Command has not been executed.');
    }
    // }}}
    // {{{ testRenderResize()
    /**
     * Tests render method after resize (process & execution)
     **/
    public function testRenderResize()
    {
        $this->assertFalse($this->graphics->getExecuted(), 'Command has already been executed.');
        $this->graphics->addResize(200, 200);
        $this->graphics->render(__DIR__ . '/images/test.jpg', __DIR__ . '/output/test.jpg');

        $this->assertSame("bin -size 200x200 -background #FFF ( '" . __DIR__ . "/images/test.jpg' -resize 200x200! ) -colorspace sRGB -flatten -quality 85 -strip -interlace Plane jpg:'" . __DIR__ . "/output/test.jpg'", $this->graphics->getCommand(), 'Error in command string.');
        $this->assertTrue($this->graphics->getExecuted(), 'Command has not been executed.');
    }
    // }}}
    // {{{ testRenderBypass()
    /**
     * Tests render method bypass
     **/
    public function testRenderBypass()
    {
        $this->assertFalse($this->graphics->getExecuted(), 'Command has already been executed.');
        $this->graphics->addResize(100, 100);
        $this->graphics->render(__DIR__ . '/images/test.jpg', __DIR__ . '/output/test2.jpg');

        $this->assertFalse($this->graphics->getExecuted(), 'Command should not have been executed.');
    }
    // }}}

    // {{{ testGetBackground()
    /**
     * Tests background command string
     **/
    public function testGetBackground()
    {
        // default (transparent)
        $this->assertSame('-size 100x100 -background none', $this->graphics->getBackground(), 'Default background should be transparent.');

        // HTML hex color code
        $this->graphics->addBackground('#abc');
        $this->assertSame('-size 100x100 -background #abc', $this->graphics->getBackground(), 'HTML hex color background error.');

        // checkerboard
        $this->graphics->addBackground('checkerboard');
        $this->assertSame('-size 100x100 -background none pattern:checkerboard', $this->graphics->getBackground(), 'Checkerboard background error.');

        // transparent
        $this->graphics->addBackground('transparent');
        $this->assertSame('-size 100x100 -background none', $this->graphics->getBackground(), 'Transparent background error.');

        $this->graphics->addBackground('foo');
        $this->assertSame('-size 100x100 -background none', $this->graphics->getBackground(), 'Fallback background should be transparent.');

        // 'transparent' JPG
        $this->graphics->addBackground('transparent');
        $this->graphics->render(__DIR__ . '/images/test.jpg', __DIR__ . '/output/test.jpg');
        $this->assertSame('-size 100x100 -background #FFF', $this->graphics->getBackground(), 'JPG can`t handle transparency -> white');
    }
    // }}}
    // {{{ testGetQualityJpg()
    /**
     * Tests getQuality method for JPG
     **/
    public function testGetQualityJpg()
    {
        $this->graphics->render(__DIR__ . '/images/test.jpg', __DIR__ . '/output/test.jpg');
        $this->assertSame('-quality 85', $this->graphics->getQuality(), 'JPG quality string error.');
    }
    // }}}
    // {{{ testGetQualityPng()
    /**
     * Tests getQuality method for PNG
     **/
    public function testGetQualityPng()
    {
        $this->graphics->render(__DIR__ . '/images/test.png', __DIR__ . '/output/test.png');
        $this->assertSame('-quality 95', $this->graphics->getQuality(), 'PNG quality string error.');
    }
    // }}}
    // {{{ testGetQualityGif()
    /**
     * Tests getQuality method for GIF
     **/
    public function testGetQualityGif()
    {
        $this->graphics->render(__DIR__ . '/images/test.gif', __DIR__ . '/output/test.gif');
        $this->assertSame('', $this->graphics->getQuality(), 'GIF quality string error.');
    }
    // }}}

    // {{{ testGetOptimizeJpg()
    /**
     * Tests getQuality method for GIF
     **/
    public function testGetOptimizeJpg()
    {
        $this->graphics->render(__DIR__ . '/images/test.jpg', __DIR__ . '/output/test.jpg');
        $this->assertSame(' -strip -interlace Plane', $this->graphics->getOptimize(), 'GIF quality string error.');
    }
    // }}}
    // {{{ testGetOptimizePng()
    /**
     * Tests getQuality method for GIF
     **/
    public function testGetOptimizePng()
    {
        $this->graphics->render(__DIR__ . '/images/test.png', __DIR__ . '/output/test.png');
        $this->assertSame(' -strip -define png:format=png00', $this->graphics->getOptimize(), 'GIF quality string error.');
    }
    // }}}
    // {{{ testGetOptimizeWebp()
    /**
     * Tests getQuality method for GIF
     **/
    public function testGetOptimizeWebp()
    {
        $this->graphics->render(__DIR__ . '/images/test.webp', __DIR__ . '/output/test.webp');
        $this->assertSame(' -strip', $this->graphics->getOptimize(), 'GIF quality string error.');
    }
    // }}}
    // {{{ testGetOptimizeWebpLossless()
    /**
     * Tests getQuality method for GIF
     **/
    public function testGetOptimizeWebpLossless()
    {
        $this->graphics->render(__DIR__ . '/images/test.png', __DIR__ . '/output/test.webp');
        $this->assertSame(' -strip -define webp:lossless=true -define webp:image-hint=graph', $this->graphics->getOptimize(), 'webp lossless string error.');
    }
    // }}}

    // {{{ testBypassTestCrop()
    /**
     * Tests bypass test for crop action
     **/
    public function testBypassTestCrop()
    {
        $this->assertTrue($this->graphics->getBypass(), 'Bypass test should be true if queue is empty.');

        $this->graphics->addCrop(100, 100, 0, 0)->addCrop(100, 100);
        $this->graphics->render(__DIR__ . '/images/test.jpg', __DIR__ . '/output/test2.jpg');
        $this->assertTrue($this->graphics->getBypass(), 'Bypass test should pass.');

        $this->graphics->addCrop(100, 100, 1, 0);
        $this->graphics->render(__DIR__ . '/images/test.jpg', __DIR__ . '/output/test2.jpg');
        $this->assertFalse($this->graphics->getBypass(), 'Bypass test should fail.');
    }
    // }}}
    // {{{ testBypassTestResize()
    /**
     * Tests bypass test for resize action
     **/
    public function testBypassTestResize()
    {
        $this->assertTrue($this->graphics->getBypass(), 'Bypass test should be true if queue is empty.');

        $this->graphics->addResize(100, 100);
        $this->graphics->render(__DIR__ . '/images/test.jpg', __DIR__ . '/output/test2.jpg');
        $this->assertTrue($this->graphics->getBypass(), 'Bypass test should pass.');

        $this->graphics->addCrop(100, 101);
        $this->graphics->render(__DIR__ . '/images/test.jpg', __DIR__ . '/output/test2.jpg');
        $this->assertFalse($this->graphics->getBypass(), 'Bypass test should fail.');
    }
    // }}}
    // {{{ testBypassTestThumb()
    /**
     * Tests bypass test for thumb action
     **/
    public function testBypassTestThumb()
    {
        $this->assertTrue($this->graphics->getBypass(), 'Bypass test should be true if queue is empty.');

        $this->graphics->addThumb(100, 100);
        $this->graphics->render(__DIR__ . '/images/test.jpg', __DIR__ . '/output/test2.jpg');
        $this->assertTrue($this->graphics->getBypass(), 'Bypass test should pass.');

        $this->graphics->addThumb(101, 100);
        $this->graphics->render(__DIR__ . '/images/test.jpg', __DIR__ . '/output/test2.jpg');
        $this->assertFalse($this->graphics->getBypass(), 'Bypass test should fail.');
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
