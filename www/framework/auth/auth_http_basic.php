<?php 
/**
 * @file    auth.php
 *
 * User and Session Handling Library
 *
 * This file contains classes for session
 * handling. 
 *
 *
 * copyright (c) 2002-2010 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
class auth_http_basic extends auth {
    // {{{ enforce()
    /**
     * enforces authentication 
     *
     * @public
     *
     * @param       string  $method     method to use for authentication. Can be http
     * @return      void
     */
    public function enforce() {
        // only enforce authentication of not authenticated before
        if ($this->user === null) {
            if (isset($_ENV["HTTP_AUTHORIZATION"])) {
                list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':' , base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
            }
            $this->user = $this->auth_basic();
        }

        return $this->user;
    }
    // }}}
    
    // {{{ auth_basic()
    public function auth_basic() {
        if (isset($_SERVER['PHP_AUTH_USER'])) { 
            // get new user object
            $user = auth_user::get_by_username($this->pdo, $_SERVER['PHP_AUTH_USER']);

            if ($user) {
                // generate the valid response
                $HA1 = $user->passwordhash;

                if ($HA1 == md5($_SERVER['PHP_AUTH_USER'] . ':' . $this->realm . ':' . $_SERVER['PHP_AUTH_PW'])) {
                    if (($uid = $this->is_valid_sid($_COOKIE[session_name()])) !== false) {
                        if ($uid == "") {
                            $this->log->log("'{$user->name}' has logged in from '{$_SERVER["REMOTE_ADDR"]}'", "auth");
                            $sid = $this->register_session($user->id, $_COOKIE[session_name()]);
                        } else {
                            $sid = $this->set_sid($_COOKIE[session_name()]);
                        }
                        session_id($sid);
                        session_start();

                        return $user;
                    } elseif (isset($_COOKIE[session_name()]) && $_COOKIE[session_name()] != "") {
                        setcookie(session_name(), "", time() - 3600);
                        unset($_COOKIE[session_name()]);
                    }
                }
            }
        }
        $sid = $this->get_sid();
        $realm = $this->realm;
        $domain = $this->domain;

        if (isset($_COOKIE[session_name()]) && $_COOKIE[session_name()] != "") {

        } else {
            session_id($sid);
            session_start();
        }

        header("WWW-Authenticate: Basic realm=\"$realm\", domain=\"{$this->domain}\"");
        header("HTTP/1.1 401 Unauthorized");

        throw new Exception("you are not allowed to to this!");
    } 
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
