<?php

require_once('../../../www/framework/depage/depage.php');
require_once('graphicsTestClass.php');

class graphicsTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->graphics = new graphicsTestClass();
    }

    public function testFactory() {
        $graphics = graphics::factory();
        $this->assertTrue($graphics instanceof graphics_gd, 'Expected graphics_gd object.');

        $graphics = graphics::factory(array('extension'=>'gd'));
        $this->assertTrue($graphics instanceof graphics_gd, 'Expected graphics_gd object.');

        $graphics = graphics::factory(array('extension'=>'foobar'));
        $this->assertTrue($graphics instanceof graphics_gd, 'Expected graphics_gd object.');

        $graphics = graphics::factory(array('extension'=>'imagemagick', 'executable'=>'bin'));
        $this->assertTrue($graphics instanceof graphics_imagemagick, 'Expected graphics_imagemagick object.');

        $graphics = graphics::factory(array('extension'=>'im', 'executable'=>'bin'));
        $this->assertTrue($graphics instanceof graphics_imagemagick, 'Expected graphics_imagemagick object.');

        $graphics = graphics::factory(array('extension'=>'graphicsmagick', 'executable'=>'bin'));
        $this->assertTrue($graphics instanceof graphics_graphicsmagick, 'Expected graphics_graphicsmagick object.');

        $graphics = graphics::factory(array('extension'=>'gm', 'executable'=>'bin'));
        $this->assertTrue($graphics instanceof graphics_graphicsmagick, 'Expected graphics_graphicsmagick object.');
    }

    public function testAddBackground() {
        $this->assertEquals('transparent', $this->graphics->getBackground(), 'Invalid default background.');

        $this->graphics->addBackground('#000');
        $this->assertEquals('#000', $this->graphics->getBackground(), 'Background setter error.');

        $this->assertEquals($this->graphics, $this->graphics->addBackground('#000'), 'Background setter should return graphics object.');
    }

    public function testAddCrop() {
        $this->assertEquals(array(), $this->graphics->getQueue(), 'Initial queue should be empty.');

        $this->graphics->addCrop(100, 200);
        $expected = array(
            array(
                'crop',
                array(
                    100,
                    200,
                )
            )
        );
        $this->assertEquals($expected, $this->graphics->getQueue(), 'AddCrop action error.');

        $this->graphics->addCrop(100, 200, 300, 400);
        $expected[] = array(
            'crop',
            array(
                100,
                200,
                300,
                400,
            )
        );
        $this->assertEquals($expected, $this->graphics->getQueue(), 'AddCrop action error.');


        $this->assertEquals($this->graphics, $this->graphics->addCrop(100, 200, 300, 400), 'Add-methods should return graphics object.');
    }

    public function testAddResize() {
        $this->assertEquals(array(), $this->graphics->getQueue(), 'Initial queue should be empty.');

        $this->graphics->addResize(100, 200);
        $expected = array(
            array(
                'resize',
                array(
                    100,
                    200,
                )
            )
        );
        $this->assertEquals($expected, $this->graphics->getQueue(), 'AddResize action error.');

        $this->assertEquals($this->graphics, $this->graphics->addResize(100, 200), 'Add-methods should return graphics object.');
    }

    public function testAddThumb() {
        $this->assertEquals(array(), $this->graphics->getQueue(), 'Initial queue should be empty.');

        $this->graphics->addThumb(100, 200);
        $expected = array(
            array(
                'thumb',
                array(
                    100,
                    200,
                )
            )
        );
        $this->assertEquals($expected, $this->graphics->getQueue(), 'AddThumb action error.');

        $this->assertEquals($this->graphics, $this->graphics->addThumb(100, 200), 'Add-methods should return graphics object.');
    }

    public function testEscapeNumber() {
        $this->assertEquals(1337, $this->graphics->escapeNumber(1337));
        $this->assertEquals(-1337, $this->graphics->escapeNumber(-1337));

        $this->assertEquals(1337, $this->graphics->escapeNumber('1337'));
        $this->assertEquals(1337, $this->graphics->escapeNumber(' 1337'));

        $this->assertEquals(null, $this->graphics->escapeNumber('X'));
        $this->assertEquals(null, $this->graphics->escapeNumber(' 8& do malicious stuff& 222'));
    }

    public function testProcessQueue() {
        $this->assertEquals('', $this->graphics->testQueueString, 'Initial queue should be empty.');

        $this->graphics->addCrop(100, 200, 300, 400);
        $this->graphics->addResize(100, 200);
        $this->graphics->addThumb(100, 200);
        $this->graphics->processQueue();

        $expected = '-crop-100-200-300-400--resize-100-200--thumb-100-200-';
        $this->assertEquals($expected, $this->graphics->testQueueString, 'Queue processing failed.');
    }
}
