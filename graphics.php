<?php

class graphics {
    protected $input;
    protected $output;
    protected $queue = array();

    public static function factory($options = array()) {
        $extension = (isset($options['extension'])) ? $options['extension'] : 'gd';

        if ( $extension == 'im' || $extension == 'imagemagick' ) {
            if (!isset($options['executable'])) {
                $executable = graphics::which('convert');
                if ($executable == null) {
                    trigger_error("Cannot find ImageMagick, falling back to GD", E_USER_ERROR);
                } else {
                    $options['executable'] = $executable;
                    return new graphics_imagemagick($options);
                }
            }
        } else if ( $extension == 'gm' || $extension == 'graphicsmagick' ) {
            if (!isset($options['executable'])) {
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

    public function __construct($options) {
        $this->background = (isset($options['background'])) ? $options['background'] : 'transparent';
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
            $height = round(($this->imageSize[1] / $this->imageSize[0]) * $width);
        } elseif (!is_numeric($width)) {
            $width = round(($this->imageSize[0] / $this->imageSize[1]) * $height);
        }

        return array($width, $height);
    }

    public function render($input, $output = null) {
        $this->input        = $input;
        $this->output       = ($output == null) ? $input : $output;
        $this->imageSize    = $this->getImageSize();
        $this->outputFormat = $this->obtainFormat($this->output);
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
}
