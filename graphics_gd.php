<?php

namespace depage\graphics;

class graphics_gd extends graphics {
    public function __construct($options) {
        parent::__construct($options);
    }

    protected function crop($options) {
    }

    protected function resize($options) {
        $newSize = $this->dimensions($options['width'], $options['height']);

        $newImage = imagecreatetruecolor($newSize[0], $newSize[1]);
        imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $newSize[0], $newSize[1], $this->imageSize[0], $this->imageSize[1]);

        $this->image = $newImage;
    }

    protected function thumb($options) {
    }

    protected function load() {
        $this->image        = imagecreatefromjpeg($this->input);
        $this->imageSize    = getimagesize($this->input);
    }

    protected function save() {
        imagejpeg($this->image, $this->output, 80);
    }

    private function dimensions($width, $height) {
        if (!is_numeric($height)) {
            $height = ($this->imageSize[1] / $this->imageSize[0]) * $width;
        } elseif (!is_numeric($width)) {
            $width = ($this->imageSize[0] / $this->imageSize[1]) * $height;
        }

        return array($width, $height);
    }
}
