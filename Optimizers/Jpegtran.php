<?php

namespace Depage\Graphics\Optimizers;

class Jpegtran extends Optimizer
{
    public function __construct($options = array())
    {
        parent::__construct($options);

        if (isset($this->options['jpegtran'])) {
            $this->executable = $this->options['jpegtran'];
        }

        if (is_null($this->executable)) {
            $this->executable = \Depage\Graphics\Graphics::which("jpegtran");
        }
    }

    public function optimize($filename)
    {
        if (!$this->executable) {
            return false;
        }

        $this->command = "{$this->executable} -optimize ";

        // @todo test for image size and make progressive only upward a specific size of 10k?
        $this->command .= "-progressive ";
        $this->command .= "-copy none ";
        $this->command .= "-outfile " . escapeshellarg($filename);
        $this->command .= " " . escapeshellarg($filename);

        return $this->execCommand();
    }
}
