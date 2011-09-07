<?php

namespace depage\graphics;

class graphics_graphicsmagick extends graphics_imagemagick {
    private $size;

    public function __construct($options) {
        graphics::__construct($options);

        $this->executable = $options['graphicsmagickpath'];
    }

    public function render($input, $output = null) {
        $this->input    = $input;
        $this->output   = ($output == null) ? $input : $output;

        $this->outputFormat = $this->obtainFormat($this->output);

        $this->command = $this->executable . " convert {$this->input}";

        $this->processQueue();

        $this->background();

        $this->command .= " {$this->output}";

        exec($this->command . ' 2>&1', $commandOutput, $returnStatus);
        if ($returnStatus != 0) {
            throw new graphicsException(implode("\n", $commandOutput));
        }
    }
}
