<?php

class graphics_graphicsmagickTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->graphics = new graphics_graphicsmagickTestClass(array('executable' => 'bin'));
    }

    public function testCropSimple() {
        $this->assertSame('', $this->graphics->getCommand(), 'Command string should be empty when queue is empty.');

        $this->graphics->crop(50, 50);
        $this->assertSame(' -gravity NorthWest -crop 50x50+0+0! -gravity NorthWest -extent 50x50+0+0', $this->graphics->getCommand(), 'Crop command error.');
        $this->assertSame(array(50, 50), $this->graphics->getSize(), 'Image size should have changed.');
    }

    public function testCropOffset() {
        $this->graphics->crop(50, 50, 20, 10);
        $this->assertSame(' -gravity NorthWest -crop 50x50+20+10! -gravity NorthWest -extent 50x50+0+0', $this->graphics->getCommand(), 'Crop command error.');
        $this->assertSame(array(50, 50), $this->graphics->getSize(), 'Image size should have changed.');
    }

    public function testCropNegativeOffset() {
        $this->graphics->crop(50, 50, -20, -10);
        $this->assertSame(' -gravity NorthWest -crop 50x50-20-10! -gravity NorthWest -extent 50x50-20-10', $this->graphics->getCommand(), 'Crop command error.');
        $this->assertSame(array(50, 50), $this->graphics->getSize(), 'Image size should have changed.');
    }

    public function testActionChain() {
        $this->graphics->crop(50, 50);
        $this->assertSame(array(50, 50), $this->graphics->getSize(), 'Image size should have changed.');
        $this->graphics->resize(60, 60);
        $this->assertSame(array(60, 60), $this->graphics->getSize(), 'Image size should have changed.');
        $this->graphics->thumb(70, 70);
        $this->assertSame(array(70, 70), $this->graphics->getSize(), 'Image size should have changed.');

        $expected = ' -gravity NorthWest -crop 50x50+0+0! -gravity NorthWest -extent 50x50+0+0 -resize 60x60! -gravity Center -thumbnail 70x70 -extent 70x70';
        $this->assertSame($expected, $this->graphics->getCommand(), 'Action chain error.');
    }

    public function testRenderSimple() {
        $this->assertFalse($this->graphics->getExecuted(), 'Command has already been executed.');
        $this->graphics->render('test.jpg');

        $this->assertSame('bin convert test.jpg -background none -flatten -background #FFF -quality 90 +page jpg:test.jpg', $this->graphics->getCommand(), 'Error in command string.');
        $this->assertTrue($this->graphics->getExecuted(), 'Command has not been executed.');
    }

    public function testRenderResize() {
        $this->assertFalse($this->graphics->getExecuted(), 'Command has already been executed.');
        $this->graphics->addResize(200, 200);
        $this->graphics->render('test.jpg');

        $this->assertSame('bin convert test.jpg -background none -resize 200x200! -flatten -background #FFF -quality 90 +page jpg:test.jpg', $this->graphics->getCommand(), 'Error in command string.');
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
        $this->graphics->render('test.jpg');
        $this->assertSame(' -flatten -background #FFF', $this->graphics->getBackground(), 'JPG can`t handle transparency -> white');
    }
}
