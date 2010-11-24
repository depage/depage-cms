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

/**
 * contains functions for handling user authentication
 * and session handling.
 */
class auth {
    // {{{ variables
    var $realm = "depage::cms";
    var $sid, $uid;
    var $valid = false;
    // }}}
    
    // {{{ constructor()
    /**
     * constructor
     *
     * @public
     *
     * @param       db_pdo  $pdo        db_pdo object for database access
     * @param       string  $realm      realm to use for http-basic and http-digest auth
     * @param       domain  $domain     domain to use for cookie and auth validity
     *
     * @return      void
     */
    public function __construct($pdo, $realm, $domain) {
        $this->pdo = $pdo;
        $this->realm = $realm;
        $this->domain = $domain;
        $this->log = new log();

        session_name("depage-session-id");
    }
    // }}}
    // {{{ enforce()
    /**
     * enforces authentication 
     *
     * @public
     *
     * @param       string  $method     method to use for authentication. Can be http
     * @return      void
     */
    public function enforce($method = "http") {
        if ($method = "http") {
            return $this->auth_http();
        }
    }
    // }}}
    
    // {{{ auth_http()
    public function auth_http() {
        if (isset($_ENV["HTTP_AUTHORIZATION"]) || function_exists('getallheaders')) {
            // use auth-digest if possible
            return $this->auth_digest();
        } else {
            // fallback to auth-basic if auth-digest not available
            if (isset($_ENV["HTTP_AUTHORIZATION"])) {
                list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':' , base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
            }
            return $this->auth_basic();
        }
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
                            $log->add_entry("'{$user->name}' has logged in from '{$_SERVER["REMOTE_ADDR"]}'", "auth");
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
        $opaque = md5($sid);
        $realm = $this->realm;
        $domain = $this->domain;
        $nonce = $sid;

        if (isset($_COOKIE[session_name()]) && $_COOKIE[session_name()] != "") {

        } else {
            session_id($sid);
            session_start();
        }

        header("WWW-Authenticate: Basic realm=\"$realm\"");
        header("HTTP/1.1 401 Unauthorized");

        die();
        //die_error("you are not allowed to to this!");
    } 
    // }}}
    // {{{ auth_digest()
    public function auth_digest() {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['Authorization']) && !empty($headers['Authorization'])) {
                $digest_header = substr($headers['Authorization'], strpos($headers['Authorization'],' ') + 1);
            }
        } else {
            $_ENV["HTTP_AUTHORIZATION"] = str_replace('\"', '"', $_ENV["HTTP_AUTHORIZATION"]);
            $digest_header = substr($_ENV["HTTP_AUTHORIZATION"], strpos($_ENV["HTTP_AUTHORIZATION"],' ') + 1);
        }
        
        if (!empty($digest_header) && $data = $this->http_digest_parse($digest_header)) { 
            // get new user object
            $user = auth_user::get_by_username($this->pdo, $data['username']);

            if ($user) {
                // generate the valid response
                $HA1 = $user->passwordhash;
                $HA1sess = md5($HA1 . ":{$data['nonce']}:{$data['cnonce']}");
                $HA2 = md5("{$_SERVER['REQUEST_METHOD']}:{$data['uri']}");
                $valid_response = md5("{$HA1sess}:{$data['nonce']}:{$data['nc']}:{$data['cnonce']}:{$data['qop']}:{$HA2}");

                $n = hexdec($data['nc']);

                if ($data['response'] == $valid_response) {
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
        $opaque = md5($sid);
        $realm = $this->realm;
        $domain = $this->domain;
        $nonce = $sid;

        if (isset($_COOKIE[session_name()]) && $_COOKIE[session_name()] != "" && $data['response'] == $valid_response) {
        //if (isset($_COOKIE[session_name()]) && $_COOKIE[session_name()] != "") {
            //$log->add_entry("stale!!! sid: $sid - nonce: {$data['nonce']}");
            //$log->add_varinfo($headers);
            $stale = ", stale=true";
        } else {
            session_id($sid);
            session_start();
            $stale = "";
        }

        header("WWW-Authenticate: Digest realm=\"$realm\", domain=\"$domain\", qop=\"auth\", algorithm=MD5-sess, nonce=\"$nonce\", opaque=\"$opaque\"$stale");
        header("HTTP/1.1 401 Unauthorized");

        throw new Exception("you are not allowed to to this!");
    } 
    // }}}
    // {{{ http_digest_parse()
    private function http_digest_parse($txt) {
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
    // {{{ get_nonce
    private function get_nonce() {
        $time = time();
        $hash = md5($time . $_SERVER['HTTP_USER_AGENT'] . $this->realm);

        return base64_encode($time) . $hash;  
    }
    // }}}
    
    // {{{ is_valid_sid()
    private function is_valid_sid($sid) {
        $this->logout_timed_out_users();

        // test for validity
        $session_query = $this->pdo->prepare(
            "SELECT 
                sid, userid
            FROM 
                {$this->pdo->prefix}_auth_sessions
            WHERE
                sid = :sid AND
                ip = :ip
            LIMIT 1"
        );
        $session_query->execute(array(
            ':sid' => $sid,
            ':ip' => $_SERVER['REMOTE_ADDR'],
        ));
        $result = $session_query->fetchAll();

        if (count($result) > 0) {
            // set new timestamp
            $timestamp_query = $this->pdo->prepare(
                "UPDATE
                    {$this->pdo->prefix}_auth_sessions
                SET
                    last_update = NOW()
                WHERE
                    sid = :sid AND
                    ip = :ip"
            );
            $timestamp_query->execute(array(
                ':sid' => $sid,
                ':ip' => $_SERVER['REMOTE_ADDR'],
            ));

            $this->uid = $result[0]['userid'];
            $this->valid = true;

            return $this->uid;
        } else {
            $this->valid = false;

            return false;
        }
    }
    // }}}
    // {{{ set_sid()
    function set_sid($sid) {
        $this->sid = $sid;

        return $sid;
    }
    // }}}
    // {{{ get_sid()
    private function get_sid() {
        if (!$this->valid) {
            if (!$this->is_valid_sid($this->sid)) {
                $this->register_session();
            }
        }
        return $this->sid;
    }
    // }}}
    // {{{ get_new_sid()
    private function get_new_sid() {
        $this->sid = md5(uniqid(dechex(rand(256, 4095))));

        return $this->sid;
    }
    // }}}
    // {{{ uniqid16()
    /**
     * generates a uniqid, used for sessions.
     *
     * @public
     *
     * @return    $id (string) new id
     */
    private function uniqid16() {
        return uniqid(dechex(rand(256, 4095)));
    }
    // }}}
    // {{{ register_session()
    private function register_session($uid = null, $sid = null) {
        if (is_null($sid)) {
            $this->sid = $this->get_new_sid();
        } else {
            $this->sid = $sid;
        }
        if (is_null($uid)) {
            $update_query = $this->pdo->prepare(
                "REPLACE INTO
                    {$this->pdo->prefix}_auth_sessions
                SET
                    sid = :sid,
                    last_update = NOW(),
                    ip = :ip,
                    useragent = :useragent"
            )->execute(array(
                ':sid' => $this->sid,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'useragent' => $_SERVER['HTTP_USER_AGENT'],
            ));
        } else {
            $this->uid = $uid;
            $update_query = $this->pdo->prepare(
                "REPLACE INTO
                    {$this->pdo->prefix}_auth_sessions
                SET
                    sid = :sid,
                    userid = :uid,
                    time_login = NOW(),
                    last_update = NOW(),
                    ip = :ip,
                    useragent = :useragent"
            )->execute(array(
                ':sid' => $this->sid,
                ':uid' => $this->uid,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'useragent' => $_SERVER['HTTP_USER_AGENT'],
            ));
        }

        $this->valid = true;

        return $sid;
    }
    // }}}
    
    // {{{ logout_timed_out_users()
    private function logout_timed_out_users() {
        // remove users which login is outdated
        $outdated_query = $this->pdo->query(
            "SELECT 
                sid 
            FROM 
                {$this->pdo->prefix}_auth_sessions
            WHERE 
                last_update < DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
        );
        $result = $outdated_query->fetchAll();

        foreach ($result as $s) {
            $this->logout($s['sid']);
        }
    }
    // }}}
    // {{{ logout()
    /**
     * logs user out
     *
     * @public
     *
     * @param    $sid (string) session id
     */
    public function logout($sid = null) {
        if ($sid == null) {
            $sid = $this->sid;
        }

        // get user object for info
        $user = auth_user::get_by_sid($this->pdo, $sid);
        if ($user) {
            $this->log->log("'{$user->name}' has logged with $sid", "auth");
        }

        // delete session data for sid
        $delete_query = $this->pdo->prepare(
            "DELETE FROM 
                {$this->pdo->prefix}_auth_sessions
            WHERE 
                sid = :sid"
        );
        $delete_query->execute(array(
            ':sid' => $sid,
        ));
    }
    // }}}
    
    // --- old ---------------
    // {{{ get_userlist()
    /**
     * gets an xml array of available user account
     **/
    function get_userlist() {
        global $conf;

        $xml = "";
        $result = db_query(
            "SELECT *
            FROM $conf->db_table_user"
        );
        if (($num = mysql_num_rows($result)) > 0) {
            for ($i = 0; $i < $num; $i++) {
                $row = mysql_fetch_assoc($result);
                $xml .= "<user name=\"" . $row['name'] . "\" fullname=\"" . $row['name_full'] . "\" uid=\"" . $row['id'] . "\" />";
            }
        }

        return $xml;
    }
    // }}}
    // {{{ get_loggedin_users()
    function get_loggedin_users() {
        global $conf, $log;
        
        $this->logout_timed_out_users();

        // get logged in users
        $loggedin = array();
        $result = db_query(
            "SELECT user.name, user.name_full, user.email, sessions.project, sessions.ip, sessions.last_update, sessions.useragent
            FROM $conf->db_table_user AS user, $conf->db_table_sessions AS sessions
            WHERE user.id=sessions.userid"
        );
        if ($result && ($num = mysql_num_rows($result)) > 0) {
            for ($i = 0; $i < $num; $i++) {
                $data = mysql_fetch_object($result);
                $loggedin[] = $data;
            }
        }

        return $loggedin;
    }
    // }}}
    // {{{ get_loggedin_count()
    function get_loggedin_count() {
        global $conf, $log;

        $this->logout_timed_out_users();

        // get count of logged in users
        $result = db_query(
            "SELECT COUNT(*) AS count
            FROM $conf->db_table_sessions"
        );
        $data = mysql_fetch_assoc($result);

        return $data['count'];
    }
    // }}}
    // {{{ get_loggedin_nonpocket()
    function get_loggedin_nonpocket() {
        global $conf, $log;

        $this->logout_timed_out_users();

        $loggedin= array();
        $result = db_query(
            "SELECT sessions.sid AS sid, sessions.project AS project
            FROM $conf->db_table_sessions As sessions, $conf->db_table_sessions_win AS sessions_win
            WHERE sessions_win.port=0 AND sessions.sid=sessions_win.sid"
        );
        if ($result && ($num = mysql_num_rows($result)) > 0) {
            for ($i = 0; $i < $num; $i++) {
                $data = mysql_fetch_array($result);
                $loggedin[$data['sid']] = $data['project'];
            }
        }

        return $loggedin;
    }
    // }}}
    // {{{ add_update()
    function add_update($sid, $message) {
        global $conf;
        global $log;

        db_query(
            "INSERT INTO $conf->db_table_updates
            SET sid='$sid', message='" . mysql_escape_string($message) . "'"
        );
    }
    // }}}
    // {{{ get_updates()
    function get_updates($sid) {
        global $conf;
        global $log;

        $msgs = array();
        
        $result = db_query(
            "SELECT message
            FROM $conf->db_table_updates
            WHERE sid='$sid'
            ORDER BY id"
        );
        if ($result && ($num = mysql_num_rows($result)) > 0) {
            for ($i = 0; $i < $num; $i++) {
                $data = mysql_fetch_assoc($result);
                $msgs[] = $data['message'];
            }
        }
        db_query(
            "DELETE FROM $conf->db_table_updates
            WHERE sid='$sid'"
        );

        return $msgs;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
