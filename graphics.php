<?php
/**
 * @file    graphics.php
 * @brief   Main graphics class
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 * @author  Sebastian Reinhold <sebastian@bitbernd.de>
 **/

namespace depage\graphics;

/**
 * @brief Main graphics class
 *
 * Contains graphics factory and tools. Collects actions with "add"-methods.
 **/
class graphics {
    // {{{ variables
    /**
     * @brief Input filename
     **/
    protected $input;
    /**
     * @brief Output filename
     **/
    protected $output;
    /**
     * @brief Action queue array
     **/
    protected $queue = array();
    /**
     * @brief Image size array(width, height)
     **/
    protected $size = array();
    /**
     * @brief Image background string
     **/
    protected $background;
    /**
     * @brief Image quality string
     **/
    protected $quality = '';
    /**
     * @brief Input image format
     **/
    protected $inputFormat;
    /**
     * @brief Output image format
     **/
    protected $outputFormat;
    /**
     * @brief Process bypass bool
     **/
    protected $bypass = true;
    // }}}

    // {{{ factory()
    /**
     * @brief   graphics object factory
     * 
     * Generates various graphics objects depending on extension type (default
     * is PHP GD)
     *
     * @param   $options (array) image processing parameters
     * @return  (object) graphics object
     **/
    public static function factory($options = array()) {
        $extension = (isset($options['extension'])) ? $options['extension'] : 'gd';

        if ( $extension == 'im' || $extension == 'imagemagick' ) {
            if (isset($options['executable'])) {
                return new graphics_imagemagick($options);
            } else {
                $executable = graphics::which('convert');
                if ($executable == null) {
                    trigger_error("Cannot find ImageMagick, falling back to GD", E_USER_WARNING);
                } else {
                    $options['executable'] = $executable;
                    return new graphics_imagemagick($options);
                }
            }
        } else if ( $extension == 'gm' || $extension == 'graphicsmagick' ) {
            if (isset($options['executable'])) {
                return new graphics_graphicsmagick($options);
            } else {
                $executable = graphics::which('gm');
                if ($executable == null) {
                    trigger_error("Cannot find GraphicsMagick, falling back to GD", E_USER_WARNING);
                } else {
                    $options['executable'] = $executable;
                    return new graphics_graphicsmagick($options);
                }
            }
        }

        return new graphics_gd($options);
    }
    // }}}

    // {{{ __construct()
    /**
     * @brief graphics class constructor
     *
     * @param $options (array) image processing parameters
     **/
    public function __construct($options = array()) {
        $this->background   = (isset($options['background']))   ? $options['background']        : 'transparent';
        $this->quality      = (isset($options['quality']))      ? intval($options['quality'])   : null;
        $this->format       = (isset($options['format']))       ? $options['format']            : null;
    }
    // }}}

    // {{{ addBackground()
    /**
     * @brief   Background "action"
     *
     * Sets image background.
     *
     * @param   $background (string) image background
     * @return  $this       (object)
     **/
    public function addBackground($background) {
        $this->background = $background;
        return $this;
    }
    // }}}

    // {{{ addCrop()
    /**
     * @brief   Adds crop action
     *
     * Adds crop action to action queue.
     *
     * @param   $width  (int)       output width
     * @param   $height (int)       output height
     * @param   $x      (int)       crop x-offset
     * @param   $y      (int)       crop y-offset
     * @return  $this   (object)
     **/
    public function addCrop($width, $height, $x = 0, $y = 0) {
        $this->queue[] = array('crop', func_get_args());
        return $this;
    }
    // }}}

    // {{{ addResize()
    /**
     * @brief   Adds resize action
     *
     * Adds resize action to action queue.
     *
     * @param   $width  (int)       output width
     * @param   $height (int)       output height
     * @return  $this   (object)
     **/
    public function addResize($width, $height) {
        $this->queue[] = array('resize', func_get_args());
        return $this;
    }
    // }}}

    // {{{ addThumb()
    /**
     * @brief   Adds thumb action
     *
     * Adds thumb action to action queue.
     *
     * @param   $width  (int)       output width
     * @param   $height (int)       output height
     * @return  $this   (object)
     **/
    public function addThumb($width, $height) {
        $this->queue[] = array('thumb', func_get_args());
        return $this;
    }
    // }}}

    // {{{ escapeNumber()
    /**
     * @brief   Validates integers
     *
     * Tests integers, returns only integers or null.
     *
     * @param   $number (int)       int to check
     * @return          (int)
     **/
    protected function escapeNumber($number) {
        return (is_numeric($number)) ? intval($number) : null;
    }
    // }}}

