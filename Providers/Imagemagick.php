<?php
/**
 * @file    graphics_imagemagick.php
 * @brief   ImageMagick interface
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 * @author  Sebastian Reinhold <sebastian@bitbernd.de>
 **/

namespace Depage\Graphics\Providers;

/**
 * @brief ImageMagick interface
 *
 * The graphics_imagemagick class provides depage::graphics features using
 * the ImageMagick library.
 **/
class Imagemagick extends \Depage\Graphics\Graphics {
    // {{{ variables
    /**
     * @brief Imagemagick command string
     **/
    protected $command = '';
    /**
     * @brief Imagemagick executable path
     **/
    protected $executable;
    // }}}
    // {{{ __construct()
    /**
     * @brief graphics_graphicsmagick class constructor
     *
     * @param $options (array) image processing parameters
     **/
    public function __construct($options = array()) {
        parent::__construct($options);

        $this->executable = isset($options['executable']) ? $options['executable'] : null;
    }
    // }}}

    // {{{ crop()
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

            $this->command .= " -gravity NorthWest -crop {$width}x{$height}{$x}{$y}! -flatten";
            $this->size = array($width, $height);
        }
    }
    // }}}
    // {{{ resize()
    /**
     * @brief   Resize action
     *
     * Adds resize command to command string.
     *
     * @param   $width  (int) output width
     * @param   $height (int) output height
     * @return  void
     **/
    protected function resize($width, $height) {
        $newSize = $this->dimensions($width, $height);

        if (!$this->bypassTest($newSize[0], $newSize[1])) {

            $this->command .= " -resize {$newSize[0]}x{$newSize[1]}!";
            $this->size = $newSize;
        }
    }
    // }}}
    // {{{ thumb()
    /**
     * @brief   Thumb action
     *
     * Adds thumb command to command string.
     *
     * @param   $width  (int) output width
     * @param   $height (int) output height
     * @return  void
     **/
    protected function thumb($width, $height) {
        if (!$this->bypassTest($width, $height)) {
            $this->command .= " -gravity Center -thumbnail {$width}x{$height} -extent {$width}x{$height}";
            $this->size = array($width, $height);
        }
    }
    // }}}
    // {{{ thumbfill()
    /**
     * @brief   Thumb action
     *
     * Adds thumb command to command string.
     *
     * @param   $width  (int) output width
     * @param   $height (int) output height
     * @return  void
     **/
    protected function thumbfill($width, $height) {
        if (!$this->bypassTest($width, $height)) {
            $this->command .= " -gravity Center -thumbnail {$width}x{$height}^ -extent {$width}x{$height}";
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
            $identify       = preg_replace('/convert$/', 'identify', $this->executable);
            $command        = "{$identify} -format \"%wx%h\" " . escapeshellarg($this->input);
            $escapedCommand = str_replace('!', '\!', escapeshellcmd($command));

            exec($escapedCommand . ' 2>&1', $commandOutput, $returnStatus);
            if ($returnStatus === 0) {
                return explode('x', $commandOutput[0]);
            } else {
                throw new Exceptions\Exception(implode("\n", $commandOutput));
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
        parent::render($input, $output);

        $this->command = '';
        $this->processQueue();

        if (
            $this->bypass
            && $this->inputFormat == $this->outputFormat
        ) {
            $this->bypass();
        } else {
            $background = $this->getBackground();
            $quality    = $this->getQuality();

            $this->command = "{$this->executable} {$background} ( " . escapeshellarg($this->input) . "{$this->command}";
            $this->command .= " ) -flatten {$quality} {$this->outputFormat}:" . escapeshellarg($this->output);

            $this->execCommand();
        }
    }
    // }}}

    // {{{ execCommand()
    /**
     * @brief Executes ImageMagick command.
     * 
     * Escapes $this->command and executes it.
     *
     * @return void
     **/
    protected function execCommand() {
        $command = str_replace('!', '\!', escapeshellcmd($this->command));

        exec($command . ' 2>&1', $commandOutput, $returnStatus);
        if ($returnStatus != 0) {
            throw new Exceptions\Exception(implode("\n", $commandOutput));
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
        $background = "-size {$this->size[0]}x{$this->size[1]}";

        if ($this->background[0] === '#') {
            $background .= " -background {$this->background}";
        } else if ($this->background == 'checkerboard') {
            $background .= " -background none pattern:checkerboard";
        } else {
            if ($this->outputFormat == 'jpg') {
                $background .= " -background #FFF";
            } else {
                $background .= " -background none";
            }
        }

        return $background;
    }
    // }}}
    // {{{ getQuality()
    /**
     * @brief Generates quality command
     *
     * @return (string) quality part of the command string
     **/
    protected function getQuality() {
        if (
            $this->outputFormat == 'jpg'
            || $this->outputFormat == 'png'
        ) {
            return '-quality ' . parent::getQuality();
        } else {
            return '';
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
