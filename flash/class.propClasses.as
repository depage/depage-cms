/*
 *	Class Prop
 *
 *	main Prop Class
 */
// {{{ constructor()
class_prop = function() {

};
// }}}
// {{{ init()
class_prop.prototype.init = function(type, projectObj) {
	this.type = type;
	
	this.project = projectObj;
	this.onChangeObj = null;
	this.clear();
};
// }}}
// {{{ clear()
class_prop.prototype.clear = function() {
	this.data = new XML("<empty></empty>");
};
// }}}
// {{{ onShow()
class_prop.prototype.onShow = function() {
	alert("show me this propObj");
};
// }}}
// {{{ load()
class_prop.prototype.load = function(id, node) {
	var args = [];
	args['data'] = this.data.toString();
	
	this.set_data(args);
};
// }}}
// {{{ set_data()
class_prop.prototype.set_data = function(args) {
	if (!args['error']) {
		this.data = new XML(args['data']);
		this.setNodeIds(this.data.getRootNode());
		this.onChange();
	}
};
// }}}
// {{{ setNodeIds()
class_prop.prototype.setNodeIds = class_tree.prototype.setNodeIds;
// }}}
// {{{ isPropNode()
class_prop.prototype.isPropNode = function(node) {
	return node.nodeType == 1;
};
// }}}
// {{{ onChange()
class_prop.prototype.onChange = function() {
	if (this.onChangeObj != null) {
		this.onChangeObj.onChange();	
	}
};
// }}}
// {{{ save()
class_prop.prototype.save = function(id) {
	var tempNode = this.data.getRootNode();
	var save_data;
	
	for (i = 0; i < tempNode.childNodes.length; i++) {
		if (tempNode.childNodes[i].nid == id) {
			save_data = tempNode.childNodes[i];
		}	
	}
	
	alert("data on id '" + id + "' to save:\n" + save_data);		
};
// }}}
// {{{ getPropNodes()
class_prop.prototype.getPropNodes = function() {
	var tempNode;
	var propNodes = [];

	tempNode = this.data.getRootNode().firstChild;
	while (tempNode != null) {
		if (this.isPropNode(tempNode)) {
			propNodes.push(tempNode);
		}
		
		tempNode = tempNode.nextSibling;	
	}
	
	return propNodes;	
};
// }}}

/*
 *	Class Proppage_data
 *
 *	Extends class_prop()
 *	Handles Page-Element-Properties on XML_DB
 *	Cooperates with class_tree_page_data()
 */
// {{{ constructor
class_prop_page_data = function() {};
class_prop_page_data.prototype = new class_prop();

