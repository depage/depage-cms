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
        $width  = $options['width'];
        $height = $options['height'];

        $newSize = $this->dimensions($width, null);

        if ($newSize[1] > $height) {
            $newSize = $this->dimensions(null, $height);
            $xOffset = round(($width - $newSize[0]) / 2);
            $yOffset = 0;
        } else {
            $xOffset = 0;
            $yOffset = round(($height - $newSize[1]) / 2);
        }

        $newImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($newImage, $this->image, $xOffset, $yOffset, 0, 0, $newSize[0], $newSize[1], $this->imageSize[0], $this->imageSize[1]);

        $this->image = $newImage;
    }

    protected function load() {
        $this->imageSize    = getimagesize($this->input);
        $this->imageType    = $this->imageSize[2];

        if ($this->imageType == 1 && function_exists('imagecreatefromgif')) {
            //GIF
            $this->image = imagecreatefromgif($this->input);
        } else if ($this->imageType == 2) {
            //JPEG
            $this->image = imagecreatefromjpeg($this->input);
        } else if ($this->imageType == 3) {
            //PNG
            $this->image = imagecreatefrompng($this->input);
        }
    }

    protected function save() {
        if ($this->imageType == 1 && function_exists('imagegif')) {
            //GIF
            imagegif($this->image, $this->output);
        } else if ($this->imageType == 2) {
            //JPEG
            imagejpeg($this->image, $this->output, 80);
        } else if ($this->imageType == 3) {
            //PNG
            imagepng($this->image, $this->output);
        }
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
