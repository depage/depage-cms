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
namespace depage\Auth\Methods;

use depage\Auth\User;

class HttpBasic extends HttpCookie
{
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
        if ($this->has_session()) {
            $this->set_sid($_COOKIE[session_name()]);
        } else {
            $this->set_sid("");
        }

        if (isset($_SERVER['PHP_AUTH_USER'])) { 
            // get new user object
            $user = User::get_by_username($this->pdo, $_SERVER['PHP_AUTH_USER']);

            if ($user) {
                // generate the valid response
                $HA1 = $user->passwordhash;

                if ($HA1 == $this->hash_user_pass($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
                    if (($uid = $this->is_valid_sid($_COOKIE[session_name()])) !== false) {
                        if ($uid == "") {
                            $this->log->log("'{$user->name}' has logged in from '{$_SERVER["REMOTE_ADDR"]}'", "auth");
                            $sid = $this->register_session($user->id, $_COOKIE[session_name()]);
                        }
                        $this->start_session();

                        return $user;
                    } elseif ($this->has_session()) {
                        $this->destroy_session();
                    }
                }
            }
        }

        $this->send_auth_header();
        $this->start_session();

        throw new \Exception("you are not allowed to to this!");
    } 
    // }}}
    // {{{ send_auth_header()
    protected function send_auth_header($valid_response = false) {
        $sid = $this->get_sid();
        $realm = $this->realm;
        $domain = $this->domain;

        header("WWW-Authenticate: Basic realm=\"$realm\", domain=\"{$this->domain}\"");
        header("HTTP/1.1 401 Unauthorized");
    } 
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
