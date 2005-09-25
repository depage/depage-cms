<?php
/**
 * @file	lib_pocket_server.php
 *
 * Pocket Server Library
 *
 * Pocket Server stands for phpSocket Server, a xml-rpc server
 * which executes in the background and provides functions for 
 * realtime communications with flash or other supported plugins
 * and clients.
 * It also contains classes for an Pocket Client to connect
 * to the Pocket Server by PHP directly.
 * The Pocket Server can call a specific callback func everytime
 * a message-handling loop is finished.
 *
 *
 * copyright (c) 2002-2004 Frank Hellenkamp [jonas.info@gmx.net]
 *
 * @author	Frank Hellenkamp [jonas.info@gmx.net]
 *
 * $Id: lib_pocket_server.php,v 1.34 2004/11/12 19:45:31 jonas Exp $
 */

if (!function_exists('die_error')) require_once('lib_global.php');
require_once('lib_auth.php');
require_once('lib_tasks.php');

/**
 * main class for creating a tcp xml rpc remote server,
 * used for direct communication between the flash interface
 * and the server backend.
 */
class PocketServer{
	// {{{ variables
	var $host;
	var $port;
	var $sock;
	var $connhosts = array();
	var $sockFuncs = array();
	var $userFuncs = array();
	var $bufferSize;
	var $msgHandler;
	var $running = false;
	var $initsuccess = true;
	var $maxUnusedTime = 0;
	var $force_shutdown = false;
	// }}}

