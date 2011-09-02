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
    }

    public function addCrop($width, $height, $x = 0, $y = 0) {
        $this->queue[] = array('crop', func_get_args());
    }

    public function addResize($width, $height) {
        $this->queue[] = array('resize', func_get_args());
    }

    public function addThumb($width, $height) {
        $this->queue[] = array('thumb', func_get_args());
    }

    protected function load() {}

    protected function save() {}

    public function render($input, $output = null) {
        $this->input    = $input;
        $this->output   = ($output == null) ? $input : $output;

        $this->load();

        foreach($this->queue as $task) {
            call_user_func_array(array($this, $task[0]), $task[1]);
        }

        $this->save();
    }
}
