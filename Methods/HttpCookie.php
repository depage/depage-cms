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
 * copyright (c) 2010 Frank Hellenkamp [jonas@depage.net]
 * copyright (c) 2010 Lion Vollnhals
 *
 * @author    Lion Vollnhals
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Auth\Methods;

use Depage\Auth\Auth;
use Depage\Auth\User;
use Depage\Html\Html;

class HttpCookie extends Auth
{
    // {{{ variables
    protected $cookiePath = "";
    /**
     * @brief name of the session cookie
     **/
    public $cookieName = "depage-session-id";
    /**
     * @brief domain of the cookie
     *
     * false is the php default to use current domain automatically
     **/
    public $cookieDomain = false;
    /**
     * @brief set cookie only through secure connection
     **/
    public $cookieSecure = false;
    /**
     * @brief set http-only cookie -> no javascript access
     **/
    public $cookieHttponly = true;
    // }}}

    /* {{{ constructor */
    public function __construct($pdo, $realm, $domain, $digestCompat = false) {
        parent::__construct($pdo, $realm, $domain, $digestCompat);

        // increase lifetime of cookies in order to allow detection of timedout users
        $url = parse_url($this->domain);
        $this->cookiePath = !empty($url['path']) ? $url['path'] : '';

        $cookiePrefix = $this->realm . "-" . $url['host'];
        $cookiePrefix = preg_replace("/[^-_a-zA-Z0-9]/", "", $cookiePrefix);
        $cookiePrefix = trim($cookiePrefix, "-");
        if (!empty($cookiePrefix)) {
            $this->cookieName = "$cookiePrefix-sid";
        }
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") {
            $this->cookieSecure = true;
        }
        session_name($this->cookieName);
        session_set_cookie_params(
            $this->sessionLifetime,
            $this->cookiePath,
            $this->cookieDomain,
            $this->cookieSecure,
            $this->cookieHttponly
        );
    }
    /* }}} */

    /* {{{ enforce */
    public function enforce($testUserFunction = null) {
        // only enforce authentication if not authenticated before
        if (!$this->user) {
            $this->user = $this->authCookie();
        }

        // test user with custom user function
        if ($this->user && !is_null($testUserFunction)) {
            $this->user = $testUserFunction($this->user);
        }

        // redirect to login page
        if (!$this->user) {
            // remove trailing slashes when comparing urls, disregard query string
            $loginUrl = Html::link($this->loginUrl, "auto");

            // set protocol
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") {
                $protocol = "https://";
            } else {
                $protocol = "http://";
            }

            $requestUrl = strstr($protocol . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . '?', '?', true);
            if (rtrim($loginUrl, '/') != rtrim($requestUrl, '/')) {
                $redirectTo = urlencode($_SERVER['REQUEST_URI']);

                \Depage\Router\Router::redirect("$loginUrl?redirectTo=$redirectTo");
            }
        }

        return $this->user;
    }
    /* }}} */
    /* {{{ enforceLazy*/
    /**
     * @return   function returns the authenticated user or false if not logged in
     */
    public function enforceLazy() {
        if (!$this->user) {
            if ($this->hasSession()) {
                if (isset($_COOKIE[$this->cookieName]) && $this->isValidSid($_COOKIE[$this->cookieName])) {
                    $this->user = $this->authCookie();
                } else {
                    $this->justLoggedOut = true;
                    $this->user = false;
                }
            } else {
                $this->user = false;
            }
        }

        return $this->user;
    }
    /* }}} */
    /* {{{ enforceLogout */
    public function enforceLogout() {
        if ($this->hasSession()) {
            $this->justLoggedOut = true;
            $this->logout($_COOKIE[$this->cookieName]);
            $this->destroySession();
        }
    }
    /* }}} */
    /* {{{ check() */
    public function check($username, $password) {
        try {
            if (strpos($username, "@") !== false) {
                // email login
                $user = User::loadByEmail($this->pdo, $username);
            } else {
                // username login
                $user = User::loadByUsername($this->pdo, $username);
            }
            $pass = new \Depage\Auth\Password($this->realm, $this->digestCompat);

            if ($pass->verify($user->name, $password, $user->passwordhash)) {
                $this->updatePasswordHash($user, $password);

                if (defined("DEPAGE_LANG")) {
                    $user->lang = DEPAGE_LANG;
                    $user->save();
                }


                return $user;
            } else {
                $this->prolongLogin($user);
            }
        } catch (\Depage\Auth\Exceptions\User $e) {
        }

        return false;
    }
    /* }}} */
    /* {{{ login() */
    public function login($username, $password) {
        $user = $this->check($username, $password);

        if ($user) {
            $this->destroySession();
            $this->registerSession($user->id);
            $this->startSession();

            return true;
        }

        return false;
    }
    /* }}} */

    /* {{{ authCookie() */
    protected function authCookie() {
        if ($this->hasSession()) {
            if ($this->isValidSid($_COOKIE[$this->cookieName]) !== false) {
                $this->setSid($_COOKIE[$this->cookieName]);
                $this->startSession();

                $user = User::loadBySid($this->pdo, $this->getSid());

                return $user;
            } else {
                $this->justLoggedOut = true;
                if (!empty($this->log)) {
                    $this->log->log("http_auth_cookie: invalid session ID");
                }
            }
        }

        $this->sendAuthHeader();

        //throw new Exception("you are not allowed to do this!");
        return false;
    }
    /* }}} */

    // {{{ startSession()
    protected function startSession() {
        $sid = $this->getSid();

        $sessionName = session_name();

        if (!is_callable("session_status") || session_status() !== \PHP_SESSION_ACTIVE) {
            session_id($sid);
            session_start();
        }

        // Override session cookie and extend the expiration time upon page load
        if (isset($_COOKIE[$sessionName])) {
            $params = session_get_cookie_params();
            setcookie(
                $this->cookieName,
                $sid,
                time() + $this->sessionLifetime,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
    }
    // }}}
    // {{{ hasSession()
    protected function hasSession() {
        if (is_callable("session_status") && session_status() == \PHP_SESSION_ACTIVE) {
            // PHP 5.4
            return true;
        } else {
            // PHP 5.3
            return isset($_COOKIE[$this->cookieName]) && $_COOKIE[$this->cookieName] != "";
        }
    }
    // }}}
    // {{{ destroySession()
    protected function destroySession() {
        if (!is_callable("session_status") || session_status() == \PHP_SESSION_ACTIVE) {
            // delete cookie
            $params = session_get_cookie_params();
            setcookie(
                $this->cookieName,
                '',
                time() - 42000,
                "",
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
            setcookie(
                $this->cookieName,
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
            unset($_COOKIE[$this->cookieName]);
            session_destroy();
        }
    }
    // }}}

    // {{{ sendAuthHeader()
    protected function sendAuthHeader($validResponse = false) {
        // @todo look for a way to suppress password saving dialogs when password is wrong
        //header("HTTP/1.1 403 Unauthorized");
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
