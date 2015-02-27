<?php

namespace Depage\Fs;

class FsSsh extends Fs
{
    // {{{ variables
    protected $session = null;
    protected $connection = null;
    protected $privateKey = null;
    protected $publicKey = null;
    protected $fingerprint = null;
    // }}}
    // {{{ constructor
    public function __construct($params = array())
    {
        parent::__construct($params);
        $this->privateKey = (isset($params['private'])) ? $params['private'] : false;
        $this->publicKey = (isset($params['public'])) ? $params['public'] : false;
        $this->fingerprint = (isset($params['fingerprint'])) ? $params['fingerprint'] : false;
    }
    // }}}
    // {{{ destructor
    public function __destruct()
    {
        $this->disconnect();
    }
    // }}}

    // {{{ lateConnect
    protected function lateConnect()
    {
        parent::lateConnect();
        $this->getSession();
    }
    // }}}
    // {{{ getFingerprint
    public function getFingerprint()
    {
        $this->getConnection($fingerprint);
        return $fingerprint;
    }
    // }}}
    // {{{ getConnection
    protected function getConnection(&$fingerprint = null)
    {
        if (!$this->connection) {
            if (isset($this->url['port'])) {
                $this->connection = ssh2_connect($this->url['host'], $this->url['port']);
            } else {
                $this->connection = ssh2_connect($this->url['host']);
            }
        }
        $fingerprint = ssh2_fingerprint($this->connection);

        return $this->connection;
    }
    // }}}
    // {{{ getSession
    protected function getSession()
    {
        if (!$this->session) {
            $connection = $this->getConnection($fingerprint);

            if (strcasecmp($this->fingerprint, $fingerprint) !== 0) {
                throw new Exceptions\FsException('SSH RSA Fingerprints don\'t match.');
            }

            if ($this->privateKey || $this->publicKey || $this->tmp) {
                $authenticated = $this->authenticateKey($connection);
            } else {
                $authenticated = $this->authenticatePassword($connection);
            }

            if ($authenticated) {
                $this->session = ssh2_sftp($connection);
            } else {
                throw new Exceptions\FsException('Could not authenticate session.');
            }
        }

        return $this->session;
    }
    // }}}
    // {{{ authenticatePassword
    protected function authenticatePassword($connection)
    {
        return ssh2_auth_password(
            $connection,
            $this->url['user'],
            $this->url['pass']
        );
    }
    // }}}
    // {{{ authenticateKey
    protected function authenticateKey($connection)
    {
        $private = $this->privateKey;
        $public = $this->publicKey;
        $temp = false;

        if (!is_readable($private)) {
            throw new Exceptions\FsException('Cannot read SSH private key file "' . $private . '".');
        }
        $privateKeyResource = openssl_pkey_get_private(file_get_contents($private));
        if ($privateKeyResource === false) {
            throw new Exceptions\FsException('Invalid SSH private key file format "' . $private . '" (PEM format required).');
        }
        if (is_dir($public) && is_writable($public)) {
            $public = tempnam($public, 'depage-fs');
            $temp = true;
            $publicKeyString = $this->extractPublicKey($privateKeyResource);
            file_put_contents($public, $publicKeyString);
        }
        if (
            (is_file($public) && !is_readable($public))
            || !file_exists($public)
        ) {
            throw new Exceptions\FsException('Cannot read SSH public key file "' . $public . '".');
        }

        $authenticated = ssh2_auth_pubkey_file(
            $connection,
            $this->url['user'],
            $public,
            $private,
            $this->url['pass']
        );
        if ($temp) {
            unlink($public);
        }

        return $authenticated;
    }
    // }}}
    // {{{ disconnect
    protected function disconnect()
    {
        $this->connection = null;
        $this->session = null;
    }
    // }}}
    // {{{ buildUrl
    protected function buildUrl($parsed)
    {
        $path = $parsed['scheme'] . '://';
        $path .= $this->getSession();
        $path .= isset($parsed['path']) ? $parsed['path'] : '/';

        return $path;
    }
    // }}}
    // {{{ extractPublicKey
    /**
     * from http://stackoverflow.com/questions/5524121/converting-an-openssl-generated-rsa-public-key-to-openssh-format-php
     */
    protected function extractPublicKey($privateKey)
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

        return 'ssh-rsa ' . base64_encode($buffer) . "\n";
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
