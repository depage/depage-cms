<?php

namespace Depage\Auth;

class Password
{
    /**
     * @brief realm
     **/
    protected $realm = null;

    public function __construct($realm, $digestCompat = false)
    {
        if (!function_exists("password_hash")) {
            require_once(__DIR__ . "/Compat/password.php");
        }

        $this->realm = $realm;
        $this->digestCompat = $digestCompat;
    }

    // {{{ hash()
    /**
     * @brief hash
     *
     * @param mixed $username
     * @param mixed $password
     * @return string hash
     **/
    public function hash($username, $password)
    {
        if ($this->digestCompat) {
            return md5($username . ':' . $this->realm . ':' . $password);
        } else {
            return password_hash($password, \PASSWORD_DEFAULT);
        }
    }
    // }}}

    // {{{ needsRehash()
    /**
     * @brief needsRehash
     *
     * @param mixed $hash
     * @return bool
     **/
    public function needsRehash($hash)
    {
        $info = $this->getInfo($hash);

        if ($info['algoName'] == "dp-digest" && $this->digestCompat) {
            return false;
        } else {
            return password_needs_rehash($hash, \PASSWORD_DEFAULT);
        }
    }
    // }}}

    // {{{ verify()
    /**
     * @brief verify
     *
     * @param mixed $username
     * @param mixed $password
     * @param mixed $hash
     * @return bool
     **/
    public function verify($username, $password, $hash)
    {
        $info = $this->getInfo($hash);

        if ($info['algoName'] == "dp-digest") {
            return md5($username . ':' . $this->realm . ':' . $password) == $hash;
        } else {
            return password_verify($password, $hash);
        }

    }
    // }}}

    public function getInfo($hash)
    {
        $info = password_get_info($hash);

        if ($info['algo'] === 0) {
            if (strlen($hash) == 32) {
                // assume digest md5 hash based on hash length
                $info['algoName'] = "dp-digest";
            }
        }

        return $info;
    }

    public function generate($options = array())
    {
        $options = array_merge(array(
            'length' => 8,
        ), $options);

        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $count = mb_strlen($chars);

        for ($i = 0, $result = ''; $i < $options['length']; $i++) {
            $index = mt_rand(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }

        return $result;
    }
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
