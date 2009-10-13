<?php 
/**
 * @file    lib_auth.php
 *
 * User and Session Handling Library
 *
 * This file contains classes for user and session
 * handling. It also contains functions for locking
 * actual edit pages.
 *
 *
 * copyright (c) 2002-2008 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 *
 * $Id: lib_user.php,v 1.13 2004/05/26 14:49:05 jonas Exp $
 */

if (!function_exists('die_error')) require_once('lib_global.php');

/**
 * contains functions for handling user authentication
 * and session handling.
 */
class ttUser{
    var $realm = "depage::cms";

    // {{{ variables
    var $sid, $wid, $uid;
    // }}}
    // {{{ uniqid16()
    /**
     * generates a uniqid, used for sessions.
     *
     * @public
     *
     * @return    $id (string) new id
     */
    function uniqid16() {
        return uniqid(dechex(rand(256, 4095)));
    }
    // }}}
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
            "SELECT user.name, user.name_full, user.email, sessions.project, sessions.ip, sessions.last_update
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
    // {{{ login()
    /**
     * logs user in and sets a new sid for this user.
     *
     * @public
     *
     * @param    $user (string) user name
     * @param    $pass (string) password of user
     * @param    $project (string) project name to log into
     * @param    $ip (string) ip from where user logs in
     *
     * @return    $sid (string) new session id or false if login failed
     */
    function login($user, $pass, $project, $ip) {
        global $conf, $log;

        $user = mysql_real_escape_string($user);
        $project = mysql_real_escape_string($project);
        $ip = mysql_real_escape_string($ip);

        $result = db_query(
            "SELECT name, id
            FROM $conf->db_table_user 
            WHERE name='$user' and pass='" . md5($pass) . "'"
        );
        if (mysql_num_rows($result) == 1) {
            $data = mysql_fetch_assoc($result);
            $sid = $this->uniqid16();
            db_query(
                "INSERT 
                INTO $conf->db_table_sessions 
                SET sid='$sid', userid='" . $data['id'] . "', project='$project', ip='$ip', last_update=NOW()"
            );
            $log->add_entry("'" . $user . "' has logged in Project '" . $project . "' from '" . $ip . "'", "auth");
            $retval = $sid;
        } else {
            $log->add_entry("unauthorisized: '" . $ip . "' tried to log in Project '" . $project . "' as '" . $user . "'", "auth");
            $retval = false;
        }
        //mysql_free_result($result);
        
        return $retval;
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
    function logout($sid = null) {
        global $conf, $log;

        if ($sid == null) {
            $sid = $this->sid;
        }
        
        $result = db_query(
            "SELECT * 
            FROM $conf->db_table_sessions AS session, $conf->db_table_user AS user 
            WHERE session.sid='$sid' and session.userid = user.id"
        );
        if (mysql_num_rows($result) > 0) {
            $data = mysql_fetch_assoc($result);
            
            $log->add_entry("'{$data['name']}' has logged out Project '{$data['project']}' from '{$data['ip']}' with $sid", "auth");
        }
        db_query(
            "DELETE 
            FROM $conf->db_table_sessions 
            WHERE sid='$sid'"
        );
        db_query(
            "DELETE 
            FROM $conf->db_table_sessions_win 
            WHERE sid='$sid'"
        );
        db_query(
            "DELETE
            FROM $conf->db_table_updates
            WHERE sid='$sid'"
        );
        //mysql_free_result($result);
    }
    // }}}
    // {{{ logout_timed_out_users()
    function logout_timed_out_users() {
        global $conf, $log;

        // remove users which login is outdated
        $result = db_query(
            "SELECT sid 
            FROM $conf->db_table_sessions
            WHERE last_update < DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
        );
        if (($num = mysql_num_rows($result)) > 0) {
            for ($i = 0; $i < $num; $i++) {
                $data = mysql_fetch_assoc($result);
                $this->logout($data['sid']);
            }
        }
    }
    // }}}
    // {{{ update_login()
    function update_login($sid) {
        global $conf;

        db_query(
            "UPDATE $conf->db_table_sessions
            SET last_update=NOW()
            WHERE sid='$sid'"
        );
    }
    // }}}
    // {{{ clearsessions()
    /**
     * clears all sessions (and so logs all users out, if
     * some were there.
     *
     * @public
     */
    function clearsessions() {
        global $conf;
        
        db_query("DELETE FROM $conf->db_table_sessions");
        db_query("DELETE FROM $conf->db_table_sessions_win");
    }
    // }}}
    // {{{ register_window
    /**
     * registers a new window (pocket connection) to a user.
     *
     * @public
     *
     * @param    $sid (string) session id
     * @param    $ip (string) ip of user
     * @param    $port (int) port from which the user logs in
     * @param    $type (string) type of window to be registered
     *
     * @return    $wid (string) new session window id, or false on failure
     */
    function register_window($sid, $ip, $port, $type, $project_name){
        global $conf, $log;

        $result = db_query(
            "SELECT name 
            FROM $conf->db_table_sessions AS session, $conf->db_table_user AS user 
            WHERE session.sid='$sid' and session.ip='$ip' and session.userid = user.id"
        );
        if (mysql_num_rows($result) == 1) {
            $data = mysql_fetch_assoc($result);
            $wid = $this->uniqid16();
            db_query(
                "INSERT 
                INTO $conf->db_table_sessions_win 
                SET sid='$sid', wid='$wid', port='$port', type='$type'"
            );
            db_query(
                "UPDATE $conf->db_table_sessions
                SET project='" . mysql_escape_string($project_name) . "'
                WHERE sid='$sid'"
            );

            $log->add_entry("'{$data['name']}' has registered $type-window from '$ip:$port'", "auth");

            $retval = $wid;
        } else {
            $log->add_entry("unauthorisized: '$ip:$port' tried to register $type-window", "auth");

            $retval = false;
        }
        //mysql_free_result($result);
        
        return $retval;
    }
    // }}}
    // {{{ unregister_window
    /**
     * unregisters a window. if thi was the last window of this user
     * the user will also logged out.
     *
     * @public
     *
     * @param    $ip (string) ip of connection
     * @param    $port (int) port of connection
     */
    function unregister_window($ip, $port){
        global $conf;

        $result = db_query(
            "SELECT * 
            FROM $conf->db_table_sessions_win AS session_win, $conf->db_table_sessions AS session 
            WHERE session.ip='$ip' and session_win.port='$port' and session.sid = session_win.sid"
        );
        if (mysql_num_rows($result) == 1) {
            $data = mysql_fetch_assoc($result);
            $sid = $data['sid'];
            //mysql_free_result($result);
            
            db_query(
                "DELETE 
                FROM $conf->db_table_sessions_win 
                WHERE sid='$sid' and wid='{$data['wid']}'"
            );

            $result = db_query(
                "SELECT * 
                FROM $conf->db_table_sessions_win 
                WHERE sid='$sid'"
            );
            if (mysql_num_rows($result) == 0) {
                $this->logout($sid);
            }
            //mysql_free_result($result);
        }    
    }
    // }}}
    // {{{ is_valid_user()
    /**
     * test, if the combination of sid, wid and ip belongs to
     * an already logged in user and a registered window.
     *
     * @public
     *
     * @param    $sid (string) session id
     * @param    $wid (string) session window id
     * @param    $ip (string) ip from where user is connected
     * @param    $neededlevel (int) authentication level. if this
     *            parameter is given, the function tests also, if 
     *            user has the given user level or not.
     *
     * @return    $isValid (bool) true, if user is valid, false otherwise
     */
    function is_valid_user($sid, $wid, $ip, $neededlevel = 0){
        global $conf;

        $this->logout_timed_out_users();

        // @todo add sanity-check for parameters
        $result = db_query(
            "SELECT * 
            FROM $conf->db_table_sessions_win AS session_win, $conf->db_table_sessions AS session 
            WHERE session.sid='$sid' and session.ip='$ip' and session_win.wid='$wid' and session.sid = session_win.sid"
        );
        if (mysql_num_rows($result) == 1) {
            $row = mysql_fetch_assoc($result);
            //mysql_free_result($result);
            
            $this->sid = $sid;
            $this->wid = $wid;
            $this->uid = $row['userid'];
            $this->project = $row['project'];
            if ($neededlevel == 0) {
                $retVal = $this->project;
            } else {
                $result = db_query(
                    "SELECT * 
                    FROM $conf->db_table_user AS user, $conf->db_table_sessions AS session 
                    WHERE session.sid='$sid' and user.id = session.userid and user.level <= $neededlevel"
                );
                if (mysql_num_rows($result) == 1) {
                    $retVal = $this->project;
                } else {
                    $retVal = false;
                }
                //mysql_free_result($result);
            }
        } else {
            $retVal = false;
        }
        return $retVal;
    }
    // }}}
    // {{{ is_logged_in()
    /**
     * checks, wether someone is logged in from given ip and port.
     * if project is given also, it will be checked, if this user
     * logged in to this project, too.
     *
     * @public
     *
     * @param    $host (string) ip of connection
     * @param    $port (int) port of connection
     * @param    $project (string) name of project
     *
     * @return    $loggedIn (bool) true if this connection is
     *            still logged in, false otherwise.
     */
    function is_logged_in($host, $port, $project = null) {
        global $conf;
        if ($project == null) {
            $result = db_query(
                "SELECT * 
                FROM $conf->db_table_sessions_win AS session_win, $conf->db_table_sessions AS session 
                WHERE session.ip='$host' and session_win.port='$port' and session.sid = session_win.sid"
            );
        } else {
            $result = db_query(
                "SELECT * 
                FROM $conf->db_table_sessions_win AS session_win, $conf->db_table_sessions AS session 
                WHERE session.project='$project' and session.ip='$host' and session_win.port='$port' and session.sid = session_win.sid"
            );
        }
        if (mysql_num_rows($result) == 1) {
            $retVal = true;    
        } else {
            $retVal = false;
        }
        //mysql_free_result($result);
        
        return $retVal;
    }
    // }}}
    // {{{ get_project_by_sid()
    /**
     * gets project, the user with given sid is logged into.
     *
     * @public
     *
     * @param    $sid (string) session id
     *
     * @return    $project (string) project name or false, if sid
     *            is unknown.
     */
    function get_project_by_sid($sid) {
        global $conf;
        
        $result = db_query(
            "SELECT project 
            FROM $conf->db_table_sessions 
            WHERE sid = '$sid'"
        );
        if ($result) {
            $row = mysql_fetch_assoc($result);
            $retVal = $row['project'];
        } else {
            $retVal = false;
        }
        //mysql_free_result($result);
        
        return $retVal;
    }
    // }}}
    // {{{ get_userid_by_sid()
    /**
     * gets id of user by its sid.
     *
     * @public
     *
     * @param    Sid (string) session id
     *
     * @return    $userid (int) id of user, or false, if
     *            sid is unknown.
     */
    function get_userid_by_sid($sid) {
        global $conf;
        
        $result = db_query(
            "SELECT userid 
            FROM $conf->db_table_sessions 
            WHERE sid = '$sid'"
        );
        if ($result) {
            $row = mysql_fetch_assoc($result);
            $retVal = $row['userid'];
        } else {
            $retVal = false;
        }
        
        return $retVal;
    }
    // }}}
    // {{{ get_level_by_sid()
    /**
     * gets authentication level of user by sid.
     *
     * @public
     *
     * @param    $sid (string) session id
     * 
     * @return    $level (int) authentication level of logged in user.
     */
    function get_level_by_sid($sid = null) {
        global $conf;

        if ($sid == null) {
            $sid = $this->sid;
        }
        
        $result = db_query(
            "SELECT user.level AS level 
            FROM $conf->db_table_sessions AS sessions, $conf->db_table_user AS user 
            WHERE sessions.sid = '$sid' and sessions.userid=user.id"
        );
        if ($result) {
            $row = mysql_fetch_assoc($result);
            $retVal = $row['level'];
        } else {
            $retVal = NULL;    
        }
        //mysql_free_result($result);
        
        return $retVal;
    }
    // }}}
    // {{{ get_host_port_sid_wid
    /**
     * gets ip and port of a connection by sid and wid.
     *
     * @public
     *
     * @param    $sid (string) session id
     * @param    $wid (string) session window id
     *
     * @return    $connection (array) array of connection sesstings of user.\n
     *            'ip' contains ip of connection.\n
     *            'port contains the port.
     */
    function get_host_port_sid_wid($sid, $wid) {
        global $conf;
        $return_array = array();
        
        $result = db_query(
            "SELECT sessions.ip AS ip, sessions_win.port AS port 
            FROM $conf->db_table_sessions AS sessions, $conf->db_table_sessions_win AS sessions_win 
            WHERE sessions.sid=sessions_win.sid AND sessions_win.sid='$sid' AND sessions_win.wid='$wid'"
        );
        if ($result) {
            $row = mysql_fetch_assoc($result);
            $retVal = $row;
        } else {
            $retVal = false;
        }
        //mysql_free_result($result);
        
        return $retVal;
    }
    // }}}
    
    // {{{ auth_http()
    function auth_http() {
        if (isset($_ENV["HTTP_AUTHORIZATION"]) || function_exists('getallheaders')) {
            $this->auth_digest();
        } else {
            if (isset($_ENV["HTTP_AUTHORIZATION"])) {
                list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':' , base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
            }
            $this->auth_basic();
        }
    } 
    // }}}
    // {{{ auth_basic()
    function auth_basic() {
        global $conf;
        global $log;

        $HA1 = $this->get_passwd_hash($_SERVER['PHP_AUTH_USER']);
        if (isset($_SERVER['PHP_AUTH_USER'])) { 
            // generate the valid response
            $HA1 = $this->get_passwd_hash($_SERVER['PHP_AUTH_USER']);

            if ($HA1 == md5($_SERVER['PHP_AUTH_USER'] . ':' . $this->realm . ':' . $_SERVER['PHP_AUTH_PW'])) {
                if (($uid = $this->is_valid_sid($_COOKIE[session_name()])) !== false) {
                    if ($uid == "") {
                        $log->add_entry("'{$_SERVER['PHP_AUTH_USER']}' has logged in from '{$_SERVER["REMOTE_ADDR"]}'", "auth");
                        $sid = $this->register_session($this->get_uid_by_name($_SERVER['PHP_AUTH_USER']), $_COOKIE[session_name()]);
                    } else {
                        $sid = $this->set_sid($_COOKIE[session_name()]);
                    }
                    session_id($sid);
                    session_start();

                    return;
                } elseif (isset($_COOKIE[session_name()]) && $_COOKIE[session_name()] != "") {
                    setcookie(session_name(), "", time() - 3600);
                    unset($_COOKIE[session_name()]);
                }
            }
        }
        $sid = $this->get_sid();
        $opaque = md5($sid);
        $realm = $this->realm;
        $domain = $conf->path_base;
        $nonce = $sid;

        if (isset($_COOKIE[session_name()]) && $_COOKIE[session_name()] != "") {

        } else {
            session_id($sid);
            session_start();
        }

        header("WWW-Authenticate: Basic realm=\"$realm\"");
        header("HTTP/1.1 401 Unauthorized");

        phpinfo();
        die();
        //die_error("you are not allowed to to this!");
    } 
    // }}}
    // {{{ auth_digest()
    function auth_digest() {
        global $conf;
        global $log;

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
            // generate the valid response
            $HA1 = $this->get_passwd_hash($data['username']);
            $HA1sess = md5($HA1 . ":{$data['nonce']}:{$data['cnonce']}");
            $HA2 = md5("{$_SERVER['REQUEST_METHOD']}:{$data['uri']}");
            $valid_response = md5("{$HA1sess}:{$data['nonce']}:{$data['nc']}:{$data['cnonce']}:{$data['qop']}:{$HA2}");

            $n = hexdec($data['nc']);

            if ($data['response'] == $valid_response) {
                if (($uid = $this->is_valid_sid($_COOKIE[session_name()])) !== false) {
                    if ($uid == "") {
                        $log->add_entry("'{$data['username']}' has logged in from '{$_SERVER["REMOTE_ADDR"]}'", "auth");
                        $sid = $this->register_session($this->get_uid_by_name($data['username']), $_COOKIE[session_name()]);
                    } else {
                        $sid = $this->set_sid($_COOKIE[session_name()]);
                    }
                    session_id($sid);
                    session_start();

                    return;
                } elseif (isset($_COOKIE[session_name()]) && $_COOKIE[session_name()] != "") {
                    setcookie(session_name(), "", time() - 3600);
                    unset($_COOKIE[session_name()]);
                }
            }
        }
        $sid = $this->get_sid();
        $opaque = md5($sid);
        $realm = $this->realm;
        $domain = $conf->path_base . "/";
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

        die_error("you are not allowed to to this!");
    } 
    // }}}
    // {{{ http_digest_parse()
    function http_digest_parse($txt) {
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
    function get_nonce() {
        $time = time();
        $hash = md5($time . $_SERVER['HTTP_USER_AGENT'] . $this->realm);

        return base64_encode($time) . $hash;  
    }
    // }}}
    
    // {{{ get_new_sid()
    function get_new_sid() {
        $this->sid = md5(uniqid(dechex(rand(256, 4095))));

        return $this->sid;
    }
    // }}}
    // {{{ get_sid()
    function get_sid() {
        if (!$this->valid) {
            if (!$this->is_valid_sid($this->sid)) {
                $this->register_session();
            }
        }
        return $this->sid;
    }
    // }}}
    // {{{ set_sid()
    function set_sid($sid) {
        $this->sid = $sid;

        return $sid;
    }
    // }}}
    // {{{ is_valid_sid()
    function is_valid_sid($sid) {
        global $conf;
        global $log;

        $this->logout_timed_out_users();

        // test for validity
        $result = db_query(
            "SELECT sid, userid
            FROM {$conf->db_table_sessions} 
            WHERE 
                sid='" . mysql_escape_string($sid) . "' AND
                ip='{$_SERVER["REMOTE_ADDR"]}'"
        );
        if ($result) {
            if (($num = mysql_num_rows($result)) > 0) {
                $data = mysql_fetch_assoc($result);

                // set new timestamp
                db_query(
                    "UPDATE {$conf->db_table_sessions} SET 
                        last_update=NOW()
                    WHERE sid='" . mysql_escape_string($sid) . "'"
                );

                $this->uid = $data['userid'];
                $this->valid = true;

                return $this->uid;
            }
        }

        $this->valid = false;
        return false;
    }
    // }}}
    
    // {{{ get_uid()
    function get_uid() {
        global $conf;

        if (is_null($this->uid)) {
            $result = db_query(
                "SELECT userid 
                FROM {$conf->db_table_sessions}
                WHERE sid='" . $this->get_sid() . "'"
            );
            $data = mysql_fetch_assoc($result);
            $this->uid = $data['userid'];
        }
        return $this->uid;
    }
    // }}}
    // {{{ get_uid_by_name()
    function get_uid_by_name($name) {
        global $conf;
        global $log;

        $result = db_query(
            "SELECT id 
            FROM {$conf->db_table_user}
            WHERE name='" . mysql_escape_string($name) . "'"
        );
        $data = mysql_fetch_assoc($result);
        
        return $data['id'];
    }
    // }}}
    // {{{ get_passwd_hash()
    function get_passwd_hash($name) {
        global $conf;

        $result = db_query(
            "SELECT pass
            FROM {$conf->db_table_user}
            WHERE name='" . mysql_escape_string($name) . "'"
        );

        if ($result && ($num = mysql_num_rows($result)) > 0) {
            $data = mysql_fetch_assoc($result);
        }
        return $data['pass'];
    }
    // }}}
    
    // {{{ register_session()
    function register_session($uid = null, $sid = null) {
        global $conf;
        global $log;

        if (is_null($sid)) {
            $sid = $this->get_new_sid();
        }
        if (is_null($uid)) {
            $uid_query = "";
            $time_login_query = "";
        } else {
            $uid_query = "userid='{$uid}',";
            $time_login_query = "time_login=NOW(),";
        }

        db_query(
            "REPLACE INTO {$conf->db_table_sessions} SET 
                sid='{$sid}',
                $uid_query
                $time_login_query
                last_update=NOW(),
                ip='" . mysql_escape_string($_SERVER["REMOTE_ADDR"]) . "'"
        );
        $this->valid = true;
        $this->sid = $sid;
        $this->uid = $uid;

        return $sid;
    }
    // }}}
    // {{{ login2()
    function login2($user, $passwd) {
        global $conf;

        // test credentials
        $result = db_query(
            "SELECT id 
            FROM {$conf->db_table_users}
            WHERE 
                name='" . mysql_escape_string($user) . "' AND 
                passwd='" . md5("$user:" . $this->auth_realm . ":$passwd") . "'"
        );
        if (mysql_num_rows($result) > 0) {
            $data = mysql_fetch_assoc($result);

            return $this->register_session($data[userid]);
        } else {
            echo "error: wrong creditentials";
        }
    }
    // }}}
    // {{{ logout2()
    function logout2() {
        db_query(
            "DELETE FROM {$conf->db_table_sessions}
            WHERE sid='" . mysql_escape_string($this->get_sid()) . "'"
        );

        return true;
    }
    // }}}
    // {{{ get_auth_level()
    function get_auth_group($sid) {
        // @todo define auth level/groups
        return 0;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
