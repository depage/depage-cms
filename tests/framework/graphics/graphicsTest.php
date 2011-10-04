<?php

use depage\graphics\graphics;

/**
 * Tests for graphics class
 **/
class graphicsTest extends PHPUnit_Framework_TestCase {
    /**
     * Creates fresh graphics objects for tests
     **/
    public function setUp() {
        $this->graphics = new graphicsTestClass();
    }

    /**
     * Tests factory with various extensions. Imagemagick and graphicsmagick
     * classes are created with set 'executable'-option so that the factory won't
     * look for their executables with 'exec("which ...")'.
     **/
    public function testFactory() {
        $graphics = graphics::factory();
        $this->assertType('depage\\graphics\\graphics_gd', $graphics, 'Expected graphics_gd object.');

        $graphics = graphics::factory(array('extension'=>'gd'));
        $this->assertType('depage\\graphics\\graphics_gd', $graphics, 'Expected graphics_gd object.');

        $graphics = graphics::factory(array('extension'=>'foobar'));
        $this->assertType('depage\\graphics\\graphics_gd', $graphics, 'Expected graphics_gd object.');

        $graphics = graphics::factory(array('extension'=>'imagemagick', 'executable'=>'bin'));
        $this->assertType('depage\\graphics\\graphics_imagemagick', $graphics, 'Expected graphics_imagemagick object.');

        $graphics = graphics::factory(array('extension'=>'im', 'executable'=>'bin'));
        $this->assertType('depage\\graphics\\graphics_imagemagick', $graphics, 'Expected graphics_imagemagick object.');

        $graphics = graphics::factory(array('extension'=>'graphicsmagick', 'executable'=>'bin'));
        $this->assertType('depage\\graphics\\graphics_graphicsmagick', $graphics, 'Expected graphics_graphicsmagick object.');

        $graphics = graphics::factory(array('extension'=>'gm', 'executable'=>'bin'));
        $this->assertType('depage\\graphics\\graphics_graphicsmagick', $graphics, 'Expected graphics_graphicsmagick object.');
    }

    /**
     * Tests background setter.
     **/
    public function testAddBackground() {
        $this->assertSame('transparent', $this->graphics->getBackground(), 'Invalid default background.');

        $this->graphics->addBackground('#000');
        $this->assertSame('#000', $this->graphics->getBackground(), 'Background setter error.');

        // another addBackground() should override old background
        $this->graphics->addBackground('#111');
        $this->assertSame('#111', $this->graphics->getBackground(), 'Background setter error.');

        $this->assertSame($this->graphics, $this->graphics->addBackground('#000'), 'Background setter should return graphics object.');
    }

    /**
     * Tests adding crop actions to queue.
     **/
    public function testAddCrop() {
        $this->assertSame(array(), $this->graphics->getQueue(), 'Initial queue should be empty.');

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
        $this->assertSame($expected, $this->graphics->getQueue(), 'AddCrop action error.');

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
        $this->assertSame($expected, $this->graphics->getQueue(), 'AddCrop action error.');


        $this->assertSame($this->graphics, $this->graphics->addCrop(100, 200, 300, 400), 'Add-methods should return graphics object.');
    }

    /**
     * Tests adding resize action to queue.
     **/
    public function testAddResize() {
        $this->assertSame(array(), $this->graphics->getQueue(), 'Initial queue should be empty.');

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
        $this->assertSame($expected, $this->graphics->getQueue(), 'AddResize action error.');

        $this->assertSame($this->graphics, $this->graphics->addResize(100, 200), 'Add-methods should return graphics object.');
    }

    /**
     * Tests adding thumb action to queue.
     **/
    public function testAddThumb() {
        $this->assertSame(array(), $this->graphics->getQueue(), 'Initial queue should be empty.');

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
        $this->assertSame($expected, $this->graphics->getQueue(), 'AddThumb action error.');

        $this->assertSame($this->graphics, $this->graphics->addThumb(100, 200), 'Add-methods should return graphics object.');
    }

    /**
     * Tests method that handles action number parameters.
     **/
    public function testEscapeNumber() {
        $this->assertSame(1337, $this->graphics->escapeNumber(1337));
        $this->assertSame(-1337, $this->graphics->escapeNumber(-1337));

        $this->assertSame(1337, $this->graphics->escapeNumber('1337'));
        $this->assertSame(1337, $this->graphics->escapeNumber(' 1337'));

        $this->assertSame(null, $this->graphics->escapeNumber('X'));
        $this->assertSame(null, $this->graphics->escapeNumber(' 8& do malicious stuff& 222'));
    }

