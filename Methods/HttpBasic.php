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
 * copyright (c) 2002-2010 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */
namespace Depage\Auth\Methods;

use Depage\Auth\User;

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
    public function enforce($testUserFunction = null) {
        // only enforce authentication of not authenticated before
        if (!$this->user) {
            if (isset($_ENV["HTTP_AUTHORIZATION"])) {
                list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':' , base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
            }
            $this->user = $this->authBasic();

            if ($this->user && !is_null($testUserFunction)) {
                $this->user = $testUserFunction($this->user);
            }
        }

        return $this->user;
    }
    // }}}
    // {{{ enforceLogout()
    /**
     * enforces logout
     *
     * @public
     *
     * @return      boolean             true
     */
    public function enforceLogout() {
        if ($this->hasSession()) {
            $this->justLoggedOut = true;
            $this->logout($_COOKIE[session_name()]);
            $this->destroySession();
        }
    }
    // }}}

    // {{{ authBasic()
    public function authBasic() {
        if ($this->hasSession()) {
            $this->setSid($_COOKIE[session_name()]);
        } else {
            $this->setSid("");
        }

        if (isset($_SERVER['PHP_AUTH_USER'])) {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];

            // get new user object
            try {
                if (strpos($username, "@") !== false) {
                    // email login
                    $user = User::loadByEmail($this->pdo, $username);
                    $username = $user->name;
                } else {
                    // username login
                    $user = User::loadByUsername($this->pdo, $username);
                }
                $pass = new \Depage\Auth\Password($this->realm, $this->digestCompat);

                if ($pass->verify($user->name, $password, $user->passwordhash)) {
                    $this->updatePasswordHash($user, $password);

                    if (($uid = $this->isValidSid($_COOKIE[session_name()])) !== false) {
                        if ($uid == "") {
                            $ipAddress = \Depage\Http\Request::getRequestIp();
                            $this->log->log("'{$user->name}' has logged in from '{$ipAddress}'", "auth");
                            $sid = $this->registerSession($user->id, $_COOKIE[session_name()]);
                        }
                        $this->startSession();
                        $user->sid = $this->sid;

                        return $user;
                    } elseif ($this->hasSession()) {
                        $this->destroySession();
                    }
                } else {
                    $this->prolongLogin($user);
                }
            } catch (\Depage\Auth\Exceptions\User $e) {
            }

        }

        $this->sendAuthHeader();
        $this->startSession();

        throw new \Exception("you are not allowed to to this!");
    }
    // }}}
    // {{{ sendAuthHeader()
    protected function sendAuthHeader($validResponse = false) {
        $sid = $this->getSid();
        $realm = $this->realm;
        $domain = $this->domain;

        header("WWW-Authenticate: Basic realm=\"$realm\", domain=\"{$this->domain}\"");
        header("HTTP/1.1 401 Unauthorized");
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
