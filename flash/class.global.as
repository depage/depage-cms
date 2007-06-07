/**
 * C L A S S 
 * Class Global
 *
 * actionScript-library:
 * (c)2003 jonas [jonas.info@gmx.net]
 */

/*
 *	Class Configuration
 *
 *	Global Configuration Object, that
 *	handles all global configuration-directives
 *	and interface-texts
 */
// {{{ constructor()
class_conf = function() { 
	this.lang = new Object();
	this.interface = new Object();
	this.user = new class_user();
	this.projects = new Array();
	this.ns = new Array();
	this.global_entities = new Array();
	this.output_file_types = new Array();
	this.output_encodings = new Array();
	this.output_methods = new Array();
};
// }}}
// {{{ set_values()
class_conf.prototype.set_values = function(args) {
	var tempXML, tempObj;
	
	this.app_name = args["app_name"];	
	this.app_version = args["app_version"];	
	
	this.thumb_width = int(args["thumb_width"]);	
	this.thumb_height = int(args["thumb_height"]);	
	this.thumb_load_num = int(args["thumb_load_num"]);	
	
	this.interface_lib = args["interface_lib"];	
	this.url_page_scheme_intern = args["url_page_scheme_intern"];
	this.url_lib_scheme_intern = args["url_lib_scheme_intern"];
	
	tempXML = new XML("<root>" + args["interface_text"] + "</root>");
	tempXML = tempXML.getRootNode().firstChild;
	while (tempXML != null) {
		this.lang[tempXML.attributes.name] = tempXML.attributes.value;
		tempXML = tempXML.nextSibling;	
	}

	tempXML = new XML("<root>" + args["interface_scheme"] + "</root>");
	tempXML = tempXML.getRootNode().firstChild;
	while (tempXML != null) {
		tname = tempXML.attributes.name;
		if (tname == "component_doubleclicktime" || tname == "component_height" || tname == "register_width" || tname == "register_space" || tname == "menu_line_height") {	
			this.interface[tempXML.attributes.name] = int(tempXML.attributes.value);
		} else {
			this.interface[tempXML.attributes.name] = tempXML.attributes.value;
		}
		tempXML = tempXML.nextSibling;	
	}

	tempXML = new XML("<root>" + args["projects"] + "</root>");
	tempXML = tempXML.getRootNode().firstChild;
	while (tempXML != null) {
		this.projects.push(new class_project(tempXML.attributes.name, tempXML.attributes.preview));
		tempXML = tempXML.nextSibling;	
	}

	tempXML = new XML("<root>" + args["namespaces"] + "</root>");
	tempXML = tempXML.getRootNode().firstChild;
	while (tempXML != null) {
		this.ns[tempXML.attributes.name] = new class_ns(tempXML.attributes.prefix, tempXML.attributes.uri);
		tempXML = tempXML.nextSibling;	
	}
	
	tempXML = new XML("<root>" + args["global_entities"] + "</root>");
	tempXML = tempXML.getRootNode().firstChild;
	while (tempXML != null) {
		this.global_entities.push(tempXML.attributes.name);
		tempXML = tempXML.nextSibling;	
	}
	
	tempXML = new XML("<root>" + args["output_file_types"] + "</root>");
	tempXML = tempXML.getRootNode().firstChild;
	while (tempXML != null) {
		this.output_file_types.push({
			name		: tempXML.attributes.name,
			extension	: tempXML.attributes.extension
		});
		tempXML = tempXML.nextSibling;	
	}

	tempXML = new XML("<root>" + args["output_encodings"] + "</root>");
	tempXML = tempXML.getRootNode().firstChild;
	while (tempXML != null) {
		this.output_encodings.push(tempXML.attributes.name);
		tempXML = tempXML.nextSibling;	
	}
	
	tempXML = new XML("<root>" + args["output_methods"] + "</root>");
	tempXML = tempXML.getRootNode().firstChild;
	while (tempXML != null) {
		this.output_methods.push(tempXML.attributes.name);
		tempXML = tempXML.nextSibling;	
	}
	
	this.check_settings();
}
// }}}
// {{{ check_settings()
class_conf.prototype.check_settings = function() {
	var availableFonts = ["_sans", "_serif", "_typewriter"].concat(TextField.getFontList());
	var fontArray = [];
	var i;
	
	//test font	
	if (this.interface.font_device) {
		fontArray = this.interface.font.split(",");
		for (i = 0; i < fontArray.length; i++) {
			fontArray[i] = fontArray[i].trim();
			if (availableFonts.searchFor(fontArray[i]) > -1) {
				this.interface.font = fontArray[i].toString();
				break;
			}
		}
	}
		
	//test font_source
	if (this.interface.font_source_device) {
		fontArray = this.interface.font_source.split(",");
		for (i = 0; i < fontArray.length; i++) {
			fontArray[i] = fontArray[i].trim();
			if (availableFonts.searchFor(fontArray[i]) > -1) {
				this.interface.font_source = fontArray[i].toString();
				break;
			}
		}
	}
		
	//test font_vertical
	if (this.interface.font_vertical_device) {
		fontArray = this.interface.font_vertical.split(",");
		for (i = 0; i < fontArray.length; i++) {
			fontArray[i] = fontArray[i].trim();
			if (availableFonts.searchFor(fontArray[i]) > -1) {
				this.interface.font_vertical = fontArray[i].toString();
				break;
			}
		}
	}
};
// }}}

