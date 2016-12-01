<?php

namespace Depage\Fs;

use Depage\Fs\Exceptions\FsException;
use Depage\Fs\Streams\FtpCurl;

class FsFtp extends Fs
{
    // {{{ variables
    protected $caCert;
    // }}}
    // {{{ constructor
    public function __construct($params = array())
    {
        parent::__construct($params);

        $this->caCert = (isset($params['caCert'])) ? $params['caCert'] : null;

        FtpCurl::registerStream($this->url['scheme'], ['caCert' => $this->caCert]);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
