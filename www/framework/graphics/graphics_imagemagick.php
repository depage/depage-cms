<?php

namespace depage\graphics;

class graphics_imagemagick extends graphics {
    protected function crop($width, $height, $x = 0, $y = 0) {
        // '+' for positive offset (the '-' is already there)
        $x = ($x < 0) ? $x : '+' . $x;
        $y = ($y < 0) ? $y : '+' . $y;

        $this->command .= " -crop {$width}x{$height}{$x}{$y}\! -flatten";
    }

    protected function resize($width, $height) {
        // allows to change aspect ratio
        $override = ($width === null || $height === null) ? '' : '\!';

        $this->command .= " -resize {$width}x{$height}{$override}";
    }

    protected function thumb($width, $height) {
        $this->command .= " -thumbnail {$width}x{$height} -gravity center -extent {$width}x{$height}";
    }

    public function render($input, $output = null) {
        $this->input    = $input;
        $this->output   = ($output == null) ? $input : $output;

        $this->command = "convert {$this->input}";

        $this->processQueue();

        $this->command .= " {$this->output}";

        exec($this->command);
    }
}
