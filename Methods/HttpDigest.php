<?php
/**
 * @file    auth_http_digest.php
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

class HttpDigest extends HttpBasic
{
    // {{{ enforce()
    /**
     * enforces authentication
     *
     * @public
     *
     * @return      user                user object or false if no valid authorization
     */
    public function enforce() {
        // only enforce authentication if not authenticated before
        if (!$this->user) {
            $this->user = $this->authDigest();

            if ($this->user && !is_null($testUserFunction)) {
                $this->user = $testUserFunction($this->user);
            }

            if (!$this->user) {
                throw new \Exception("you are not allowed to to this!");
            }
        }

        return $this->user;
    }
    // }}}
    // {{{ enforceLazy()
    /**
     * enforces authentication but lazily with fallback content if someone is not logged in
     *
     * @public
     *
     * @return      user                user object or false if no valid authorization
     */
    public function enforceLazy() {
        // only enforce authentication if not authenticated before
        if ($this->user === null) {
            // only authenticate if session cookie is set
            if ($this->hasSession() && ($uid = $this->isValidSid($_COOKIE[session_name()])) !== false) {
                $this->user = $this->authDigest();
            } else {
                $this->user = false;
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
        // only enforce authentication if not authenticated before
        if (!$this->user) {
            $this->user = $this->authDigestLogout();
        }

        return $this->user;
    }
    // }}}

    // {{{ authDigest()
    public function authDigest() {
        $validResponse = false;
        $digest_header = $this->getDigestHeader();

        if ($this->hasSession()) {
            $this->setSid($_COOKIE[session_name()]);
        } else {
            $this->setSid("");
        }
        if (!empty($digest_header) && $data = $this->httpDigestParse($digest_header)) {
            try {
                // get new user object
                $user = User::loadByUsername($this->pdo, $data['username']);
                $validResponse = $this->checkResponse($data, isset($user->passwordhash) ? $user->passwordhash : "");

                if ($validResponse) {
                    if (($uid = $this->isValidSid($this->sid)) !== false) {
                        if ($uid == "") {
                            $ipAddress = \Depage\Http\Request::getRequestIp();
                            $this->log->log("'{$user->name}' has logged in from '{$ipAddress}'", "auth");
                            $sid = $this->registerSession($user->id, $this->sid);
                        }
                        $this->startSession();
                        $user->sid = $sid;

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

        $this->sendAuthHeader($validResponse);
        $this->startSession();

        return false;
    }
    // }}}
    // {{{ authDigestLogout()
    public function authDigestLogout() {
        $validResponse = false;
        $digest_header = $this->getDigestHeader();

        if (!empty($digest_header) && $data = $this->httpDigestParse($digest_header)) {
            $validResponse = $this->checkResponse($data, md5("logout" . ':' . $this->realm . ':' . "logout"));

            if ($validResponse) {
                if (isset($_COOKIE[session_name()]) && $_COOKIE[session_name()] != "") {
                    $this->justLoggedOut = true;
                    $this->logout($_COOKIE[session_name()]);
                    $this->destroySession();

                    return true;
                }
            }
        }

        $this->sendAuthHeader($validResponse);
    }
    // }}}
    // {{{ sendAuthHeader()
    protected function sendAuthHeader($validResponse = false) {
        $sid = $this->getSid();
        $opaque = md5($sid);
        $realm = $this->realm;
        $domain = $this->domain;
        $nonce = $sid;

        if ($this->hasSession() && $validResponse) {
            //$this->log->log("stale!!! sid: $sid - nonce: {$data['nonce']}");
            $stale = ", stale=true";
        } else {
            $stale = "";
        }

        header("WWW-Authenticate: Digest realm=\"{$realm}\", domain=\"{$domain}\", qop=\"auth\", algorithm=MD5-sess, nonce=\"{$nonce}\", opaque=\"{$opaque}\"{$stale}");
        header("HTTP/1.1 401 Unauthorized");
    }
    // }}}
    // {{{ checkResponse()
    protected function checkResponse(&$data, $passwordhash) {
        // generate the valid response
        $HA1 = $passwordhash;
        $HA1sess = md5($HA1 . ":{$data['nonce']}:{$data['cnonce']}");
        $HA2 = md5("{$_SERVER['REQUEST_METHOD']}:{$data['uri']}");
        $validResponse = md5("{$HA1sess}:{$data['nonce']}:{$data['nc']}:{$data['cnonce']}:{$data['qop']}:{$HA2}");

        $data['n'] = hexdec($data['nc']);
        $data['valid_response'] = $validResponse;

        return $data['response'] == $validResponse;
    }
    // }}}
    // {{{ httpDigestParse()
    protected function httpDigestParse($txt) {
        // protect against missing data
        $needed_parts = array(
            'nonce' => 1,
            'nc' => 1,
            'cnonce' => 1,
            'qop' => 1,
            'username' => 1,
            'uri' => 1,
            'response' => 1,
            'opaque' => 1,
        );
        $data = array();

        preg_match_all('@(\w+)=(?:(([\'"])(.+?)\3|([A-Za-z0-9/]+)))@', $txt, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $data[$m[1]] = $m[4] ? $m[4] : $m[5];
            unset($needed_parts[$m[1]]);
        }

        return $needed_parts ? false : $data;
    }
    // }}}
    // {{{ getDigestHeader()
    protected function getDigestHeader() {
        $digest_header = false;

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['Authorization']) && !empty($headers['Authorization'])) {
                $digest_header = substr($headers['Authorization'], strpos($headers['Authorization'],' ') + 1);
            }
        } else {
            $header = "";
            if (isset($_ENV["HTTP_AUTHORIZATION"])) {
                $header = $_ENV["HTTP_AUTHORIZATION"];
            } else if (isset($_SERVER["HTTP_AUTHORIZATION"])){
                $header = $_SERVER["HTTP_AUTHORIZATION"];
            }
            $header = str_replace('\"', '"', $header);
            $digest_header = substr($header, strpos($header,' ') + 1);
        }

        return $digest_header;
    }
    // }}}
    // {{{ getNonce
    protected function getNonce() {
        $ipAddress = \Depage\Http\Request::getRequestIp();
        $time = ceil(time() / $this->sessionLifetime) * $this->sessionLifetime;
        $hash = md5(date('Y-m-d H:i', $time).':'.$ipAddress.':'.$this->privateKey);

        return $hash;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
