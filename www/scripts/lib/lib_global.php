<?php
/**
 * @file	lib_global.php
 *
 * Global Configuration Library
 *
 * This file defines the most important global classes.
 * It is needed in all scripts. And it defines a custom 
 * errorHandler with logging.
 *
 *
 * copyright (c) 2002-2004 Frank Hellenkamp [jonas.info@gmx.net]
 *
 * @author	Frank Hellenkamp [jonas.info@gmx.net]
 *
 * $Id: lib_global.php,v 1.61 2004/11/12 19:45:31 jonas Exp $
 */

/**
 * Reads the configuration file, and defines all global
 * configuration variables.
 */
class config {
	// {{{ constructor
	/**
	 * Configuration Object Constructor
	 *
	 * Reads the configuration file, sets standard values
	 * for configuration variables and adds lib and
	 * pear path to include path. 
	 *
	 * @param	$file (string) filename of ini-file
	 *
	 * @public
	 */
	function config($file) {
		if (file_exists('../settings/' . $file)) {
			$this->settingsPath = '../settings/';
		} else if (file_exists('../../settings/' . $file)) {
			$this->settingsPath = '../../settings/';
		} else if (file_exists('settings/' . $file)) {
			$this->settingsPath = 'settings/';
		} else {
			die("no '$file' found");
		}
		$inifile = parse_ini_file($this->settingsPath . $file, false);
		
		$this->app_name = 'dePage';
		$this->app_version = '0.9.16';

		$vars_to_set = array(
			'xml_version' => (string) '1.0',
			'home_url' => (string) 'tool.untitled.net',
				
			'url_page_scheme_intern' => (string) 'pageref',
			'url_lib_scheme_intern' => (string) 'libref',
				
			'date_format_UTC' => (string) 'Y/m/d H:i:s',
			);

		$vars_to_set_eval = array(
			'db_table_user' => (string) '%db_praefix%_auth_user',
			'db_table_sessions' => (string) '%db_praefix%_auth_sessions',
			'db_table_sessions_win' => (string) '%db_praefix%_auth_sessions_win',
			'db_table_updates' => (string) '%db_praefix%_auth_updates',
				
			'db_table_env' => (string) '%db_praefix%_env',
				
			'db_table_interface_text' => (string) '%db_praefix%_interface_text',
			'db_table_mediathumbs' => (string) '%db_praefix%_mediathumbs',
				
			'db_table_transform_cache' => (string) '%db_praefix%_transform_cache',
			'db_table_xml_elements' => (string) '%db_praefix%_xmldata_elements',
			'db_table_xml_cache' => (string) '%db_praefix%_xmldata_cache',
				
			'db_table_tasks' => (string) '%db_praefix%_tasks',
			'db_table_tasks_threads' => (string) '%db_praefix%_tasks_threads',

			'db_log' => (string) '%db_praefix%_log',
			);

		$vars_to_get = array(
			'project_interface' => (string) 'mysql2',

			'log_dateformat' => (string) '%a %d %b %h:%m:%s %Y',

			'pocket_use' => (bool) true,
			'pocket_addr' => (string) '0.0.0.0',
			'pocket_port' => (int) 19123,
			'pocket_buffersize' => (int) 256,
			'pocket_max_unused_time' => (int) 300,

			'thumb_width' => (int) 100,
			'thumb_height' => (int) 100,
			'thumb_quality' => (int) 75,
			'thumb_load_num' => (int) 6,
			
			'interface_autologin' => (bool) false,
			'interface_autologin_user' => (string) '',
			'interface_autologin_pass' => (string) '',
			'interface_autologin_project' => (string) '',
			'interface_dateformat' => (string) 'd.m.Y H:m',
				
			'backup_add_dev_backup' => (bool) false,
				
			'mail_interface' => (string) 'mail',
			'mail_sender_adress' => (string) '',
				
			'mail_smtp_host' => (string) '',
			'mail_smtp_port' => (int) 25,
			'mail_smtp_auth' => (bool) false,
			'mail_smtp_user' => (string) '',
			'mail_smtp_pass' => (string) '',
				
			'mail_sendmail_path' => (string) '',
			'mail_sendmail_args' => (string) '',

			'debug_msg_host' => (string) 'localhost',
			);

		$vars_to_get_eval = array(
			'path_base' => (string) '',
			'path_server_root' => (string) '',
			'path_projects' => (string) '%path_base%/projects',
			'path_phpcli' => (string) '',
			'path_imageMagick' => (string) '',
			'path_gif2png' => (string) '',
			'path_backup' => (string) '%path_base%/backup',

			'db_host' => (string) 'localhost',
			'db_database' => (string) '',
			'db_user' => (string) '',
			'db_pass' => (string) '',

			'db_praefix' => (string) 'tt',

			'file_notfound' => (string) '%path_server_root%%path_base%/interface/pics/file_notfound.swf',
			'file_thumbs' => (string) '%path_server_root%%path_base%/interface/pics/file_thumbs.swf',
				
			'interface_language' => (string) 'en',
			'interface_language_by_browser' => (bool) true,
			'interface_lib' => (string) 'lib_interface.swf',
			'interface_scheme' => (string) 'interface_standard.ini',
				
			'log_sql_do' => (bool) true,
			'log_debug_do' => (bool) true,
			'log_pocket_do' => (bool) true,
			'log_auth_do' => (bool) true,
			'log_task_do' => (bool) true,
			);
			
		// get and set Values
		foreach ($vars_to_set as $key => $val) {
			$this->$key = $this->_getSetValue($key, $vars_to_set);
		}
		
		foreach ($vars_to_set_eval as $key => $val) {
			$this->$key = $this->_getSetValue($key, $vars_to_set_eval);
		}
		
		foreach ($vars_to_get as $key => $val) {
			$this->$key = $this->_getIniValue($inifile, $key, $vars_to_get);
		}
		
		foreach ($vars_to_get_eval as $key => $val) {
			$this->$key = $this->_getIniValue($inifile, $key, $vars_to_get_eval);
		}
		
		// evaluate values
		foreach ($vars_to_set as $key => $val) {
			$this->$key = $this->_evalValue($key, $vars_to_set);
		}
		
		foreach ($vars_to_set_eval as $key => $val) {
			$this->$key = $this->_evalValue($key, $vars_to_set_eval, true);
		}
		
		foreach ($vars_to_get as $key => $val) {
			$this->$key = $this->_evalValue($key, $vars_to_get);
		}
		
		foreach ($vars_to_get_eval as $key => $val) {
			$this->$key = $this->_evalValue($key, $vars_to_get_eval, true);
		}
		
		// set global output entities available in templates 
		// (name | definition)
		$this->global_entities_values = Array(
			'nbsp' => '#160', 
			'auml' => '#228', 
			'ouml' => '#246', 
			'uuml' => '#252',
			'Auml' => '#196', 
			'Ouml' => '#214', 
			'Uuml' => '#220',
			'mdash' => '#8212', 
			'ndash' => '#8211', 
			'copy' => '#169',
			'euro' => '#8364',
			);
		$this->global_entities = array_keys($this->global_entities_values);
		
		// set global namespaces
		// (name | (ns | uri))
		$this->ns = Array(
			'xsl' => array(ns => 'xsl', uri => "http://www.w3.org/1999/XSL/Transform"),
			'rpc' => array(ns => 'rpc', uri => "http://{$this->home_url}/ns/rpc"),
			'database' => array(ns => 'db', uri => "http://{$this->home_url}/ns/database"),
			'project' => array(ns => 'proj', uri => "http://{$this->home_url}/ns/project"),
			'page' => array(ns => 'pg', uri => "http://{$this->home_url}/ns/page"),
			'section' => array(ns => 'sec', uri => "http://{$this->home_url}/ns/section"),
			'edit' => array(ns => 'edit', uri => "http://{$this->home_url}/ns/edit"),
			'backup' => array(ns => 'backup', uri => "http://{$this->home_url}/ns/backup"),
		);
		
		// available outputtypes
		// (name | (dynamic | extension))
		$this->output_file_types = Array(
			'html' => Array(
				'dynamic' => false, 
				'extension' => 'html'
				),
			'shtml' => Array(
				'dynamic' => true, 
				'extension' => 'shtml'
				),
			'text' => Array(
				'dynamic' => false, 
				'extension' => 'txt'
				),
			'php' => Array(
				'dynamic' => true, 
				'extension' => 'php'
				),
			);
		
		// available output-encodings
		$this->output_encodings = Array(
			'UTF-8',
			'ISO-8859-1',
			);
		
		// available output-methods
		$this->output_methods = Array(
			'html',
			'xhtml',
			'xml',
			'text',
			);
		
		// standard settings for environement-variables
		$this->env_vars_default = Array(
			'pocket_server_running' => (int) 0,
			);
		
		//change include_path
		if (substr(php_uname(), 0, 7) == 'Windows') {
			$path_divider = ';';
		} else {
			$path_divider = ':';
		}
		//$old_include_path = ini_get('inlude_path');
		//$new_include_path = $old_include_path;
		$new_include_path = '';
		$new_include_path .= $path_divider . $this->path_server_root . $this->path_base . 'scripts/lib';
		$new_include_path .= $path_divider . $this->path_server_root . $this->path_base . 'scripts/pear';
		
		ini_set('include_path', $new_include_path);
	}
	// }}}
	// {{{ get_language_by_browser()
	/**
	 * Gets the available languages from browser-header
	 *
	 * @public
	 */
	function get_language_by_browser() {
		$result = db_query("SHOW COLUMNS FROM $this->db_table_interface_text;");
		if ($result && ($num = mysql_num_rows($result)) > 0) {
			$available_languages = array();
			for ($i = 0; $i < $num; $i++) {
				$row = mysql_fetch_row($result);
				if ($row[0] != 'name') {
					$available_languages[] = $row[0];
				}
			}
			$browser_languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);	
			foreach ($browser_languages as $lang) {
				$actual_language_array = explode(';', $lang);
				$actual_language_array = explode('-', $actual_language_array[0]);
				$actual_language = trim($actual_language_array[0]);
				if (in_array($actual_language, $available_languages)) {
					$this->interface_language = $actual_language;
					break;
				}	
			}
		}
		mysql_free_result($result);
	}
	// }}}
	// {{{ getTexts()
	/**
	 * Get interface-texts from database
	 *
	 * @public
	 *
	 * @param	$lang (string) language of texts
	 * @param	$type (string) if $type != '' only texts are returned, which name starts with $type 
	 * @param	$codespecialchars (bool) tells whether special chars should be html encoded
	 *
	 * @return	texts (array) an array of texts
	 */
	function getTexts($lang, $type = '', $codespecialchars = true){
		$texts = array();
		
		if ($type != '') {
			$type_select = "WHERE name LIKE '$type%'";
		} else {
			$type_select = '';
		}
		
		$result = db_query(
			"SELECT name AS name, $lang AS text 
			FROM $this->db_table_interface_text 
			$type_select
			ORDER BY name"
		);
		if ($result && $numrows = mysql_num_rows($result)) {
			for ($i=0; $i < $numrows; $i++) {
				$row = mysql_fetch_assoc($result);
				if ($codespecialchars) {
					$texts[$row['name']] = htmlspecialchars($row['text']);
				} else {
					$texts[$row['name']] = $row['text'];
				}
			}
			mysql_free_result($result);
		}
		
		return $texts;
	}
	// }}}
	// {{{ getScheme()
	/**
	 * get interface-color-scheme
	 *
	 * @public
	 *
	 * @param	$schemefile (string) name of interface scheme ini-file
	 *
	 * @return	$scheme (array)
	 */
	function getScheme($schemefile){
		$scheme = parse_ini_file($this->settingsPath . $schemefile, false);

		return $scheme;
	}
	// }}}
	// {{{ _getIniValue()
	/**
	 * gets value from iniarray
	 *
	 * @private
	 *
	 * @param	&$iniarray (array)
	 * @param	$key (string)
	 * @param	&$standard_array (array)
	 *
	 * @return	$value (mixed)
	 */
	function _getIniValue(&$iniarray, $key, &$standard_array){
		$value = $iniarray[$key];
		if ($value == ''){
			$value = $standard_array[$key];
		}

		return $value;
	}
	// }}}
	// {{{ _getSetValue()
	/**
	 * sets value from iniarray
	 *
	 * @private
	 *
	 * @param	$key (string)
	 * @param	&$standard_array (array)
	 * @param	$parse (bool)
	 *
	 * @return	$value (mixed)
	 */
	function _getSetValue($key, &$standard_array, $parse = true){
		$value = $standard_array[$key];

		return $value;
	}
	// }}}
	// {{{ _evalValue()
	/**
	 * gets value and replaces internal values like \%foo\% 
	 *
	 * @private
	 *
	 * @param	$key (string)
	 * @param	&$type_array (array)
	 * @param	$parse (bool)
	 *
	 * @return	$value (mixed)
	 */
	function _evalValue($key, &$type_array, $parse = false){
		$value = $this->$key;

		if ($parse){
			while (($pos1 = strpos($value, '%')) !== false){
				$pos2 = strpos($value, '%', $pos1 + 1);
				$value = substr($value, 0, $pos1) . $this->_evalValue(substr($value, $pos1 + 1, $pos2 - $pos1 - 1), $null = null, true) . substr($value, $pos2 + 1, strlen($value));
			}
		}

		if ($type_array != null){
			settype($value, gettype($type_array[$key]));
		}

		return $value;
	}
	// }}}
	// {{{ get_tt_env()
	/**
	 * gets global environement variable from db
	 *
	 * @public
	 *
	 * @param	$name (string)
	 *
	 * @return	$value (mixed)
	 */
	function get_tt_env($name) {
		$result = db_query(
			"SELECT value 
			FROM $this->db_table_env 
			WHERE name='" . mysql_real_escape_string($name) . "'"
		);
		if ($result && mysql_num_rows($result) == 1) {
			$row = mysql_fetch_assoc($result);
			$value = $row['value'];
			if (isset($this->env_vars_default[$name])) {
				settype($value, gettype($this->env_vars_default[$name]));
			}
		} else if (isset($this->env_vars_default[$name])) {
			$value = $this->env_vars_default[$name];
		} else {
			$value = NULL;
		}
		mysql_free_result($result);
		
		return $value;
	}	
	// }}}
	// {{{ set_tt_env()
	/**
	 * sets global environement variable in db
	 *
	 * @public
	 *
	 * @param	$name (string)
	 * @param	$value (mixed)
	 */
	function set_tt_env($name, $value) {
		db_query(
			"REPLACE $this->db_table_env 
			SET name='$name', value='$value'"
		);
	}
	// }}}
	// {{{ dateUTC()
	/**
	 * gets date converted to UTC
	 *
	 * @public
	 *
	 * @param	$format (string)
	 * @param	$timestamp (int)
	 *
	 * @return	$date (string)
	 */
	function dateUTC($format, $timestamp = null) {
		if ($timestamp == null) {
			return date($format, (date('U') - date('Z')));
		} else {
			return date($format, (date('U', $timestamp) - date('Z', $timestamp)));
		}
	}
	// }}}
	// {{{ execInBackground()
	/**
	 * executes another php script in background
	 *
	 * script is executed as background task
	 * and function returns immediately to current script.
	 *
	 * @public
	 *
	 * @param	$path (string)
	 * @param	$script (string)
	 * @param	$args (string)
	 * @param	$start_low_priority (bool)
	 */
	function execInBackground($path, $script, $args = '', $start_low_priority = false) {
		$pro_param = '';
		
		if (file_exists($path . $script) || $path == '') {
			chdir($path);
			if (substr(php_uname(), 0, 7) == 'Windows') {
				if ($start_low_priority) {
					$prio_param = "/belownormal";
				}
				pclose(popen("start \"php subTask\" /min $prio_param \"" . str_replace("/", "\\", $this->path_phpcli) . "\" -f $script " . escapeshellarg($args), "r"));	
			} else {
				if ($start_low_priority) {
					$prio_param = "nice -10";
				}
				exec("$prio_param \"$this->path_phpcli\" -f $script " . escapeshellarg($args) . " > /dev/null &");	
			}
		}
	}
	// }}}
}

