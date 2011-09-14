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
        $this->command .= " -gravity NorthWest -crop {$width}x{$height}{$x}{$y}\! -gravity NorthWest -extent {$width}x{$height}{$xExtent}{$yExtent}";
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

    public function render($input, $output = null) {
        $this->input        = $input;
        $this->imageSize    = getimagesize($this->input);
        $this->output       = ($output == null) ? $input : $output;

        $this->outputFormat = $this->obtainFormat($this->output);

        $this->command = $this->executable . " convert {$this->input} -background none";

        $this->processQueue();

        if ($this->background === 'checkerboard') {
            $tempFile = tempnam(sys_get_temp_dir(), 'depage-graphics');
            $this->command .= " miff:{$tempFile}";

            $this->gmExec();

            $this->command = $this->executable . " convert";
            $this->command .= " -page {$this->size[0]}x{$this->size[1]} -size {$this->size[0]}x{$this->size[1]} pattern:checkerboard";
            $this->command .= " -page {$this->size[0]}x{$this->size[1]} miff:{$tempFile} -flatten +page {$this->output}";

            $this->gmExec($this->command);
            unlink($tempFile);
        } else {
            $this->command .= $this->background() . " {$this->output}";

            $this->gmExec();
        }
    }

    private function gmExec() {
        exec($this->command . ' 2>&1', $commandOutput, $returnStatus);
        if ($returnStatus != 0) {
            throw new graphicsException(implode("\n", $commandOutput));
        }
    }

    protected function background() {
        if ($this->background[0] === '#') {
            // TODO escape!!
            $background = " -flatten -background \"{$this->background}\"";
        } else if ($this->outputFormat == 'jpg') {
            $background = " -flatten -background \"#FFF\"";
        } else {
            $background = '';
        }

        return $background;
    }
}
