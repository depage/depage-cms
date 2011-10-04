<?php

class graphics_imagemagickTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->graphics = new graphics_imagemagickTestClass(array('executable' => 'bin'));
    }

    public function testCropSimple() {
        $this->assertSame('', $this->graphics->getCommand(), 'Command string should be empty when queue is empty.');

        $this->graphics->crop(50, 50);
        $this->assertSame(' -gravity NorthWest -crop 50x50+0+0! -flatten', $this->graphics->getCommand(), 'Crop command error.');
        $this->assertSame(array(50, 50), $this->graphics->getSize(), 'Image size should have changed.');
    }

    public function testCropOffset() {
        $this->graphics->crop(50, 50, 20, 10);
        $this->assertSame(' -gravity NorthWest -crop 50x50+20+10! -flatten', $this->graphics->getCommand(), 'Crop command error.');
        $this->assertSame(array(50, 50), $this->graphics->getSize(), 'Image size should have changed.');
    }

    public function testCropNegativeOffset() {
        $this->graphics->crop(50, 50, -20, -10);
        $this->assertSame(' -gravity NorthWest -crop 50x50-20-10! -flatten', $this->graphics->getCommand(), 'Crop command error.');
        $this->assertSame(array(50, 50), $this->graphics->getSize(), 'Image size should have changed.');
    }

    public function testResizeSimple() {
        $this->graphics->resize(50, 50);
        $this->assertSame(' -resize 50x50!', $this->graphics->getCommand(), 'Resize command error.');
        $this->assertSame(array(50, 50), $this->graphics->getSize(), 'Image size should have changed.');
    }

    public function testResizeScaleX() {
        $this->graphics->resize('X', 60);
        $this->assertSame(' -resize 60x60!', $this->graphics->getCommand(), 'Resize command error.');
        $this->assertEquals(array(60, 60), $this->graphics->getSize(), 'Image size should have changed.');
    }

    public function testResizeScaleY() {
        $this->graphics->resize(60, 'X');
        $this->assertSame(' -resize 60x60!', $this->graphics->getCommand(), 'Resize command error.');
        $this->assertEquals(array(60, 60), $this->graphics->getSize(), 'Image size should have changed.');
    }

    public function testThumbSimple() {
        $this->graphics->thumb(50, 50);
        $this->assertSame(' -gravity Center -thumbnail 50x50 -extent 50x50', $this->graphics->getCommand(), 'Thumb command error.');
        $this->assertSame(array(50, 50), $this->graphics->getSize(), 'Image size should have changed.');
    }

    public function testActionChain() {
        $this->graphics->crop(50, 50);
        $this->assertSame(array(50, 50), $this->graphics->getSize(), 'Image size should have changed.');
        $this->graphics->resize(60, 60);
        $this->assertSame(array(60, 60), $this->graphics->getSize(), 'Image size should have changed.');
        $this->graphics->thumb(70, 70);
        $this->assertSame(array(70, 70), $this->graphics->getSize(), 'Image size should have changed.');

        $expected = ' -gravity NorthWest -crop 50x50+0+0! -flatten -resize 60x60! -gravity Center -thumbnail 70x70 -extent 70x70';
        $this->assertSame($expected, $this->graphics->getCommand(), 'Action chain error.');
    }

    public function testRenderSimple() {
        $this->assertFalse($this->graphics->getExecuted(), 'Command has already been executed.');
        $this->graphics->render('test.jpg', 'test2.png');

        $this->assertSame('bin -size 100x100 -background none ( test.jpg ) -flatten -quality 95 png:test2.png', $this->graphics->getCommand(), 'Error in command string.');
        $this->assertTrue($this->graphics->getExecuted(), 'Command has not been executed.');
    }

    public function testRenderResize() {
        $this->assertFalse($this->graphics->getExecuted(), 'Command has already been executed.');
        $this->graphics->addResize(200, 200);
        $this->graphics->render('test.jpg');

        $this->assertSame('bin -size 200x200 -background #FFF ( test.jpg -resize 200x200! ) -flatten -quality 90 jpg:test.jpg', $this->graphics->getCommand(), 'Error in command string.');
        $this->assertTrue($this->graphics->getExecuted(), 'Command has not been executed.');
    }

    public function testRenderBypass() {
        $this->assertFalse($this->graphics->getExecuted(), 'Command has already been executed.');
        $this->graphics->addResize(100, 100);
        $this->graphics->render('test.jpg', 'test2.jpg');

        $this->assertFalse($this->graphics->getExecuted(), 'Command should not have been executed.');
    }

    public function testGetBackground() {
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
        $this->graphics->render('test.jpg');
        $this->assertSame('-size 100x100 -background #FFF', $this->graphics->getBackground(), 'JPG can`t handle transparency -> white');
    }

    public function testGetQualityJpg() {
        $this->graphics->render('test.jpg');
        $this->assertSame('-quality 90', $this->graphics->getQuality(), 'JPG quality string error.');
    }

    public function testGetQualityPng() {
        $this->graphics->render('test.png');
        $this->assertSame('-quality 95', $this->graphics->getQuality(), 'PNG quality string error.');
    }

    public function testGetQualityGif() {
        $this->graphics->render('test.gif');
        $this->assertSame('', $this->graphics->getQuality(), 'GIF quality string error.');
    }

    /**
     * Tests bypass test for crop action
     **/
    public function testBypassTestCrop() {
        $this->assertTrue($this->graphics->getBypass(), 'Bypass test should be true if queue is empty.');

        $this->graphics->addCrop(100, 100, 0, 0)->addCrop(100, 100);
        $this->graphics->render('test.jpg', 'test2.jpg');
        $this->assertTrue($this->graphics->getBypass(), 'Bypass test should pass.');

        $this->graphics->addCrop(100, 100, 1, 0);
        $this->graphics->render('test.jpg', 'test2.jpg');
        $this->assertFalse($this->graphics->getBypass(), 'Bypass test should fail.');
    }

    /**
     * Tests bypass test for resize action
     **/
    public function testBypassTestResize() {
        $this->assertTrue($this->graphics->getBypass(), 'Bypass test should be true if queue is empty.');

        $this->graphics->addResize(100, 100);
        $this->graphics->render('test.jpg', 'test2.jpg');
        $this->assertTrue($this->graphics->getBypass(), 'Bypass test should pass.');

        $this->graphics->addCrop(100, 101);
        $this->graphics->render('test.jpg', 'test2.jpg');
        $this->assertFalse($this->graphics->getBypass(), 'Bypass test should fail.');
    }

    /**
     * Tests bypass test for thumb action
     **/
    public function testBypassTestThumb() {
        $this->assertTrue($this->graphics->getBypass(), 'Bypass test should be true if queue is empty.');

        $this->graphics->addThumb(100, 100);
        $this->graphics->render('test.jpg', 'test2.jpg');
        $this->assertTrue($this->graphics->getBypass(), 'Bypass test should pass.');

        $this->graphics->addThumb(101, 100);
        $this->graphics->render('test.jpg', 'test2.jpg');
        $this->assertFalse($this->graphics->getBypass(), 'Bypass test should fail.');
    }
}