/**
 * connects to mySQL database
 */
class mySQLConnect {
	// {{{ constructor()
	/**
	 * constructor, connects to database
	 *
	 * @public
	 */
	function mySQLConnect(){
		global $conf;
		
		$this->logall = false;

		@$database = mysql_connect($conf->db_host, $conf->db_user, $conf->db_pass);
		if (!$database){
			die('Could not connect to database:<br>' . mysql_error());
		}
		if (!mysql_select_db($conf->db_database, $database)){
			die('Could not select database:<br>' . mysql_error());
		}
	}
	// }}}
	// {{{ query()
	/**
	 * executes SQL-query and returns result
	 *
	 * executes query and logs query and error,
	 * if query fails
	 *
	 * @public
	 *
	 * @param	$query (string)
	 *
	 * @return	$result (resource)
	 */
	function query($query){
		global $conf, $log;

		$result = mysql_query($query);
		if ($log->logall) {
			$log->add_entry($query, 'sql');
			echo(htmlentities($query) . ";<br>\n");
		}
		if (!$result){
			$log->add_entry($query, 'sql');
			$log->add_entry(mysql_errno() . ': ' . mysql_error(), 'sql');
			alert('error at ' . $query . ' - ' . mysql_errno() . ': ' . mysql_error());
		}
		return $result;
	}
	// }}}
}

