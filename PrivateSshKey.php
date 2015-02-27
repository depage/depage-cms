<?php

namespace Depage\Fs;

class PrivateSshKey extends PublicSshKey
{
    // {{{ details
    protected function details($keyString)
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
    public function extractPublicKey()
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
        $keyInfo = openssl_pkey_get_details($privateKey);
        $buffer = pack('N', 7) . 'ssh-rsa' .
            sshEncodeBuffer($keyInfo['rsa']['e']) .
            sshEncodeBuffer($keyInfo['rsa']['n']);

        $publicKeyString = 'ssh-rsa ' . base64_encode($buffer) . "\n";

        return new PublicSshKey($publicKeyString, $this->tmpDir);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
