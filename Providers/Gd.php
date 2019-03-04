<?php
/**
 * @file    graphics_gd.php
 * @brief   PHP GD extension interface
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 * @author  Sebastian Reinhold <sebastian@bitbernd.de>
 **/

namespace Depage\Graphics\Providers;

/**
 * @brief PHP GD extension interface
 *
 * The graphics_gd class provides depage::graphics features using the PHP GD
 * extension.
 **/
class Gd extends \Depage\Graphics\Graphics
{
    // {{{ crop()
    /**
     * @brief   Crop action
     *
     * Applies crop action to $this->image.
     *
     * @param  int  $width  output width
     * @param  int  $height output height
     * @param  int  $x      crop x-offset
     * @param  int  $y      crop y-offset
     * @return void
     **/
    protected function crop($width, $height, $x = 0, $y = 0)
    {
        if (!$this->bypassTest($width, $height, $x, $y)) {
            $newImage = $this->createCanvas($width, $height);

            imagecopy(
                $newImage,
                $this->image,
                ($x > 0) ? 0 : abs($x),
                ($y > 0) ? 0 : abs($y),
                ($x < 0) ? 0 : $x,
                ($y < 0) ? 0 : $y,
                $this->size[0] - abs($x),
                $this->size[1] - abs($y)
            );

            $this->image = $newImage;
            $this->size = array($width, $height);
        }
    }
    // }}}
    // {{{ resize()
    /**
     * @brief   Resize action
     *
     * Applies resize action to $this->image.
     *
     * @param  int  $width  output width
     * @param  int  $height output height
     * @return void
     **/
    protected function resize($width, $height)
    {
        $newSize = $this->dimensions($width, $height);

        if (!$this->bypassTest($newSize[0], $newSize[1])) {
            $newImage = $this->createCanvas($newSize[0], $newSize[1]);
            imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $newSize[0], $newSize[1], $this->size[0], $this->size[1]);

            $this->image = $newImage;
            $this->size = $newSize;
        }
    }
    // }}}
    // {{{ thumb()
    /**
     * @brief   Thumb action
     *
     * Applies thumb action to $this->image.
     *
     * @param  int  $width  output width
     * @param  int  $height output height
     * @return void
     **/
    protected function thumb($width, $height)
    {
        list($width, $height) = $this->dimensions($width, $height);

        if (!$this->bypassTest($width, $height)) {
            $newSize = $this->dimensions($width, null);

            if ($newSize[1] > $height) {
                $newSize = $this->dimensions(null, $height);
                $xOffset = round(($width - $newSize[0]) / 2);
                $yOffset = 0;
            } else {
                $xOffset = 0;
                $yOffset = round(($height - $newSize[1]) / 2);
            }

            $newImage = $this->createCanvas($width, $height);

            imagecopyresampled($newImage, $this->image, $xOffset, $yOffset, 0, 0, $newSize[0], $newSize[1], $this->size[0], $this->size[1]);

            $this->image = $newImage;
            $this->size = array($width, $height);
        }
    }
    // }}}
    // {{{ thumbfill()
    /**
     * @brief   Thumb-Fill action
     *
     * Applies thumb-fill action to $this->image.
     *
     * @param  int  $width  output width
     * @param  int  $height output height
     * @return void
     **/
    protected function thumbfill($width, $height)
    {
        list($width, $height) = $this->dimensions($width, $height);

        if (!$this->bypassTest($width, $height)) {
            $newSize = $this->dimensions($width, null);

            if ($newSize[1] < $height) {
                $newSize = $this->dimensions(null, $height);
                $xOffset = round(($width - $newSize[0]) / 2);
                $yOffset = 0;
            } else {
                $xOffset = 0;
                $yOffset = round(($height - $newSize[1]) / 2);
            }

            $newImage = $this->createCanvas($width, $height);

            imagecopyresampled($newImage, $this->image, $xOffset, $yOffset, 0, 0, $newSize[0], $newSize[1], $this->size[0], $this->size[1]);

            $this->image = $newImage;
            $this->size = array($width, $height);
        }
    }
    // }}}

    // {{{ load()
    /**
     * @brief   Loads image from file
     *
     * Determines image format and loads it to $this->image.
     *
     * @return void
     **/
    protected function load()
    {
        if ($this->inputFormat == 'gif' && function_exists('imagecreatefromgif')) {
            //GIF
            $this->image = imagecreatefromgif($this->input);
        } elseif ($this->inputFormat == 'jpg') {
            //JPEG
            $this->image = imagecreatefromjpeg($this->input);
        } elseif ($this->inputFormat == 'png') {
            //PNG
            $this->image = imagecreatefrompng($this->input);
        } elseif ($this->inputFormat == 'webp' && function_exists('imagecreatefromwebp')) {
            //WEBP
            $this->image = imagecreatefromwebp($this->input);
        } else {
            throw new \Depage\Graphics\Exceptions\Exception('Unknown image format.');
        }
    }
    // }}}
    // {{{ save()
    /**
     * @brief   Saves image to file.
     *
     * Adds background and saves $this->image to file.
     *
     * @return void
     **/
    protected function save()
    {
        $bg = $this->createBackground($this->size[0], $this->size[1]);
        imagecopy($bg, $this->image, 0, 0, 0, 0, $this->size[0], $this->size[1]);
        $this->image = $bg;
        $result = false;

        if ($this->outputFormat == 'gif' && function_exists('imagegif')) {
            $result = imagegif($this->image, $this->output);
        } elseif ($this->outputFormat == 'jpg') {
            $result = imagejpeg($this->image, $this->output, $this->getQuality());
        } elseif ($this->outputFormat == 'png') {
            $quality = (int) ($this->getQuality() / 10);
            $result = imagepng($this->image, $this->output, $quality, PNG_ALL_FILTERS);
        } elseif ($this->outputFormat == 'webp' && function_exists('imagewebp')) {
            $result = imagewebp($this->image, $this->output, $this->getQuality());
        }
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
        return getimagesize($this->input);
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

    // {{{ createCanvas()
    /**
     * @brief   Creates transparent canvas with given dimensions
     *
     * @param  int    $width  canvas width
     * @param  int    $height canvas height
     * @return object $canvas image resource identifier
     **/
    private function createCanvas($width, $height)
    {
        $canvas = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefill($canvas, 0, 0, $bg);

        return $canvas;
    }
    // }}}
    // {{{ createBackground()
    /**
     * @brief   Creates background with given dimensions
     *
     * Creates image background specified in $this->background
     *
     * @param  int    $width  canvas width
     * @param  int    $height canvas height
     * @return object $newImage   image resource identifier
     **/
    private function createBackground($width, $height)
    {
        $newImage = imagecreatetruecolor($width, $height);

        if ($this->background[0] == '#') {
            /**
            * uses example from http://www.anyexample.com/programming/php/php_convert_rgb_from_to_html_hex_color.xml
            **/
            $color = substr($this->background, 1);

            if (strlen($color) == 6) {
                list($r, $g, $b) = array(
                    $color[0].$color[1],
                    $color[2].$color[3],
                    $color[4].$color[5]
                );
            } elseif (strlen($color) == 3) {
                list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
            }

            $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);

            imagefill($newImage, 0, 0, imagecolorallocate($newImage, $r, $g, $b));
        } elseif ($this->background == 'checkerboard') {
            $transLen = 15;
            $transColor = array();
            $transColor[0] = imagecolorallocate ($newImage, 153, 153, 153);
            $transColor[1] = imagecolorallocate ($newImage, 102, 102, 102);
            for ($i = 0; $i * $transLen < $width; $i++) {
                for ($j = 0; $j * $transLen < $height; $j++) {
                    imagefilledrectangle(
                        $newImage,
                        $i * $transLen,
                        $j * $transLen,
                        ($i + 1) * $transLen,
                        ($j + 1) * $transLen,
                        $transColor[$j % 2 == 0 ? $i % 2 : ($i % 2 == 0 ? 1 : 0)]
                    );
                }
            }
        } elseif ($this->background == 'transparent') {
            imagefill($newImage, 0, 0, imagecolorallocatealpha($newImage, 255, 255, 255, 127));
            if ($this->outputFormat == 'gif') imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 255, 255, 255, 127));
            imagesavealpha($newImage, true);
        }

        return $newImage;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
