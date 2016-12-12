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

        $streamOptions = [];
        if (isset($params['caCert'])) {
            $streamOptions['caCert'] = $params['caCert'];
        }
        if (isset($params['passive'])) {
            $streamOptions['passive'] = $params['passive'];
        }

        FtpCurl::registerStream($this->url['scheme'], $streamOptions);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
