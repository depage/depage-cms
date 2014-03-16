<?php

namespace Depage\Graphics\Optimizers;

class Optimizer
{
    protected $executable = null;
    protected $command = '';
    protected $options = array();

    public function __construct($options = array())
    {
        $this->options = $options;
    }

    protected function execCommand()
    {
        exec($this->command . ' 2>&1', $commandOutput, $returnStatus);
        if ($returnStatus != 0) {
            throw new \Depage\Graphics\Exceptions\Exception(implode("\n", $commandOutput));
        }

        return true;
    }

    public function optimize($filename)
    {
        $parts = explode('.', $filename);
        $extension = strtolower(end($parts));

        if ($extension == "jpg" || $extension == "jpeg") {
            if (isset($this->options['jpegoptim'])) {
                $optimizer = new Jpegoptim($this->options);
            } else {
                $optimizer = new Jpegtran($this->options);
            }
        } elseif ($extension == "png") {
            if (isset($this->options['pngcrush'])) {
                $optimizer = new Pngcrush($this->options);
            } else {
                $optimizer = new Optipng($this->options);
            }
        }
        return $optimizer->optimize($filename);
    }
}
