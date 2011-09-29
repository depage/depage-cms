<?php

class graphics_imagemagickTestClass extends graphics_imagemagick {
    protected $size = array(100, 100);
    protected $executed = false;

    public function getCommand() {
        return $this->command;
    }

    public function getExecutable() {
        return $this->executable;
    }

    public function getSize() {
        return $this->size;
    }

    public function crop($width, $height, $x = 0, $y = 0) {
        parent::crop($width, $height, $x, $y);
    }

    public function resize($width, $height) {
        parent::resize($width, $height);
    }

    public function thumb($width, $height) {
        parent::thumb($width, $height);
    }

    protected function getImageSize() {
        return array(100, 100);
    }

    protected function execCommand() {
        $this->executed = true;
    }

    public function getExecuted() {
        return $this->executed;
    }

    protected function bypass() {
    }

    public function getBackground() {
        return parent::getBackground();
    }

    public function getQuality() {
        return parent::getQuality();
    }
}