    /**
     * Tests processing of tasks in queue.
     **/
    public function testProcessQueue() {
        $this->assertSame('', $this->graphics->getTestQueueString(), 'Initial queue should be empty.');

        $this->graphics->addCrop(100, 200, 300, 400);
        $this->graphics->addResize(100, 200);
        $this->graphics->addThumb(100, 200);
        $this->graphics->processQueue();

        $expected = '-crop-100-200-300-400--resize-100-200--thumb-100-200-';
        $this->assertSame($expected, $this->graphics->getTestQueueString(), 'Queue processing failed.');
    }

    /**
     * Tests image size scaling.
     **/
    public function testDimensions() {
        $this->graphics->setSize(array(100, 100));

        $this->assertSame(array(200, 200), $this->graphics->dimensions(200, 200), 'Dimensions bypass failed.');
        $this->assertSame(array(42, 1337), $this->graphics->dimensions(42, 1337), 'Dimensions bypass failed.');

        $this->assertEquals(array(200, 200), $this->graphics->dimensions(200, null), 'Dimensions calculation failed.');
        $this->assertEquals(array(200, 200), $this->graphics->dimensions(null, 200), 'Dimensions calculation failed.');

        $this->assertEquals(array(200, 200), $this->graphics->dimensions(200, 'X'), 'Dimensions calculation failed.');
        $this->assertEquals(array(200, 200), $this->graphics->dimensions('X', 200), 'Dimensions calculation failed.');
    }

    /**
     * Tests render method (graphics::render() contains initialization for
     * subclass render methods).
     **/
    public function testRender() {
        $this->graphics->render('test.jpg');

        $this->assertSame('test.jpg', $this->graphics->getInput(), 'Input file setter error.');
        $this->assertSame('test.jpg', $this->graphics->getOutput(), 'Output file should be same as input file if not set.');
        $this->assertSame(array(100,100), $this->graphics->getSize(), 'Render method should set image size.');
        $this->assertSame('jpg', $this->graphics->getInputFormat(), 'Render method should set input format.');
        $this->assertSame('jpg', $this->graphics->getOutputFormat(), 'Render method should set output format.');
    }

    /**
     * Tests render method (graphics::render() contains initialization for
     * subclass render methods) for different input & output files.
     **/
    public function testRenderSetOutput() {
        $this->graphics->render('test.jpg', 'test2.png');

        $this->assertSame('test.jpg', $this->graphics->getInput(), 'Input file setter error.');
        $this->assertSame('test2.png', $this->graphics->getOutput(), 'Output file setter error.');
        $this->assertSame(array(100,100), $this->graphics->getSize(), 'Render method should set image size.');
        $this->assertSame('jpg', $this->graphics->getInputFormat(), 'Render method should set input format.');
        $this->assertSame('png', $this->graphics->getOutputFormat(), 'Render method should set output format.');
    }

    /**
     * Tests image format detection by filename extension.
     **/
    public function testObtainFormat() {
        $this->assertSame('jpg', $this->graphics->obtainFormat('test.jpg'), 'Format parser error.');
        $this->assertSame('jpg', $this->graphics->obtainFormat('test.jpeg'), 'Format parser error.');
        $this->assertSame('foo', $this->graphics->obtainFormat('test.foo'), 'Format parser error.');
        $this->assertSame('png', $this->graphics->obtainFormat('/path.to/test.png'), 'Format parser error.');
    }

    /**
     * Tests quality index getter/calculator with no given image format.
     **/
    public function testGetQuality() {
        $this->assertSame('0', $this->graphics->getQuality(), 'Default quality should be 0.');
    }

    /**
     * Tests quality index getter/calculator for JPG format.
     * ( 0 <= valid quality <= 100 )
     **/
    public function testGetQualityJpg() {
        $this->graphics->setOutputFormat('jpg');

        $this->assertSame('90', $this->graphics->getQuality(), 'Default JPG quality should be 90.');

        $this->graphics->setQuality(80);
        $this->assertSame('80', $this->graphics->getQuality(), 'Error in JPG quality calculator.');

        // boundary values
        $this->graphics->setQuality(0);
        $this->assertSame('0', $this->graphics->getQuality(), 'Error in JPG quality calculator.');
        $this->graphics->setQuality(100);
        $this->assertSame('100', $this->graphics->getQuality(), 'Error in JPG quality calculator.');

        // boundary values
        $this->graphics->setQuality(-1);
        $this->assertSame('90', $this->graphics->getQuality(), 'Error in JPG quality calculator.');
        $this->graphics->setQuality(101);
        $this->assertSame('90', $this->graphics->getQuality(), 'Error in JPG quality calculator.');

        $this->graphics->setQuality('foo');
        $this->assertSame('90', $this->graphics->getQuality(), 'Error in JPG quality calculator.');
    }

