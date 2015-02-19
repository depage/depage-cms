<?php

namespace Depage\Fs;

class FsSsh extends Fs
{
    // {{{ variables
        protected $sshSession = null;
    // }}}
    // {{{ constructor
    public function __construct($params = array())
    {
        parent::__construct($params);
        $this->key = (isset($params['key'])) ? $params['key'] : false;
        $this->fingerprint = (isset($params['fingerprint'])) ? $params['fingerprint'] : false;
    }
    // }}}

    // {{{ lateConnect
    protected function lateConnect()
    {
        parent::lateConnect();
        $this->sshConnect();
    }
    // }}}
    // {{{ sshConnect
    protected function sshConnect()
    {
        if (!$this->sshSession) {
            $session = ssh2_connect($this->url['host'], $this->url['port']);

            if (strcasecmp($this->fingerprint, ssh2_fingerprint($session))) {
                throw new Exceptions\FsException('SSH RSA Fingerprints don\'t match.');
            }

            if ($this->key) {
                ssh2_auth_pubkey_file(
                    $session,
                    $this->url['user'],
                    $this->key . '.pub',
                    $this->key,
                    $this->url['pass']
                );
            } else {
                ssh2_auth_password(
                    $session,
                    $this->url['user'],
                    $this->url['pass']
                );
            }

            $this->sshSession = ssh2_sftp($session);
        }

        return $this->sshSession;
    }
    // }}}
    // {{{ buildUrl
    protected function buildUrl($parsed)
    {
        $path = $parsed['scheme'] . '://';
        $path .= $this->sshSession;
        $path .= isset($parsed['path']) ? $parsed['path'] : '/';

        return $path;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