	// {{{ constructor
	/**
	 * constructor, sets needed options to start and specifies the
	 * message-handling object, which gives the functionality.
	 *
	 * @public
	 *
	 * @param	$host (string) ip to listen to. set to 0.0.0.0 to listen to
	 *			all incoming networks.
	 * @param	$port (int) port to listen to.
	 * @param	$bufferSize (int) size of buffer. attention: may be restricted
	 *			depending on operating system and its settings.
	 * @param	$maxUnusedTime (int) time in seconds, after which the server shuts
	 *			itself down, if no connections where made.
	 * @param	$msgHandler (rpcfunchandler) instance of an rpc function handler
	 *			which inherits all callable functions.
	 */
	function PocketServer($host, $port, $bufferSize, $maxUnusedTime, &$msgHandler) {
		$this->host = $host;
		$this->port = $port;
		$this->bufferSize = $bufferSize;
		$this->maxUnusedTime = $maxUnusedTime;
		$this->msgHandler = &$msgHandler;
	}
	// }}}
	// {{{ init()
	/**
	 * initializes pocket server. has to be called before listening.
	 *
	 * @public
	 */
	function init(){
		global $conf;

		if ($conf->pocket_use) {
			$this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if ($this->sock === false){
				$this->serverMessage("Could not create a socket. $this->sock");
				$this->initsuccess = false;
			}
			//socket_setoption($this->sock, SOL_SOCKET, SO_REUSEADDR, 1);
			$ret = @socket_bind ($this->sock, $this->host, $this->port);
			if (!$ret) {
				$this->serverMessage("Could not bind socket: $this->host:$this->port. Is Pocket-Server still running?");
				$this->initsuccess = false;
			} else {
				$this->serverMessage("Pocket-Server started on $this->host:$this->port.");
			}

			$ret = socket_listen($this->sock, 100);
			if (!$ret){
				$this->serverMessage('Listen failed.' . $ret);
				$this->initsuccess = false;
			}
		} else {
			$this->serverMessage("Pocket-Server started without listening.");
		}
	}
	// }}}
	// {{{ serverShutdown()
	/**
	 * tells server to shutdown after next listening loop.
	 * 
	 * @public
	 */
	function serverShutdown() {
		$this->force_shutdown = true;
	}
	// }}}
	// {{{ serverMessage()
	/**
	 * prints out server message and logs to server log-file or database.
	 * 
	 * @public
	 *
	 * @param	$message (string) message to print
	 */
	function serverMessage($message){
		global $conf, $log;
		
		echo("[" . $conf->dateUTC($conf->date_format_UTC) . "] " . $message . "\n");
		$log->add_entry($message, 'pocket');
	}
	// }}}
	// {{{ sendMessageToAll()
	/**
	 * sends a message to all connected clients
	 *
	 * @public
	 *
	 * @param	$message (string) message to send
	 */
	function sendMessageToAll($message){
		for ($i = 0; $i < count($this->connhosts); $i++){
			$this->connhosts[$i]->sendMessage($message);
		}
	}
	// }}}
	// {{{ sendMessageTo()
	/**
	 * sends a message to a specific client
	 *
	 * @public
	 *
	 * @param	$ip (string) ip of connected client
	 * @param	$port (int) port of connected client
	 * @param	$message (string) message to send
	 *
	 * @todo	implement this function
	 */
	function sendMessageTo($ip, $port, $message){
		/*
		for ($i = 0; $i < count($this->connhosts); $i++){
			$this->connhosts[$i]->sendMessage($message);
		}
		*/
	}
	// }}}
	// {{{ startListen()
	/**
	 * starts the server to listen for incoming connections.
	 * runs till it would be told to shutdown or the $maxUnusedTime
	 * has reached.
	 *
	 * @public
	 *
	 * @param	$callbackFunc (function) this function will be called
	 *			after every listening loop. used for using background
	 *			tasks between handling remote functions.
	 */
	function startListen($callbackFunc = null){
		global $conf, $project, $log;
		
		$user = new ttUser();
		if ($this->initsuccess) {
			set_time_limit(0);
			$conf->set_tt_env('pocket_server_running', 1);
			$this->running = true;
			$this->force_shutdown = false;
			$lasttime = time();

			$usercount = $project->user->get_loggedin_count();
			while (($this->running || $usercount > 0) && !$this->force_shutdown){
				if ($conf->pocket_use) {
					$actConnections = array();

					$actConnections[0] = $this->sock;
					for ($i = 0; $i < count($this->connhosts); $i++){
						$actConnections[$i + 1] = $this->connhosts[$i]->connection;
					} 
					if (socket_select($actConnections, $w = null, $e = null, 1) === false){
						$this->running = false;
					} 
					// add new connections
					if (in_array($this->sock, $actConnections)){
						$this->connhosts[] = new PocketConnection($this, $this->sock);
					} 
					// read open connections
					for ($i = 0; $i < count($this->connhosts); $i++){
						if (in_array($this->connhosts[$i]->connection, $actConnections)){
							$sockFunc = $this->connhosts[$i]->readMessage();
							if ($sockFunc != null){
								$this->sockFuncs[] = $sockFunc;
							}
						}
					} 
					// delete closed connections
					for ($i = 0; $i < count($this->connhosts); $i++){
						if ($this->connhosts[$i]->isToDelete === true){
							socket_close($this->connhosts[$i]->connection);
							$user->unregister_window($this->connhosts[$i]->host, $this->connhosts[$i]->port);
							$this->connhosts[$i] = null;
							array_splice($this->connhosts, $i, 1);
						}
					} 
					// executing registered functions
					for ($i = 0; $i < count($this->sockFuncs); $i++){
						$this->sockFuncs[$i]->execute();
					}
					$this->sockFuncs = array(); 
				} else {
					sleep(2);
				}
				
				// call callBackFunc
				$may_shutdown = true;
				if (is_callable($callbackFunc)) {
					$may_shutdown = $may_shutdown && call_user_func($callbackFunc, &$this);
				}
				
				$usercount = $project->user->get_loggedin_count();
				// test keep running
				if ($this->running && (count($this->connhosts) > 0 || $usercount > 0) && !may_shutdown) {
					$lasttime = time();
					$this->running = true;
				} else if (count($this->connhosts) == 0) {
					if ($this->maxUnusedTime > 0 && time() - $lasttime >= $this->maxUnusedTime) {
						$this->running = false;
					}
				}
				
				$status = $conf->get_tt_env('pocket_server_running');
				if ($status == -1){
					$this->running = false;
				} else if ($status == -2) {
					$this->running = false;
					$this->force_shutdown = true;
				}
			}
			if ($conf->pocket_use) {
				for ($i = 0; $i < count($this->connhosts); $i++){
					socket_close($this->connhosts[$i]->connection);
				}
				socket_close($this->sock);
			}
			$this->serverMessage('Pocket-Server Shutdown.');
			$conf->set_tt_env('pocket_server_running', 0);

			$user->clearsessions();
		}
	}
	// }}}
}

/**
 * handles connections made to server. for every connection, that is made
 * on PocketConnection-Object will be initialized.
 */