/**
 * handles error and informational logging 
 */
class logObj{
	// {{{ variables
	/**
	 * database for logging
	 *
	 * @private
	 */
	var $log_db;
	// }}}

	// {{{ constructor
	/**
	 * logObj constructor
	 *
	 * @public
	 *
	 * @param	$log_sb (string)
	 */
	function logObj($log_db){
		$this->log_db = $log_db;
	}
	// }}}
	// {{{ add_entry()
	/**
	 * adds entry to log-db
	 *
	 * @public
	 *
	 * @param	$entry (string)
	 * @param	$type (string)
	 */
	function add_entry($entry, $type = 'debug'){
		global $conf;
		
		if ($type == 'sql' && $conf->log_sql_do) {
			$do_log = true; 
		} else if ($type == 'auth' && $conf->log_auth_do) {
			$do_log = true; 
		} else if ($type == 'pocket' && $conf->log_pocket_do) {
			$do_log = true; 
		} else if ($type == 'task' && $conf->log_task_do) {
			$do_log = true; 
		} else if ($conf->log_debug_do) {
			$type = 'debug';
			$do_log = true;	
		} else {
			$do_log = false; 
		}
		if ($do_log){
			if ($conf->path_base != "" && $conf->path_server_root != "") {
				error_log("[" . date("r") . "] $type: $entry\n", 3, $conf->path_server_root . $conf->path_base . "/logs/depage.log");
			} else {
				error_log("[" . date("r") . "] $type: $entry\n", 3, "../logs/depage.log");
			}
			/*
			$result = mysql_query(
				"INSERT INTO $this->log_db 
				SET entry='" . mysql_real_escape_string($entry) . "', type='$type'"
			);
			*/
		}
	}
	// }}}
	// {{{ add_varinfo()
	/**
	 * adds variable debug to log-db
	 *
	 * @public
	 *
	 * @param	$var (mixed)
	 * @param	$type (string)
	 */
	function add_varinfo(&$var, $type = 'debug') {
		ob_start();
		var_dump($var);
		$varinfo = ob_get_clean();

		$this->add_entry($varinfo, $type);
	}
	// }}}
	// {{{ get_entries()
	/**
	 * get log entries from db
	 *
	 * @public
	 *
	 * @param	$start (int)
	 * @param	$rownum (int)
	 * @param	$type (string)
	 *
	 * @return	$entries (array)
	 */
	function get_entries($start, $rownum, $type){
		global $conf;

		$entries = array();

		$result = mysql_query("SELECT DATE_FORMAT(times, '$conf->log_dateformat') AS times, entry FROM $this->log_db WHERE type='$type' ORDER BY id DESC LIMIT $start, $rownum");
		if ($result){
			$num = mysql_num_rows($result);
			for ($i = 0; $i < $num; $i++){
				$row = mysql_fetch_assoc($result);
				$entries[] = $row;
			}
		}
		mysql_free_result($result);

		return $entries;
	}
	// }}}
	// {{{ get_entrynum()
	/**
	 * gets number of log-entries of specific type
	 *
	 * @public
	 *
	 * @param	$type (string)
	 *
	 * @return	$num (int)
	 */
	function get_entrynum($type){
		$result = mysql_query("SELECT COUNT(*) AS NUM FROM $this->log_db WHERE type='$type'");
		$row = mysql_fetch_assoc($result);
		mysql_free_result($result);

		return $row['NUM'];
	}
	// }}}
	// {{{ clear()
	/**
	 * clears all entries of specific type
	 *
	 * @public
	 *
	 * @param	$type (string)
	 */
	function clear($type){
		$result = mysql_query("DELETE FROM $this->log_db WHERE type='$type'");
	}
	// }}}
}

