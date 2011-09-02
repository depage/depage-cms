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

    public function render($input, $output = null) {
        $this->input    = $input;
        $this->output   = ($output == null) ? $input : $output;

        $this->command = "convert {$this->input}";

        foreach($this->queue as $task) {
            call_user_func_array(array($this, $task[0]), $task[1]);
        }

        $this->command .= " {$this->output}";

        exec($this->command);
    }
}
