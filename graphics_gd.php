<?php

class graphics_gd extends graphics {
    protected function crop($width, $height, $x = 0, $y = 0) {
        $newImage = $this->createCanvas($width, $height);

        imagecopy(
            $newImage,
            $this->image,
            ($x > 0) ? 0 : abs($x),
            ($y > 0) ? 0 : abs($y),
            ($x < 0) ? 0 : $x,
            ($y < 0) ? 0 : $y,
            $this->size[0] - abs($x),
            $this->size[1] - abs($y)
        );

        $this->image = $newImage;
        $this->size = array($width, $height);
    }

    protected function resize($width, $height) {
        $newSize = $this->dimensions($width, $height);

        $newImage = $this->createCanvas($newSize[0], $newSize[1]);
        imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $newSize[0], $newSize[1], $this->size[0], $this->size[1]);

        $this->image = $newImage;
        $this->size = $newSize;
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

        imagecopyresampled($newImage, $this->image, $xOffset, $yOffset, 0, 0, $newSize[0], $newSize[1], $this->size[0], $this->size[1]);

        $this->image = $newImage;
        $this->size = array($width, $height);
    }

    protected function load() {
        $this->inputFormat  = $this->size[2];

        if ($this->inputFormat == 1 && function_exists('imagecreatefromgif')) {
            //GIF
            $this->image = imagecreatefromgif($this->input);
        } else if ($this->inputFormat == 2) {
            //JPEG
            $this->image = imagecreatefromjpeg($this->input);
        } else if ($this->inputFormat == 3) {
            //PNG
            $this->image = imagecreatefrompng($this->input);
        }
    }

    protected function save() {
        $bg = $this->createBackground($this->size[0], $this->size[1]);
        imagecopy($bg, $this->image, 0, 0, 0, 0, $this->size[0], $this->size[1]);
        $this->image = $bg;

        if ($this->outputFormat == 'gif' && function_exists('imagegif')) {
            imagegif($this->image, $this->output);
        } else if ($this->outputFormat == 'jpg') {
            imagejpeg($this->image, $this->output, $this->getQuality());
        } else if ($this->outputFormat == 'png') {
            $quality = $this->getQuality();
            imagepng($this->image, $this->output, $quality[0], $quality[1]);
        }

        imagedestroy($this->image);
    }

    protected function getImageSize() {
        return getimagesize($this->input);
    }

    public function render($input, $output = null) {
        parent::render($input, $output);

        if ($this->bypassTest()) {
            $this->bypass();
        } else {
            $this->load();
            $this->processQueue();
            $this->save();
        }
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
        } else if ($this->background == 'checkerboard') {
            $transLen = 15;
            $transColor = array();
            $transColor[0] = imagecolorallocate ($newImage, 153, 153, 153);
            $transColor[1] = imagecolorallocate ($newImage, 102, 102, 102);
            for ($i = 0; $i * $transLen < $width; $i++) {
                for ($j = 0; $j * $transLen < $height; $j++) {
                    imagefilledrectangle($newImage, $i * $transLen, $j * $transLen, ($i + 1) * $transLen, ($j + 1) * $transLen, $transColor[$j % 2 == 0 ? $i % 2 : ($i % 2 == 0 ? 1 : 0)]);
                }
            }
        } else if ($this->background == 'transparent') {
            imagefill($newImage, 0, 0, imagecolorallocatealpha($newImage, 255, 255, 255, 127));
            if ($this->outputFormat == 'gif') imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 255, 255, 255, 127));
            imagesavealpha($newImage, true);
        }

        return $newImage;
    }
}
