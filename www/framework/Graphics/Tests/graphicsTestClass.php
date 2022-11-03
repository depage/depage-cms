<?php

namespace Depage\Graphics\Tests;

/**
 * Override graphics class to access protected methods/attributes in
 * tests
 **/
class GraphicsTestClass extends \Depage\Graphics\Graphics
{
    protected $testQueueString = '';
    // imaginary test image size
    protected $size = array(100, 100);

    public function getBackground()
    {
        return $this->background;
    }

    public function getQueue()
    {
        return $this->queue;
    }

    public function escapeNumber($number)
    {
        return parent::escapeNumber($number);
    }

    // simulating queue
    public function crop($width, $height, $x, $y)
    {
        $this->testQueueString .= "-crop-{$width}-{$height}-{$x}-{$y}-";
    }

    // simulating queue
    public function resize($width, $height)
    {
        $this->testQueueString .= "-resize-{$width}-{$height}-";
    }

    // simulating queue
    public function thumb($width, $height)
    {
        $this->testQueueString .= "-thumb-{$width}-{$height}-";
    }

    // don't lock
    protected function lock()
    {

    }
    protected function unlock()
    {

    }

    public function getTestQueueString()
    {
        return $this->testQueueString;
    }

    public function processQueue()
    {
        parent::processQueue();
    }

    public function setSize($size)
    {
        $this->size = $size;
    }

    public function dimensions($width, $height)
    {
        return parent::dimensions($width, $height);
    }

    public function getInput()
    {
        return $this->input;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function getSize()
    {
        return $this->size;
    }

    // imaginary test image size
    public function getImageSize()
    {
        return array(100, 100);
    }
    public function getInputFormat()
    {
        return $this->inputFormat;
    }

    public function getOutputFormat()
    {
        return $this->outputFormat;
    }

    public function obtainFormat($fileName)
    {
        return parent::obtainFormat($fileName);
    }

    public function setOutputFormat($format)
    {
        $this->outputFormat = $format;
    }

    public function setQuality($quality)
    {
        $this->quality = $quality;
    }

    public function getQuality()
    {
        return parent::getQuality();
    }

    public function bypassTest($width, $height, $x = 0, $y = 0)
    {
        return parent::bypassTest($width, $height, $x, $y);
    }
}
