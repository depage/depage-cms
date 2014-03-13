<?php

namespace Depage\Graphics\Optimizers;

class Optipng extends Optimizer
{
    public function __construct()
    {
        $this->executable = \Depage\Graphics\Graphics::which("optipng");
    }

    public function optimize($filename)
    {
        if (!$this->executable) {
            return false;
        }

        $this->command = "{$this->executable} ";
        
        // 2 is the default (8 trials) -> may change between versions
        //$this->command .= "-o 2 ";
        
        $this->command .= "-force ";
        $this->command .= "-strip all ";
        $this->command .= "-preserve ";

        $this->command .= " " . escapeshellarg($filename);

        return $this->execCommand();
    }
}
