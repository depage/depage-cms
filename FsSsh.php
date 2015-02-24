<?php

namespace Depage\Fs;

class FsSsh extends Fs
{
    // {{{ variables
    protected $session = null;
    protected $connection = null;
    // }}}
    // {{{ constructor
    public function __construct($params = array())
    {
        parent::__construct($params);
        $this->key = (isset($params['key'])) ? $params['key'] : false;
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
            $this->connection = ssh2_connect($this->url['host'], $this->url['port']);
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

            if ($this->key) {
                $private = $this->key;
                $public = $this->key . '.pub';

                if (!is_readable($private)) {
                    throw new Exceptions\FsException('Cannot read SSH private key file "' . $private . '".');
                }

                if (!is_readable($public)) {
                    throw new Exceptions\FsException('Cannot read SSH public key file "' . $public . '".');
                }

                if (openssl_pkey_get_private('file://' . $private) === false) {
                    throw new Exceptions\FsException('Invalid SSH private key file format "' . $private . '" (PEM format required).');
                }

                ssh2_auth_pubkey_file(
                    $connection,
                    $this->url['user'],
                    $public,
                    $private,
                    $this->url['pass']
                );
            } else {
                ssh2_auth_password(
                    $connection,
                    $this->url['user'],
                    $this->url['pass']
                );
            }

            $this->session = ssh2_sftp($connection);
        }

        return $this->session;
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
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