/**
 * xml-rpc message handler
 *
 * @todo	make ttRpcFunc xmlrpc compatible
 */
class ttRpcMsgHandler{
	// {{{ variables
	var $funcs = array();
	var $return = array();
	// }}}

	// {{{ constructor
	/**
	 * constructor, sets function handling object
	 *
	 * @public
	 *
	 * @param	$funcObj (object)
	 */
	function ttRpcMsgHandler($funcObj = null) {
		$this->funcObj = &$funcObj;
	}
	// }}}
	// {{{ create_msg()
	/**
	 * creates rpc-message with given function object
	 *
	 * @public
	 *
	 * @param	$funcs (func-object | array of func-objects)
	 *
	 * @return	$xmlMsgData (string)
	 */
	function create_msg($funcs) {
		global $conf;
		
		$data = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
		$data .= "<{$conf->ns['rpc']['ns']}:msg";
		
		foreach($conf->ns as $ns_key => $ns) {
			$data .= " xmlns:{$ns['ns']}=\"{$ns['uri']}\"";
		}
		$data .= ">";
		if (is_array($funcs)) {
			foreach ($funcs as $func) {
				if (is_object($func)) {
					$data .= $func->create_msg_func();
				} else if (is_string($func)) {
					$data .= $func;
				}
			}
		} else if (is_object($funcs)) {
			$data .= $funcs->create_msg_func();
		} else if (is_string($funcs)) {
			$data .= $funcs;	
		}
		$data .= "</{$conf->ns['rpc']['ns']}:msg>";
		
		return($data);
	}
	// }}}
	// {{{ parse_msg()
	/**
	 * parses rpc-xml-message
	 *
	 * @public
	 *
	 * @param	$xmldata (string)
	 *
	 * @return	$func_objects (array)
	 */
	function parse_msg($xmldata){
		global $conf;
		
		$funcs = Array();

		$error = false;

		if (!$xmlobj = @domxml_open_mem($xmldata)) {
			trigger_error("error in rpc:msg message:\n'$xmldata'\n");
		} else {
			$xmlctx = xpath_new_context($xmlobj);
			foreach($conf->ns as $ns_key => $ns) {
				xpath_register_ns($xmlctx, $ns['ns'], $ns['uri']);
			}
			$xfetch_func = xpath_eval($xmlctx, "/{$conf->ns['rpc']['ns']}:msg/{$conf->ns['rpc']['ns']}:func");
			for ($i = 0; $i < count($xfetch_func->nodeset); $i++){
				if ($xfetch_func->nodeset[$i]->type == XML_ELEMENT_NODE){
					$func = $xfetch_func->nodeset[$i]->get_attribute('name');
					if (method_exists($this->funcObj, $func)) {
						$xfetch_param = xpath_eval($xmlctx, "./{$conf->ns['rpc']['ns']}:param", $xfetch_func->nodeset[$i]);
						$args = Array();
						for ($j = 0; $j < count($xfetch_param->nodeset); $j++) {
							if (isset($xfetch_param->nodeset[$j]) && $xfetch_param->nodeset[$j]->has_child_nodes()){
								$argnode = $xfetch_param->nodeset[$j]->first_child();
								while($argnode !== null) {
									$args[$xfetch_param->nodeset[$j]->get_attribute('name')] .= $xmlobj->dump_node($argnode, false);
									
									$argnode = $argnode->next_sibling();
								}
							}
						}
						$funcs[] = new ttRpcFunc($func, $args, &$this->funcObj);
					}
				}
			}
			$xmlobj->free();
				
			return $funcs;
		}
	}
	// }}}
}

