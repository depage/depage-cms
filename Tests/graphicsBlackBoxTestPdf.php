<?php

use Depage\Graphics\Graphics;

/**
 * Blackbox tests for all extensions, compares imagesizes/filesizes
 **/
class graphicsBlackBoxTest extends PHPUnit_Framework_TestCase
{
    protected $extensions   = array(
        'im',
        'gm',
    );
    protected $formats      = array(
        array(1, 'gif'),
        array(2, 'jpg'),
        array(3, 'png'),
    );
    protected $maxDifference = 0.5;

    // {{{ constructor()
    /**
     * Constructor function
     **/
    public function __construct()
    {
        $i = 1;
        $types = imagetypes();
        $aSupportedTypes = array();

        $aPossibleImageTypeBits = array(
            IMG_GIF  => 'gif',
            IMG_JPG  => 'jpg',
            IMG_PNG  => 'png',
        );
        if (defined('IMG_WEBP')) {
            $aPossibleImageTypeBits[IMG_WEBP] = "webp";
        }

        foreach ($aPossibleImageTypeBits as $iImageTypeBits => $sImageTypeString) {
            if (imagetypes() & $iImageTypeBits) {
                $aSupportedTypes[] = array($i++, $sImageTypeString);
            }
        }

        $this->formats = $aSupportedTypes;
    }
    // }}}

    // {{{ clean()
    /**
     * Cleanup method, deletes output test images
     **/
    private function clean()
    {
        foreach ($this->extensions as $extension) {
            foreach ($this->formats as $format) {
                $file = "output/test-{$extension}.{$format[1]}";
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
    // }}}
    // {{{ compareImage()
    /**
     * Test runs for all format/graphic extension permutations
     **/
    private function compareImage($file1, $file2)
    {
        $delta = 1;
        $results = array();

        $command = sprintf(
            "gm compare -metric mae %s %s null:",
            escapeshellarg($file1),
            escapeshellarg($file2)
        );

        if (!file_exists($file1)) {
            var_dump($file1);
            die();
        }
        exec($command . ' 2>&1', $results, $returnVal);
        $resultString = implode($results);

        // GraphicsMagick Results using Total
        if (preg_match("/Total: ([\d.,e]+)?/", $resultString, $match)) {
            return (float) $match[1];
        }
        return $this->maxDifference + 1;
    }
    // }}}
    // {{{ runSuite()
    /**
     * Test runs for all format/graphic extension permutations
     **/
    private function runSuite($width, $height, $message, $bypass = false)
    {
        $inFormat = "pdf";
        foreach ($this->formats as $outFormat) {
            foreach ($this->extensions as $extension) {
                $input  = __DIR__ . "/images/test.{$inFormat}";
                $output = __DIR__ . "/output/test-{$extension}.{$outFormat[1]}";

                $this->graphics[$extension]->render($input, $output);
                $info = getimagesize($output);

                $errorMessage = "{$extension} {$input} {$output} {$message} error.";

                // can only check image dimensions and type
                $this->assertSame($width, $info[0], "Width, {$errorMessage}");
                $this->assertSame($height, $info[1], "Height, {$errorMessage}");
                $this->assertSame($outFormat[0], $info[2], "Type, {$errorMessage}");
            }
            foreach ($this->extensions as $extension) {
                $output = __DIR__ . "/output/test-{$extension}.{$outFormat[1]}";

                // delete test output
                unlink($output);
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

        $this->runSuite(200, 200, 'crop-simple');
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

        $this->runSuite(200, 200, 'crop-offset');
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

        $this->runSuite(200, 200, 'crop-negative-offset');
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

        $this->runSuite(50, 50, 'resize-simple');
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

        $this->runSuite(42, 60, 'resize-scale-width');
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

        $this->runSuite(60, 85, 'resize-scale-height');
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

        $this->runSuite(100, 50, 'thumb-simple');
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

        $this->runSuite(50, 100, 'thumb-simple');
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

        $this->runSuite(50, 100, 'thumb-color');
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

        $this->runSuite(50, 100, 'thumb-color');
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

        $this->runSuite(50, 100, 'thumb-checkerboard');
    }
    // }}}

    // {{{ testThumbfillSimpleLargeWidth()
    /**
     * Tests thumbfill action (different formats for gd thumb method)
     **/
    public function testThumbfillSimpleLargeWidth()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addThumbfill(100, 50);
        }

        $this->runSuite(100, 50, 'thumbfill-simple');
    }
    // }}}
    // {{{ testThumbfillSimpleLargeHeight()
    /**
     * Tests thumbfill action (different formats for gd thumb method)
     **/
    public function testThumbfillSimpleLargeHeight()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addThumbfill(50, 100);
        }

        $this->runSuite(50, 100, 'thumbfill-simple');
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

        $this->runSuite(70, 70, 'action-chain');
    }
    // }}}

    // {{{ testBypassClean()
    /**
     * Tests rendering (bypass) without actions
     **/
    public function testBypassClean()
    {
        $this->runSuite(595, 842, 'clean-bypass', true);
    }
    // }}}
    // {{{ testBypassCrop()
    /**
     * Tests crop bypass (same dimensions & format)
     **/
    public function testBypassCrop()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addCrop(768, 576, 0, 0)->addCrop(768, 576);
        }

        $this->runSuite(768, 576, 'crop-bypass', true);
    }
    // }}}
    // {{{ testBypassResize()
    /**
     * Tests resize bypass (same dimensions & format)
     **/
    public function testBypassResize()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addResize(768, 576);
        }

        $this->runSuite(768, 576, 'resize-bypass', true);
    }
    // }}}
    // {{{ testBypassThumb()
    /**
     * Tests thumb bypass (same dimensions & format)
     **/
    public function testBypassThumb()
    {
        foreach ($this->extensions as $extension) {
            $this->graphics[$extension]->addThumb(768, 576);
        }

        $this->runSuite(768, 576, 'thumb-bypass', true);
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
