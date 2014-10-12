/*
 *	Class Project
 */
// {{{ constructor
class_project = function(name, type) {
	this.name = name;
	this.pathname = this.name.toLowerCase().replace(" ", "_");
	if (type != undefined) {
		this.type = type;
	}
	this.tree = new Object();
	this.prop = new Object();
	this.preview_type = "html";
	this.preview_lang = "de";
	this.preview_setIdFromOutside = false;

	this.taskHandlers = [];
	this.activeTasks = [];
};
// }}}
// {{{ init()
class_project.prototype.init = function(xml_data) {
	this.parseProjectData(xml_data);

	_root.phpConnect.msgHandler.register_func("preview_update", this.previewUpdate, this);
	_root.pocketConnect.msgHandler.register_func("preview_loaded", this.previewLoaded, this);
	_root.phpConnect.msgHandler.register_func("preview_loaded", this.previewLoaded, this);
	_root.pocketConnect.msgHandler.register_func("set_active_tasks_status", this.setActiveTasksStatus, this);
	_root.phpConnect.msgHandler.register_func("set_active_tasks_status", this.setActiveTasksStatus, this);
	_root.pocketConnect.msgHandler.register_func("error_alert", this.error_alert, this);
	_root.phpConnect.msgHandler.register_func("error_alert", this.error_alert, this);

	if (conf.usePocket == true) {
		setInterval(this, "sendKeepAlive", 15000);
	} else {
		setInterval(this, "sendKeepAlive", 5000);
	}
};
// }}}
// {{{ error_alert()
class_project.prototype.error_alert = function(args) {
	alert(args["error_msg"]);
};
// }}}
// {{{ sendKeepAlive()
class_project.prototype.sendKeepAlive = function() {
	if (conf.usePocket == true) {
		_root.pocketConnect.send("keepAlive", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name]]);
	} else {
		_root.phpConnect.send("keepAlive", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name]]);
	}
};
// }}}
// {{{ parseProjectData
class_project.prototype.parseProjectData = function(xml_data) {
	var i, tempType;

	var xml_doc = new XML(xml_data);
	if (xml_doc.status == 0) {
		xml_doc = xml_doc.getRootNode();
		for (i = 0; i < xml_doc.childNodes.length; i++) {
			if (xml_doc.childNodes[i].nodeName == conf.ns.project + ":type") {
				this.type = xml_doc.childNodes[i].attributes.type;
				this.preview_available = xml_doc.childNodes[i].attributes.preview == "yes";
			}
		}
		if (this.type == "webProject") {
                    if (conf.user.mayEditTemplates()) {
			this.datatypes = ["settings", "tpl_templates", "tpl_newnodes", "colors", "page_data", "pages", "files"];
                    } else {
			this.datatypes = ["settings", "tpl_newnodes", "colors", "page_data", "pages", "files"];
                    }
		} else {
			this.datatypes = [];
		}
		for (i = 0; i < this.datatypes.length; i++) {
			tempType = this.datatypes[i];
			this.tree[tempType] = getNewTreeObj(tempType, this);
			this.prop[tempType] = getNewPropObj(tempType, this);
			if (tempType == "pages") {
				this.tree[tempType].propObj = this.prop["page_data"];
			} else {
				this.tree[tempType].propObj = this.prop[tempType];
				this.prop[tempType].treeObj = this.tree[tempType];
			}
		}
	} else {
            alert("error while loading projectData");
        }
	this.waitForLoaded();
};
// }}}
// {{{ waitForLoaded()
class_project.prototype.waitForLoaded = function(i) {
	var j;
	if (i == undefined) var i = 0;

	if (this.type == "webProject") {
            if (conf.user.mayEditTemplates()) {
                var toLoad = [["settings", "colors", "tpl_templates", "tpl_newnodes"], ["pages"]];
            } else {
                var toLoad = [["settings", "colors", "tpl_newnodes"], ["pages"]];
            }
	}

	if (i <= toLoad.length) {
		if (this.tree[toLoad[i][0]].isEmpty() && !this.tree[toLoad[i][0]].loading) {
			for (j = 0; j < toLoad[i].length; j++) {
				this.tree[toLoad[i][j]].load();
			}
		} else if (!this.tree[toLoad[i][0]].isEmpty()) {
			i++;
		}
		setTimeout(this.waitForLoaded, this, 30, [i]);
		_root.interface.project_loader_update(i / (toLoad.length + 1));
	} else {
		_root.project_loaded();
	}
}
// }}}
// {{{ setPreviewNode()
class_project.prototype.setPreviewNode = function(node) {
	this.previewNode = node;
};
// }}}
// {{{ preview()
class_project.prototype.preview = function(forcePreview) {
	if (conf.standalone != "true") {
		var tempNode = this.previewNode;

		while (this.tree.pages.isFolder(tempNode) && tempNode.firstChild != null) {
			tempNode = tempNode.firstChild;
		}

		if (conf.user.settings.preview_automatic < 2) {
			var previewTimeout = 1000;
		} else {
			var previewTimeout = 1000;
		}
		if (tempNode.nid != null && ((conf.user.settings.preview_automatic == 2 || (conf.user.settings.preview_automatic == 1 && this.previewId != tempNode.nid) || forcePreview))) {
			if (this.preview_setIdFromOutside) {
				this.preview_setIdFromOutside = false;
				if (this.previewId != tempNode.nid) {
					this.previewId = tempNode.nid;

					this.timeoutObj.clear();
					this.timeoutObj = setTimeout(this.previewNow, this, previewTimeout);
				}
			} else {
				this.previewId = tempNode.nid;

				this.timeoutObj.clear();
				this.timeoutObj = setTimeout(this.previewNow, this, previewTimeout);
			}
		}
	}
};
// }}}
// {{{ previewManual()
class_project.prototype.previewManual = function() {
	this.preview(true);
};
// }}}
// {{{ previewNow()
class_project.prototype.previewNow = function() {
	var url;

        urlId = conf.project.tree.pages.getPathById(this.previewId, this.preview_lang, this.preview_type);
        if (urlID != "") {
            url = "project/";
            url += conf.project.pathname;
            url += "/preview/";
            url += this.preview_type + "/";

            if (this.previewDisableCache) {
                    url += "noncached"
            } else {
                    url += "cached"
            }
            url += urlId;

            call_jsfunc("depageCMS.preview('" + escape(url) + "')");
        }
};
// }}}
// {{{ previewLoaded()
class_project.prototype.previewLoaded = function(args) {
	var langs = this.tree.settings.getLanguages();

	if (args['error'] == "") {
		if (langs.searchFor(args['lang']) != -1) {
			this.preview_lang = args['lang'];
		}
		if (this.previewId != args['id'] && args['id'] != "false" && conf.user.settings.preview_feedback) {
			this.previewId = args['id'];
			this.preview_setIdFromOutside = true;
			this.tree.pages.setActiveIdOutside(args);
		} else {
			this.preview_setIdFromOutside = false;
		}
	} else {
		//alert("error while parsing:\n" + args['error']);
	}
};
// }}}
// {{{ previewUpdate()
class_project.prototype.previewUpdate = function(args) {
	this.preview_setIdFromOutside = false;
	this.previewId = -1;
	this.preview();
};
// }}}
// {{{ backupProject()
class_project.prototype.backupProject = function(type, comment) {
	_root.phpConnect.send("backup_project", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["type", type], ["comment", comment]]);
};
// }}}
// {{{ getBackupFiles()
class_project.prototype.getBackupFiles = function(callBackObj, callBackFunc) {
	this.backupCallBackObj = callBackObj;
	this.backupCallBackFunc = callBackFunc;

	_root.phpConnect.msgHandler.register_func("set_backup_files", this.parseBackupFiles, this);
	_root.phpConnect.send("get_backup_files", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name]]);
};
// }}}
// {{{ parseBackupFiles()
class_project.prototype.parseBackupFiles = function(args) {

	var DBFiles = [];
	var LibFiles = [];
	var tempNode;

	var tempXML = new XML(args["listDB"]);
	tempNode = tempXML.getRootNode();
	tempNode = tempNode.firstChild;
	while (tempNode != null) {
		DBFiles.push({
			name	: tempNode.attributes.name,
			date	: tempNode.attributes.date,
			comment	: tempNode.firstChild.attributes.comment
		});
		tempNode = tempNode.nextSibling;
	}

	var tempXML = new XML(args["listLib"]);
	tempNode = tempXML.getRootNode();
	tempNode = tempNode.firstChild;
	while (tempNode != null) {
		LibFiles.push({
			name	: tempNode.attributes.name,
			date	: tempNode.attributes.date,
			comment	: tempNode.firstChild.attributes.comment
		});
		tempNode = tempNode.nextSibling;
	}

	_root.phpConnect.msgHandler.unregister_func("set_backup_files");

	this.backupCallBackFunc.apply(this.backupCallBackObj, [DBFiles, LibFiles]);

	delete this.backupCallBackObj;
	delete this.backupCallBackFunc;
};
// }}}
// {{{ restoreProject()
class_project.prototype.restoreProject = function(restoreType, file, options) {
	_root.phpConnect.send("restore_project", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["type", restoreType], ["file", file], ["options", options]]);
};
// }}}
// {{{ publishProject()
class_project.prototype.publishProject = function(id) {
	_root.phpConnect.send("publish_project", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["publish_id", id]]);
};
// }}}
// {{{ addTaskHandler()
class_project.prototype.addTaskHandler = function(obj, func, type) {
	this.taskHandlers.push({
		obj		: obj,
		func	: func,
		type	: type
	});
};
// }}}
// {{{ removeTaskHandler()
class_project.prototype.removeTaskHandler = function(obj, func) {
	var i;

	for (i = 0; i < this.taskHandlers.length; i++) {
		if (this.taskHandlers[i].obj == obj && this.taskHandlers[i].func == func) {
			this.taskHandlers.splice(i, 1);
			break;
		}
	}
};
// }}}
// {{{ setActiveTaskStatus()
class_project.prototype.setActiveTasksStatus = function(args) {
	var taskXML = new XML(args["status"]);
	var taskObj;
	var found = false;
	var i;

	taskXML = taskXML.getRootNode();

	taskObj = {
		name				: taskXML.attributes.name,
		id					: taskXML.attributes.id,
		progress_percent	: taskXML.attributes.progress_percent,
		time_from_start		: taskXML.attributes.time_from_start,
		time_at_all			: taskXML.attributes.time_at_all,
		time_until_end		: taskXML.attributes.time_until_end,
		description			: taskXML.attributes.description
	}

	for (i = 0; i < this.activeTasks.length; i++) {
		if (this.activeTasks[i].id == taskObj.id) {
			this.activeTasks[i] = taskObj;
			found = true;
		}
	}

	if (!found) {
		this.activeTasks.push(taskObj);
	}

	for (i = 0; i < this.taskHandlers.length; i++) {
		if (this.taskHandlers[i].type == taskObj.name) {
			this.taskHandlers[i].func.apply(this.taskHandlers[i].obj, [taskObj]);
		}
	}

	for (i = 0; i < this.activeTasks.length; i++) {
		if (this.activeTasks[i].progress_percent == 100) {
			this.activeTasks.splice(i, 1);
			i--;
		}
	}
};
// }}}
// {{{ getActiveTasks()
class_project.prototype.getActiveTasks = function(type) {
	var thisActiveTasks = [];

	for (i = 0; i < this.activeTasks.length; i++) {
		if (this.activeTasks[i].name == type) {
			thisActiveTasks.push(this.activeTasks[i]);
		}
	}

	return thisActiveTasks;
};
// }}}

