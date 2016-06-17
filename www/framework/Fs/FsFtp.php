<?php

namespace Depage\Fs;

class FsFtp extends Fs
{
    // {{{ variables
    protected $streamContextOptions = array('ftp' => array('overwrite' => true));
    // }}}
    // {{{ constructor
    public function __construct($params = array())
    {
        parent::__construct($params);

        if (isset($params['user']))     $this->url['user']      = $params['user'];
        if (isset($params['pass']))     $this->url['pass']      = $params['pass'];
        if (isset($params['port']))     $this->url['port']      = $params['port'];
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
