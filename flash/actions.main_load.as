/* functions */
	function init() {
		var tempObj;
		
		/* set global Movie Properties */
		Stage.align = "TL";
		Stage.scaleMode = "noScale";
		//Stage.showMenu = false;
		//_quality = "LOW";
		
		/* update Display on Mouse Events */
		_root.onMouseDown = function() {
			updateAfterEvent();
		};
		_root.onMouseUp = function() {
			updateAfterEvent();
		};
		_root.onMouseMove = function() {
			updateAfterEvent();
		};

		Key.addListener(_root);
		
		/* set tt var */
		_global.isInTT = true;

		/* get movie parameter */
		var params = getMovieParam(_level0);

		_global.conf = new class_conf();
		conf.user.sid = params['userid'];
		conf.ns.rpc = new class_ns(params['nsrpc'], params['nsrpcuri']);
		
		if (params['nsrpc'] == undefined || params['nsrpcuri'] == undefined) {
			getURL("hack.php", "_top");	
		} else {
			call_jsfunc("set_flashloaded()");
		}
		
		conf.phost = params['phost'];
		conf.pport = params['pport'];
		conf.puse = params['puse'];
		conf.project_name = params['project'];

		conf.standalone = params['standalone'];
		
		/* set connection objects */
		_root.phpConnect = new class_phpConnect(conf.ns.rpc.toString(), conf.ns.rpc.uri);
		_root.pocketConnect = new class_pocketConnect(conf.phost, conf.pport, conf.ns.rpc.toString(), conf.ns.rpc.uri);
		_root.pocketConnect.onSuccessHandler = pocket_connect_success;
		_root.pocketConnect.onFaultHandler = pocket_connect_fault;
		_root.pocketConnect.onCloseHandler = pocket_connect_abort;

		_root.phpConnect.msgHandler.register_func("set_config", set_config);
		
		_root.phpConnect.send("get_config", []);
	}
	
	function set_config(args) {
		conf.set_values(args);
		status(conf.lang.start_config_loaded);
		load_interface_lib();
		pocket_connect();
	}

	function pocket_connect() {
		if (conf.puse == "false") {
			pocket_connect_fault();
		} else {
			_root.pocketConnect.connect();
		}
	}
	
	function pocket_connect_success() {
		status(conf.lang.start_pocket_connected);
		interface.loadBox_setText(conf.lang.start_pocket_connected);

		conf.usepocket = true;
		init_pocket = true;
		init_end();
	}
	
	function pocket_connect_fault() {
		status(conf.lang.start_pocket_reconnect);

		if (_root.pocketConnect.connectFaults < 1 && conf.puse != "false") {
			interface.loadBox_setText(conf.lang.start_pocket_reconnect);
			pocket_connect();
		} else {
			conf.usepocket = false;
			init_pocket = true;
			init_end();
		}
	}
	
	function pocket_connect_abort() {
		getURL("msg.php?msg=inhtml_connection_closed&title=inhtml_connection_closed_title", _self);
	}

	function load_interface_lib() {
		status(conf.lang.start_preload + " " + conf.interface_lib);
		attachLibrary(conf.interface_lib, "interface", 2, [], loaded_interface_lib);
	}
	
	function loaded_interface_lib() {
		status(conf.lang.start_loaded);
		init_interface = true;
		init_end();
	}

	function init_end() {
		if (init_pocket == true && init_interface == true) {
			if (conf.user.sid != "null") {
				register_window();
			} else {
				interface.login(conf.user.getPrevUser(), conf.user.getPrevProject());	
			}
		}
	}

	function login(user, pass, project) {
		var i;
		for (i = 0; i < conf.projects.length; i++) {
			if (project == conf.projects[i].name) {
				conf.projectId = i;
				
				break;
			}
		}
		
		conf.user.login(user, pass, project, logInSuccess, logInFailure);
	}
	
	function logInSuccess() {
		//alert("logged in");
		if (conf.projects[conf.projectId].type == "yes" && conf.standalone != "true") {
			call_jsfunc("open_edit('" + conf.user.sid + "')");
		} else if (conf.projects[conf.projectId].type == "no" || conf.standalone == "true") {
			load_project();	
		} 
	}
	
	function logInFailure() {
		alert(conf.lang.start_login_wrong_login);
		interface.relogin();
	}
	
	function register_window() {
		conf.user.registerWindow(register_windowSuccess, register_windowFailure);
	}
	
	function register_windowSuccess(args) {
		load_project();
	}
	
	function register_windowFailure(args) {
		interface.login();
	}
	
	function load_project() {
		_root.phpConnect.msgHandler.register_func("set_project_data", set_project_data);

		status(conf.lang.start_loading_project);
		
		_root.phpConnect.send("get_project", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name]]);
	}
	
	function set_project_data(args) {
		status(conf.lang.start_loaded_project);
		
		delete conf.projects;
		conf.project = new class_project(args["name"]);
		conf.project.init(args["settings"]);
		conf.user.updateUserList(args["users"]);
		
		createClipboard(3);
		
		interface.project_loader();
	}
	
	function project_loaded() {
		start_main_interface();
	}
	
	function start_main_interface() {
		interface.start_main_interface();
	}
/* functions end */

init();
stop();

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
