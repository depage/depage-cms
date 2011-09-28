<?php

class graphicsTestClass extends graphics {
    public $testQueueString = '';

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

    public function processQueue() {
        parent::processQueue();
    }
}
