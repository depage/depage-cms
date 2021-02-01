<?php
/**
 * @file    Imagick.php
 *
 * description
 *
 * copyright (c) 2021 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Graphics\Providers;

/**
 * @brief Imagick
 * Class Imagick
 */
class Imagick extends \Depage\Graphics\Graphics
{
    // {{{ crop()
    /**
     * @brief crop
     *
     * @param mixed $width, $height, $x, $y
     * @return void
     **/
    protected function crop($width, $height, $x, $y)
    {
        if (!$this->bypassTest($width, $height, $x, $y)) {
            $this->image->setGravity("NorthWest");
            $this->image->cropImage($width, $height, $x, $y);
            $this->size = array($width, $height);
        }
    }
    // }}}
    // {{{ resize()
    /**
     * @brief resize
     *
     * @param mixed $width, $height
     * @return void
     **/
    protected function resize($width, $height)
    {
        $newSize = $this->dimensions($width, $height);

        if (!$this->bypassTest($newSize[0], $newSize[1])) {
            $this->image->resizeImage($newSize[0], $newSize[1]. \Imagick::FILTER_LANCZOS, 1);
            $this->size = $newSize;
        }
    }
    // }}}
    // {{{ thumb()
    /**
     * @brief thumb
     *
     * @param mixed $width, $height
     * @return void
     **/
    protected function thumb($width, $height)
    {
        if (!$this->bypassTest($width, $height)) {
            $this->image->setGravity("Center");
            $this->image->resizeImage($width, $height. \Imagick::FILTER_LANCZOS, 1);
            $this->image->setImageExtent($width, $height);
            $this->size = array($width, $height);
        }
    }
    // }}}
    // {{{ thumbfill()
    /**
     * @brief thumbfill
     *
     * @param mixed $width, $height
     * @return void
     **/
    protected function thumbfill($width, $height)
    {
        if (!$this->bypassTest($width, $height)) {
            $this->image->setGravity("Center");
            $this->image->resizeImage($width, $height. \Imagick::FILTER_LANCZOS, 1, true);
            $this->image->setImageExtent($width, $height);
            $this->size = array($width, $height);
        }
    }
    // }}}

    // {{{ getImageSize()
    /**
     * @brief   Determine size of input image
     *
     * @return void
     **/
    protected function getImageSize()
    {
        return getimagesize($this->input);
    }
    // }}}

    // {{{ load()
    /**
     * @brief load
     *
     * @param mixed
     * @return void
     **/
    protected function load()
    {
        $this->image = new \Imagick($this->input);
    }
    // }}}
    // {{{ save()
    /**
     * @brief save
     *
     * @param mixed
     * @return void
     **/
    protected function save()
    {

        if ($this->outputFormat == 'jpg') {
            $this->image->setImageFormat('jpeg');
        } else {
            $this->image->setImageFormat($this->outputFormat);
        }
        $result = $this->image->writeImage($this->output);

        if (!$result) {
            throw new \Depage\Graphics\Exceptions\Exception('Could not save output image.');
        }
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
