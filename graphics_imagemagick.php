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

        $this->command .= " -background none -gravity NorthWest -crop {$width}x{$height}{$x}{$y}\! -flatten";
        $this->size = array($width, $height);
    }

    protected function resize($width, $height) {
        $newSize = $this->dimensions($width, $height);

        $this->command .= " -background none -resize {$newSize[0]}x{$newSize[1]}\!";
        $this->size = $newSize;
    }

    protected function thumb($width, $height) {
        $this->command .= " -background none -gravity Center -thumbnail {$width}x{$height} -extent {$width}x{$height}";
        $this->size = array($width, $height);
    }

    public function render($input, $output = null) {
        $this->input        = $input;
        $this->imageSize    = getimagesize($this->input);
        $this->output       = ($output == null) ? $input : $output;

        $this->outputFormat = $this->obtainFormat($this->output);

        $this->command = $this->executable . " {$this->input}";

        $this->processQueue();

        $this->background();

        $this->command .= " -reverse -layers merge {$this->output}";

        exec($this->command . ' 2>&1', $commandOutput, $returnStatus);
        if ($returnStatus != 0) {
            throw new graphicsException(implode("\n", $commandOutput));
        }
    }

    protected function background() {
        $this->command .= " -size {$this->size[0]}x{$this->size[1]}";

        if ($this->background[0] === '#') {
            // TODO escape!!
            $this->command .= " -background \"{$this->background}\"";
        } else if ($this->background == 'checkerboard') {
            $this->command .= " pattern:checkerboard";
        } else {
            if ($this->outputFormat == 'jpg') {
                $this->command .= " -background \"#FFF\"";
            } else {
                $this->command .= " -background none";
            }
        }
    }
}
