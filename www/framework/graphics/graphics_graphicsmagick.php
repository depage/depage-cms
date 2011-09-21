<?php

class graphics_graphicsmagick extends graphics_imagemagick {
    protected function crop($width, $height, $x = 0, $y = 0) {
        // '+' for positive offset (the '-' is already there)
        $x = ($x < 0) ? $x : '+' . $x;
        $y = ($y < 0) ? $y : '+' . $y;

        $xExtent = ($x > 0) ? "+0" : $x;
        $yExtent = ($y > 0) ? "+0" : $y;
        $this->command .= " -gravity NorthWest -crop {$width}x{$height}{$x}{$y}! -gravity NorthWest -extent {$width}x{$height}{$xExtent}{$yExtent}";
        $this->size = array($width, $height);
    }

    protected function getImageSize() {
        if (is_callable('getimagesize')) {
            return getimagesize($this->input);
        } else {
            exec("{$this->executable} identify -format \"%wx%h\" {$this->input}" . ' 2>&1', $commandOutput, $returnStatus);
            if ($returnStatus === 0) {
                return explode('x', $commandOutput[0]);
            } else {
                throw new graphics_exception(implode("\n", $commandOutput));
            }
        }
    }

    public function render($input, $output = null) {
        graphics::render($input, $output);

        if ($this->bypassTest()) {
            $this->bypass();
        } else {
            $this->command = $this->executable . " convert {$this->input} -background none";

            $this->processQueue();

            $quality = $this->getQuality();

            if ($this->background === 'checkerboard') {
                $tempFile = tempnam(sys_get_temp_dir(), 'depage-graphics-');
                $this->command .= " miff:{$tempFile}";

                $this->execCommand();

                $canvasSize = $this->size[0] . "x" . $this->size[1];

                $this->command = $this->executable . " convert";
                $this->command .= " -page {$canvasSize} -size {$canvasSize} pattern:checkerboard";
                $this->command .= " -page {$canvasSize} miff:{$tempFile} -flatten -quality {$quality} +page {$this->outputFormat}:{$this->output}";

                $this->execCommand();
                unlink($tempFile);
            } else {
                $background = $this->getBackground();

                $this->command .= "{$background} -quality {$quality} {$this->outputFormat}:{$this->output}";

                $this->execCommand();
            }
        }
    }

    protected function getBackground() {
        if ($this->background[0] === '#') {
            $background = " -flatten -background {$this->background}";
        } else if ($this->outputFormat == 'jpg') {
            $background = " -flatten -background #FFF";
        } else {
            $background = '';
        }

        return $background;
    }
}
