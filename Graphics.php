<?php
/**
 * @file    graphics.php
 * @brief   Main graphics class
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 * @author  Sebastian Reinhold <sebastian@bitbernd.de>
 *
 * @todo add interlacing/progressive loading to jpegs
 *       gd: imageinterlace
 *       im: PJPEG:
 **/

namespace Depage\Graphics;

// {{{ autoloader
/**
 * @brief PHP autoloader
 *
 * Autoloads classes by namespace. (requires PHP >= 5.3)
 **/
function autoload($class)
{
    if (strpos($class, __NAMESPACE__ . '\\') == 0) {
        $class = str_replace('\\', '/', str_replace(__NAMESPACE__ . '\\', '', $class));
        $file = __DIR__ . '/' .  $class . '.php';

        if (file_exists($file)) {
            require_once($file);
        }
    }
}

spl_autoload_register(__NAMESPACE__ . '\autoload');
// }}}

/**
 * @brief Main graphics class
 *
 * Contains graphics factory and tools. Collects actions with "add"-methods.
 **/
class Graphics
{
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
     * @brief
     **/
    protected $outputLockFp = null;
    /**
     * @brief otherRender is set to true if another render process has already locked file
     **/
    protected $otherRender = false;
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
     * @brief Optimize output images
     **/
    protected $optimize;
    /**
     * @brief List of optimizer binaries
     **/
    protected $optimizers;
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
    /**
     * @brief oldIgnoreUserAbort
     **/
    private $oldIgnoreUserAbort = false;
    // }}}
    // {{{ factory()
    /**
     * @brief   graphics object factory
     *
     * Generates various graphics objects depending on extension type (default
     * is PHP GD)
     *
     * @param  array  $options image processing parameters
     * @return object graphics object
     **/
    public static function factory($options = array())
    {
        $extension = (isset($options['extension'])) ? $options['extension'] : 'gd';

        if ($extension == 'imagick' && extension_loaded('imagick')) {
            return new Providers\Imagick($options);
        } elseif ($extension == 'im' || $extension == 'imagemagick') {
            if (isset($options['executable'])) {
                return new Providers\Imagemagick($options);
            } else {
                $executable = Graphics::which('convert');
                if ($executable == null) {
                    trigger_error("Cannot find ImageMagick, falling back to GD", E_USER_WARNING);
                } else {
                    $options['executable'] = $executable;

                    return new Providers\Imagemagick($options);
                }
            }
        } elseif ($extension == 'gm' || $extension == 'graphicsmagick') {
            if (isset($options['executable'])) {
                return new Providers\Graphicsmagick($options);
            } else {
                $executable = Graphics::which('gm');
                if ($executable == null) {
                    trigger_error("Cannot find GraphicsMagick, falling back to GD", E_USER_WARNING);
                } else {
                    $options['executable'] = $executable;

                    return new Providers\Graphicsmagick($options);
                }
            }
        }

        return new Providers\Gd($options);
    }
    // }}}
    // {{{ __construct()
    /**
     * @brief graphics class constructor
     *
     * @param array $options image processing parameters
     **/
    public function __construct($options = array())
    {
        $this->background   = (isset($options['background']))   ? $options['background']        : 'transparent';
        $this->quality      = (isset($options['quality']))      ? intval($options['quality'])   : null;
        $this->format       = (isset($options['format']))       ? $options['format']            : null;
        $this->optimize     = (isset($options['optimize']))     ? $options['optimize']          : false;
        $this->optimizers   = (isset($options['optimizers']))   ? $options['optimizers']        : array();
    }
    // }}}