/*
 *	Functions
 */
// {{{ getNewTreeObj()
function getNewTreeObj(type, projectObj) {
	var newObj;

	if (type == "pages") {
		newObj = new class_tree_pages();
	} else if (type == "page_data") {
		newObj = new class_tree_page_data();
	} else if (type == "files") {
		newObj = new class_tree_files();
	} else if (type == "tpl_templates") {
		newObj = new class_tree_tpl_templates();
	} else if (type == "tpl_newnodes") {
		newObj = new class_tree_tpl_newnodes();
	} else if (type == "colors") {
		newObj = new class_tree_colors();
	} else if (type == "settings") {
		newObj = new class_tree_settings();
	} else {
		alert("treetype not yet defined: " + type);
	}
	newObj.init(type, projectObj);

	return newObj;
}
// }}}
// {{{ getNewPropObj()
function getNewPropObj(type, projectObj) {
	var newObj;

	if (type == "pages") {
		newObj = new class_prop_pages();
	} else if (type == "page_data") {
		newObj = new class_prop_page_data();
	} else if (type == "files") {
		newObj = new class_prop_files();
	} else if (type == "tpl_templates") {
		newObj = new class_prop_tpl_templates();
	} else if (type == "tpl_newnodes") {
		newObj = new class_prop_tpl_newnodes();
	} else if (type == "colors") {
		newObj = new class_prop_colors();
	} else if (type == "settings") {
		newObj = new class_prop_settings();
	} else {
		alert("proptype not yet defined: " + type);
	}
	newObj.init(type, projectObj);

	return newObj;
}
// }}}

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