/**
 * creates new and handles given functions
 *
 * @todo	make ttRpcFunc xmlrpc compatible
 */
class ttRpcFunc {
	// {{{ variables
	var $name;
	var $args;
	var $invisibleFuncs = Array(
		'send_message_to_clients',
		'send_message_to_client',
		'keepAlive',
	);
	// }}}
	
	// {{{ constructor
	/**
	 * constructor, creates new rpc-func-object
	 *
	 * @public
	 *
	 * @param	$name (string)
	 * @param	$args (array)
	 * @param	$funcObj (object)
	 */
	function ttRpcFunc($name, $args = Array(), $funcObj = null) {
		$this->name = $name;
		$this->args = $args;
		$this->funcObj = &$funcObj;
	}
	// }}}
	// {{{ create_msg_func()
	/**
	 * creates message by func-obj
	 *
	 * @public
	 *
	 * @return	(string) $xml_data
	 */
	function create_msg_func() {
		global $conf;

		$data = "<{$conf->ns['rpc']['ns']}:func name=\"$this->name\">";
		foreach ($this->args as $key => $val) {
			$data .= "<{$conf->ns['rpc']['ns']}:param name=\"$key\">";
			$data .= $val;
			$data .= "</{$conf->ns['rpc']['ns']}:param>";
		}
		$data .= "</{$conf->ns['rpc']['ns']}:func>";

		return $data;
	}
	// }}}
	// {{{ add_args()
	/**
	 * adds new argument to argument list
	 *
	 * @public
	 *
	 * @param	$args (array)
	 */
	function add_args($args) {
		$this->args = array_merge($this->args, $args);
	}
	// }}}
	// {{{ call()
	/**
	 * calls function in func-obj with given arguments
	 *
	 * @public
	 *
	 * @return	$value (mixed)
	 */
	function call() {
		global $conf;
		
		$val = call_user_func_array(array(&$this->funcObj, $this->name), Array($this->args));
		if (php_sapi_name() == 'cli' && !in_array($this->name, $this->invisibleFuncs)) {
			echo("[" . $conf->dateUTC($conf->date_format_UTC) . "] $this->name called\n");
		}
		
		return $val;
	}
	// }}}
}

