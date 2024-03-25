<?php

use Depage\Graphics\Providers;

/**
 * Override graphicsmagick class to access protected methods/attributes in
 * tests
 **/
class graphics_graphicsmagickTestClass extends \Depage\Graphics\Providers\Graphicsmagick
{
    // imaginary test image size
    protected $size = array(100, 100);
    // simulate gm execution
    protected $executed = false;

    public function getCommand()
    {
        return $this->command;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getLimit($limit)
    {
        return $this->limits[$limit];
    }

    public function crop($width, $height, $x = 0, $y = 0)
    {
        parent::crop($width, $height, $x, $y);
    }

    public function resize($width, $height)
    {
        parent::resize($width, $height);
    }

    public function thumb($width, $height)
    {
        parent::thumb($width, $height);
    }

    // imaginary test image size
    protected function getImageSize()
    {
        return array(100, 100);
    }

    // simulate gm execution
    protected function execCommand()
    {
        $this->executed = true;
    }

    public function getExecuted()
    {
        return $this->executed;
    }

    // don't even copy on bypass
    protected function bypass()
    {
    }

    public function getBackground()
    {
        return parent::getBackground();
    }

    public function getQuality()
    {
        return parent::getQuality();
    }

    public function getBypass()
    {
        return $this->bypass;
    }
}
