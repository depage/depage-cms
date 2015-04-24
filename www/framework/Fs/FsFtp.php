<?php

namespace Depage\Fs;

class FsFtp extends Fs
{
    // {{{ variables
    protected $streamContextOptions = array('ftp' => array('overwrite' => true));
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
