<?php

namespace depage\graphics;

class graphics_imagemagick extends graphics {
    protected function crop($width, $height, $x = 0, $y = 0) {
        $this->command .= " -crop {$width}x{$height}+{$x}+{$y}\! -flatten";
    }

    protected function resize($width, $height) {
        // allows to change aspect ratio
        $override = (is_numeric($width) && is_numeric($height)) ? '\!' : '';

        $width  = (isset($width) && is_numeric($width))   ? $width  : '';
        $height = (isset($height) && is_numeric($height)) ? $height : '';

        $this->command .= " -resize {$width}x{$height}{$override}";
    }

    protected function thumb($width, $height) {
        $width  = (isset($width) && is_numeric($width))   ? $width  : '';
        $height = (isset($height) && is_numeric($height)) ? $height : '';

        $this->command .= " -thumbnail {$width}x{$height} -gravity center -extent {$width}x{$height}";
    }

    protected function load() {
        $this->command = "convert {$this->input}";
    }

    protected function save() {
        $this->command .= " {$this->output}";

        exec($this->command);
    }
}
