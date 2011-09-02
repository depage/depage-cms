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

        exec("convert {$this->input} -resize {$width}x{$height}{$override} {$this->output}");
    }

    protected function thumb($options) {
    }
}