/*
 *	Class namespace
 */
// {{{ constructor
class_ns = function(prefix, uri) {
	this.prefix = prefix;
	this.uri = uri;
};
// }}}
// {{{ toString()
class_ns.prototype.toString = function() {
	return this.prefix;
};
// }}}

/*
 *	Class user
 *
 *	Element in Configuration-Class
 *	has all User-Informations and handles user-rights
 */
// {{{ constructor()
class_user = function() {
	this.sharedObj = SharedObject.getLocal("tt");

	this.sid = null;
	this.wid = null;
	this.level = null;
	this.user = null;
	this.project = null;

	this.userlist = Array();

	this.getSettings();
};
// }}}
// {{{ updateUserList()
class_user.prototype.updateUserList = function(xmldata) {
	var xmldoc, i;

	this.userlist = Array();

	xmldoc = new XML("<root>" + xmldata + "</root>");
	if (xmldoc.status == 0) {
		xmldoc = xmldoc.getRootNode();
		for (i = 0; i < xmldoc.childNodes.length; i++) {
			if (xmldoc.childNodes[i].nodeName == "user") {
				this.userlist[xmldoc.childNodes[i].attributes.uid] = new Array(xmldoc.childNodes[i].attributes.name, xmldoc.childNodes[i].attributes.fullname);
			}
		}
	}
};
// }}}
// {{{ login()
class_user.prototype.login = function(user, pass, project, onSuccessFunc, onErrorFunc) {
	this.user = user;
	this.project = project;
	this.onSuccessFunc = onSuccessFunc;
	this.onErrorFunc = onErrorFunc;

	_root.pocketConnect.msgHandler.register_func("logged_in", this.loginHandler, this);
	_root.phpConnect.msgHandler.register_func("logged_in", this.loginHandler, this);
	if (conf.usepocket == true) {
		_root.pocketConnect.send("login", [["user", user], ["pass", pass], ["project", project]]);
	} else {
		_root.phpConnect.send("login", [["user", user], ["pass", pass], ["project", project]]);
	}
};
// }}}
// {{{ loginHandler()
class_user.prototype.loginHandler = function(args) {
	if (args['error']) {
		this.onErrorFunc();
	} else {
		this.sharedObj.data.prevUser = this.user;
		this.sharedObj.data.prevProject = this.project;
		this.sharedObj.flush();

		this.sid = args['sid'];
		this.wid = args['wid'];
		this.level = args['user_level'];
		this.onSuccessFunc();
	}
};
// }}}
// {{{ registerWindow()
class_user.prototype.registerWindow = function(onSuccessFunc, onErrorFunc) {
	this.onSuccessFunc = onSuccessFunc;
	this.onErrorFunc = onErrorFunc;

	_root.pocketConnect.msgHandler.register_func("registered_window", this.registerWindowHandler, this);
	_root.phpConnect.msgHandler.register_func("registered_window", this.registerWindowHandler, this);
	if (conf.usepocket == true) {
		_root.pocketConnect.send("register_window", [["sid", this.sid], ["type", "main"]]);
	} else {
		_root.phpConnect.send("register_window", [["sid", this.sid], ["type", "main"]]);
	}
};
// }}}
// {{{ registerWindowHandler()
class_user.prototype.registerWindowHandler = function(args) {
	if (args['error']) {
		this.onErrorFunc();
	} else {
		this.wid = args['wid'];
		this.level = args['user_level'];

		this.onSuccessFunc();
	}
};
// }}}
// {{{ getSettings()
class_user.prototype.getSettings = function() {
	this.settings = new Object();
	//set standard values if not available
	if (this.sharedObj.data.preview_automatic == undefined) this.sharedObj.data.preview_automatic = 2;
	if (this.sharedObj.data.preview_feedback == undefined) this.sharedObj.data.preview_feedback = true;

	//read settings
	this.settings._filelistType = this.sharedObj.data.settings_filelistType;
	this.settings._preview_automatic = this.sharedObj.data.preview_automatic;
	this.settings._preview_feedback = this.sharedObj.data.preview_feedback;

	//add filelistType property
	// {{{ _filelistTypeGet()
	this.settings._filelistTypeGet = function() {
		if (this._filelistType == 1) {
			return "thumbs";
		} else {
			return "details";
		}
	};
	// }}}
	// {{{ _filelistTypeSet()
	this.settings._filelistTypeSet = function(newVal) {
		if (newVal == "thumbs") {
			this._filelistType = 1;
		} else {
			this._filelistType = 0;
		}
		this.userObj.saveSettings();
	};
	// }}}
	this.settings.addProperty("filelistType", this.settings._filelistTypeGet, this.settings._filelistTypeSet);

	//add preview_automatic property
	// {{{ _preview_automaticGet()
	this.settings._preview_automaticGet = function() {
		return this._preview_automatic;
	};
	// }}}
	// {{{ _preview_automaticSet
	this.settings._preview_automaticSet = function(newVal) {
		this._preview_automatic = newVal;
		this.userObj.saveSettings();
	};
	// }}}
	this.settings.addProperty("preview_automatic", this.settings._preview_automaticGet, this.settings._preview_automaticSet);

	//add preview_feedback property
	// {{{ _preview_feedbackGet()
	this.settings._preview_feedbackGet = function() {
		return this._preview_feedback;
	};
	// }}}
	// {{{ _preview_feedbackSet()
	this.settings._preview_feedbackSet = function(newVal) {
		this._preview_feedback = newVal;
		this.userObj.saveSettings();
	};
	// }}}
	this.settings.addProperty("preview_feedback", this.settings._preview_feedbackGet, this.settings._preview_feedbackSet);

	//link to this for saving settings
	this.settings.userObj = this;
};
// }}}
// {{{ saveSettings()
class_user.prototype.saveSettings = function() {
	this.sharedObj.data.settings_filelistType = this.settings._filelistType;
	this.sharedObj.data.preview_automatic = this.settings._preview_automatic;
	this.sharedObj.data.preview_feedback = this.settings._preview_feedback;
	this.sharedObj.flush();
};
// }}}
// {{{ getPrevUser()
class_user.prototype.getPrevUser = function() {
	return this.sharedObj.data.prevUser;
};
// }}}
// {{{ getPrevProject()
class_user.prototype.getPrevProject= function() {
	return this.sharedObj.data.prevProject;
};
// }}}