class PocketConnection {
	// {{{ variables
	var $serverObj;
	var $connection;
	var $data = '';
	var $host, $port;
	var $isToDelete = false;
	// }}}

	// {{{ constructor
	/**
	 * constructor, adds reference to serverObject and logs the connection,
	 * that is made.
	 *
	 * @public
	 *
	 * @param	$serverObj (object) serverObject which handles connections
	 * @param	$connection (ressource) connection ressource of server
	 */
	function PocketConnection(&$serverObj, &$connection){
		$this->serverObj = &$serverObj;

		$this->connection = socket_accept($connection);
		socket_setopt($this->connection, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_getpeername($this->connection, $this->host, $this->port);
		
		if ($this->host == '127.0.0.1') {
			//$this->serverObj->serverMessage('local connection on ' . $this->port . '.');
		} else {
			$this->serverObj->serverMessage('New connection from ' . $this->host . ':' . $this->port . '.');
		}
	}
	// }}}
	// {{{ sendMessage()
	/**
	 * sends a message to the client.
	 *
	 * @public
	 *
	 * @param	$message (string) message to send
	 */
	function sendMessage($message){
		$message .= "\0";
		while (strlen($message) > 0) {
			$part = substr($message, 0, $this->serverObj->bufferSize);
			$message = substr($message, $this->serverObj->bufferSize);

			if (@socket_write($this->connection, $part) === false) {
				$this->serverObj->serverMessage('write error on ' . $this->host . ':' . $this->port . '.');
			}
		}
	}

	/**
	 * reads part of the message from the connection buffer.
	 * if the data is an empty string, connectionObject will
	 * be marked for deletion, because connection has closed.
	 *
	 * @private
	 */
	function _readMessagePart(){
		$data = @socket_read($this->connection, $this->serverObj->bufferSize, PHP_BINARY_READ);
		if ($data == ''){
			if ($this->host != '127.0.0.1') {
				$this->serverObj->serverMessage('Connection closed from ' . $this->host . ':' . $this->port . '.');
			}
			$this->isToDelete = true;
		} else if ($data === false) {
			$data = '';
		}

		return $data;
	}
	// }}}
	// {{{ readMessage()
	/**
	 * reads part of message from client. if message is ended by \0,
	 * a pocketFunctionObject will be created to call the functions.
	 *
	 * @public
	 *
	 * @return	$pocketFunction (object) pocketFunctionObject, if message
	 *			is transferred completely, else null.
	 */
	function readMessage(){
		$buf = $this->_readMessagePart();
		$this->data .= $buf;
			
		if (strpos($buf, "\0") !== false) {
			$parts = explode("\0", $this->data, 2);
			$this->data = $parts[1];
			
			return new PocketFunction($parts[0], $this);
		} else {
			return null;
		}
	}
	// }}}
}

/**
 * parses and calls remotely called functions
 */
class PocketFunction{
	// {{{ variables
	var $serverObj;
	var $connectionObj;
	var $msg;
	// }}}

	// {{{ constructor
	/**
	 * constructor
	 *
	 * @public
	 *
	 * @param	$msg (string) message part, send from client
	 * @param	$connectionObj (object) reference to connection obj
	 */
	function PocketFunction($msg, &$connectionObj){
		$this->serverObj = &$connectionObj->serverObj;
		$this->connectionObj = &$connectionObj;
		
		$this->msg = $msg;
	}
	// }}}
	// {{{ execute()
	/**
	 * parses message and executes functions given 
	 *
	 * @public
	 */
	function execute(){ 
		$funcs = $this->serverObj->msgHandler->parse_msg($this->msg);
		
		for ($i = 0; $i < count($funcs); $i++) {
			$funcs[$i]->add_args(array('serverObj' => &$this->serverObj, 'connectionObj' => &$this->connectionObj));
			$funcs[$i]->call();			
		}
	}
	// }}}
}

/**
 * class to create a tcp client to connect to pocket server.
 * usually used for creating local messages between scripts.
 */
class PocketClient {
	// {{{ variables
	var $host;
	var $port;
	var $fp;
	// }}}
	
