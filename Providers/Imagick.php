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
    protected function crop($width, $height, $x = 0, $y = 0)
    {
        if (!$this->bypassTest($width, $height, $x, $y)) {
            $this->image->setGravity(\Imagick::GRAVITY_NORTHWEST);
            $this->image->cropImage($width, $height, $x, $y);
            $this->image->setImagePage($width, $height, 0, 0);
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
            $this->image->resizeImage($newSize[0], $newSize[1], \Imagick::FILTER_LANCZOS, 1);
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
            $this->image->setGravity(\Imagick::GRAVITY_CENTER);
            $this->image->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1, true);
            $this->image->setGravity(\Imagick::GRAVITY_CENTER);
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
            $this->image->setGravity(\Imagick::GRAVITY_CENTER);
            $this->image->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1, true);
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
        $this->image->transformimagecolorspace(\Imagick::COLORSPACE_SRGB);
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
            //$this->image->setImageFormat('jpeg');
        } else {
            //$this->image->setImageFormat($this->outputFormat);
        }
        $result = $this->image->writeImage($this->output);
        //file_put_contents($this->output, $this->image);

        if (!$result) {
            throw new \Depage\Graphics\Exceptions\Exception('Could not save output image.');
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
        } else if ($this->bypass
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
