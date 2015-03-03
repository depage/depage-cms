<?php

namespace Depage\Fs;

class FsFtp extends Fs
{
    // {{{ copy
    protected function copy($source, $target, $context = null)
    {
        // overwriting files is disabled by default in ftp stream wrapper
        if ($context === null) {
            $options = array('ftp' => array('overwrite' => true));
            $context = stream_context_create($options);
        }

        return parent::copy($source, $target, $context);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
