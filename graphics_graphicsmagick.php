<?php
/**
 * @file    graphics_graphicsmagick.php
 * @brief   GraphicsMagick interface
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 * @author  Sebastian Reinhold <sebastian@bitbernd.de>
 **/

namespace depage\graphics;

/**
 * @brief GraphicsMagick interface
 *
 * The graphics_graphicsmagick class provides depage::graphics features using
 * the GraphicsMagick library.
 **/
class graphics_graphicsmagick extends graphics_imagemagick {
    // {{{ crop
    /**
     * @brief   Crop action
     *
     * Adds crop command to command string.
     *
     * @param   $width  (int) output width
     * @param   $height (int) output height
     * @param   $x      (int) crop x-offset
     * @param   $y      (int) crop y-offset
     * @return  void
     **/
    protected function crop($width, $height, $x = 0, $y = 0) {
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
     * @return  void
     **/
    protected function getImageSize() {
        if (is_callable('getimagesize')) {
            return getimagesize($this->input);
        } else {
            exec("{$this->executable} identify -format \"%wx%h\" {$this->input}" . ' 2>&1', $commandOutput, $returnStatus);
            if ($returnStatus === 0) {
                return explode('x', $commandOutput[0]);
            } else {
                throw new graphics_exception(implode("\n", $commandOutput));
            }
        }
    }
    // }}}

    // {{{ render()
    /**
     * @brief   Main method for image handling.
     *
     * Starts actions, saves image, calls bypass if necessary.
     *
     * @param   $input  (string) input filename
     * @param   $output (string) output filename
     * @return  void
     **/
    public function render($input, $output = null) {
        graphics::render($input, $output);

        $this->command = $this->executable . " convert {$this->input} -background none";
        $this->processQueue();

        if (
            $this->bypass
            && $this->inputFormat == $this->outputFormat
        ) {
            $this->bypass();
        } else {
            $quality = $this->getQuality();

            if ($this->background === 'checkerboard') {
                $tempFile = tempnam(sys_get_temp_dir(), 'depage-graphics-');
                $this->command .= " miff:{$tempFile}";

                $this->execCommand();

                $canvasSize = $this->size[0] . "x" . $this->size[1];

                $this->command = $this->executable . " convert";
                $this->command .= " -page {$canvasSize} -size {$canvasSize} pattern:checkerboard";
                $this->command .= " -page {$canvasSize} miff:{$tempFile} -flatten {$quality} +page {$this->outputFormat}:{$this->output}";

                $this->execCommand();
                unlink($tempFile);
            } else {
                $background = $this->getBackground();
                $this->command .= "{$background} {$quality} +page {$this->outputFormat}:{$this->output}";

                $this->execCommand();
            }
        }
    }
    // }}}

    // {{{ getBackground()
    /**
     * @brief Generates background command
     *
     * @return $background (string) background part of the command string
     **/
    protected function getBackground() {
        if ($this->background[0] === '#') {
            $background = " -flatten -background {$this->background}";
        } else if ($this->outputFormat == 'jpg') {
            $background = " -flatten -background #FFF";
        } else {
            $background = '';
        }

        return $background;
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
