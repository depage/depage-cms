<?php

use PHPUnit\Framework\TestCase;
use Depage\Graphics\Imgurl;

/**
 * Tests for graphics class
 **/
class imgUrlTest extends TestCase
{
    // {{{ setUp()
    /**
     * Creates fresh imgurl objects for tests
     **/
    public function setUp():void
    {
        $this->baseUrl = "https://host.com/path/";
        $this->imgurl = new imgurlTestClass([
            'baseUrl' => $this->baseUrl,
            'cachePath' => "cache/",
            'relPath' => '../',
        ]);
    }
    // }}}

    // {{{ testAnalyzeResize()
    public function testAnalyzeResize()
    {
        $actions = [
            'r100x100',
            'r-100x100',
            'resize100x100',
            'resize-100x100',
        ];
        foreach ($actions as $action) {
            $this->imgurl->analyze($this->baseUrl . "test.png.$action.png");

            $this->assertSame([
                [
                    'addResize',
                    [100, 100]
                ],
            ], $this->imgurl->getActions());
            $this->assertSame("../test.png", $this->imgurl->getSrcImg());
            $this->assertSame("cache/test.png.$action.png", $this->imgurl->getOutImg());
        }
    }
    // }}}
    // {{{ testAnalyzeThumb()
    public function testAnalyzeThumb()
    {
        $actions = [
            't100x100',
            't-100x100',
            'thumb100x100',
            'thumb-100x100',
        ];
        foreach ($actions as $action) {
            $this->imgurl->analyze($this->baseUrl . "test.png.$action.png");

            $this->assertSame([
                [
                    'addThumb',
                    [100, 100]
                ],
            ], $this->imgurl->getActions());
            $this->assertSame("../test.png", $this->imgurl->getSrcImg());
            $this->assertSame("cache/test.png.$action.png", $this->imgurl->getOutImg());
        }
    }
    // }}}
    // {{{ testAnalyzeThumbfill()
    public function testAnalyzeThumbfill()
    {
        $actions = [
            'tf100x100',
            'tf-100x100',
            'thumbfill100x100',
            'thumbfill-100x100',
        ];
        foreach ($actions as $action) {
            $this->imgurl->analyze($this->baseUrl . "test.png.$action.png");

            $this->assertSame([
                [
                    'addThumbfill',
                    [100, 100]
                ],
            ], $this->imgurl->getActions());
            $this->assertSame("../test.png", $this->imgurl->getSrcImg());
            $this->assertSame("cache/test.png.$action.png", $this->imgurl->getOutImg());
        }
    }
    // }}}
    // {{{ testAnalyzeThumbfillTopLeft()
    public function testAnalyzeThumbfillTopLeft()
    {
        $actions = [
            'tf100x100-0x0',
            'tf-100x100-0x0',
            'thumbfill100x100-0x0',
            'thumbfill-100x100-0x0',
        ];
        foreach ($actions as $action) {
            $this->imgurl->analyze($this->baseUrl . "test.png.$action.png");

            $this->assertSame([
                [
                    'addThumbfill',
                    [100, 100, 0, 0]
                ],
            ], $this->imgurl->getActions());
            $this->assertSame("../test.png", $this->imgurl->getSrcImg());
            $this->assertSame("cache/test.png.$action.png", $this->imgurl->getOutImg());
        }
    }
    // }}}
    // {{{ testAnalyzeThumbfillBottomRight()
    public function testAnalyzeThumbfillBottomRight()
    {
        $actions = [
            'tf100x100-100x100',
            'tf-100x100-100x100',
            'thumbfill100x100-100x100',
            'thumbfill-100x100-100x100',
        ];
        foreach ($actions as $action) {
            $this->imgurl->analyze($this->baseUrl . "test.png.$action.png");

            $this->assertSame([
                [
                    'addThumbfill',
                    [100, 100, 100, 100]
                ],
            ], $this->imgurl->getActions());
            $this->assertSame("../test.png", $this->imgurl->getSrcImg());
            $this->assertSame("cache/test.png.$action.png", $this->imgurl->getOutImg());
        }
    }
    // }}}