	// {{{ constructor()
	/**
	 * constructor, sets init parameter
	 *
	 * @public
	 *
	 * @param	$host (string) hostname or ip to connect to
	 * @param	$port (int) port to connect to
	 */
	function PocketClient($host, $port) {
		$this->host = $host;
		$this->port = $port;	
		$this->connected = false;
	}
	// }}}
	// {{{ connect()
	/**
	 * opens connection to server
	 *
	 * @public
	 *
	 * @return	$connected (bool) true on succes, false on failure
	 */
	function connect() {
		global $conf;

		if ($conf->pocket_use) {
			$this->fp = fsockopen($this->host, $this->port, &$errno , &$errstr, 1);
			if (!$this->fp) {
				return false;
			} else {
				$this->connected = true;
				return true;
			}
		} else {
			return false;
		}
	}
	// }}}
	// {{{ send()
	/**
	 * sends a remote function message to server, to be executed there
	 *
	 * @public
	 *
	 * @param	$func (rpcFuncObj) function to call on server
	 */
	function send($func) {
		if ($this->connected) {
			$msgHandler = new ttRpcMsgHandler();
			$msg = $msgHandler->create_msg($func);
			fwrite($this->fp, $msg . "\0");
		}
	}
	// }}}
	// {{{ send_to_clients()
	/**
	 * tells the server to resend given function message to connected
	 * clients.
	 *
	 * @public
	 *
	 * @param	$func (rpcFuncObj) function to call on clients
	 * @param	$project_name (string) if given, message will only be send
	 *			to clients, that are connected to given project.
	 */
	function send_to_clients($func, $project_name = null, $info = '') {
		global $project;

		$users = $project->user->get_loggedin_nonpocket();
		foreach ($users as $act_sid => $act_project) {
			if ($project_name == $act_project || $project_name == null) {
				$project->user->add_update($act_sid, $func->create_msg_func());
			}
		}

		if ($project_name != null) {
			$func = new ttRpcFunc('send_message_to_clients', array('message' => $func->create_msg_func(), 'project' => $project_name, 'info' => $info));
		} else {
			$func = new ttRpcFunc('send_message_to_clients', array('message' => $func->create_msg_func(), 'info' => $info));
		}
		$this->send($func);
	}
	// }}}
	// {{{ send_to_client()
	/**
	 * tells the server to resend given function message to a client,
	 * that is logged in under given sid and wid.
	 *
	 * @public
	 *
	 * @param	$func (rpcFuncObj) function to call on client
	 * @param	$sid (string) session id of client
	 * @param	$wid (string) session window id of client
	 */
	function send_to_client($func, $sid, $wid) {
		global $project;

		$users = $project->user->get_loggedin_nonpocket();
		foreach ($users as $act_sid => $act_project) {
			if ($sid == $act_sid) {
				$project->user->add_update($sid, $func->create_msg_func());
			}
		}

		$func = new ttRpcFunc('send_message_to_client', array('message' => $func->create_msg_func(), 'sid' => $sid, 'wid' => $wid));
		
		$this->send($func);
	}
	// }}}
	// {{{ close()
	/**
	 * closes connection to the server
	 *
	 * @public
	 */
	function close() {
		if ($this->connected) {
			$this->connected = false;
			fclose($this->fp);	
		}
	}
	// }}}
}


/**
 * defines standard function, for server to give simple needed
 * functionality. may be extended for extended use.
 */
class rpc_pocketConnect_default_functions extends rpc_functions_class {
	// {{{ shutdown()
	/**
	 * shuts server down, after actual listening loop
	 *
	 * @public
	 *
	 * @param	$args['sid'] (string) session id
	 * @param	$args['wid'] (string) session window id
	 */ 
	function shutdown($args) {
		global $conf;
		
		$user = new ttUser();
		if ($user->is_valid_user($args['sid'], $args['wid'], $args['connectionObj']->host, 1)) {
			$args['serverObj']->serverShutdown();
		}
	}
	// }}}
	// {{{ login()
	/**
	 * logs user in to server and registers a window to user. so a message will 
	 * be send to client with new created sid, wid and current user_level, or 
	 * an error, if login failed.
	 *
	 * @public
	 *
	 * @param	$args['user'] (string) user name
	 * @param	$args['pass'] (string) password (attention: unencypted!!!!)
	 * @param	$args['project'] (string) name of project to log in to
	 */ 
	function login($args) {
		global $conf;
		
		$user = new ttUser();
		$sid = $user->login($args['user'], $args['pass'], $args['project'], $args['connectionObj']->host);
		if ($sid) {
			$wid = $user->register_window($sid, $args['connectionObj']->host, $args['connectionObj']->port, 'main');
		}
		if ($sid && $wid) {
			$func = new ttRpcFunc('logged_in', array('sid' => $sid, 'wid' => $wid, 'user_level' => $user->get_level_by_sid($sid), 'error' => false));
		} else {
			$func = new ttRpcFunc('logged_in', array('error' => true));
		}
		$args['connectionObj']->sendMessage($args['serverObj']->msgHandler->create_msg($func));
	}
	// }}}
	// {{{ register_window()
	/**
	 * registeres a new window to user. a message will be send back to
	 * client with new wid or an error, if registration fails.
	 *
	 * @public
	 *
	 * @param	$args['sid'] (string) session id
	 * @param	$args['type'] (string) type of window to be registered
	 */ 
	function register_window($args) {
		global $conf;
		
		$user = new ttUser();
		$wid = $user->register_window($args['sid'], $args['connectionObj']->host, $args['connectionObj']->port, $args['type']);

		if ($wid) {
			$func = new ttRpcFunc('registered_window', array('wid' => $wid, 'user_level' => $user->get_level_by_sid($args['sid']), 'error' => false));
		} else {
			$func = new ttRpcFunc('registered_window', array('error' => true));
		}
		$args['connectionObj']->sendMessage($args['serverObj']->msgHandler->create_msg($func));
	}
	// }}}
	// {{{ send_message_to_clients()
	/**
	 * sends a message to connected clients.
	 *
	 * @public
	 *
	 * @param	$args['sid'] (string) session id
	 * @param	$args['wid'] (string) session window id
	 * @param	$args['message'] (string) message to send
	 * @param	$args['project'] (string) project name. if given, the message
	 *			will only be send to clients, which are connected to given project.
	 * @param	$args['info'] (string) info to print out into log/console
	 */ 
	function send_message_to_clients($args) {
		$msg = $args['serverObj']->msgHandler->create_msg($args['message']);
		
		$user = new ttUser();
		if ($args['info'] != '') {
			$args['serverObj']->serverMessage($args['info']);
		}
		for ($i = 0; $i < count($args['serverObj']->connhosts); $i++) {
			if ($args['project'] != '') {
				if ($user->is_logged_in($args['serverObj']->connhosts[$i]->host, $args['serverObj']->connhosts[$i]->port, $args['project'])) {
					$args['serverObj']->connhosts[$i]->sendMessage($msg);
				}
			} else {
				if ($user->is_logged_in($args['serverObj']->connhosts[$i]->host, $args['serverObj']->connhosts[$i]->port)) {
					$args['serverObj']->connhosts[$i]->sendMessage($msg);
				}
			}
		} 
	}
	// }}}
	// {{{ send_message_to_client()
	/**
	 * sends a message to logged in client
	 *
	 * @public
	 *
	 * @param	$args['sid'] (string) session id of user, to send message to
	 * @param	$args['wid'] (string) session window id of user, to send message to
	 */ 
	function send_message_to_client($args) {
		$msg = $args['serverObj']->msgHandler->create_msg($args['message']);

		$user = new ttUser();
		$connection_data = $user->get_host_port_sid_wid($args['sid'], $args['wid']);
		
		if ($connection_data) {
			for ($i = 0; $i < count($args['serverObj']->connhosts); $i++) {
				if ($args['serverObj']->connhosts[$i]->host == $connection_data['ip'] && $args['serverObj']->connhosts[$i]->port == $connection_data['port']) {
					$args['serverObj']->connhosts[$i]->sendMessage($msg);
				}
			}
		}
	}
	// }}}
	// {{{ keepAlive()
	/**
	 * dummy function called to keep TCP connection alive.
	 * does nothing.
	 */ 
	function keepAlive($args) {
		global $project;

		$project->user->update_login($args['sid']);
	}
	// }}}
}

//non class functions
// {{{ tell_clients_to_update()
/**
 * tells connected clients to update a specific type of data or
 * send directly the data that has been updated.
 * data will be send in one message at the end of execution of
 * current script.
 *
 * @param	$project_name (string) name of project
 * @param	$sid (string) session id (really needed ???)
 * @param	$type (string) type of data, that has to be updated
 * @param	$ids (mixed) use depends on type of data to be updated.\n
 *			'page_data': array of ids that has changed\n
 *			'fileProps': path, that has been changed\n
 */ 
function tell_clients_to_update($project_name, $sid, $type, $ids = false) {
	global $conf, $project;
	
	if (!is_array($GLOBALS['pocket_updates'])) {
		$GLOBALS['pocket_updates'] = array();
	}
	
	global $pocket_updates;
	
	if ($project_name && ($ids === true || count($ids) > 0)) {
		// {{{ pages
		if ($type == 'pages') {
			$xml_def = $project->get_page_struct($project_name);
			
			if (get_class($xml_def) == 'domdocument') {
				$data['data'] = $xml_def->dump_node($xml_def->document_element());
				$xml_def->free();
				
				$func = new ttRpcFunc("update_tree_$type", $data);
				$pocket_updates[] = array('func' => $func, 'project' => $project_name, 'info' => "sending update for '$type' to project '$project_name'");
			}
		// }}}
		// {{{ page_data
		} else if ($type == 'page_data') {
			for ($i = 0; $i < count($ids); $i++) {
				$data['id' . ($i + 1)] = $ids[$i];
			}
			$data['id_num'] = count($ids);

			$func = new ttRpcFunc("get_update_tree_$type", $data);
			$pocket_updates[] = array('func' => $func, 'project' => $project_name, 'info' => "sending update for '$type' to project '$project_name'");
		// }}}
		// {{{ files
		} else if ($type == 'files') {
			$data = array();
			
			$func = new ttRpcFunc("get_update_tree_$type", $data);
			$pocket_updates[] = array('func' => $func, 'project' => $project_name, 'info' => "sending update for '$type' to project '$project_name'");
		// }}}
		// {{{ fileProps
		} else if ($type == 'fileProps') {
			$data = array();
			$data['path'] = $ids;
			
			$func = new ttRpcFunc('get_update_prop_files', $data);
			$pocket_updates[] = array('func' => $func, 'project' => $project_name, 'info' => "sending update for '$type' to project '$project_name'");
		// }}}
		// {{{ colors
		} else if ($type == 'colors') {
			$xml_def = $project->get_colors($project_name);
			
			$data['data'] = $xml_def->dump_node($xml_def->document_element());
			$xml_def->free();
			
			$func = new ttRpcFunc("update_tree_$type", $data);
			$pocket_updates[] = array('func' => $func, 'project' => $project_name, 'info' => "sending update for '$type' to project '$project_name'");
		// }}}
		// {{{ tpl_templates
		} else if ($type == 'tpl_templates') {			
			$xml_def = $project->get_tpl_template_struct($project_name);
			$data['data'] = $xml_def->dump_node($xml_def->document_element());
			$xml_def->free();
			
			$func = new ttRpcFunc("update_tree_$type", $data);
			$pocket_updates[] = array('func' => $func, 'project' => $project_name, 'info' => "sending update for '$type' to project '$project_name'");
		// }}}
		// {{{ tpl_newnodes
		} else if ($type == 'tpl_newnodes') {
			$xml_def = $project->get_tpl_newnodes($project_name);
			$data['data'] = $xml_def->dump_node($xml_def->document_element());
			$xml_def->free();

			$func = new ttRpcFunc("update_tree_$type", $data);
			$pocket_updates[] = array('func' => $func, 'project' => $project_name, 'info' => "sending update for '$type' to project '$project_name'");
		// }}}
		// {{{ settings
		} else if ($type == 'settings') {
			$xml_def = $project->get_settings($project_name);
			
			$data['data'] = $xml_def->dump_node($xml_def->document_element());
			$xml_def->free();

			$func = new ttRpcFunc("update_tree_$type", $data);
			$pocket_updates[] = array('func' => $func, 'project' => $project_name, 'info' => "sending update for '$type' to project '$project_name'");
		}
		// }}}
	}
}
// }}}
// {{{ send_updates()
/**
 * sends update calls through local pocketServer. it's called at
 * at end of script execution if needed.
 */
function send_updates() {
	global $conf, $pocket_updates;

	$pocket_client = new PocketClient('127.0.0.1', $conf->pocket_port);
	
	if (count($pocket_updates) > 0) {
		$pocket_client->connect();
		for ($i = 0; $i < count($pocket_updates); $i++) {
			$pocket_client->send_to_clients($pocket_updates[$i]['func'], $pocket_updates[$i]['project'], $pocket_updates[$i]['info']);
		}
		$pocket_client->close();
	}
}
// }}}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
