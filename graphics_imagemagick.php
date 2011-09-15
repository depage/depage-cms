<?php

namespace depage\graphics;

class graphics_imagemagick extends graphics {
    protected $size;

    public function __construct($options) {
        parent::__construct($options);

        $this->executable = $options['executable'];
    }

    protected function crop($width, $height, $x = 0, $y = 0) {
        // '+' for positive offset (the '-' is already there)
        $x = ($x < 0) ? $x : '+' . $x;
        $y = ($y < 0) ? $y : '+' . $y;

        $this->command .= " -gravity NorthWest -crop {$width}x{$height}{$x}{$y}\! -flatten";
        $this->size = array($width, $height);
    }

    protected function resize($width, $height) {
        $newSize = $this->dimensions($width, $height);

        $this->command .= " -resize {$newSize[0]}x{$newSize[1]}\!";
        $this->size = $newSize;
    }

    protected function thumb($width, $height) {
        $this->command .= " -gravity Center -thumbnail {$width}x{$height} -extent {$width}x{$height}";
        $this->size = array($width, $height);
    }

    protected function getImageSize() {
        $path = preg_replace('/convert$/', 'identify', $this->executable);
        exec("{$path} -format \"%wx%h\" {$this->input}" . ' 2>&1', $commandOutput, $returnStatus);
        if ($returnStatus === 0) {
            return explode('x', $commandOutput[0]);
        } else {
            return getimagesize($this->input);
        }
    }

    public function render($input, $output = null) {
       parent::render($input, $output);

        $this->processQueue();

        $this->command = $this->executable . $this->background() . " \( {$this->input}" . $this->command;

        $this->command .= " \) -flatten {$this->output}";

        $this->execCommand();
    }

    protected function execCommand() {
        exec($this->command . ' 2>&1', $commandOutput, $returnStatus);
        if ($returnStatus != 0) {
            throw new graphicsException(implode("\n", $commandOutput));
        }
    }

    protected function background() {
        $background = " -size {$this->size[0]}x{$this->size[1]}";

        if ($this->background[0] === '#') {
            // TODO escape!!
            $background .= " -background \"{$this->background}\"";
        } else if ($this->background == 'checkerboard') {
            $background .= " -background none pattern:checkerboard";
        } else {
            if ($this->outputFormat == 'jpg') {
                $background .= " -background \"#FFF\"";
            } else {
                $background .= " -background none";
            }
        }

        return $background;
    }
}
