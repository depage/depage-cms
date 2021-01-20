<?php

namespace Depage\Graphics\Optimizers;

class Pngcrush extends Optimizer
{
    // {{{ constructor
    public function __construct($options = array())
    {
        parent::__construct($options);

        if (isset($this->options['pngcrush'])) {
            $this->executable = $this->options['pngcrush'];
        }

        if (is_null($this->executable)) {
            $this->executable = \Depage\Graphics\Graphics::which("pngcrush");
        }
    }
    // }}}

    // {{{ optimize()
    public function optimize($filename)
    {
        if (!$this->executable) {
            return false;
        }

        $tmpfile = tempnam(sys_get_temp_dir(), 'pngcrush');

        $this->command = "{$this->executable} ";

        // remove all metadata
        $this->command .= "-rem gAMA ";
        $this->command .= "-rem cHRM ";
        $this->command .= "-rem iCCP ";
        $this->command .= "-rem sRGB ";

        // blacken transparent areas
        $this->command .= "-blacken ";

        // add file arguments
        $this->command .= " " . escapeshellarg($filename);
        $this->command .= " " . escapeshellarg($tmpfile);

        $success = $this->execCommand();

        if ($success && filesize($filename) > filesize($tmpfile)) {
            unlink($filename);
            rename($tmpfile, $filename);
        } else {
            unlink($tmpfile);
        }

        return $success;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