    // {{{ processQueue()
    /**
     * @brief   Process action queue
     *
     * Calls extension specific action methods.
     *
     * @return  void
     **/
    protected function processQueue() {
        foreach($this->queue as $task) {
            $action     = $task[0];
            $arguments  = array_map(array($this, 'escapeNumber'), $task[1]);

            call_user_func_array(array($this, $action), $arguments);
        }
    }
    // }}}

    // {{{ dimensions()
    /**
     * @brief   Scales image dimensions
     *
     * If either width or height is not set, it calculates the other, preserving
     * the ratio of the origіnal image.
     *
     * @param   $width  (int) output width
     * @param   $height (int) output height
     **/
    protected function dimensions($width, $height) {
        if (!is_numeric($width) && !is_numeric($height)) {
            $width  = null;
            $height = null;
        } else if (!is_numeric($height)) {
            $height = round(($this->size[1] / $this->size[0]) * $width);
        } else if (!is_numeric($width)) {
            $width  = round(($this->size[0] / $this->size[1]) * $height);
        }

        return array($width, $height);
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
        $this->input        = $input;
        $this->output       = ($output == null) ? $input : $output;
        $this->size         = $this->getImageSize();
        $this->inputFormat  = $this->obtainFormat($this->input);
        $this->outputFormat = ($this->format == null) ? $this->obtainFormat($this->output) : $this->format;
    }
    // }}}

    // {{{ obtainFormat()
    /**
     * @brief   Determines image format from file extension
     *
     * @param   $fileName   (string) filename/path
     * @return  $extension  (string) image format/filename extension
     **/
    protected function obtainFormat($fileName) {
        $parts = explode('.', $fileName);
        $extension = strtolower(end($parts));

        $extension = ($extension == 'jpeg') ? 'jpg' : $extension;

        return $extension;
    }
    // }}}

    // {{{ which()
    /**
     * @brief   Executes "which" command
     *
     * Looks for path to binary in system.
     *
     * @param   $binary             (string) filename of binary to look for
     * @return  $commandOutput[0]   (string) first line of output (path to binary)
     **/
    protected function which($binary) {
        exec('which ' . $binary, $commandOutput, $returnStatus);
        if ($returnStatus === 0) {
            return $commandOutput[0];
        } else {
            return null;
        }
    }
    // }}}

    // {{{ getQuality()
    /**
     * @brief   Returns quality-index for current image format.
     *
     * Checks plausibility of quality index for current image format. Returns
     * default value if invalid. (PNG & JPG have different systems)
     *
     * @return  $quality (string) quality index
     **/
    protected function getQuality() {
        if ($this->outputFormat == 'jpg') {
            if (
                is_numeric($this->quality)
                && $this->quality >= 0
                && $this->quality <= 100
            ) {
                $quality = $this->quality;
            } else {
                $quality = 90;
            }
        } else if ($this->outputFormat == 'png') {
            if (
                is_numeric($this->quality)
                && $this->quality >= 0
                && $this->quality <= 95
                && $this->quality % 10 <= 5
            ) {
                $quality = sprintf("%02d", $this->quality);
            } else {
                $quality = 95;
            }
        } else {
            $quality = intval($this->quality);
        }

        return (string) $quality;
    }
    // }}}

    // {{{ bypassTest()
    /**
     * @brief   Tests if action would change current image
     *
     * @param   $width  (int)   output width
     * @param   $height (int)   output height
     * @param   $x      (int)   crop x-offset
     * @param   $y      (int)   crop y-offset
     * @return  $bypass (bool)  bypass current action
     **/
    protected function bypassTest($width, $height, $x = 0, $y = 0) {
        if (
            ($width !== null && $width < 1) 
            || ($height !== null && $height < 1)
            || ($width == null && $height == null)
        ) {
            throw new graphics_exception('Invalid image size.');
        }

        $bypass = (
            $width      == $this->size[0]
            && $height  == $this->size[1]
            && $x       == 0
            && $y       == 0
        );

        $this->bypass = $this->bypass && $bypass;

        return $bypass;
    }
    // }}}

    // {{{ bypass()
    /**
     * @brief   Runs bypass (copies file)
     *
     * @return  void
     **/
    protected function bypass() {
        if ($this->input != $this->output) {
            copy($this->input, $this->output);
        }
    }
    // }}}
}
