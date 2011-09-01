<?php

namespace depage\graphics;

class graphics {
    protected $input;
    protected $output;
    protected $queue = array();

    public static function factory($options = array()) {
        if (!isset($options['extension'])) {
            $options['extension'] = 'gd';
        }

        if ($options['extension'] == 'imagemagick') {
            return new \depage\graphics\graphics_imagemagick($options);
        } else {
            return new \depage\graphics\graphics_gd($options);
        }
    }

    public function __construct($options) {
        $this->input    = (isset($options['input']))    ? $options['input']     : null;
        $this->output   = (isset($options['output']))   ? $options['output']    : $this->input;
    }

    public function addCrop($width, $height, $x, $y) {
    }

    public function addResize($width, $height) {
        $this->queue[] = array(
            'action'    => 'resize',
            'options'   => array(
                'width'     => $width,
                'height'    => $height,
            ),
        );
    }

    public function addThumb($width, $height) {
    }

    public function render() {
        foreach($this->queue as $step) {
            call_user_func(array($this, $step['action']), $step['options']);
        }
    }
}
