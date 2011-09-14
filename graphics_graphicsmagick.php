<?php

namespace depage\graphics;

class graphics_graphicsmagick extends graphics {
    public function __construct($options) {
        parent::__construct($options);

        $this->executable = $options['executable'];
    }

    protected function crop($width, $height, $x = 0, $y = 0) {
        // '+' for positive offset (the '-' is already there)
        $x = ($x < 0) ? $x : '+' . $x;
        $y = ($y < 0) ? $y : '+' . $y;

        $xExtent = ($x > 0) ? "+0" : $x;
        $yExtent = ($y > 0) ? "+0" : $y;
        $this->command .= " -background none -gravity NorthWest -crop {$width}x{$height}{$x}{$y}\! -gravity NorthWest -extent {$width}x{$height}{$xExtent}{$yExtent}";
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

        $this->command = $this->executable . " convert {$this->input}";

        $this->processQueue();

        $tempFile = tempnam(sys_get_temp_dir(), 'depage-graphics');

        $this->command .= " miff:{$tempFile}";

        exec($this->command . ' 2>&1', $commandOutput, $returnStatus);
        if ($returnStatus != 0) {
            throw new graphicsException(implode("\n", $commandOutput));
        }

        $this->command = $this->executable . " convert";

        $this->command .= $this->background();
        $flatten = ($this->background() == null) ? '' : " -flatten";
        $this->command .= " -page {$this->size[0]}x{$this->size[1]} miff:{$tempFile}{$flatten} +page {$this->output}";

        exec($this->command . ' 2>&1', $commandOutput, $returnStatus);
        if ($returnStatus != 0) {
            throw new graphicsException(implode("\n", $commandOutput));
        }

        unlink($tempFile);
    }

    protected function background() {
    $background = " -page {$this->size[0]}x{$this->size[1]} -size {$this->size[0]}x{$this->size[1]}";
        if ($this->background[0] === '#') {
            // TODO escape!!
            $background .= " -background \"{$this->background}\"";
        } else if ($this->background == 'checkerboard') {
            $background .= " pattern:checkerboard";
        } else if ($this->outputFormat == 'jpg') {
            $background .= " -background \"#FFF\"";
        } else {
            $background = null;
        }

        return $background;
    }
}
