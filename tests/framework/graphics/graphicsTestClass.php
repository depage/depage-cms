<?php

use depage\graphics\graphics;

class graphicsTestClass extends graphics {
    protected $testQueueString = '';

    public function getBackground() {
        return $this->background;
    }

    public function getQueue() {
        return $this->queue;
    }

    public function escapeNumber($number) {
        return parent::escapeNumber($number);
    }

    public function crop($width, $height, $x, $y) {
        $this->testQueueString .= "-crop-{$width}-{$height}-{$x}-{$y}-";
    }

    public function resize($width, $height) {
        $this->testQueueString .= "-resize-{$width}-{$height}-";
    }

    public function thumb($width, $height) {
        $this->testQueueString .= "-thumb-{$width}-{$height}-";
    }

    public function getTestQueueString() {
        return $this->testQueueString;
    }

    public function processQueue() {
        parent::processQueue();
    }

    public function setSize($size) {
        $this->size = $size;
    }

    public function dimensions($width, $height) {
        return parent::dimensions($width, $height);
    }

    public function getInput() {
        return $this->input;
    }

    public function getOutput() {
        return $this->output;
    }

    public function getSize() {
        return $this->size;
    }

    public function getImageSize() {
        return array(100, 100);
    }
    public function getInputFormat() {
        return $this->inputFormat;
    }

    public function getOutputFormat() {
        return $this->outputFormat;
    }

    public function obtainFormat($fileName) {
        return parent::obtainFormat($fileName);
    }

    public function setOutputFormat($format) {
        $this->outputFormat = $format;
    }

    public function setQuality($quality) {
        $this->quality = $quality;
    }

    public function getQuality() {
        return parent::getQuality();
    }

    public function bypassTest() {
        return parent::bypassTest();
    }
}
