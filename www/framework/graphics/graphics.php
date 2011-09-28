<?php

class graphics {
    protected $input;
    protected $output;
    protected $queue = array();
    protected $size = array();
    protected $background;
    protected $quality;
    protected $inputFormat;
    protected $outputFormat;

    public static function factory($options = array()) {
        $extension = (isset($options['extension'])) ? $options['extension'] : 'gd';

        if ( $extension == 'im' || $extension == 'imagemagick' ) {
            if (isset($options['executable'])) {
                return new graphics_imagemagick($options);
            } else {
                $executable = graphics::which('convert');
                if ($executable == null) {
                    trigger_error("Cannot find ImageMagick, falling back to GD", E_USER_ERROR);
                } else {
                    $options['executable'] = $executable;
                    return new graphics_imagemagick($options);
                }
            }
        } else if ( $extension == 'gm' || $extension == 'graphicsmagick' ) {
            if (isset($options['executable'])) {
                return new graphics_graphicsmagick($options);
            } else {
                $executable = graphics::which('gm');
                if ($executable == null) {
                    trigger_error("Cannot find GraphicsMagick, falling back to GD", E_USER_ERROR);
                } else {
                    $options['executable'] = $executable;
                    return new graphics_graphicsmagick($options);
                }
            }
        }

        return new graphics_gd($options);
    }

    public function __construct($options = array()) {
        $this->background   = (isset($options['background']))   ? $options['background']        : 'transparent';
        $this->quality      = (isset($options['quality']))      ? intval($options['quality'])   : null;
        $this->outputFormat = (isset($options['format']))       ? $options['format']            : null;
    }

    public function addBackground($background) {
        $this->background = $background;
        return $this;
    }

    public function addCrop($width, $height, $x = 0, $y = 0) {
        $this->queue[] = array('crop', func_get_args());
        return $this;
    }

    public function addResize($width, $height) {
        $this->queue[] = array('resize', func_get_args());
        return $this;
    }

    public function addThumb($width, $height) {
        $this->queue[] = array('thumb', func_get_args());
        return $this;
    }

    protected function escapeNumber($number) {
        return (is_numeric($number)) ? intval($number) : null;
    }

    protected function processQueue() {
        foreach($this->queue as $task) {
            $action     = $task[0];
            $arguments  = array_map(array($this, 'escapeNumber'), $task[1]);

            call_user_func_array(array($this, $action), $arguments);
        }
    }

    protected function dimensions($width, $height) {
        if (!is_numeric($height)) {
            $height = round(($this->size[1] / $this->size[0]) * $width);
        } elseif (!is_numeric($width)) {
            $width = round(($this->size[0] / $this->size[1]) * $height);
        }

        return array($width, $height);
    }

    public function render($input, $output = null) {
        $this->input        = $input;
        $this->output       = ($output == null) ? $input : $output;
        $this->size         = $this->getImageSize();
        $this->inputFormat  = $this->obtainFormat($this->input);

        if ($this->outputFormat == null) $this->outputFormat = $this->obtainFormat($this->output);
    }

    protected function obtainFormat($fileName) {
        $parts = explode('.', $fileName);
        $extension = strtolower($parts[count($parts) - 1]);

        $extension = ($extension == 'jpeg') ? 'jpg' : $extension;

        return $extension;
    }

    protected function which($binary) {
        exec('which ' . $binary, $commandOutput, $returnStatus);
        if ($returnStatus === 0) {
            return $commandOutput[0];
        } else {
            return null;
        }
    }

    protected function getQuality() {
        if ($this->outputFormat == 'jpg') {
            if (
                $this->quality != null
                && $this->quality >= 0
                && $this->quality <= 100
            ) {
                $quality = $this->quality;
            } else {
                $quality = 90;
            }
        } else if ($this->outputFormat == 'png') {
            if (
                $this->quality != null
                && $this->quality >= 0
                && $this->quality <= 95
                && $this->quality % 10 <= 5
            ) {
                $quality = sprintf("%02d", $this->quality);
            } else {
                $quality = 95;
            }
        }

        return (string) $quality;
    }

    protected function bypassTest() {
        return (
            count($this->queue)         == 1
            && $this->queue[0][0]       == 'resize'
            && $this->queue[0][1][0]    == $this->size[0]
            && $this->queue[0][1][1]    == $this->size[1]
            && $this->outputFormat      == $this->inputFormat
        );
    }

    protected function bypass() {
        copy($this->input, $this->output);
    }
}
