
<?php

require_once('../../../www/framework/depage/depage.php');
require_once('graphics_imagemagickTestClass.php');

class graphicsTest extends PHPUnit_Framework_TestCase {
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

    public function testResizeScale() {
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
        $this->graphics->render('test.jpg');

        $this->assertSame('bin -size 100x100 -background #FFF ( test.jpg ) -flatten -quality 90 jpg:test.jpg', $this->graphics->getCommand(), 'Error in command string.');
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
        $this->assertSame('-size 100x100 -background none', $this->graphics->getBackground());

        // HTML hex color code
        $this->graphics->addBackground('#abc');
        $this->assertSame('-size 100x100 -background #abc', $this->graphics->getBackground());

        // checkerboard
        $this->graphics->addBackground('checkerboard');
        $this->assertSame('-size 100x100 -background none pattern:checkerboard', $this->graphics->getBackground());

        // transparent
        $this->graphics->addBackground('transparent');
        $this->assertSame('-size 100x100 -background none', $this->graphics->getBackground());

        $this->graphics->addBackground('foo');
        $this->assertSame('-size 100x100 -background none', $this->graphics->getBackground());
    }
}
