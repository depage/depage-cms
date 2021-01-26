<?php
/**
 * @file    graphics_graphicsmagick.php
 * @brief   GraphicsMagick interface
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 * @author  Sebastian Reinhold <sebastian@bitbernd.de>
 **/

namespace Depage\Graphics\Providers;

/**
 * @brief GraphicsMagick interface
 *
 * The graphics_graphicsmagick class provides depage::graphics features using
 * the GraphicsMagick library.
 **/
class Graphicsmagick extends Imagemagick
{
    // {{{ crop
    /**
     * @brief   Crop action
     *
     * Adds crop command to command string.
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
            // '+' for positive offset (the '-' is already there)
            $x = ($x < 0) ? $x : '+' . $x;
            $y = ($y < 0) ? $y : '+' . $y;

            $xExtent = ($x > 0) ? "+0" : $x;
            $yExtent = ($y > 0) ? "+0" : $y;
            $this->command .= " -gravity NorthWest -crop {$width}x{$height}{$x}{$y}! -gravity NorthWest -extent {$width}x{$height}{$xExtent}{$yExtent}";
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
        $imageSize = false;
        if (is_callable('getimagesize')) {
            $imageSize = getimagesize($this->input);
        }
        if (!$imageSize) {
            $pageNumber = $this->getPageNumber();
            exec("{$this->executable} identify -format \"%wx%h\" " . escapeshellarg($this->input) . $pageNumber . ' 2>&1', $commandOutput, $returnStatus);
            if ($returnStatus === 0) {
                $imageSize = explode('x', $commandOutput[0]);
            } else {
                $this->unlock();

                throw new \Depage\Graphics\Exceptions\Exception(implode("\n", $commandOutput));
            }
        }

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
        \Depage\Graphics\Graphics::render($input, $output);

        $pageNumber = $this->getPageNumber();

        $this->command = $this->executable . " convert " . escapeshellarg($this->input) . "{$pageNumber} -background none";
        $this->processQueue();

        if ($this->otherRender && file_exists($this->output)) {
            // do nothing file is already generated
        } else if (
            $this->bypass
            && $this->inputFormat == $this->outputFormat
        ) {
            $this->bypass();
        } else {
            $quality = $this->getQuality();
            $optimize   = $this->getOptimize();

            if ($this->background === 'checkerboard') {
                $tempFile = tempnam(sys_get_temp_dir(), 'depage-graphics-');
                $this->command .= " miff:{$tempFile}";

                $this->execCommand();

                $canvasSize = $this->size[0] . "x" . $this->size[1];

                $this->command = $this->executable . " convert";
                $this->command .= " -page {$canvasSize} -size {$canvasSize} pattern:checkerboard";
                $this->command .= " -page {$canvasSize} miff:{$tempFile} -colorspace rgb -flatten {$quality}{$optimize} +page {$this->outputFormat}:" . escapeshellarg($this->output);

                $this->execCommand();
                unlink($tempFile);
            } else {
                $background = $this->getBackground();
                $this->command .= "{$background} -colorspace rgb {$quality}{$optimize} +page {$this->outputFormat}:" . escapeshellarg($this->output);

                $this->execCommand();

                if ($this->optimize) {
                    $this->optimizeImage($this->output);
                }
            }
        }

        parent::renderFinished();
    }
    // }}}

    // {{{ getBackground()
    /**
     * @brief Generates background command
     *
     * @return string $background background part of the command string
     **/
    protected function getBackground()
    {
        if ($this->background[0] === '#') {
            $background = " -flatten -background {$this->background}";
        } elseif ($this->outputFormat == 'jpg') {
            $background = " -flatten -background #FFF";
        } else {
            $background = '';
        }

        return $background;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
