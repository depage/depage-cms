<?php

namespace Depage\Fs;

class PrivateSshKey extends PublicSshKey
{
    // {{{ parse
    protected function parse($keyString)
    {
        $details = openssl_pkey_get_private($keyString);

        if ($details === false) {
            throw new Exceptions\FsException('Invalid SSH private key file format "' . $keyString . '" (PEM format required).');
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
        function sshEncodeBuffer($buffer)
        {
            $len = strlen($buffer);
            if (ord($buffer[0]) & 0x80) {
                $len++;
                $buffer = "\x00" . $buffer;
            }

            return pack('Na*', $len, $buffer);
        }

        $details = openssl_pkey_get_details($this->key);
        $buffer = pack('N', 7) . 'ssh-rsa' .
            sshEncodeBuffer($details['rsa']['e']) .
            sshEncodeBuffer($details['rsa']['n']);
        $publicKeyString = 'ssh-rsa ' . base64_encode($buffer) . "\n";

        return new PublicSshKey($publicKeyString, $tmpDir);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