/**
 * rpc function parent class
 *
 * this class is the parent class for all function objects
 * called remotely via phpConnect or pocketConnect.
 * until now, this class has no functions but this will change
 * sooner or later
 */
class rpc_functions_class {

}

// functions
// {{{ db_query()
/**
 * executes sql-query
 *
 * @relates mySQLConnect
 *
 * @return	$result (resource)
 */
function db_query($query){
	global $db;

	return $db->query($query);
}
// }}}
// {{{ alert()	
/**
 * alert a local message on server
 *
 * executes under WinNT/2000/XP only with
 * message service enabled
 *
 * @param	$msg (string)
 */
function alert($msg){
	global $conf;

	if (substr(php_uname(), 0, 7) == 'Windows'){
		//$ip = getenv('REMOTE_ADDR');
		//$ip = $_SERVER['HOSTNAME'];
		$ip = $conf->debug_msg_host;

		pclose(popen("start net send $ip \"" . escapeshellarg(str_replace(array("\n", "\t", "\r", "\0"), array("", "", "", ""), $msg)) . "\"", "r"));	
	}
}
// }}}
// {{{ getmicrotime()
/**
 * gets time in microseconds
 *
 * @return	$msecs (float)
 */
function getmicrotime(){ 
	list($usec, $sec) = explode(' ',microtime()); 
	return ((float)$usec + (float)$sec); 
} 
// }}}
// {{{ start_timer()
/**
 * starts timer to measure time
 */
