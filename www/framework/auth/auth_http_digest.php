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
 * copyright (c) 2002-2010 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
class auth_http_digest extends auth {
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
            $this->user = $this->auth_digest();
        }

        return $this->user;
    }
    // }}}
    // {{{ enforce_logout()
    /**
     * enforces authentication 
     *
     * @public
     *
     * @param       string  $method     method to use for authentication. Can be http
     * @return      void
     */
    public function enforce_logout() {
        // only enforce authentication if not authenticated before
        if ($this->user === null) {
            $this->user = $this->auth_digest_logout();
        }

        return $this->user;
    }
    // }}}
    
    // {{{ auth_digest()
    public function auth_digest() {
        $valid_response = false;
        $digest_header = $this->get_digest_header();

        //@todo fix in safari which does not send auth-header in first request
        if (!empty($digest_header) && $data = $this->http_digest_parse($digest_header)) { 
            // get new user object
            $user = auth_user::get_by_username($this->pdo, $data['username']);
            $valid_response = $this->check_response($data, isset($user->passwordhash) ? $user->passwordhash : "");
            if ($user && $valid_response) {
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

        $this->send_header($valid_response);

        session_id($this->get_sid());
        session_start();

        throw new Exception("you are not allowed to to this!");
    } 
    // }}}
    // {{{ auth_digest_logout()
    public function auth_digest_logout() {
        $valid_response = false;
        $digest_header = $this->get_digest_header();

        if (!empty($digest_header) && $data = $this->http_digest_parse($digest_header)) { 
            $user = true;
            $valid_response = $this->check_response($data, md5("logout" . ':' . $this->realm . ':' . "logout"));

            if ($user && $valid_response) {
                if (isset($_COOKIE[session_name()]) && $_COOKIE[session_name()] != "") {
                    $this->logout($_COOKIE[session_name()]);

                    setcookie(session_name(), "", time() - 3600);
                    unset($_COOKIE[session_name()]);

                    return $user;
                }
            }
        }

        $this->send_header($valid_response);
    } 
    // }}}
    // {{{ send_header()
    protected function send_header($valid_response) {
        $sid = $this->get_sid();
        $opaque = md5($sid);
        $realm = $this->realm;
        $domain = $this->domain;
        $nonce = $sid;

        if (isset($_COOKIE[session_name()]) && $_COOKIE[session_name()] != "" && $valid_response) {
        //if (isset($_COOKIE[session_name()]) && $_COOKIE[session_name()] != "") {
            //$log->add_entry("stale!!! sid: $sid - nonce: {$data['nonce']}");
            //$log->add_varinfo($headers);
            $stale = ", stale=true";
        } else {
            $stale = "";
        }

        header("WWW-Authenticate: Digest realm=\"{$realm}\", domain=\"{$domain}\", qop=\"auth\", algorithm=MD5-sess, nonce=\"{$nonce}\", opaque=\"{$opaque}\"{$stale}");
        header("HTTP/1.1 401 Unauthorized");
    } 
    // }}}
    // {{{ check_response()
    protected function check_response(&$data, $passwordhash) {
        // generate the valid response
        $HA1 = $passwordhash;
        $HA1sess = md5($HA1 . ":{$data['nonce']}:{$data['cnonce']}");
        $HA2 = md5("{$_SERVER['REQUEST_METHOD']}:{$data['uri']}");
        $valid_response = md5("{$HA1sess}:{$data['nonce']}:{$data['nc']}:{$data['cnonce']}:{$data['qop']}:{$HA2}");

        $data['n'] = hexdec($data['nc']);
        $data['valid_response'] = $valid_response;

        return $data['response'] == $valid_response;
    } 
    // }}}
    // {{{ http_digest_parse()
    protected function http_digest_parse($txt) {
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
    // {{{ get_digest_header()
    protected function get_digest_header() {
        $digest_header = false;

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['Authorization']) && !empty($headers['Authorization'])) {
                $digest_header = substr($headers['Authorization'], strpos($headers['Authorization'],' ') + 1);
            }
        } else {
            $_ENV["HTTP_AUTHORIZATION"] = str_replace('\"', '"', $_ENV["HTTP_AUTHORIZATION"]);
            $digest_header = substr($_ENV["HTTP_AUTHORIZATION"], strpos($_ENV["HTTP_AUTHORIZATION"],' ') + 1);
        }
        
        return $digest_header;
    } 
    // }}}
    // {{{ get_nonce
    protected function get_nonce() {
        $time = ceil(time() / $this->session_lifetime) * $this->session_lifetime;
        $hash = md5(date('Y-m-d H:i', $time).':'.$_SERVER['REMOTE_ADDR'].':'.$this->privateKey);

        return $hash;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
