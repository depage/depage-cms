<?php

namespace depage\graphics;

class graphics_graphicsmagick extends graphics_imagemagick {
    public function render($input, $output = null) {
        $this->input    = $input;
        $this->output   = ($output == null) ? $input : $output;

        $this->outputFormat = $this->obtainFormat($this->output);

        $this->command = $this->executable . " convert {$this->input}";

        $this->processQueue();

        $this->command .= " {$this->output}.miff";

        exec($this->command . ' 2>&1', $commandOutput, $returnStatus);
        if ($returnStatus != 0) {
            throw new graphicsException(implode("\n", $commandOutput));
        }

        $this->command = $this->executable . " convert";

        $this->background();

        $this->command .= " -background none {$this->output}.miff -flatten {$this->output}";

        exec($this->command . ' 2>&1', $commandOutput, $returnStatus);
        if ($returnStatus != 0) {
            throw new graphicsException(implode("\n", $commandOutput));
        }

        unlink("{$this->output}.miff");
    }
}