// {{{ user auth pages
class_user.prototype.mayEditPages = function() { return true; };
class_user.prototype.mayAddPages = function() {	return true; };
class_user.prototype.mayDeletePages = function() { return this.level <= 4; };
class_user.prototype.mayEditSourceCode = function() { return this.mayEditTemplates(); };
// }}}
// {{{ user auth file
class_user.prototype.mayRenameFolder = function() { return this.level <= 4; };
class_user.prototype.mayDeleteFolder = function() { return this.level <= 4; };
class_user.prototype.mayRenameFiles = function() { return this.level <= 4; };
class_user.prototype.mayDeleteFiles = function() { return this.level <= 4; };
// }}}
// {{{ user auth colors
class_user.prototype.mayEditColors = function() { return this.level <= 4; };
class_user.prototype.mayAddDeleteColors = function() { return this.mayEditTemplates(); };
// }}}
// {{{ user auth templates
class_user.prototype.mayEditTemplates = function() { return this.level <= 2; };
// }}}
// {{{ user auth settings
class_user.prototype.mayPublish = function() { return this.level <= 3; };
class_user.prototype.mayEditSettings = function() { return this.level <= 3; };
class_user.prototype.mayEditSettingsPublish = function() { return this.level <= 2; };
class_user.prototype.mayEditSettingsTemplateSets = function() { return this.level <= 2; };
class_user.prototype.mayBackup = function() { return this.level <= 1; };
class_user.prototype.mayEditSettingsLanguages = function() { return this.level <= 1; };
class_user.prototype.mayEditSettingsNavigation = function() { return this.level <= 2; };
// }}}

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
