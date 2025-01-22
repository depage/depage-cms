<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for graphicsmagick class
 **/
class graphics_graphicsmagickTest extends TestCase
{
    protected $graphics;

    // {{{ setUp()
    /**
     * Prepares fresh test objects
     **/
    public function setUp():void
    {
        $this->graphics = new graphics_graphicsmagickTestClass(array('executable' => 'bin'));
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
        $this->assertSame(' -gravity NorthWest -crop 50x50+0+0! -gravity NorthWest -extent 50x50+0+0', $this->graphics->getCommand(), 'Crop command error.');
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
        $this->assertSame(' -gravity NorthWest -crop 50x50+20+10! -gravity NorthWest -extent 50x50+0+0', $this->graphics->getCommand(), 'Crop command error.');
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
        $this->assertSame(' -gravity NorthWest -crop 50x50-20-10! -gravity NorthWest -extent 50x50-20-10', $this->graphics->getCommand(), 'Crop command error.');
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

        $expected = ' -gravity NorthWest -crop 50x50+0+0! -gravity NorthWest -extent 50x50+0+0 -thumbnail 60x60! -gravity Center -thumbnail 70x70 -extent 70x70';
        $this->assertSame($expected, $this->graphics->getCommand(), 'Action chain error.');
    }
    // }}}

    // {{{ testRenderSimple()
    /**
     * Tests rendering without actions
     **/
    public function testRenderSimple()
    {
        $this->assertFalse($this->graphics->getExecuted(), 'Command has already been executed.');
        $this->graphics->render(__DIR__ . '/images/test.jpg', __DIR__ . '/output/test2.png');

        $this->assertSame("bin convert -auto-orient '+profile' '*' -auto-orient '" . __DIR__ . "/images/test.jpg' -background none -colorspace rgb -quality 95 -strip -define png:format=png00 +page png:'" . __DIR__ . "/output/test2.png'", $this->graphics->getCommand(), 'Error in command string.');
        $this->assertTrue($this->graphics->getExecuted(), 'Command has not been executed.');
    }
    // }}}
    // {{{ testRenderResize()
    /**
     * Tests rendering after resize
     **/
    public function testRenderResize()
    {
        $this->assertFalse($this->graphics->getExecuted(), 'Command has already been executed.');
        $this->graphics->addResize(200, 200);
        $this->graphics->render(__DIR__ . '/images/test.jpg', __DIR__ . '/output/test.jpg');

        $this->assertSame("bin convert -auto-orient '+profile' '*' -auto-orient '" . __DIR__ . "/images/test.jpg' -background none -resize 200x200! -flatten -background #FFF -colorspace rgb -quality 85 -strip -interlace Plane +page jpg:'" . __DIR__ . "/output/test.jpg'", $this->graphics->getCommand(), 'Error in command string.');
        $this->assertTrue($this->graphics->getExecuted(), 'Command has not been executed.');
    }
    // }}}
    // {{{ testRenderBypass()
    /**
     * Tests bypass
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
        $this->assertSame('', $this->graphics->getBackground(), 'Default background should be transparent.');

        // HTML hex color code
        $this->graphics->addBackground('#abc');
        $this->assertSame(' -flatten -background #abc', $this->graphics->getBackground(), 'HTML hex color background error.');

        // transparent
        $this->graphics->addBackground('transparent');
        $this->assertSame('', $this->graphics->getBackground(), 'Transparent background error.');

        $this->graphics->addBackground('foo');
        $this->assertSame('', $this->graphics->getBackground(), 'Fallback background should be transparent.');

        // 'transparent' JPG
        $this->graphics->addBackground('transparent');
        $this->graphics->render(__DIR__ . '/images/test.jpg', __DIR__ . '/output/test.jpg');
        $this->assertSame(' -flatten -background #FFF', $this->graphics->getBackground(), 'JPG can`t handle transparency -> white');
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
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
