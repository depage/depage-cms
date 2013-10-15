<?php

use Depage\Graphics\Graphics;

/**
 * Blackbox tests for all extensions, compares imagesizes/filesizes
 **/
class graphicsBlackBoxTest extends PHPUnit_Framework_TestCase
{
    protected $extensions   = array('gd', 'im', 'gm');
    protected $formats      = array(
        array(1, 'gif'),
        array(2, 'jpg'),
        array(3, 'png'),
    );

    // {{{ clean()
    /**
     * Cleanup method, deletes output test images
     **/
    private function clean()
    {
        foreach ($this->extensions as $extension) {
            foreach ($this->formats as $format) {
                $file = "test-{$extension}.{$format[1]}";
                if (file_exists($file)) unlink($file);
            }
        }
    }
    // }}}
    // {{{ runSuite()
    /**
     * Test runs for all format/graphic extension permutations
     **/
    private function runSuite($width, $height, $message, $bypass = false)
    {
        foreach ($this->extensions as $extension) {
            foreach ($this->formats as $inFormat) {
                foreach ($this->formats as $outFormat) {
                    $input  = "images/test.{$inFormat[1]}";
                    $output = "test-{$extension}.{$outFormat[1]}";

                    $this->graphics[$extension]->render($input, $output);
                    $info = getimagesize($output);

                    $errorMessage = "{$extension} {$input} {$output} {$message}";

                    // can only check image dimensions and type
                    $this->assertSame($width, $info[0], "Width, {$errorMessage}");
                    $this->assertSame($height, $info[1], "Height, {$errorMessage}");
                    $this->assertSame($outFormat[0], $info[2], "Type, {$errorMessage}");

                    if (
                        $bypass
                        && $inFormat == $outFormat
                    ) {
                        // on bypass filesizes should be equal (copy)
                        $this->assertSame(filesize($input), filesize($output));
                    }

                    // delete test output
                    unlink($output);
                }
            }
        }
    }
    // }}}

    // {{{ setUp()
    /**
     * Prepares fresh test objects
     **/
    public function setUp()
    {
        $this->clean();

        foreach ($this->extensions as $extension) {
            $this->graphics[$extension] = graphics::factory(array('extension' => $extension));
        }
    }
    // }}}
    // {{{ tearDown()
    public function tearDown()
    {
        $this->clean();
    }
    // }}}

    // {{{ testCropSimple()
    /**
     * Tests simple crop action
     **/
    public function testCropSimple()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addCrop(200, 200);
        }

        $this->runSuite(200, 200, 'crop-simple error.');
    }
    // }}}
    // {{{ testCropOffset()
    /**
     * Tests crop action with offset
     **/
    public function testCropOffset()
    {
       foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addCrop(200, 200, 20, 10);
        }

        $this->runSuite(200, 200, 'crop-offset error.');
    }
    // }}}
    // {{{ testCropNegativeOffset()
    /**
     * Tests crop action with negative offset
     **/
    public function testCropNegativeOffset()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addCrop(200, 200, -20, -10);
        }

        $this->runSuite(200, 200, 'crop-negative-offset error.');
    }
    // }}}

    // {{{ testResizeSimple()
    /**
     * Tests simple resize action
     **/
    public function testResizeSimple()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addResize(50, 50);
        }

        $this->runSuite(50, 50, 'resize-simple error.');
    }
    // }}}
    // {{{ testResizeScaleWidth()
    /**
     * Tests resize action with automatic width
     **/
    public function testResizeScaleWidth()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addResize('X', 60);
        }

        $this->runSuite(77, 60, 'resize-scale-width error.');
    }
    // }}}
    // {{{ testResizeScaleHeight()
    /**
     * Tests resize action with automatic height
     **/
    public function testResizeScaleHeight()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addResize(60, 'X');
        }

        $this->runSuite(60, 47, 'resize-scale-height error.');
    }
    // }}}

    // {{{ testThumbSimpleLargeWidth()
    /**
     * Tests thumb action (different formats for gd thumb method)
     **/
    public function testThumbSimpleLargeWidth()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addThumb(100, 50);
        }

        $this->runSuite(100, 50, 'thumb-simple error.');
    }
    // }}}
    // {{{ testThumbSimpleLargeHeight()
    /**
     * Tests thumb action (different formats for gd thumb method)
     **/
    public function testThumbSimpleLargeHeight()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addThumb(50, 100);
        }

        $this->runSuite(50, 100, 'thumb-simple error.');
    }
    // }}}
    // {{{ testThumbColorShort()
    /**
     * Tests background with short HTML color format
     **/
    public function testThumbColorShort()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addThumb(50, 100)->addBackground('#123');
        }

        $this->runSuite(50, 100, 'thumb-color error.');
    }
    // }}}
    // {{{ testThumbColorLong()
    /**
     * Tests background with long HTML color format
     **/
    public function testThumbColorLong()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addThumb(50, 100)->addBackground('#123456');
        }

        $this->runSuite(50, 100, 'thumb-color error.');
    }
    // }}}
    // {{{ testThumbCheckerboard()
    /**
     * Tests background with checkerboard pattern
     **/
    public function testThumbCheckerboard()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addThumb(50, 100)->addBackground('checkerboard');
        }

        $this->runSuite(50, 100, 'thumb-checkerboard error.');
    }
    // }}}

    // {{{ testActionChain()
    /**
     * Tests chaining of multiple actions
     **/
    public function testActionChain()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addCrop(50, 50)->addResize(60, 60)->addThumb(70, 70);
        }

        $this->runSuite(70, 70, 'action-chain error.');
    }
    // }}}

    // {{{ testBypassClean()
    /**
     * Tests rendering (bypass) without actions
     **/
    public function testBypassClean()
    {
        $this->runSuite(129, 101, 'clean bypass error.', true);
    }
    // }}}
    // {{{ testBypassCrop()
    /**
     * Tests crop bypass (same dimensions & format)
     **/
    public function testBypassCrop()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addCrop(129, 101, 0, 0)->addCrop(129, 101);
        }

        $this->runSuite(129, 101, 'crop bypass error.', true);
    }
    // }}}
    // {{{ testBypassResize()
    /**
     * Tests resize bypass (same dimensions & format)
     **/
    public function testBypassResize()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addResize(129, 101);
        }

        $this->runSuite(129, 101, 'resize bypass error.', true);
    }
    // }}}
    // {{{ testBypassThumb()
    /**
     * Tests thumb bypass (same dimensions & format)
     **/
    public function testBypassThumb()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addThumb(129, 101);
        }

        $this->runSuite(129, 101, 'thumb bypass error.', true);
    }
    // }}}
}