class_prop_page_data.prototype.imageCallback = [];
// }}}
// {{{ load()
class_prop_page_data.prototype.load = function(id, node) {
	if (id != null && id != this.activeId) {
		this.clear();
		this.data = node;
		this.set_data();
		this.activeId = id;
	}
};
// }}}
// {{{ set_data()
class_prop_page_data.prototype.set_data = function() {
	this.onChange();
};
// }}}
// {{{ isPropNode()
class_prop_page_data.prototype.isPropNode = function(node) {
	return !class_tree_page_data.prototype.isTreeNode.apply(this, [node]);
};
// }}}
// {{{ setNodeIds()
class_prop_page_data.prototype.setNodeIds = class_tree_page_data.prototype.setNodeIds;
// }}}
// {{{ save()
class_prop_page_data.prototype.save = function(id, dataNode, type) {
	var tempNode;
	
	if (type == "colorscheme") {
		_root.phpConnect.send("set_page_colorscheme", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", id], ["colorscheme", dataNode.attributes.colorscheme], ["type", this.type]]);
	} else if (type == "navigation") {
		tempNode = dataNode.cloneNode();
		for(attr in tempNode.attributes) {
			if (attr.substring(0, 4) != "nav_") {
				delete(tempNode.attributes[attr]);
			}
		}
		
		_root.phpConnect.send("set_page_navigations", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", id], ["navigations", tempNode], ["type", this.type]]);
	} else if (type == "file") {
		_root.phpConnect.send("set_page_file_options", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", id], ["multilang", dataNode.attributes.multilang], ["file_name", dataNode.attributes.file_name], ["file_type", dataNode.attributes.file_type], ["type", this.type]]);
	} else if (this.data.isRootNode()) {  
		//conf.project.tree.content.propObj.save(id, dataNode, type);
	} else {
		var tempNode = this.data;
		var save_data;

		if (tempNode.nid == id) {
			save_data = tempNode;
		} else {
			for (i = 0; i < tempNode.childNodes.length; i++) {
				if (tempNode.childNodes[i].nid == id) {
					save_data = tempNode.childNodes[i];
				}	
			}
		}
				
		_root.phpConnect.send("save_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["data", save_data], ["type", this.type]]);
	}
};
// }}}
// {{{ getPropNodes()
class_prop_page_data.prototype.getPropNodes = function() {
	var tempNode;
	var propNodes = [];
	
	if (this.data.nodeName == conf.ns.edit + ":plain_source") {
		propNodes.push(this.data);
	} else if (this.data.isRootNode()) {
		//propNodes = conf.project.tree.content.propObj.getPropNodes();
	} else if (this.data.nodeName == conf.ns.page + ":meta") {
		if (this.tree_displayObj.activeNode.nodeName == conf.ns.page + ":page") {
			tempNode = this.data.cloneNode(false);
			tempNode.nodeName = "pg_date";
			propNodes.push(tempNode);
		
			//tempNode = this.data.cloneNode(false);
			tempXML = new XML("<pg_colorscheme />");
			tempNode = tempXML.firstChild;
			tempNode.attributes["colorscheme"] = this.data.attributes['colorscheme'];
			tempNode.attributes["db:id"] = this.data.attributes['db:id'];
			tempNode.setNodeIdByDBId();
			propNodes.push(tempNode);
		
			tempNode = this.tree_displayObj.activeNode.cloneNode(false);
			tempNode.nodeName = "pg_navigation";
			tempNode.setNodeIdByDBId();
			propNodes.push(tempNode);

			if (conf.user.mayEditSourceCode()) {			
				tempNode = this.tree_displayObj.activeNode.cloneNode(false);
				tempNode.nodeName = "pg_file";
				tempNode.setNodeIdByDBId();
				propNodes.push(tempNode);
			}
		} else if (this.tree_displayObj.activeNode.nodeName == conf.ns.page + ":folder") {
			tempNode = this.tree_displayObj.activeNode.cloneNode(false);
			tempNode.nodeName = "pg_navigation";
			tempNode.setNodeIdByDBId();
			propNodes.push(tempNode);
		}

		tempNode = this.data.firstChild;	
		while (tempNode != null) {
			if (this.isPropNode(tempNode)) {
				propNodes.push(tempNode);
			}
			
			tempNode = tempNode.nextSibling;	
		}
	} else {
            if (conf.user.mayEditTemplates()) {
                tempXML = new XML("<edit:icon />");
                tempNode = tempXML.firstChild;
                tempNode.attributes["icon"] = this.data.attributes['icon'];
                tempNode.attributes["db:id"] = this.data.attributes['db:id'];
                tempNode.setNodeIdByDBId();
                tempNode.dataNode = this.data;
                propNodes.push(tempNode);
            }

		tempNode = this.data.firstChild;	
		while (tempNode != null) {
			if (this.isPropNode(tempNode)) {
				propNodes.push(tempNode);
			}
			
			tempNode = tempNode.nextSibling;	
		}
	}

	return propNodes;	
};
// }}}
// {{{ getImageProp()
class_prop_page_data.prototype.getImageProp = function(path, name, callbackFunc, callbackObj) {
	this.imageCallback.push([path, name, callbackFunc, callbackObj]);
	
	_root.phpConnect.msgHandler.register_func("set_imageProp", this.setImageProp, this);
	_root.phpConnect.send("get_imageProp", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["filepath", path], ["filename", name]]);
};
// }}}
// {{{ setImageProp()
class_prop_page_data.prototype.setImageProp = function(args) {
	var tempStr = "";
	for (i = 0; i < this.imageCallback.length; i++) {
		if (this.imageCallback[i][0] == args.path && this.imageCallback[i][1] == args.name) {
			this.imageCallback[i][2].apply(this.imageCallback[i][3], [args.width, args.height, args.size, args.date])
		}
	}
	for (i = 0; i < this.imageCallback.length; i++) {
		if (this.imageCallback[i][0] == args.path && this.imageCallback[i][1] == args.name) {
			delete this.imageCallback[i];
		}
	}
};
// }}}
// {{{ saveFilePath()
class_prop_page_data.prototype.saveFilePath = function(path, id) {
	var i;
	
	if (path != "") {
		for (i = 0; i < this.data.childNodes.length; i++) {
			if (this.data.childNodes[i].nid == id) {
				this.data.childNodes[i].attributes.src = path;
			}
		}
		this.save(id);
	}
};
// }}}
// {{{ saveFileRef()
class_prop_page_data.prototype.saveFileRef = function(href_id, id) {
	var i;
	
	//alert("hrefid: " + href_id + ", id: " + id);
	if (href_id != "") {
		for (i = 0; i < this.data.childNodes.length; i++) {
			if (this.data.childNodes[i].nid == id) {
				if (href_id.substring(0, conf.url_lib_scheme_intern.length + 2) == conf.url_lib_scheme_intern + ":/") {
					this.data.childNodes[i].attributes.href = href_id;
					delete(this.data.childNodes[i].attributes.href_id);
				} else {
					this.data.childNodes[i].attributes.href_id = href_id;
					delete(this.data.childNodes[i].attributes.href);
				}
				//alert(this.data.childNodes[i]);
			}
		}
		//alert(this.data);
		this.save(id);
	}
};
// }}}

/*
 *	Class PropFiles
 *
 *	Extends class_prop()
 *	Handles Files of a Project
 *	Cooperates with class_tree_files()
 */
// {{{ constructor()
class_prop_files = function() {};
class_prop_files.prototype = new class_prop();
// }}}
// {{{ init()
class_prop_files.prototype.init = function(type, projectObj) {
	super.init(type, projectObj);
	_root.phpConnect.msgHandler.register_func("update_prop_files", this.set_data, this);
	_root.pocketConnect.msgHandler.register_func("update_prop_files", this.set_data, this);
};
// }}}
// {{{ clear()
class_prop_files.prototype.clear = function() {
	super.clear();
	this.oldid = "";
};
// }}}
// {{{ load()
class_prop_files.prototype.load = function(id, temp, reload) {
	if (id != null && (id != this.oldid || reload)) {
		this.clear();
		_root.phpConnect.send("get_prop", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", id], ["file_type", this.treeObj.fileFilter.file_type], ["type", this.type]]);
		this.oldid = id;
	}
};
// }}}
// {{{ deleteFiles()
class_prop_files.prototype.deleteFiles = function(files) {
	var i;

	for (i = 0; i < files.length; i++) {
		_root.phpConnect.send("delete_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", files[i]], ["type", this.type]]);
	}
};
// }}}

/*
 *	Class PropColors
 *
 *	Extends class_prop()
 *	Handles Colorschemes
 *	Cooperates with class_tree_colors()
 */
// {{{ constructor()
class_prop_colors = function() {};
class_prop_colors.prototype = new class_prop();

class_prop_colors.prototype.setNodeIds = class_tree_colors.prototype.setNodeIds;
// }}}
// {{{ load()
class_prop_colors.prototype.load = function(id, node) {
	if (id != null && this.oldData != node.toString()) {
		this.clear();
		this.data = node;
		this.set_data();
		this.oldData = this.data.toString();
	}
};
// }}}
// {{{ set_data()
class_prop_colors.prototype.set_data = function() {
	this.onChange();
};
// }}}
// {{{  getPropNodes()
class_prop_colors.prototype.getPropNodes = function() {
	var propNodes = [];

	propNodes.push(this.data);
		
	return propNodes;	
};
// }}}

/*
 *	Class PropTemplates
 *
 *	Extends class_prop()
 *	Handles XSLT-Templates for XML-XSL-Conversion
 *	Cooperates with class_tree_templates()
 */
// {{{ constructor
class_prop_tpl_templates = function() {};
class_prop_tpl_templates.prototype = new class_prop();
// }}}
// {{{ init()
class_prop_tpl_templates.prototype.init = function(type, projectObj) {
	super.init(type, projectObj);
	_root.phpConnect.msgHandler.register_func("update_prop_" + this.type, this.set_data, this);
	_root.pocketConnect.msgHandler.register_func("update_prop_" + this.type, this.set_data, this);
};
// }}}
// {{{ load()
class_prop_tpl_templates.prototype.load = function(id) {
	if (id != null && id != this.activeId) {
		this.clear();
		this.onChange();
		_root.phpConnect.send("get_prop", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", id], ["type", this.type]]);
		this.activeId = id;
	}
};
// }}}
// {{{ setNodeIds()
class_prop_tpl_templates.prototype.setNodeIds = class_tree_tpl_templates.prototype.setNodeIds;
// }}}
// {{{ save()
class_prop_tpl_templates.prototype.save = function(id) {
	var tempNode = this.data.getRootNode();
	var save_data;
	
	for (i = 0; i < tempNode.childNodes.length; i++) {
		if (tempNode.childNodes[i].nid == id) {
			save_data = tempNode.childNodes[i];
		}	
	}

	_root.phpConnect.send("save_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["data", save_data], ["type", this.type]]);
	this.project.preview();	
};
// }}}
// {{{ getPropNodes()
class_prop_tpl_templates.prototype.getPropNodes = function() {
	var tempXML = new XML();
	var tempNode;
	var propNodes = [];

	propNodes.push(this.data.getRootNode());
	
	tempNode = this.data.getRootNode().firstChild;
	while (tempNode != null) {
		if (this.isPropNode(tempNode)) {
			propNodes.push(tempNode);
		}
		
		tempNode = tempNode.nextSibling;	
	}
	
	return propNodes;	
};
// }}}
// {{{ setTemplatePropActive()
class_prop_tpl_templates.prototype.setTemplatePropActive = function(id, newActive) {
	_root.phpConnect.send("set_template_node_active", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", id], ["type", this.type], ["new_active", newActive.toString()]]);
	this.data.attributes.active = newActive;
	this.project.preview();	
};
// }}}
// {{{ setTemplatePropType()
class_prop_tpl_templates.prototype.setTemplatePropType = function(id, newType) {
	_root.phpConnect.send("set_template_node_type", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", id], ["type", this.type], ["new_type", newType]]);
	this.data.attributes.type = newType;
	this.project.preview();	
};
// }}}

/*
 *	Class PropNewNodes
 *
 *	Extends class_prop()
 *	Handles new Elements for class_tree_page_data()
 *	Cooperates with class_tree_newnodes()
 */
// {{{ constructor
class_prop_tpl_newnodes = function() {};
class_prop_tpl_newnodes.prototype = new class_prop();
// }}}
// {{{ load()
class_prop_tpl_newnodes.prototype.load = function(id, node) {
	if (id != null && id != this.activeId) {
		this.clear();
		this.onChange();
		this.data = node;
		setTimeout(this.set_data, this, 10);
		this.activeId = id;
	}
};
// }}}
// {{{ set_data()
class_prop_tpl_newnodes.prototype.set_data = function() {
	this.onChange();
};
// }}}
// {{{ isPropNode()
class_prop_tpl_newnodes.prototype.isPropNode = function(node) {
	return !class_tree_newnodes.prototype.isTreeNode.apply(this, [node]);
};
// }}}
// {{{ save()
class_prop_tpl_newnodes.prototype.save = function(id) {
	var tempNode = this.data;
	var save_data;
	
	for (i = 0; i < tempNode.childNodes.length; i++) {
		if (tempNode.childNodes[i].nid == id) {
			save_data = tempNode.childNodes[i];
		}	
	}

	_root.phpConnect.send("save_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["data", save_data], ["type", this.type]]);
};
// }}}
// {{{ getPropNodes()
class_prop_tpl_newnodes.prototype.getPropNodes = function() {
	var tempNode;
	var propNodes = [];

	tempNode = this.data.firstChild;	
	while (tempNode != null) {
		if (this.isPropNode(tempNode)) {
			propNodes.push(tempNode);
		}
		
		tempNode = tempNode.nextSibling;	
	}
	
	return propNodes;	
};
// }}}

/*
 *	Class PropSettings 
 *
 *	Extends class_prop()
 *	Handles Project-Settings and its Modules
 */
// {{{ constructor
class_prop_settings = function() {};
class_prop_settings.prototype = new class_prop();

class_prop_settings.prototype.setNodeIds = class_tree_settings.prototype.setNodeIds;
// }}}
// {{{ load()
class_prop_settings.prototype.load = function(id, node) {
	if (id != null && id != this.activeId) {
		this.data = node;
		this.set_data();
		this.activeId = id;
	}
};
// }}}
// {{{ set_data()
class_prop_settings.prototype.set_data = function() {
	this.onChange();
};
// }}}
// {{{ getPropNodes()
class_prop_settings.prototype.getPropNodes = function() {
	var tempNode;
	var propNodes = [];

	if (this.data.nodeName == conf.ns.project + ":publish" || this.data.nodeName == conf.ns.project + ":template_sets" || this.data.nodeName == conf.ns.project + ":global_files" || this.data.nodeName == conf.ns.project + ":languages" || this.data.nodeName == conf.ns.project + ":navigations" || this.data.nodeName == conf.ns.project + ":variables" || this.data.nodeName == conf.ns.project + ":backup") {

	} else if (this.data.nodeName == conf.ns.project + ":template_set" || this.data.nodeName == conf.ns.project + ":global_file" || this.data.nodeName == conf.ns.project + ":publish_folder" || this.data.nodeName == conf.ns.project + ":language" || this.data.nodeName == conf.ns.project + ":navigation"  || this.data.nodeName == conf.ns.project + ":variable" || this.data.nodeName == conf.ns.project + ":backup_backup" || this.data.nodeName == conf.ns.project + ":backup_restore") {
		propNodes.push(this.data);
	} else {
		tempNode = this.data.firstChild;	
		while (tempNode != null) {
			if (this.isPropNode(tempNode)) {
				propNodes.push(tempNode);
			}
			
			tempNode = tempNode.nextSibling;	
		}
	}
	
	return propNodes;	
};
// }}}
// {{{ save()
class_prop_settings.prototype.save = function(id) {
	var tempNode = this.data;
	var save_data;
	
	if (tempNode.nid == id) {
		save_data = tempNode;
	} else {
		for (i = 0; i < tempNode.childNodes.length; i++) {
			if (tempNode.childNodes[i].nid == id) {
				save_data = tempNode.childNodes[i];
			}	
		}
	}
	
	_root.phpConnect.send("save_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["data", save_data], ["type", this.type]]);
	this.project.preview();	
};
// }}}

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
