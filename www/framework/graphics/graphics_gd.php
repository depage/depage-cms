<?php

namespace depage\graphics;

class graphics_gd extends graphics {
    public function __construct($options) {
        parent::__construct($options);
    }

    private function crop($options) {
    }

    protected function resize($options) {
        $newImage = imagecreatetruecolor($options['width'], $options['height']);
        imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $options['width'], $options['height'], $this->imageSize[0], $this->imageSize[1]);

        $this->image = $newImage;
    }

    private function thumb($options) {
    }

    public function render() {
        $this->image        = imagecreatefromjpeg($this->input);
        $this->imageSize    = getimagesize($this->input);

        parent::render();

        imagejpeg($this->image, $this->output);
    }
}
