<?php

namespace Depage\Fs\Tests\TestClasses;

use Depage\Fs\PrivateSshKey;

class PrivateSshKeyTestClass extends PrivateSshKey
{
    // {{{ openssl_pkey_get_private
    protected function openssl_pkey_get_private($key, $passphrase = '')
    {
        if ($key === 'writeFail!') {
            return $key;
        } else {
            return parent::openssl_pkey_get_private($key);
        }
    }
    // }}}
    // {{{ openssl_pkey_get_private
    public function file_put_contents($filename, $data, $flags = 0, $context = null)
    {
        if ($data === 'writeFail!') {
            return false;
        } else {
            return parent::file_put_contents($filename, $data, $flags, $context);
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
