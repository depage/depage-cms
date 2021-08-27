<?php

namespace Depage\Fs;

class FsSsh extends Fs
{
    // {{{ variables
    protected $session;
    protected $connection;
    protected $privateKeyFile;
    protected $publicKeyFile;
    protected $privateKey;
    protected $publicKey;
    protected $fingerprint;
    protected $tmp;
    // }}}
    // {{{ constructor
    public function __construct($params = array())
    {
        parent::__construct($params);
        $this->privateKeyFile   = (isset($params['privateKeyFile']))    ? $params['privateKeyFile'] : null;
        $this->publicKeyFile    = (isset($params['publicKeyFile']))     ? $params['publicKeyFile']  : null;
        $this->privateKey       = (isset($params['privateKey']))        ? $params['privateKey']     : null;
        $this->publicKey        = (isset($params['publicKey']))         ? $params['publicKey']      : null;
        $this->tmp              = (isset($params['tmp']))               ? $params['tmp']            : null;
        $this->fingerprint      = (isset($params['fingerprint']))       ? $params['fingerprint']    : null;
    }
    // }}}
    // {{{ destructor
    public function __destruct()
    {
        $this->disconnect();
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
        $port = $this->url['port'] ?? 22;
        $options = [
            'client_to_server' => [
                'comp' => 'zlib,none',
            ],
            'server_to_client' => [
                'comp' => 'zlib,none',
            ],
        ];
        if (!$this->connection) {
            $this->connection = \ssh2_connect($this->url['host'], $port);
        }
        $fingerprint = \ssh2_fingerprint($this->connection);

        return $this->connection;
    }
    // }}}
    // {{{ getSession
    protected function getSession()
    {
        if (!$this->session) {
            $connection = $this->getConnection($fingerprint);

            if (
                !is_null($this->fingerprint) &&
                strcasecmp($this->fingerprint, $fingerprint) !== 0
            ) {
                throw new Exceptions\FsException('SSH RSA Fingerprints don\'t match.');
            }

            if (
                $this->privateKeyFile
                || $this->publicKeyFile
                || $this->privateKey
                || $this->publicKey
                || $this->tmp
            ) {
                $this->authenticateByKey($connection);
            } else {
                $this->authenticateByPassword($connection);
            }

            $this->session = \ssh2_sftp($connection);
        }

        return $this->session;
    }
    // }}}
    // {{{ authenticateByPassword
    protected function authenticateByPassword($connection)
    {
        return \ssh2_auth_password(
            $connection,
            $this->url['user'],
            $this->url['pass']
        );
    }
    // }}}
    // {{{ authenticateByKey
    protected function authenticateByKey($connection)
    {
        if (!$this->isValidKeyCombination()) {
            throw new Exceptions\FsException('Invalid SSH key combination.');
        }

        if ($this->privateKeyFile) {
            $private = new PrivateSshKey($this->privateKeyFile);
        } elseif ($this->privateKey) {
            $private = new PrivateSshKey($this->privateKey, $this->tmp);
        }

        if ($this->publicKeyFile) {
            $public = new PublicSshKey($this->publicKeyFile);
        } elseif ($this->publicKey) {
            $public = new PublicSshKey($this->publicKey, $this->tmp);
        } else {
            $public = $private->extractPublicKey($this->tmp);
        }

        $authenticated = \ssh2_auth_pubkey_file(
            $connection,
            $this->url['user'],
            $public,
            $private,
            $this->url['pass']
        );

        $private->clean();
        $public->clean();

        return $authenticated;
    }
    // }}}
    // {{{ isValidKeyCombination
    protected function isValidKeyCombination()
    {
        return ($this->privateKeyFile && $this->publicKeyFile)
            || ($this->tmp && ($this->privateKeyFile || $this->privateKey));
    }
    // }}}
    // {{{ disconnect
    protected function disconnect()
    {
        $this->connection = null;
        $this->session = null;
    }
    // }}}

    // {{{ lateConnect
    protected function lateConnect()
    {
        parent::lateConnect();
        $this->getSession();
    }
    // }}}

    // {{{ buildUrl
    protected function buildUrl($parsed, $showPass = true)
    {
        $url = $parsed['scheme'] . '://';
        $url .= $this->getSession();

        $path = isset($parsed['path']) ? $parsed['path'] : '/';
        // workaround for https://bugs.php.net/bug.php?id=64169
        if ($path === '/') {
            $path = '/.';
        }

        $url .= $path;

        return $url;
    }
    // }}}

    // {{{ rename
    protected function rename($source, $target)
    {
        $result = true;

        // workaround, rename doesn't overwrite files via ssh
        if (is_file($target) && is_file($source)) {
            $this->rm($target);
            $result = !is_file($target);
        }

        if ($result) {
            //parent::rename($source, $target);
            \ssh2_sftp_rename($this->session, $source, $target);
            $result = is_file($target);
        }

        return $result;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
