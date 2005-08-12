<?php 
/**
 * @file	lib_auth.php
 *
 * User and Session Handling Library
 *
 * This file contains classes for user and session
 * handling. It also contains functions for locking
 * actual edit pages.
 *
 *
 * copyright (c) 2002-2004 Frank Hellenkamp [jonas.info@gmx.net]
 *
 * @author	Frank Hellenkamp [jonas.info@gmx.net]
 *
 * $Id: lib_user.php,v 1.13 2004/05/26 14:49:05 jonas Exp $
 */

if (!function_exists('die_error')) require_once('lib_global.php');

/**
 * contains functions for handling user authentication
 * and session handling.
 */
class ttUser{
	var $sid, $wid, $uid;
	/**
	 * generates a uniqid, used for sessions.
	 *
	 * @public
	 *
	 * @return	$id (string) new id
	 */
	function uniqid16() {
		return uniqid(dechex(rand(256, 4095)));
	}

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
	/**
	 * logs user in and sets a new sid for this user.
	 *
	 * @public
	 *
	 * @param	$user (string) user name
	 * @param	$pass (string) password of user
	 * @param	$project (string) project name to log into
	 * @param	$ip (string) ip from where user logs in
	 *
	 * @return	$sid (string) new session id or false if login failed
	 */
	function login($user, $pass, $project, $ip) {
		global $conf, $log;

		$result = db_query(
			"SELECT * 
			FROM $conf->db_table_user 
			WHERE name='$user' and pass='" . md5($pass) . "'"
		);
		if (mysql_num_rows($result) == 1) {
			$data = mysql_fetch_assoc($result);
			$sid = $this->uniqid16();
			db_query(
				"INSERT 
				INTO $conf->db_table_sessions 
				SET sid='$sid', userid='" . $data['id'] . "', project='$project', ip='$ip'"
			);
			$log->add_entry("'" . $user . "' has logged in Project '" . $project . "' from '" . $ip . "'", "auth");
			$retval = $sid;
		} else {
			$log->add_entry("unauthorisized: '" . $ip . "' tried to log in Project '" . $project . "' as '" . $user . "'", "auth");
			$retval = false;
		}
		mysql_free_result($result);
		
		return $retval;
	}

	/**
	 * logs user out
	 *
	 * @public
	 *
	 * @param	$sid (string) session id
	 */
	function logout($sid) {
		global $conf, $log;
		
		$result = db_query(
			"SELECT * 
			FROM $conf->db_table_sessions AS session, $conf->db_table_user AS user 
			WHERE session.sid='$sid' and session.userid = user.id"
		);
		if (mysql_num_rows($result) > 0) {
			$data = mysql_fetch_assoc($result);
			
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

			$log->add_entry("'{$data['name']}' has logged out Project '{$data['project']}' from '{$data['ip']}'", "auth");
		}
		mysql_free_result($result);
	}

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

	/**
	 * registers a new window (pocket connection) to a user.
	 *
	 * @public
	 *
	 * @param	$sid (string) session id
	 * @param	$ip (string) ip of user
	 * @param	$port (int) port from which the user logs in
	 * @param	$type (string) type of window to be registered
	 *
	 * @return	$wid (string) new session window id, or false on failure
	 */
	function register_window($sid, $ip, $port, $type){
		global $conf, $log;

		$result = db_query(
			"SELECT * 
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

			$log->add_entry("'{$data['name']}' has registered $type-window from '$ip:$port'", "auth");

			$retval = $wid;
		} else {
			$log->add_entry("unauthorisized: '$ip:$port' tried to register $type-window", "auth");

			$retval = false;
		}
		mysql_free_result($result);
		
		return $retval;
	}

	/**
	 * unregisters a window. if thi was the last window of this user
	 * the user will also logged out.
	 *
	 * @public
	 *
	 * @param	$ip (string) ip of connection
	 * @param	$port (int) port of connection
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
			mysql_free_result($result);
			
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
			mysql_free_result($result);
		}	
	}

	/**
	 * test, if the combination of sid, wid and ip belongs to
	 * an already logged in user and a registered window.
	 *
	 * @public
	 *
	 * @param	$sid (string) session id
	 * @param	$wid (string) session window id
	 * @param	$ip (string) ip from where user is connected
	 * @param	$neededlevel (int) authentication level. if this
	 *			parameter is given, the function tests also, if 
	 *			user has the given user level or not.
	 *
	 * @return	$isValid (bool) true, if user is valid, false otherwise
	 */
	function is_valid_user($sid, $wid, $ip, $neededlevel = 0){
		global $conf;
		
		$result = db_query(
			"SELECT * 
			FROM $conf->db_table_sessions_win AS session_win, $conf->db_table_sessions AS session 
			WHERE session.sid='$sid' and  session.ip='$ip' and session_win.wid='$wid' and session.sid = session_win.sid"
		);
		if (mysql_num_rows($result) == 1) {
			$row = mysql_fetch_assoc($result);
			mysql_free_result($result);
			
			$this->sid = $sid;
			$this->wid = $wid;
			$this->uid = $row['userid'];
			$project = $row['project'];
			if ($neededlevel == 0) {
				$retVal = $project;
			} else {
				$result = db_query(
					"SELECT * 
					FROM $conf->db_table_user AS user, $conf->db_table_sessions AS session 
					WHERE session.sid='$sid' and user.id = session.userid and user.level <= $neededlevel"
				);
				if (mysql_num_rows($result) == 1) {
					$retVal = $project;
				} else {
					$retVal = false;
				}
				mysql_free_result($result);
			}
		} else {
			$retVal = false;
		}
		return $retVal;
	}

	/**
	 * checks, wether someone is logged in from given ip and port.
	 * if project is given also, it will be checked, if this user
	 * logged in to this project, too.
	 *
	 * @public
	 *
	 * @param	$host (string) ip of connection
	 * @param	$port (int) port of connection
	 * @param	$project (string) name of project
	 *
	 * @return	$loggedIn (bool) true if this connection is
	 *			still logged in, false otherwise.
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
		mysql_free_result($result);
		
		return $retVal;
	}
	
	/**
	 * gets project, the user with given sid is logged into.
	 *
	 * @public
	 *
	 * @param	$sid (string) session id
	 *
	 * @return	$project (string) project name or false, if sid
	 *			is unknown.
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
		mysql_free_result($result);
		
		return $retVal;
	}
	
	/**
	 * gets id of user by its sid.
	 *
	 * @public
	 *
	 * @param	Sid (string) session id
	 *
	 * @return	$userid (int) id of user, or false, if
	 *			sid is unknown.
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
		mysql_free_result($result);
		
		return $retVal;
	}
	
	/**
	 * gets authentication level of user by sid.
	 *
	 * @public
	 *
	 * @param	$sid (string) session id
	 * 
	 * @return	$level (int) authentication level of logged in user.
	 */
	function get_level_by_sid($sid) {
		global $conf;
		
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
		mysql_free_result($result);
		
		return $retVal;
	}
	
	/**
	 * gets ip and port of a connection by sid and wid.
	 *
	 * @public
	 *
	 * @param	$sid (string) session id
	 * @param	$wid (string) session window id
	 *
	 * @return	$connection (array) array of connection sesstings of user.\n
	 *			'ip' contains ip of connection.\n
	 *			'port contains the port.
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
		mysql_free_result($result);
		
		return $retVal;
	}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
