<?php

namespace Depage\Graphics\Optimizers;

class Optipng extends Optimizer
{
    protected $version;

    public function __construct()
    {
        if (is_null($this->executable)) {
            $this->executable = \Depage\Graphics\Graphics::which("optipng");

            if ($this->executable) {
                exec($this->executable . ' --version 2>&1', $commandOutput, $returnStatus);

                preg_match("/OptiPNG version (\d).(\d).(\d)/", $commandOutput[0], $matches);
                array_shift($matches);

                $this->version = $matches;
            }
        }
    }

    public function optimize($filename)
    {
        if (!$this->executable) {
            return false;
        }

        $this->command = "{$this->executable} ";
        
        // 2 is the default (8 trials) -> may change between versions
        //$this->command .= "-o 2 ";
        
        if ($this->version[0] >= 0 && $this->version[1] >= 7) {
            // strip got added in version 0.7
            $this->command .= "-strip all ";
        }
        
        //$this->command .= "-force ";
        $this->command .= "-preserve ";

        $this->command .= " " . escapeshellarg($filename);

        return $this->execCommand();
    }
}
