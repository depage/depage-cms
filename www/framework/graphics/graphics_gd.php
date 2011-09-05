<?php

namespace depage\graphics;

class graphics_gd extends graphics {
    public function __construct($options) {
        parent::__construct($options);
    }

    protected function crop($width, $height, $x = 0, $y = 0) {
        $newImage = $this->createCanvas($width, $height);

        imagecopy($newImage, $this->image, 0, 0, $x, $y, $width, $height);

        $this->image = $newImage;
        $this->imageSize = array($width, $height);
    }

    protected function resize($width, $height) {
        $newSize = $this->dimensions($width, $height);

        $newImage = imagecreatetruecolor($newSize[0], $newSize[1]);
        imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $newSize[0], $newSize[1], $this->imageSize[0], $this->imageSize[1]);

        $this->image = $newImage;
        $this->imageSize = $newSize;
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

        $newImage = $this->createCanvas($width, $height);

        imagecopyresampled($newImage, $this->image, $xOffset, $yOffset, 0, 0, $newSize[0], $newSize[1], $this->imageSize[0], $this->imageSize[1]);

        $this->image = $newImage;
        $this->imageSize = array($width, $height);
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
        $bg = $this->createBackground($this->imageSize[0], $this->imageSize[1]);
        imagecopy($bg, $this->image, 0, 0, 0, 0, $this->imageSize[0], $this->imageSize[1]);
        $this->image = $bg;

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

        imagedestroy($this->image);
    }

    public function render($input, $output = null) {
        $this->input    = $input;
        $this->output   = ($output == null) ? $input : $output;

        $this->load();

        $this->processQueue();

        $this->save();
    }

    private function dimensions($width, $height) {
        if (!is_numeric($height)) {
            $height = round(($this->imageSize[1] / $this->imageSize[0]) * $width);
        } elseif (!is_numeric($width)) {
            $width = round(($this->imageSize[0] / $this->imageSize[1]) * $height);
        }

        return array($width, $height);
    }

    private function createCanvas($width, $height) {
        $canvas = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefill($canvas, 0, 0, $bg);

        return $canvas;
    }

       private function createBackground($width, $height) {
        $newImage = imagecreatetruecolor($width, $height);

        /**
        * uses example from http://www.anyexample.com/programming/php/php_convert_rgb_from_to_html_hex_color.xml
        **/
        if ($this->background[0] == '#') {
            $color = substr($this->background, 1);

            if (strlen($color) == 6) {
                list($r, $g, $b) = array(
                    $color[0].$color[1],
                    $color[2].$color[3],
                    $color[4].$color[5]
                );
            } elseif (strlen($color) == 3) {
                list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
            }

            $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);

            imagefill($newImage, 0, 0, imagecolorallocate($newImage, $r, $g, $b));
        } else if ($this->background === 'checkerboard') {
            $transLen = 15;
            $transColor = array();
            $transColor[0] = imagecolorallocate ($newImage, 153, 153, 153);
            $transColor[1] = imagecolorallocate ($newImage, 102, 102, 102);
            for ($i = 0; $i * $transLen < $width; $i++) {
                for ($j = 0; $j * $transLen < $height; $j++) {
                    imagefilledrectangle($newImage, $i * $transLen, $j * $transLen, ($i + 1) * $transLen, ($j + 1) * $transLen, $transColor[$j % 2 == 0 ? $i % 2 : ($i % 2 == 0 ? 1 : 0)]);
                }
            }
        } else if ($this->background === 'transparent') {
            imagefill($newImage, 0, 0, imagecolorallocatealpha($newImage, 255, 255, 255, 127));
            imagesavealpha($newImage, true);
        }

        return $newImage;
    }
}
