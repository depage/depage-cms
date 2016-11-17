<?php

namespace Depage\Fs;

use Depage\Fs\Exceptions\FsException;

class PrivateSshKey extends PublicSshKey
{
    // {{{ parse
    protected function parse($keyString)
    {
        $details = $this->openssl_pkey_get_private($keyString);

        if ($details === false) {
            throw new FsException('Invalid SSH private key format (PEM format required).');
        }

        return $details;
    }
    // }}}
    // {{{ extractPublicKey
    /**
     * from http://stackoverflow.com/questions/5524121/converting-an-openssl-generated-rsa-public-key-to-openssh-format-php
     */
    public function extractPublicKey($tmpDir)
    {
        $details = openssl_pkey_get_details($this->key);

        if (isset($details['rsa'])) {
            $buffer = pack('N', 7) . 'ssh-rsa' .
                $this->sshEncodeBuffer($details['rsa']['e']) .
                $this->sshEncodeBuffer($details['rsa']['n']);
        } else {
            throw new FsException('Currently public key generation is only supported for RSA keys.');
        }
        $publicKeyString = 'ssh-rsa ' . base64_encode($buffer) . "\n";

        return new PublicSshKey($publicKeyString, $tmpDir);
    }
    // }}}
    // {{{ sshEncodeBuffer
    /**
     * from http://stackoverflow.com/questions/5524121/converting-an-openssl-generated-rsa-public-key-to-openssh-format-php
     */
    protected function sshEncodeBuffer($buffer)
    {
        $len = strlen($buffer);
        if (ord($buffer[0]) & 0x80) {
            $len++;
            $buffer = "\x00" . $buffer;
        }

        return pack('Na*', $len, $buffer);
    }
    // }}}

    // {{{ openssl_pkey_get_private
    /**
     * hook, allows overriding openssl_pkey_get_private
     */
    protected function openssl_pkey_get_private($key, $passphrase = '')
    {
        return \openssl_pkey_get_private($key, $passphrase);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
