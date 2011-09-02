<?php

namespace depage\graphics;

class graphics_imagemagick extends graphics {
    protected function crop($options) {
    }

    protected function resize($options) {
        // allows to change aspect ratio
        $override = (is_numeric($options['width']) && is_numeric($options['height'])) ? '\!' : '';

        $width  = (isset($options['width']) && is_numeric($options['width']))   ? $options['width']     : '';
        $height = (isset($options['height']) && is_numeric($options['height'])) ? $options['height']    : '';

        $this->command .= " -resize {$width}x{$height}{$override}";
    }

    protected function thumb($options) {
        $width  = (isset($options['width']) && is_numeric($options['width']))   ? $options['width']     : '';
        $height = (isset($options['height']) && is_numeric($options['height'])) ? $options['height']    : '';

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
