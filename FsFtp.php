<?php

namespace Depage\Fs;

use Depage\Fs\Exceptions\FsException;
use Depage\Fs\Streams\FtpCurl;

class FsFtp extends Fs
{
    // {{{ constructor
    public function __construct($params = array())
    {
        parent::__construct($params);

        FtpCurl::registerStream($this->url['scheme']);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