    /**
     * Tests quality index getter/calculator for PNG format.
     * compression  ( 0 <= valid first digit <= 9 )
     * filter       ( 0 <= valid second digit <= 5 )
     **/
    public function testGetQualityPng() {
        $this->graphics->setOutputFormat('png');

        $this->assertSame('95', $this->graphics->getQuality(), 'Default PNG quality should be 95.');

        $this->graphics->setQuality(80);
        $this->assertSame('80', $this->graphics->getQuality(), 'Error in PNG quality calculator.');

        // boundary values
        $this->graphics->setQuality(0);
        $this->assertSame('00', $this->graphics->getQuality(), 'Error in PNG quality calculator.');
        $this->graphics->setQuality(5);
        $this->assertSame('05', $this->graphics->getQuality(), 'Error in PNG quality calculator.');
        $this->graphics->setQuality(95);
        $this->assertSame('95', $this->graphics->getQuality(), 'Error in PNG quality calculator.');

        // boundary values
        $this->graphics->setQuality(-1);
        $this->assertSame('95', $this->graphics->getQuality(), 'Error in PNG quality calculator.');
        $this->graphics->setQuality(96);
        $this->assertSame('95', $this->graphics->getQuality(), 'Error in PNG quality calculator.');
        $this->graphics->setQuality(06);
        $this->assertSame('95', $this->graphics->getQuality(), 'Error in PNG quality calculator.');
        $this->graphics->setQuality(17);
        $this->assertSame('95', $this->graphics->getQuality(), 'Error in PNG quality calculator.');
        $this->graphics->setQuality(28);
        $this->assertSame('95', $this->graphics->getQuality(), 'Error in PNG quality calculator.');

        $this->graphics->setQuality('foo');
        $this->assertSame('95', $this->graphics->getQuality(), 'Error in PNG quality calculator.');
    }

    /**
     * Tests bypass (copy) on resize/thumb to same image size
     **/
    public function testBypassTest() {
        $this->assertFalse($this->graphics->bypassTest(10, 10));
        $this->assertFalse($this->graphics->bypassTest(10, 10, 1, 2));
        $this->assertFalse($this->graphics->bypassTest(100, 100, 1, 2));

        $this->assertTrue($this->graphics->bypassTest(100, 100, 0, 0));
        $this->assertTrue($this->graphics->bypassTest(100, 100));
    }

    /**
     * Tests exception on invalid image size
     **/
    public function testBypassTestException0X() {
        try {
            $this->graphics->bypassTest(0, 100);
        } catch (depage\graphics\graphics_exception $expected) {
            return;
        }
        $this->fail('Expected graphics_exception');
    }

    /**
     * Tests exception on invalid image size
     **/
    public function testBypassTestException0Y() {
        try {
            $this->graphics->bypassTest(100, 0);
        } catch (depage\graphics\graphics_exception $expected) {
            return;
        }
        $this->fail('Expected graphics_exception');
    }

    /**
     * Tests exception on invalid image size
     **/
    public function testBypassTestExceptionNegativeX() {
        try {
            $this->graphics->bypassTest(-1, 100);
        } catch (depage\graphics\graphics_exception $expected) {
            return;
        }
        $this->fail('Expected graphics_exception');
    }

    /**
     * Tests exception on invalid image size
     **/
    public function testBypassTestExceptionNegativeY() {
        try {
            $this->graphics->bypassTest(100, -1);
        } catch (depage\graphics\graphics_exception $expected) {
            return;
        }
        $this->fail('Expected graphics_exception');
    }

    /**
     * Tests exception on invalid image size
     **/
    public function testBypassTestExceptionInvalidXY() {
        try {
            $this->graphics->bypassTest(null, null);
        } catch (depage\graphics\graphics_exception $expected) {
            return;
        }
        $this->fail('Expected graphics_exception');
    }
}
