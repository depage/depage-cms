<?php

namespace depage\graphics;

class graphics_gd extends graphics {
    public function __construct($options) {
        parent::__construct($options);
    }

    protected function crop($width, $height, $x = 0, $y = 0) {
        $newImage = imagecreatetruecolor($width, $height);
        imagecopy($newImage, $this->image, 0, 0, $x, $y, $width, $height);

        $this->image = $newImage;
    }

    protected function resize($width, $height) {
        $newSize = $this->dimensions($width, $height);

        $newImage = imagecreatetruecolor($newSize[0], $newSize[1]);
        imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $newSize[0], $newSize[1], $this->imageSize[0], $this->imageSize[1]);

        $this->image = $newImage;
    }

    protected function thumb($width, $height) {
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

    public function render($input, $output = null) {
        $this->input    = $input;
        $this->output   = ($output == null) ? $input : $output;

        $this->load();

        foreach($this->queue as $task) {
            call_user_func_array(array($this, $task[0]), $task[1]);
        }

        $this->save();
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
