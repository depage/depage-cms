<?php

/**
 * @file    Imagick.php
 *
 * description
 *
 * copyright (c) 2021-2022 Frank Hellenkamp [jonas@depage.net]
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
    /**
     * @brief image
     **/
    protected $image = null;

    // {{{ canRead()
    /**
     * @brief   Checks if extension support reading file type
     *
     * @param  string $ext file extension
     * @return bool   true if image type can be read
     **/
    public function canRead($ext)
    {
        return parent::canRead($ext) || in_array($ext, ['tif', 'tiff', 'pdf', 'eps']);
    }
    // }}}

    // {{{ crop()
    /**
     * @brief crop
     *
     * @param mixed $width, $height, $x, $y
     * @return void
     **/
    protected function crop($width, $height, $x = 0, $y = 0)
    {
        if (!$this->bypassTest($width, $height, $x, $y)) {
            $this->image->setImageGravity(\Imagick::GRAVITY_NORTHWEST);
            $this->image->cropImage($width, $height, $x, $y);
            $this->image->extentImage($width, $height, 0, 0);
            $this->image->setImagePage(0, 0, 0, 0);
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
            $filter = $this->getResizeFilter($newSize[0], $newSize[1]);

            $this->image->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
            $this->image->setImageGravity(\Imagick::GRAVITY_CENTER);
            $this->image->resizeImage($newSize[0], $newSize[1], $filter, 0.5);
            $this->image->setImageExtent($newSize[0], $newSize[1]);
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
        list($width, $height) = $this->dimensions($width, $height);

        if (!$this->bypassTest($width, $height)) {
            $newSize = $this->dimensions($width, null);
            $xOffset = 0;
            $yOffset = 0;

            if ($newSize[1] > $height) {
                $newSize = $this->dimensions(null, $height);
                $xOffset = -1 * round(($width - $newSize[0]) / 2);
            } else {
                $yOffset = -1 * round(($height - $newSize[1]) / 2);
            }
            $filter = $this->getResizeFilter($newSize[0], $newSize[1]);

            $this->image->resizeImage($newSize[0], $newSize[1], $filter, 1, true);
            $this->image->extentImage($width, $height, $xOffset, $yOffset);
            $this->size = array($newSize[0], $newSize[1]);
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
    protected function thumbfill($width, $height, $centerX = 50, $centerY = 50)
    {
        list($width, $height) = $this->dimensions($width, $height);

        if (!$this->bypassTest($width, $height, $centerX - 50, $centerY - 50)) {
            $newSize = $this->dimensions($width, null);
            $centerX /= 100;
            $centerY /= 100;

            if ($newSize[1] < $height) {
                $newSize = $this->dimensions(null, $height);
                $xOffset = -1 * round(($width - $newSize[0]) * $centerX);
                $yOffset = 0;
            } else {
                $xOffset = 0;
                $yOffset = -1 * round(($height - $newSize[1]) * $centerY);
            }
            $filter = $this->getResizeFilter($newSize[0], $newSize[1]);

            $this->image->resizeImage($newSize[0], $newSize[1], $filter, 1, true);
            $this->image->extentImage($width, $height, $xOffset, $yOffset);
            $this->size = array($width, $height);
        }
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
        $this->image = new \Imagick(realpath($this->input));
        $this->image->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
        $this->setBackground();
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

        $result = $this->image->writeImage($this->output);

        $this->image->clear();

        if (!$result) {
            throw new \Depage\Graphics\Exceptions\Exception('Could not save output image.');
        }
    }
    // }}}

    // {{{ setBackground()
    /**
     * @brief Generates background command
     *
     * @return string $background background part of the command string
     **/
    protected function setBackground()
    {
        if ($this->background[0] === '#') {
            $this->image->setImageBackgroundColor($this->background);
        } elseif ($this->background == 'checkerboard') {
        } else {
            if ($this->outputFormat == 'jpg') {
                $this->image->setImageBackgroundColor('#fff');
            } else {
                $this->image->setImageBackgroundColor('transparent');
            }
        }
    }
    // }}}
    // {{{ getResizeFilter()
    /**
     * @brief Gets the resize filter depending on target size
     *
     * this assumes that all images below 160px width or height will be thumbnails
     * everything bigger gets resized slowly in better quality
     *
     * @return int of the one of the filter constants
     **/
    protected function getResizeFilter($width, $height)
    {
        if ($width <= 160 && $height <= 160) {
            return \Imagick::FILTER_TRIANGLE;
        } else {
            return \Imagick::FILTER_LANCZOS;
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
        $this->image = new \Imagick(realpath($this->input));

        $imageSize = [
            $this->image->getImageWidth(),
            $this->image->getImageHeight()
        ];

        return $imageSize;
    }
    // }}}

    // {{{ render()
    /**
     * @brief   Main method for image handling.
     *
     * Starts actions, saves image, calls bypass if necessary.
     *
     * @param  string $input  input filename
     * @param  string $output output filename
     * @return void
     **/
    public function render($input, $output = null)
    {
        parent::render($input, $output);

        $this->load();
        $this->processQueue();

        if ($this->otherRender && file_exists($this->output)) {
            // do nothing file is already generated
        } elseif ($this->bypass
            && $this->inputFormat == $this->outputFormat
        ) {
            $this->bypass();
        } else {
            $this->save();

            if ($this->optimize) {
                $this->optimizeImage($this->output);
            }
        }

        parent::renderFinished();
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