function start_timer() {
	$GLOBALS['time_start'] = getmicrotime(); 
}
// }}}
// {{{ stop_timer()
/**
 * prints time needed since start_timer called
 *
 * @param	$text (string)
 */
function stop_timer($text = '') {
	global $conf, $log;
	
	$GLOBALS['time_end'] = getmicrotime(); 
	$exectime = $GLOBALS['time_end'] - $GLOBALS['time_start'];
	echo("needed " . $exectime . " for '" . $text . "'<br>\n");
	//$log->add_entry("needed " . $exectime . " for '" . $text . "'", "debug");
}
// }}}
// {{{ usleepw()
/**
 * simulates usleep for systems, where it isnt supported
 *
 * @param	$delay (int)
 */
function usleepw($delay) {
	$UNUSED_PORT = 31238; //make sure this port isn't being used on your server
	@fsockopen("tcp://localhost", $UNUSED_PORT, $errno, $errstr, $delay);
}
// }}}
// {{{ headerNoCache()
/**
 * outputs http header, which makes sure that content would be
 * generate by server everytime it is requested
 *
 * it must be called before any other output is sent to client
 */
function headerNoCache() {
	@header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); 
	@header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); 
	@header('Cache-Control: no-store, no-cache, must-revalidate'); 
	@header('Cache-Control: post-check=0, pre-check=0', false); 
	@header('Pragma: no-cache'); 
}
// }}}
// {{{ headerType()
/**
 * outputs http header, which makes sure that content would be
 * generate by server everytime it is requested
 *
 * it must be called before any other output is sent to client
 */
