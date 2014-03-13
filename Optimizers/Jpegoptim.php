<?php

namespace Depage\Graphics\Optimizers;

class Jpegoptim extends Optimizer
{
    public function __construct()
    {
        if (is_null($this->executable)) {
            $this->executable = \Depage\Graphics\Graphics::which("jpegoptim");
        }
    }

    public function optimize($filename)
    {
        if (!$this->executable) {
            return false;
        }

        $this->command = "{$this->executable} --strip-all";

        // jpegoptim unfortunately does not support progressive jpgs
        $this->command .= " " . escapeshellarg($filename);

        return $this->execCommand();
    }
}
