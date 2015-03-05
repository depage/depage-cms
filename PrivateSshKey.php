<?php

namespace Depage\Fs;

class PrivateSshKey extends PublicSshKey
{
    // {{{ parse
    protected function parse($keyString)
    {
        $details = openssl_pkey_get_private($keyString);

        if ($details === false) {
            throw new Exceptions\FsException('Invalid SSH private key format (PEM format required).');
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
            throw new Exceptions\FsException('Currently public key generation is only supported for RSA keys.');
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
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