function headerType($type = 'text/html', $charset = 'UTF-8') {
	@header("Content-type: $type; charset=$charset"); 
}
// }}}
// {{{ errorHandler()
/**
 * errorHandling function, which logs errors to db
 *
 * @param	$errno (int)
 * @param	$errmsg (string)
 * @param	$filename (string)
 * @param	$linenum (-)
 * @param	$vars (-)
 */
function errorHandler ($errno, $errmsg, $filename, $linenum, $vars) { 
	global $conf, $log;

    if ($errno != E_NOTICE) {
		$hide_errors = Array(
			"socket_read",
		);
		
		if (!in_array(substr($errmsg, 0, strpos($errmsg, "()")), $hide_errors)) {
			$errtype = array ( 
				E_ERROR => 'Error',
				E_WARNING => 'Warning',
				E_NOTICE => 'Notice',
				E_USER_ERROR => 'User Error',
				E_USER_WARNING => 'User Warning',
				E_USER_NOTICE => 'User Notice',
			); 
			$errstr = "ERROR [$errno | " . $errtype[$errno] . "]: $errmsg in $filename at line $linenum";
			
			error_log($errstr);
			$log->add_entry($errstr, "debug");
		}
	}
}
// }}}
// {{{ die_error()
/**
 * exits script with custom message that an error occured
 *
 * @param	$msg (string)
 * @param	$redirect_url (string)
 */
function die_error($msg, $redirect_url = null) { 
	global $conf;
	 
	if (!is_callable('htmlout::echoMsg')) {
		require_once('lib_html.php');
	}
	
	$settings = $conf->getScheme($conf->interface_scheme); 
	echo("<html><head>" . ($redirect_url != null ? "<meta http-equiv=\"refresh\" content=\"3; URL=" . $redirect_url . "\">" : "")); 
	htmlout::echoStyleSheet(); 
	echo("</head><body bgcolor=\"" . $settings['color_background'] . "\">"); 
	htmlout::echoMsg("Error", $msg); 
	echo("</body></html>"); 
	die(); 
}
// }}}

/**
 * Main
 */

//init configuration object
$conf = &new config('main.ini');
//init database object
$db = &new MySQLConnect;
//init log object
$log = &new logObj($conf->db_log);

if ($conf->interface_language_by_browser) {
	$conf->get_language_by_browser();
}

if (IS_IN_CONTOOL !== true || !defined('IS_IN_CONTOOL')){
	die_error('you are not allowed to do this!');
}

//error_reporting(0);
set_error_handler('errorHandler');

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
