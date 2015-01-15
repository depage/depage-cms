<?php

namespace Depage\Fs;

class FsSsh extends Fs
{
    // {{{ constructor
    public function __construct($params = array())
    {
        parent::__construct($params);
        $this->sshConnect();
    }
    // }}}

    // {{{ sshConnect
    protected function sshConnect()
    {
        $this->session = ssh2_connect($this->url['host'], $this->url['port']);

        if (isset($this->key)) {
            ssh2_auth_pubkey_file(
                $this->session,
                $this->url['user'],
                $this->key . '.pub',
                $this->key,
                $this->url['pass']
            );
        } else {
            ssh2_auth_password(
                $this->session,
                $this->url['user'],
                $this->url['pass']
            );
        }

        $this->sftpSession = ssh2_sftp($this->session);
    }
    // }}}

    // {{{ buildUrl
    protected function buildUrl($parsed)
    {
        $path = $parsed['scheme'] . '://';
        $path .= $this->sftpSession;
        $path .= isset($parsed['path']) ? $parsed['path'] : '/';

        return $path;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