    // {{{ testImageExtensions()
    public function testImageExtensions()
    {
        $in = [
            'jpg',
            'jpeg',
            'gif',
            'png',
            'webp',
            'eps',
            'tif',
            'tiff',
            'pdf',
        ];
        $out = [
            'jpg',
            'gif',
            'png',
            'webp',
        ];

        foreach($in as $inExt) {
            foreach($out as $outExt) {
                $this->imgurl->analyze($this->baseUrl . "test." . $inExt . ".r100x100." . $outExt);

                $this->assertSame([
                    [
                        'addResize',
                        [100, 100]
                    ],
                ], $this->imgurl->getActions());
                $this->assertSame("../test." . $inExt, $this->imgurl->getSrcImg());
                $this->assertSame("cache/test." . $inExt . ".r100x100." . $outExt, $this->imgurl->getOutImg());
            }
        }
    }
    // }}}

    // {{{ testGetUrlAddBackground()
    public function testGetUrlAddBackground()
    {
        $this->imgurl->addBackground("000000");
        $url = $this->imgurl->getUrl("test.png");

        $this->assertSame('test.png.bg000000.png', $url);
    }
    // }}}
    // {{{ testGetUrlAddCrop()
    public function testGetUrlAddCrop()
    {
        $this->imgurl->addCrop(100, 100);
        $url = $this->imgurl->getUrl("test.png");

        $this->assertSame('test.png.crop100x100-0x0.png', $url);
    }
    // }}}
    // {{{ testGetUrlAddCrop2()
    public function testGetUrlAddCrop2()
    {
        $this->imgurl->addCrop(100, 100, 10, 10);
        $url = $this->imgurl->getUrl("test.png");

        $this->assertSame('test.png.crop100x100-10x10.png', $url);
    }
    // }}}
    // {{{ testGetUrlAddResize()
    public function testGetUrlAddResize()
    {
        $this->imgurl->addResize(100, 100);
        $url = $this->imgurl->getUrl("test.png");

        $this->assertSame('test.png.r100x100.png', $url);
    }
    // }}}
    // {{{ testGetUrlAddThumb()
    public function testGetUrlAddThumb()
    {
        $this->imgurl->addThumb(100, 100);
        $url = $this->imgurl->getUrl("test.png");

        $this->assertSame('test.png.t100x100.png', $url);
    }
    // }}}
    // {{{ testGetUrlAddThumbfill()
    public function testGetUrlAddThumbfill()
    {
        $this->imgurl->addThumbfill(100, 100);
        $url = $this->imgurl->getUrl("test.png");

        $this->assertSame('test.png.tf100x100.png', $url);
    }
    // }}}
    // {{{ testGetUrlAddThumbfillTopLeft()
    public function testGetUrlAddThumbfillTopLeft()
    {
        $this->imgurl->addThumbfill(100, 100, 0, 0);
        $url = $this->imgurl->getUrl("test.png");

        $this->assertSame('test.png.tf100x100-0x0.png', $url);
    }
    // }}}
    // {{{ testGetUrlAddThumbfillBottomRight()
    public function testGetUrlAddThumbfillBottomRight()
    {
        $this->imgurl->addThumbfill(100, 100, 100, 100);
        $url = $this->imgurl->getUrl("test.png");

        $this->assertSame('test.png.tf100x100-100x100.png', $url);
    }
    // }}}
    // {{{ testGetUrlSetQuality()
    public function testGetUrlSetQuality()
    {
        $this->imgurl->setQuality(50);
        $url = $this->imgurl->getUrl("test.png");

        $this->assertSame('test.png.q50.png', $url);
    }
    // }}}
    // {{{ testGetUrlChainActions()
    public function testGetUrlChainActions()
    {
        $this->imgurl
            ->addResize(100, 100)
            ->addBackground('ffffff')
            ->setQuality(50);
        $url = $this->imgurl->getUrl("test.png");

        $this->assertSame('test.png.r100x100.bgffffff.q50.png', $url);
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