    // {{{ addBackground()
    /**
     * @brief   Background "action"
     *
     * Sets image background.
     *
     * @param  string $background image background
     * @return object $this
     **/
    public function addBackground($background)
    {
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
     * @param  int    $width  output width
     * @param  int    $height output height
     * @param  int    $x      crop x-offset
     * @param  int    $y      crop y-offset
     * @return object $this
     **/
    public function addCrop($width, $height, $x = 0, $y = 0)
    {
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
     * @param  int    $width  output width
     * @param  int    $height output height
     * @return object $this
     **/
    public function addResize($width, $height)
    {
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
     * @param  int    $width  output width
     * @param  int    $height output height
     * @return object $this
     **/
    public function addThumb($width, $height)
    {
        $this->queue[] = array('thumb', func_get_args());

        return $this;
    }
    // }}}
    // {{{ addThumbfill()
    /**
     * @brief   Adds thumb-fill action
     *
     * Adds thumb-fill action to action queue.
     *
     * @param  int    $width  output width
     * @param  int    $height output height
     * @param  int    $centerX center of image from left in percent
     * @param  int    $centerY center of image from top in percent
     * @return object $this
     **/
    public function addThumbfill($width, $height, $centerX = 50, $centerY = 50)
    {
        $this->queue[] = array('thumbfill', func_get_args());

        return $this;
    }
    // }}}

    // {{{ escapeNumber()
    /**
     * @brief   Validates integers
     *
     * Tests integers, returns only integers or null.
     *
     * @param  int $number int to check
     * @return int number value
     **/
    protected function escapeNumber($number)
    {
        return (is_numeric($number)) ? intval($number) : null;
    }
    // }}}
    // {{{ dimensions()
    /**
     * @brief   Scales image dimensions
     *
     * If either width or height is not set, it calculates the other, preserving
     * the ratio of the origіnal image.
     *
     * @param  int   $width  output width
     * @param  int   $height output height
     * @return array of width and height
     **/
    protected function dimensions($width, $height)
    {
        if (!is_numeric($width) && !is_numeric($height)) {
            $width  = null;
            $height = null;
        } elseif (!is_numeric($height)) {
            $height = round(($this->size[1] / $this->size[0]) * $width);
        } elseif (!is_numeric($width)) {
            $width  = round(($this->size[0] / $this->size[1]) * $height);
        }

        return array($width, $height);
    }
    // }}}

    // {{{ processQueue()
    /**
     * @brief   Process action queue
     *
     * Calls extension specific action methods.
     *
     * @return void
     **/
    protected function processQueue()
    {
        foreach ($this->queue as $task) {
            $action     = $task[0];
            $arguments  = array_map(array($this, 'escapeNumber'), $task[1]);

            call_user_func_array(array($this, $action), $arguments);
        }
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
        if (!file_exists($input)) throw new Exceptions\FileNotFound();

        $this->input        = $input;
        $this->output       = ($output == null) ? $input : $output;
        $this->inputFormat  = $this->obtainFormat($this->input);
        $this->outputFormat = ($this->format == null) ? $this->obtainFormat($this->output) : $this->format;
        $this->size         = $this->getImageSize();
        $this->otherRender  = false;

        $this->lock();

        $this->oldIgnoreUserAbort = ignore_user_abort();
        ignore_user_abort(true);
    }
    // }}}
    // {{{ renderFinished()
    /**
     * @brief   Called after rendering has finished
     *
     * Resets ignore_user_abort
     **/
    public function renderFinished()
    {
        ignore_user_abort($this->oldIgnoreUserAbort);

        $this->unlock();
    }
    // }}}
    // {{{ optimizeImage()
    /**
     * @brief   Opimizes final image through one of the optimization programs
     *
     * @param  string $file name of file to optimize
     * @return bool true if image has been optimized successfully
     **/
    public function optimizeImage($filename)
    {
        if (!file_exists($filename)) {
            return false;
        }

        $optimizer = new Optimizers\Optimizer($this->optimizers);
        $success = $optimizer->optimize($filename);

        return $success;
    }
    // }}}
    // {{{ lock()
    /**
     * @brief lock
     *
     * @return void
     **/
    protected function lock()
    {
        // set lock
        $this->outputLockFp = fopen($this->output . ".lock", 'w');
        $locked = flock($this->outputLockFp, LOCK_EX | LOCK_NB, $wouldblock);

        if (!$locked && $wouldblock) {
            $this->otherRender = true;
            flock($this->outputLockFp, LOCK_EX);
        }
    }
    // }}}
    // {{{ unlock()
    /**
     * @brief unlock
     *
     * @return void
     **/
    protected function unlock()
    {
        // release lock
        if (isset($this->outputLockFp)) {
            flock($this->outputLockFp, LOCK_UN);
            unlink($this->output . ".lock");

            $this->outputLockFp = null;
        }
    }
    // }}}

    // {{{ obtainFormat()
    /**
     * @brief   Determines image format from file extension
     *
     * @param  string $fileName filename/path
     * @return string $extension image format/filename extension
     **/
    protected function obtainFormat($fileName)
    {
        $parts = explode('.', $fileName);
        $extension = strtolower(end($parts));

        if ($extension == 'jpeg') {
            $extension = 'jpg';
        } elseif (
            $extension != 'jpg'
            && $extension != 'png'
            && $extension != 'gif'
        ) {
            if (is_callable('getimagesize') && file_exists($fileName)) {
                $info = getimagesize($fileName);
                if (isset($info[2])) {
                    $format = $info[2];

                    if ($format == 1) {
                        $extension = 'gif';
                    } elseif ($format == 2) {
                        $extension = 'jpg';
                    } elseif ($format == 3) {
                        $extension = 'png';
                    }
                }
            }
        }

        return $extension;
    }
    // }}}

    // {{{ which()
    /**
     * @brief   Executes "which" command
     *
     * Looks for path to binary in system.
     *
     * @param  string $binary filename of binary to look for
     * @return string $commandOutput[0]   first line of output (path to binary)
     **/
    public static function which($binary)
    {
        exec('which ' . $binary, $commandOutput, $returnStatus);
        if ($returnStatus === 0) {
            return $commandOutput[0];
        } else {
            return null;
        }
    }
    // }}}

    // {{{ setQuality()
    /**
     * @brief   Sets quality parameter
     **/
    public function setQuality($quality)
    {
        $this->quality = $quality;
    }
    // }}}
    // {{{ getQuality()
    /**
     * @brief   Returns quality-index for current image format.
     *
     * Checks plausibility of quality index for current image format. Returns
     * default value if invalid. (PNG & JPG have different systems)
     *
     * @return string $quality quality index
     **/
    protected function getQuality()
    {
        if ($this->outputFormat == 'jpg') {
            if (
                is_numeric($this->quality)
                && $this->quality >= 0
                && $this->quality <= 100
            ) {
                $quality = $this->quality;
            } else {
                $quality = 85;
            }
        } elseif ($this->outputFormat == "webp") {
            if (
                is_numeric($this->quality)
                && $this->quality >= 0
                && $this->quality <= 100
            ) {
                $quality = $this->quality;
            } else {
                $quality = 75;
            }
        } elseif ($this->outputFormat == 'png') {
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
     * @param  int  $width  output width
     * @param  int  $height output height
     * @param  int  $x      crop x-offset
     * @param  int  $y      crop y-offset
     * @return bool $bypass bypass current action
     **/
    protected function bypassTest($width, $height, $x = 0, $y = 0)
    {
        if (
            ($width !== null && $width < 1)
            || ($height !== null && $height < 1)
            || ($width == null && $height == null)
        ) {
            $this->unlock();

            throw new Exceptions\Exception('Invalid image size.');
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
     * @return void
     **/
    protected function bypass()
    {
        if ($this->input != $this->output) {
            copy($this->input, $this->output);
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
