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


class auth_http_cookie extends auth {
    /* {{{ constructor */
    public function __construct($pdo, $realm, $domain) {
        parent::__construct($pdo, $realm, $domain);

        // increase lifetime of cookies in order to allow detection of timedout users
        $url = parse_url($this->domain);
        session_set_cookie_params($this->session_lifetime + 120, $url['path'], "", false, true);
    }
    /* }}} */
    
    // {{{ hash_user_pass() 
    public function hash_user_pass($user, $pass) {
	return md5($user . ':' . $this->realm . ':' . $pass);
    }
    // }}}

    /* {{{ enforce */
    public function enforce() {
        // only enforce authentication if not authenticated before
        if ($this->user === null) {
            $this->user = $this->auth_cookie();

            if (!$this->user) {
                $url = DEPAGE_BASE . $this->loginUrl;
                if ($url != "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]) {
                    $redirect_to = urlencode($_SERVER['REQUEST_URI']);

                    header("Location: $url?redirect_to=$redirect_to");
                    die( "Tried to redirect you to " . $url);
                }
            }
        }

        return $this->user;
    }
    /* }}} */
    /* {{{  enforce_lazy*/
    /**
     * @return   function returns the authenticated user or false if not logged in
     */
    public function enforce_lazy() {
        if ($this->user === null) {
            if ($this->has_session() && $this->is_valid_sid($_COOKIE[session_name()])) {
                $this->user = $this->auth_cookie();
            } else {
                $this->user = false;
            }
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

        if ($user && $user->passwordhash === $hash) {
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

                return $user;
            } else {
                $this->destroy_session();
                $this->log->log("http_auth_cookie: invalid session ID");
            }
        } else {
            $this->log->log("http_auth_cookie: no session");
        }

        $this->send_auth_header();
        //throw new Exception("you are not allowed to do this!");
        return false;
    }
    /* }}} */
    
    // {{{ start_session()
    protected function start_session() {
        session_id($this->get_sid());
        session_start();
    } 
    // }}}
    // {{{ has_session()
    protected function has_session() {
        return isset($_COOKIE[session_name()]) && $_COOKIE[session_name()] != "";
    } 
    // }}}
    // {{{ destroy_session()
    protected function destroy_session() {
        $this->start_session();

        setcookie(session_name(), "", time() - 3600);
        session_destroy();
        unset($_COOKIE[session_name()]);
    } 
    // }}}
    
    // {{{ send_auth_header()
    protected function send_auth_header($valid_response = false) {
        // @todo look for a way to suppress password saving dialogs when password is wrong
        //header("HTTP/1.1 403 Unauthorized");
    } 
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
