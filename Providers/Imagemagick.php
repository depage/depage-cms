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
 *
 * @todo use ghostscript to convert pdf and eps files directly
 * @todo or use poppler pdftoppm to convert pdf directly
 **/
class Imagemagick extends \Depage\Graphics\Graphics
{
    // {{{ variables
    /**
     * @brief Imagemagick command string
     **/
    protected $command = '';
    /**
     * @brief Imagemagick executable path
     **/
    protected $executable;
    /**
     * @brief timeout after which the image conversion will be canceled
     **/
    protected $timeout = 0;
    // }}}
    // {{{ __construct()
    /**
     * @brief graphics_graphicsmagick class constructor
     *
     * @param array $options image processing parameters
     **/
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->executable = isset($options['executable']) ? $options['executable'] : null;
        $this->timeout = isset($options['timeout']) ? $options['timeout'] : 0;
    }
    // }}}

    // {{{ crop()
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
     * @param  int  $width  output width
     * @param  int  $height output height
     * @return void
     **/
    protected function resize($width, $height)
    {
        $newSize = $this->dimensions($width, $height);

        if (!$this->bypassTest($newSize[0], $newSize[1])) {
            $resizeAction = $this->getResizeAction($newSize[0], $newSize[1]);

            //$this->command .= " -colorspace Lab";
            $this->command .= " $resizeAction {$newSize[0]}x{$newSize[1]}!";
            //$this->command .= " -colorspace sRGB";

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
     * @param  int  $width  output width
     * @param  int  $height output height
     * @return void
     **/
    protected function thumb($width, $height)
    {
        if (!$this->bypassTest($width, $height)) {
            $resizeAction = $this->getResizeAction($width, $height);

            $this->command .= " -gravity Center $resizeAction {$width}x{$height} -extent {$width}x{$height}";
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
     * @param  int  $width  output width
     * @param  int  $height output height
     * @return void
     **/
    protected function thumbfill($width, $height)
    {
        if (!$this->bypassTest($width, $height)) {
            $resizeAction = $this->getResizeAction($width, $height);

            $this->command .= " -gravity Center $resizeAction {$width}x{$height}^ -extent {$width}x{$height}";
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
            $identify       = preg_replace('/convert$/', 'identify', $this->executable);
            $command        = "{$identify} -format \"%wx%h\" " . escapeshellarg($this->input) . $pageNumber;
            $escapedCommand = str_replace('!', '\!', escapeshellcmd($command));

            exec($escapedCommand . ' 2>&1', $commandOutput, $returnStatus);
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
        parent::render($input, $output);

        $this->command = '';
        $this->processQueue();

        if ($this->otherRender && file_exists($this->output)) {
            // do nothing file is already generated
        } else if (
            $this->bypass
            && $this->inputFormat == $this->outputFormat
        ) {
            $this->bypass();
        } else {
            $background = $this->getBackground();
            $quality    = $this->getQuality();
            $optimize   = $this->getOptimize();
            $pageNumber = $this->getPageNumber();

            $this->command = "{$this->executable} {$background} ( " . escapeshellarg($this->input) . "{$pageNumber}{$this->command}";
            $this->command .= " ) -flatten {$quality}{$optimize}";

            $this->command .= " {$this->outputFormat}:" . escapeshellarg($this->output);

            $this->execCommand();

            if ($this->optimize) {
                $this->optimizeImage($this->output);
            }
        }

        parent::renderFinished();
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
    protected function execCommand()
    {
        $command = str_replace('!', '\!', escapeshellcmd($this->command));

        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("pipe", "a") // stderr is pip
        );
        $process = proc_open("exec " . $command, $descriptorspec, $pipes);

        // set pipes non blocking
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $startTime = time();
        $output = array(1 => "", 2 => "");
        $terminated = false;

        if (is_resource($process)) {
            // read stdin and stderr
            while(!feof($pipes[1]) && !feof($pipes[2])) {
                for ($i = 1; $i < 3; $i++) {
                    $s = fgets($pipes[$i]);
                    $output[$i] .= $s;
                }

                usleep(10000);
                if ($this->timeout > 0 && time() - $startTime > $this->timeout) {
                    // terminate process of takes longer than timeout
                    proc_terminate($process);
                    $terminated = true;
                }
            }

            for ($i = 0; $i < 3; $i++) {
                fclose($pipes[$i]);
            }
            $returnStatus = proc_close($process);
        }

        if ($terminated) {
            $this->unlock();

            throw new \Depage\Graphics\Exceptions\Exception("Conversion over timeout");
        } else if ($returnStatus != 0) {
            $this->unlock();

            throw new \Depage\Graphics\Exceptions\Exception($output[2]);
        }
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
        $background = "-size {$this->size[0]}x{$this->size[1]}";

        if ($this->background[0] === '#') {
            $background .= " -background {$this->background}";
        } elseif ($this->background == 'checkerboard') {
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
     * @return string quality part of the command string
     **/
    protected function getQuality()
    {
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
    // {{{ getOptimize()
    /**
     * @brief Generates optimization parameters
     *
     * @return string optimization part of the command string
     **/
    protected function getOptimize()
    {
        $param = "";
        if ($this->optimize) {
            $param .= " -strip";

            if ($this->outputFormat == 'jpg') {
                $param .= " -interlace Plane";
            } else if ($this->outputFormat == 'png') {
                $param .= " -define png:format=png00";
            }
        }

        return $param;
    }
    // }}}
    // {{{ getResizeAction()
    /**
     * @brief Gets the resize action depending on target size
     *
     * this assumes that all images below 160px width or height will be thumbnails
     * everything bigger gets resized slowly in better quality
     *
     * @return string resize action as part of command string
     **/
    protected function getResizeAction($width, $height)
    {
        if ($width <= 160 && $height <= 160) {
            return "-thumbnail";
        } else {
            return "-resize";
        }
    }
    // }}}
    // {{{ getPageNumber()
    /**
     * @brief getPageNumber
     *
     * @param mixed
     * @return void
     **/
    public function getPageNumber()
    {
        if ($this->inputFormat == "pdf") {
            return "[0]";
        } else {
            return "";
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
