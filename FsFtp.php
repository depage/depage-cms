<?php

namespace Depage\Fs;

use Depage\Fs\Exceptions\FsException;

class FsFtp extends Fs
{
    // {{{ constructor
    public function __construct($params = array())
    {
        parent::__construct($params);

        $scheme = $this->url['scheme'];
        $wrappers = stream_get_wrappers();

        if (array_search($scheme, $wrappers) !== false) {
            stream_wrapper_unregister($scheme);
        }

        //var_dump(class_exists('\Depage\Fs\Streams\FtpCurl'));
        //var_dump(in_array('FtpCurl', get_declared_classes()));

        if (!stream_wrapper_register($scheme, 'FtpCurl')) {
            throw new FsException("Unable to register $scheme stream wrapper.");
        }
    }
    // }}}

    // {{{ variables
    protected $streamContextOptions = array('ftp' => array('overwrite' => true));
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
