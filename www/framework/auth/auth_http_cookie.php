<?php
/**
 * @file    auth_http_cookie.php
 *
 * User and Session Handling Library
 *
 * This file contains classes for session
 * handling. 
 *
 *
 * copyright (c) 2010 Lion Vollnhals
 * copyright (c) 2010 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Lion Vollnhals
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */


class auth_http_cookie extends auth_http_basic {
    const INVALID_SID = -1;
    const NO_SESSION = -2;

    /* {{{ constructor */
    public function __construct($pdo, $realm, $domain) {
        parent::__construct($pdo, $realm, $domain);
        
        // increase lifetime of cookies in order to allow detection of timedout users
        $url = parse_url($this->domain);
        session_set_cookie_params($this->session_lifetime + 120, $url['path'], "", false, true);
    }
    /* }}} */
    /* {{{ enforce */
    public function enforce() {
        $user = $this->enforce_lazy();
        if (!$user)
                throw new Exception("You are not allowed here!");
        
        return $user;
    }
    /* }}} */
    /* {{{  enforce_lazy*/
    /**
     * @return   function returns the authenticated user or INVALID_SID or NO_SESSION
     */
    public function enforce_lazy() {
        if ($this->user === null) {
            $this->user = $this->auth_cookie();
        }

        return $this->user;
    }
    /* }}} */
    /* {{{ enforce_logout */
    public function enforce_logout() {
        if ($this->has_session()) {
            $this->logout($_COOKIE[session_name()]);
            $this->destroy_session();
        }
    }
    /* }}} */
    /* {{{ login() */
    public function login($username, $password) {
        $user = auth_user::get_by_username($this->pdo, $username);
        $hash = $this->hash_user_pass($username, $password);
        if ($user && $user->passwordhash == $hash) {
            // destroy session if logging in directly after registering user
            $this->destroy_session();
            $this->register_session($user->id);
            $this->start_session();
            return true;
        }

        //throw new Exception("Login failed! Wrong username password combination.");
        return false;
    }
    /* }}} */
    /* {{{ auth_cookie() */
    protected function auth_cookie() {
        if ($this->has_session()) {
            if ($this->is_valid_sid($_COOKIE[session_name()]) !== false) {
                $this->set_sid($_COOKIE[session_name()]);
                $this->start_session();

                $user = auth_user::get_by_sid($this->pdo, $this->get_sid());
                $this->log->log("http_auth_cookie: authenticated {$user->name}");
                return $user;
            } else {
                $this->destroy_session();
                $this->log->log("http_auth_cookie: invalid session ID");
                return auth_http_cookie::INVALID_SID;
            }
        }

        //throw new Exception("you are not allowed to do this!");
        $this->log->log("http_auth_cookie: no session");
        return auth_http_cookie::NO_SESSION;
    }
    /* }}} */
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
