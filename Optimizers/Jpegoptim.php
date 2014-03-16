<?php

namespace Depage\Graphics\Optimizers;

class Jpegoptim extends Optimizer
{
    // {{{ constructor()
    public function __construct($options = array())
    {
        parent::__construct($options);

        if (isset($this->options['jpegoptim'])) {
            $this->executable = $this->options['jpegoptim'];
        }

        if (is_null($this->executable)) {
            $this->executable = \Depage\Graphics\Graphics::which("jpegoptim");
        }
    }
    // }}}

    // {{{ optimize()
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
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
