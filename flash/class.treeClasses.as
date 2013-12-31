/**
 * Class Tree
 * 
 * main Tree Class
 */
// {{{ constructor
class_tree = function() {};

class_tree.prototype.showRootNode = true;
class_tree.prototype.showChildrenInitLevel = 3;
class_tree.prototype.allowReordering = true;
// }}}
// {{{ init()
class_tree.prototype.init = function(type, projectObj) {
	this.type = type;
	
	this.project = projectObj;
	this.loading = false;
	this.clipboardMsgHandler = new class_ttRpcMsgHandler(conf.nsrpc, conf.nsrpcuri);
	this.onChangeObjs = [];
	this.clear();
	
	this.addProperty("updateEnabled", this.updateEnabledGet, this.updateEnabledSet);
};
// }}}
// {{{ updateEnabledSet()
class_tree.prototype.updateEnabledSet = function(value) {
	this.__updateEnabled = Boolean(value);
};
// }}}
// {{{ updateEnabledGet()
class_tree.prototype.updateEnabledGet = function() {
	return this.__updateEnabled == undefined ? true : this.__updateEnabled;
};
// }}}
// {{{ clear()
class_tree.prototype.clear = function() {
	this.data = new XML("<empty " + conf.ns.database + ":name=\"tree_nodata\"></empty>");
};
// }}}
// {{{ load()
class_tree.prototype.load = function() {
	var args = [];
	
	this.loading = true;
	args['data'] = this.data.toString();
	
	this.set_data(args);
};
// }}}
// {{{ set_data()
class_tree.prototype.set_data = function(args) {
	this.loading = false;
	if (!args['error']) {
		this.data = new XML(args['data']);
		this.setNodeIds();
		this.onChange();
	}
};
// }}}
// {{{ load_prop()
class_tree.prototype.load_prop = function(id, node, reload) {
	this.project.prop[this.type].load(id, node, reload);
};
// }}}
// {{{ setNodeIds()
class_tree.prototype.setNodeIds = function(actualNode) {
	if (actualNode == undefined) {
		this.setNodeIds(this.data);
		this.maxNodeId = 0;
	} else {
		actualNode.nid = this.maxNodeId++;
		actualNode = actualNode.firstChild;
		while (actualNode != null) {
			this.setNodeIds(actualNode);
			actualNode = actualNode.nextSibling;	
		}
	}
};
// }}}
// {{{ update_data()
class_tree.prototype.update_data = function(args) {
	this.onChange();
};
// }}}
// {{{ isEmpty()
class_tree.prototype.isEmpty = function() {
	return this.data.getRootNode().nodeName == "empty";	
};
// }}}
// {{{ isTreeNode()
class_tree.prototype.isTreeNode = function(node) {
	return node.nodeType == 1;	
};
// }}}
// {{{ isFolder()
class_tree.prototype.isFolder = function(node) {
	return node.nodeName == conf.ns.page + ":folder";	
};
// }}}
// {{{ isSeparatorNode()
class_tree.prototype.isSeparatorNode = function(node) {
	return node.nodeName == conf.ns.section + ":separator";	
};
// }}}
// {{{ isAccessible
class_tree.prototype.isAccessible = function(node) {
	return node.nid != undefined && node.nid != "";
};
// }}}
// {{{ isRenamable()
class_tree.prototype.isRenamable = function(node) {
        if (node.nodeName == conf.ns.section + ":separator") {
            return false;
        } else if (node.attributes[conf.ns.database + ":invalid"] != undefined) {
		var invalidActions = node.attributes[conf.ns.database + ":invalid"].replace(" ", "").split(",");
		return invalidActions.searchFor("name") == -1;
	} else {
		return true;	
	}
};
// }}}
// {{{ isMovable()
class_tree.prototype.isMovable = function(node) {
	if (node.attributes[conf.ns.database + ":invalid"] != undefined) {
		var invalidActions = node.attributes[conf.ns.database + ":invalid"].replace(" ", "").split(",");
		if (invalidActions.searchFor("move") > -1) {
			return false;
		} else {
			return true;
		}
	} else {
		return true;
	}
};
// }}}
// {{{ isValidMove()
class_tree.prototype.isValidMove = function(node, targetNode) {
	var isValid = node!=targetNode && !node.isRootNode() && !node.isParentNodeOf(targetNode) && targetNode.parentNode != null;
	if (isValid && node.attributes[conf.ns.database + ":invalid"] != undefined) {
		var invalidActions = node.attributes[conf.ns.database + ":invalid"].replace(" ", "").split(",");
		if (invalidActions.searchFor("move") > -1) {
			return false;		
		} else if (invalidActions.searchFor("inlayer") > -1 && node.parentNode != targetNode) {
			return false;
		} else {
			return true;
		}
	} else {
		return isValid;	
	}
};
// }}}
// {{{ isValidCopy()
class_tree.prototype.isValidCopy = function(node, targetNode) {
	var isValid = !node.isParentNodeOf(targetNode) && targetNode.parentNode != null;
	if (isValid && node.attributes[conf.ns.database + ":invalid"] != undefined) {
		var invalidActions = node.attributes[conf.ns.database + ":invalid"].replace(" ", "").split(",");
		if (invalidActions.searchFor("dupl") > -1) {
			return false;		
		} else {
			return true;
		}
	} else {
		return isValid;	
	}
};
// }}}
// {{{ isValidDelete()
class_tree.prototype.isValidDelete = function(node) {
	var isValid = node.parentNode.nodeType == 1 && node.parentNode.nodeName != null;
	if (isValid && node.attributes[conf.ns.database + ":invalid"] != undefined) {
		var invalidActions = node.attributes[conf.ns.database + ":invalid"].replace(" ", "").split(",");
		if (invalidActions.searchFor("del") > -1) {
			return false;		
		} else {
			return true;
		}
	} else {
		return isValid;	
	}
};
// }}}
// {{{isValidDuplicate()
class_tree.prototype.isValidDuplicate = function(node) {
	var isValid = (this.showRootNode || node != this.data.getRootNode()) && node != null;
	if (isValid && node.attributes[conf.ns.database + ":invalid"] != undefined) {
		var invalidActions = node.attributes[conf.ns.database + ":invalid"].replace(" ", "").split(",");
		if (invalidActions.searchFor("dupl") > -1) {
			return false;		
		} else {
			return true;
		}
	} else {
		return isValid;	
	}
};
// }}}
// {{{ isValidName
class_tree.prototype.isValidName = function(node, new_name) {
	return true;
};
// }}}
// {{{ hasTreeChildNodes()
class_tree.prototype.hasTreeChildNodes = function(node) {
	var actualChild;
	
	actualChild = node.firstChild;
	while (actualChild != null) {
		if (this.isTreeNode(actualChild)) {
			return true;	
		}
		actualChild = actualChild.nextSibling;
	}	
	return false;
};
// }}}
// {{{ setActiveIdOutside()
class_tree.prototype.setActiveIdOutside = function(args) {
	var i;
	
	for (i = 0; i < this.onChangeObjs.length; i++) {
		this.onChangeObjs[i].setActiveNodeByIdWaiting(args['id']);
	}
};
// }}}
// {{{ renameNode()
class_tree.prototype.renameNode = function(node, newName) {
	node.attributes['name'] = newName;
};
// }}}
// {{{ getValidNameLetters()
class_tree.prototype.getValidNameLetters = function() {
	return "";
};
// }}}
// {{{ onChange()
class_tree.prototype.onChange = function(node) {
	var i;
	
	for (i = 0; i < this.onChangeObjs.length; i++) {
		this.onChangeObjs[i].onChange(node);
	}
};
// }}}
// {{{ addOnChangeListener()
class_tree.prototype.addOnChangeListener = function(obj) {
	var i, found = false;
	
	for (i = 0; i < this.onChangeObjs.length; i++) {
		if (this.onChangeObjs[i] == obj) {
			found = true;
		}
	}
	
	if (!found) {
		this.onChangeObjs.push(obj);
	}
};

class_tree.prototype.removeOnChangeListener = function (obj) {
	var i;
	
	for (i = 0; i < this.onChangeObjs.length; i++) {
		if (this.onChangeObjs[i] == obj) {
			this.onChangeObjs.splice(i, 1);
			i--;
		}
	}
};
// }}}
// {{{ onPaste()
class_tree.prototype.onPaste = function(value) {
	this.onChange();
};
// }}}
// {{{ onCut()
class_tree.prototype.onCut = function() {
	this.onChange();
};
// }}}
// {{{ onDelete()
class_tree.prototype.onDelete = function(node) {
	if (this.isValidDelete(node)) {
		var tempNode = node;
		if (node.nextSibling != null) {
			node = node.nextSibling;
		} else if (node.previousSibling != null) {
			node = node.previousSibling;
		} else {
			node = node.parentNode;
		}
		tempNode.removeNode();
		this.onChange(node);
	}
};
// }}}
// {{{ duplicate()
class_tree.prototype.duplicate = function(node, noOnChange) {
	if (node != null && this.isValidDuplicate(node)) {
		tempNode = node.cloneNode(false);
		if (node.isRootNode()) {
			node.appendChild(tempNode);		
		} else {
			if (node.nextSibling != null) {
				node.parentNode.insertBefore(tempNode, node.nextSibling);	
			} else {
				node.parentNode.appendChild(tempNode);			
			}
		}
                if (noOnChange != true) {
                    this.onChange(); 
                }
	}
	return tempNode;
};
// }}}
// {{{  move_in()
class_tree.prototype.move_in = function(node, targetNode) {
	if (this.isValidMove(node, targetNode)) {
		var tempXML = new XML();
		tempXML.appendChild(node);
		
		targetNode.appendChild(node);
	}
	this.onChange();	
};
// }}}
// {{{ move_before()
class_tree.prototype.move_before = function(node, targetNode) {
	if (node != targetNode && this.isValidMove(node, targetNode.parentNode)) {
		var tempXML = new XML();
		tempXML.appendChild(node);
	
		targetNode.parentNode.insertBefore(node, targetNode);
		this.onChange();	
	}
};
// }}}
// {{{ move_after()
class_tree.prototype.move_after = function(node, targetNode) {
	if (node != targetNode && this.isValidMove(node, targetNode.parentNode)) {
		var tempXML = new XML();
		tempXML.appendChild(node);
	
		if (targetNode.nextSibling == null) {
			targetNode.parentNode.appendChild(node);
		} else {
			targetNode.parentNode.insertBefore(node, targetNode.nextSibling);
		}
		this.onChange();	
	}
};
// }}}
// {{{ copy_in()
class_tree.prototype.copy_in = function(node, targetNode) {
	if (this.isValidCopy(node, targetNode)) {
		var tempNode = node.cloneNode(true)
		targetNode.appendChild(tempNode);
		this.onChange(tempNode);
		return tempNode;
	}
};
// }}}
// {{{ copy_before()
class_tree.prototype.copy_before = function(node, targetNode) {
	if (this.isValidCopy(node, targetNode.parentNode)) {
		var tempNode = node.cloneNode(true);
		targetNode.parentNode.insertBefore(tempNode, targetNode);
		this.onChange(tempNode);
		return tempNode;
	}
};
// }}}
// {{{ copy_after()
class_tree.prototype.copy_after = function(node, targetNode) {
	if (this.isValidCopy(node, targetNode.parentNode)) {
		var tempNode = node.cloneNode(true);
		if (targetNode.nextSibling == null) {
			targetNode.parentNode.appendChild(tempNode);
		} else {
			targetNode.parentNode.insertBefore(tempNode, targetNode.nextSibling);
		}
		this.onChange(tempNode);
		return tempNode;
	}
};
// }}}
// {{{ getAddNodes()
class_tree.prototype.getAddNodes = function(targetNode) {
	return [];	
};
// }}}
// {{{ addNode()
class_tree.prototype.addNode = function(targetNode, type) {
	var tempXML = new XML();
	var tempNode = tempXML.createElement(type);
	targetNode.appendChild(tempNode);
	return tempNode;
};
// }}}
// {{{ idIsInData()
class_tree.prototype.idIsInData = function(idsToSearch, actualNode) {
	if (actualNode == undefined) {
		return this.idIsInData(IdsToSearch, this.data);	
	} else {
		var i;
		var found = false;
		
		for (i = 0; i < idsToSearch.length; i++) {
			if (actualNode.nid == idsToSearch[i]) {
				found = true;
			}	
		}
		
		actualNode = actualNode.firstChild;
		while (!found && actualNode != null) {
			found = this.idIsInData(idsToSearch, actualNode);
			
			actualNode = actualNode.nextSibling;
		}
		
		return found;
	}
};
// }}}

/*
 *	Class TreeContent
 *
 *	Extends class_tree()
 *	Handles Site-Structure on XML_DB
 */
// {{{ constructor()
class_tree_pages = function() {};
class_tree_pages.prototype = new class_tree();

class_tree_pages.prototype.showRootNode = false;
class_tree_pages.prototype.showChildrenInitLevel = 2;
// }}}
// {{{ init()
class_tree_pages.prototype.init = function(type, projectObj) {
	super.init(type, projectObj);
	
	_root.pocketConnect.msgHandler.register_func("update_tree_" + this.type, this.set_data, this);
	_root.phpConnect.msgHandler.register_func("update_tree_" + this.type, this.set_data, this);
	_root.phpConnect.msgHandler.register_func("set_activeId_" + this.type, this.setActiveIdOutside, this);
};
// }}}
// {{{ load()
class_tree_pages.prototype.load = function() {
	this.loading = true;
	_root.phpConnect.send("get_tree", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["type", this.type]]);
};
// }}}
// {{{ set_data()
class_tree_pages.prototype.set_data = function(args) {
	this.loading = false;
	if (!args['error'] && this.updateEnabled) {
            this.data = new XML(args['data']);
            this.setNodeIds();
            this.onChange();
	}
};
// }}}
// {{{ setNodeIds()
class_tree_pages.prototype.setNodeIds = function(actualNode) {
	if (actualNode == undefined) {
		this.data.setNodeIdByDBId();
	} else {
		actualNode.setNodeIdByDBId();
	}
};
// }}}
// {{{ getValidNameLetters()
class_tree_pages.prototype.getValidNameLetters = function() {
	return "";	
};
// }}}
// {{{ isTreeNode()
class_tree_pages.prototype.isTreeNode = function(node) {
	return super.isTreeNode(node) && (node.nodeName == conf.ns.page + ":page" || node.nodeName == conf.ns.page + ":folder" || node.nodeName == conf.ns.project + ":pages_struct" || node.nodeName == conf.ns.section + ":separator");	
};
// }}}
// {{{ isSeparatorNode()
class_tree_pages.prototype.isSeparatorNode = function(node) {
	return false;
};
// }}}
// {{{ isValidDelete()
class_tree_pages.prototype.isValidDelete = function(node) {
	return super.isValidDelete(node) && conf.user.mayDeletePages();
};
// }}}
// {{{ isValiMove()
class_tree_pages.prototype.isValidMove = function(node, targetNode) {
	return super.isValidMove(node, targetNode) && (node.nodeName == conf.ns.page + ":page" || node.nodeName == conf.ns.page + ":folder" || node.nodeName == conf.ns.section + ":separator");
};
// }}}
// {{{ isValidCopy()
class_tree_pages.prototype.isValidCopy = function(node, targetNode) {
	return super.isValidCopy(node, targetNode) && (node.nodeName == conf.ns.page + ":page" || node.nodeName == conf.ns.page + ":folder" || node.nodeName == conf.ns.section + ":separator");
};
// }}}
// {{{ isValidName()
class_tree_pages.prototype.isValidName = function(node, new_name) {
	var i;
	
	for (i = 0; i < node.parentNode.childNodes.length; i++) {
		if (node.parentNode.childNodes[i] != node) {
			if (node.parentNode.childNodes[i].attributes.name == new_name) {
				return false;
			}
		}
	}
	return true;
};
// }}}
// {{{ onPaste()
class_tree_pages.prototype.onPaste = function(value) {
	super.onPaste();
};
// }}}
// {{{ onCut()
class_tree_pages.prototype.onCut = function() {
	super.onCut();
};
// }}}
// {{{ onDelete()
class_tree_pages.prototype.onDelete = function(node) {
	var idToDelete;
	if (this.isValidDelete(node)) {
		idToDelete = node.nid;
		
		node.removeIds();
		this.loading = true;
		this.onChange();
		
		_root.phpConnect.send("delete_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", idToDelete], ["type", this.type]]);
		
		super.onDelete();
	}
};
// }}}
// {{{ duplicate()
class_tree_pages.prototype.duplicate = function(node) {
	if (this.isValidDuplicate(node)) {
		var newName = node.attributes.name + " " + conf.lang.tree_after_copy;
		var tempNode = super.duplicate(node, true);
                var copyid = node.nid;
		tempNode.attributes.name = newName;
		tempNode.removeIds();
		this.loading = true;
		this.onChange();
		
		_root.phpConnect.send("duplicate_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", copyid], ["new_name", newName], ["type", this.type]]);
	}
};
// }}}
// {{{ renameNode()
class_tree_pages.prototype.renameNode = function(node, newName) {
	this.loading = true;
	_root.phpConnect.send("rename_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", node.nid], ["new_name", newName], ["type", this.type]]);
	super.renameNode(node, newName);
};
// }}}
// {{{ move_in()
class_tree_pages.prototype.move_in = function(node, targetNode) {
	if (this.isValidMove(node, targetNode)) {
		this.loading = true;
		_root.phpConnect.send("move_node_in", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", node.nid], ["target_id", targetNode.nid], ["type", this.type]]);
		super.move_in(node, targetNode);
		node.removeIds();
		this.onChange();
	}
};
// }}}
// {{{ move_before()
class_tree_pages.prototype.move_before = function(node, targetNode) {
	if (node != targetNode && this.isValidMove(node, targetNode.parentNode)) {
		this.loading = true;
		_root.phpConnect.send("move_node_before", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", node.nid], ["target_id", targetNode.nid], ["type", this.type]]);
		super.move_before(node, targetNode);
		node.removeIds();
		this.onChange();
	}
};
// }}}
// {{{ move_after()
class_tree_pages.prototype.move_after = function(node, targetNode) {
	if (node != targetNode && this.isValidMove(node, targetNode.parentNode)) {
		this.loading = true;
		_root.phpConnect.send("move_node_after", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", node.nid], ["target_id", targetNode.nid], ["type", this.type]]);
		super.move_after(node, targetNode);
		node.removeIds();
		this.onChange();
	}
};
// }}}
// {{{ copy_in
class_tree_pages.prototype.copy_in = function(node, targetNode) {
	if (this.isValidCopy(node, targetNode)) {
		var newName =  node.attributes.name + " " + conf.lang.tree_after_copy;
		this.loading = true;
		_root.phpConnect.send("copy_node_in", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", node.nid], ["target_id", targetNode.nid], ["type", this.type], ["new_name", newName]]);
		var tempNode = super.copy_in(node, targetNode);
		tempNode.attributes.name = newName;
		tempNode.removeIds();
		this.onChange();
	}
};
// }}}
// {{{ copy_before()
class_tree_pages.prototype.copy_before = function(node, targetNode) {
	if (this.isValidCopy(node, targetNode.parentNode)) {
		var newName =  node.attributes.name + " " + conf.lang.tree_after_copy;
		this.loading = true;
		_root.phpConnect.send("copy_node_before", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", node.nid], ["target_id", targetNode.nid], ["type", this.type], ["new_name", newName]]);
		var tempNode = super.copy_before(node, targetNode);
		tempNode.attributes.name = newName;
		tempNode.removeIds();
		this.onChange();
	}
};
// }}}
// {{{ copy_after()
class_tree_pages.prototype.copy_after = function(node, targetNode) {
	if (this.isValidCopy(node, targetNode.parentNode)) {
		var newName =  node.attributes.name + " " + conf.lang.tree_after_copy;
		this.loading = true;
		_root.phpConnect.send("copy_node_after", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", node.nid], ["target_id", targetNode.nid], ["type", this.type], ["new_name", newName]]);
		var tempNode = super.copy_after(node, targetNode);
		tempNode.attributes.name = newName;
		tempNode.removeIds();
		this.onChange();
	}
};
// }}}
// {{{ getAddNodes()
class_tree_pages.prototype.getAddNodes = function(targetNode) {
	return [conf.lang.tree_name_new_folder, [conf.lang.tree_name_new_page, [conf.lang.tree_name_new_page_empty].concat(this.project.tree.page_data.getAddNodes())], conf.lang.tree_name_new_separator, conf.lang.tree_name_new_redirect];	
};
// }}}
// {{{ addNode()
class_tree_pages.prototype.addNode = function(targetNode, type, subType) {
	var temp_node;
	var add_node_string = "";
	var new_name = conf.lang.tree_name_untitled;
	
	if (type == conf.lang.tree_name_new_folder) {
		type = "folder";
		var newNode = super.addNode(targetNode, conf.ns.page + ":folder");	
        } else if (type == conf.lang.tree_name_new_separator) {
		type = "separator";
		var newNode = super.addNode(targetNode, conf.ns.section + ":separator");	
        } else if (type == conf.lang.tree_name_new_redirect) {
		type = "redirect";
		var newNode = super.addNode(targetNode, conf.ns.section + ":page");	
	} else if (type == conf.lang.tree_name_new_page) {
		type = "page";
		if (subType != null && subType != conf.lang.tree_name_new_page_empty) {
			root_node = this.project.tree.tpl_newnodes.data.getRootNode();
                        for (i = 0; i < root_node.childNodes.length; i++) {
                                temp_node = root_node.childNodes[i];
				if (temp_node.attributes.name == subType) {
					for (j = 0; j < temp_node.childNodes.length; j++) {
						if (temp_node.childNodes[j].nodeName == conf.ns.edit + ":newnode") {
							add_node_string += temp_node.childNodes[j].firstChild.nodeValue;
						}
					}
				}
			}	
		}
		var newNode = super.addNode(targetNode, conf.ns.page + ":page");	
	}
	if (targetNode != null) {
		targetNode.showChildren = true;
		this.loading = true;
		_root.phpConnect.send("add_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["target_id", targetNode.nid], ["type", this.type], ["node_type", type], ["xmldata", add_node_string], ["new_name", new_name]]);
		newNode.attributes.name = new_name;
		this.onChange();
	}	
};
// }}}
// {{{ getPathById()
class_tree_pages.prototype.getPathById = function(id, lang, type) {
	var i;
	var path = "";
	var languages = conf.project.tree.settings.getLanguages();
	var tempNode = this.data.searchForId(id);
	var multilang = tempNode.attributes.multilang == "true";
        var url = tempNode.attributes.url;

        if (url.substr(url.length - 4) == ".php") {
            url = url.substr(0, url.length - 4) + ".html";
        }

        if (url == "") {
		path = "";
        } else if (multilang) {
		path = "/" + lang + url;
	} else {
		path = "/int" + url;
	}

	return path;	
};
// }}}
// {{{ getIdByPath()
class_tree_pages.prototype.getIdByPath = function(id) {
	// really needed ?
};
// }}}
// {{{ getUriById()
class_tree_pages.prototype.getUriById = function(id) {
	var path = "";
	var tempNode = this.data.searchForId(id);
	
	if (tempNode == null) {
		return "";
	}
	
	while (tempNode != this.data) {
		if (tempNode != this.data.getRootNode()) {
			if (id == tempNode.nid && !this.isFolder(tempNode)) {
				//path = tempNode.attributes.name;
				path = tempNode.attributes.name.glpEncode();
			} else {
				path = tempNode.attributes.name.glpEncode() + "/" + path;
			}
		}
		tempNode = tempNode.parentNode;
	}
	
	return  conf.url_page_scheme_intern + ":/" + path;
};
// }}}
// {{{ getUrlById()
class_tree_pages.prototype.getUrlById = function(id) {
	var path = "";
	var tempNode = this.data.searchForId(id);
	
	if (tempNode == null) {
		return "";
	}
	
        path = tempNode.attributes.url;

	return  conf.url_page_scheme_intern + ":/" + path;
};
// }}}
// {{{ getIdByUri()
class_tree_pages.prototype.getIdByURI = function(uri) {
	var i, j;
	var tempNode;
	var uri = uri.split("/");
	
	if (uri[uri.length - 1] != "") {
		uri.push("");
	}
	if (uri.length < 2) {
		targetNode = null;
	} else if (uri.length == 2 && uri[1] == "") {
		targetNode = this.data.getRootNode();
	} else {
		tempNode = this.data.getRootNode();
		for (i = 1; i < uri.length - 1; i++) {
			tempNode = tempNode.firstChild;
			while (tempNode != null && tempNode.attributes.name.glpEncode() != uri[i]) {
				tempNode = tempNode.nextSibling;
			}
			if (tempNode == null) {
				var targetNode = null;
				break;
			} else {
				var targetNode = tempNode;	
			}
		}
	}
	
	if (targetNode == null) {
		return "";
	} else {
		return targetNode.nid;
	}
};
// }}}
// {{{ getIdByUrl()
class_tree_pages.prototype.getIdByURL = function(url, node) {
    if (node == undefined) {
        node = this.data.getRootNode();
    }
	
    testurl1 = url;
    if (testurl1.substr(testurl1.length - 5) == ".html") {
        testurl2 = testurl1.substr(0, testurl1.length - 5) + ".php";
    }

    if (!this.isFolder(node) && (node.attributes.url == testurl1 || node.attributes.url == testurl2)) {
        return node.nid;
    } else {
        for (var i = 0; i < node.childNodes.length; i++) {
            nid = this.getIdByURL(url, node.childNodes[i]);
            if (nid != "") {
                return nid;
            }
        }

    }
    return "";
};
// }}}

/*
 *	Class Treepage_data
 *
 *	Extends class_tree()
 *	Handles Pages on XML_DB
 */
// {{{ constructor
class_tree_page_data = function() {};
class_tree_page_data.prototype = new class_tree();

class_tree_page_data.prototype.setNodeIds = class_tree_pages.prototype.setNodeIds;
// }}}
// {{{ init()
class_tree_page_data.prototype.init = function(type, projectObj) {
	super.init(type, projectObj);
		
	_root.phpConnect.msgHandler.register_func("update_tree_" + this.type, this.set_data, this);
	_root.phpConnect.msgHandler.register_func("set_activeId_" + this.type, this.setActiveIdOutside, this);
	_root.pocketConnect.msgHandler.register_func("get_update_tree_" + this.type, this.get_new_update, this);
	_root.phpConnect.msgHandler.register_func("get_update_tree_" + this.type, this.get_new_update, this);
};
// }}}
// {{{ get_new_update()
class_tree_page_data.prototype.get_new_update = function(args) {
	var i;
	var idsToUpdate = [];
	
	for (i = 1; i <= int(args['id_num']); i++) {
		idsToUpdate.push(args['id' + i]);	
	}
	
	if (this.idIsInData(idsToUpdate)) {
		if (this.updateEnabled) {
			this.load(this.lastid, null, true);
		}
	}
};
// }}}
// {{{ load()
class_tree_page_data.prototype.load = function(id, node, reload) {
	if (id != undefined && (id != this.lastid || reload)) {
		if (id != this.lastid) {
                    this.clear();
		}
                if (node.nodeName != conf.ns.section + ":separator") {
                    this.loading = true;
                    this.lastid = id;
                    this.onChange();
                    _root.phpConnect.send("get_tree", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["type", this.type], ["id", id]]);
                } else {
                    this.loading = false;
                    this.lastid = null;
                    this.onChange();
                }
	}
};
// }}}
// {{{ set_data()
class_tree_page_data.prototype.set_data = function(args) {
	this.loading = false;
	if (!args['error']) {
		this.data = new XML(args['data']);
		
		this.setNodeIds();
		this.onChange();
	}
};
// }}}
// {{{ isTreeNode()
class_tree_page_data.prototype.isTreeNode = function(node) {
	return node.getNameSpace() == conf.ns.section.toString() || node.nodeName == conf.ns.page + ":page_data" || node.nodeName == conf.ns.page + ":folder_data" || node.nodeName == conf.ns.page + ":meta" || node.nodeName == conf.ns.edit + ":plain_source";	
};
// }}}
// {{{ getAddNodes()
class_tree_page_data.prototype.getAddNodes = function(targetNode) {
	if (targetNode == undefined) {
		targetNodeName = conf.ns.page + ":page_data";
	} else if (targetNode.nodeName == conf.ns.page + ":meta" && targetNode.parentNode.nodeName != conf.ns.page + ":folder_data") {
		targetNodeName = conf.ns.page + ":page_data";
	} else {
		targetNodeName = targetNode.nodeName;
	}

	var temp_node, i, temp_string;
	var parent_array = [];
	var node_array = [];
	
	temp_node = this.project.tree.tpl_newnodes.data.getRootNode().firstChild;
	while (temp_node != null) {
		for (i = 0; i < temp_node.childNodes.length; i++) {
			if (temp_node.childNodes[i].nodeName == conf.ns.edit + ":newnode_valid_parents") {
				temp_string = temp_node.childNodes[i].firstChild.nodeValue;
				temp_string = temp_string.replace([
					[" ", ""],
					[";", ","]
				]);
				parent_array = temp_string.split(",");
			}
		}
		
		for (i = 0; i < parent_array.length; i++) {
			if (parent_array[i] == "*" || parent_array[i] == targetNodeName) {
				node_array.push(temp_node.attributes.name);
				break;
			}
		}
		
		temp_node = temp_node.nextSibling;
	}
	
	return node_array;	
};
// }}}
// {{{ getValidParents()
class_tree_page_data.prototype.getValidParents = function(nodeName) {
	var temp_node, i, j, temp_string, temp_xml;
	var node_array = [];
	
	temp_node = this.project.tree.tpl_newnodes.data.getRootNode().firstChild;
	while (temp_node != null) {
		for (i = 0; i < temp_node.childNodes.length; i++) {
			if (temp_node.childNodes[i].nodeName == conf.ns.edit + ":newnode") {
				temp_xml = new XML(temp_node.childNodes[i].firstChild.nodeValue);
				if (temp_xml.getRootNode().nodeName == nodeName) {
					for (j = 0; j < temp_node.childNodes.length; j++) {
						if (temp_node.childNodes[j].nodeName == conf.ns.edit + ":newnode_valid_parents") {
							temp_string = temp_node.childNodes[j].firstChild.nodeValue;
							temp_string = temp_string.replace([
								[" ", ""],
								[";", ","]
							]);
							node_array = temp_string.split(",");
						}
					}
					temp_node = null;
				}
			}
		}
		
		if (temp_node != null) {
			temp_node = temp_node.nextSibling;
		}
	}
	
	return node_array;	
};
// }}}
// {{{ addNode()
class_tree_page_data.prototype.addNode = function(targetNode, type) {
	var i, j;
	var temp_node, add_node, add_node_string = "", temp_XML;
	
	if (targetNode.nodeName == conf.ns.page + ":meta") {
		targetNode = targetNode.parentNode;
	}
	
	if (targetNode != null) {
		temp_node = this.project.tree.tpl_newnodes.data.getRootNode().firstChild;
		while (temp_node != null) {
			if (temp_node.attributes.name == type) {
				for (i = 0; i < temp_node.childNodes.length; i++) {
					if (temp_node.childNodes[i].nodeName == conf.ns.edit + ":newnode") {
						add_node_string += temp_node.childNodes[i].firstChild.nodeValue;
						temp_XML = new XML("<root>" + temp_node.childNodes[i].firstChild.nodeValue + "</root>");
						while (temp_XML.firstChild.hasChildNodes()) {
							targetNode.appendChild(temp_XML.firstChild.firstChild);
						}
					}
				}
			}
			
			temp_node = temp_node.nextSibling;
		}
		this.loading = true;
		this.onChange();
		_root.phpConnect.send("add_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["target_id", targetNode.nid], ["type", this.type], ["node_type", add_node_string], ["new_name", new_name]]);
	}	
};
// }}}
// {{{ onDelete()
class_tree_page_data.prototype.onDelete = function(node) {
	var idToDelete;
	if (this.isValidDelete(node)) {
		idToDelete = node.nid;
		node.removeIds();
		this.loading = true;
		this.onChange();
		_root.phpConnect.send("delete_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", idToDelete], ["type", this.type]]);
	}
};
// }}}
// {{{ duplicate()
class_tree_page_data.prototype.duplicate = function(node) {
	class_tree_pages.prototype.duplicate.apply(this, [node]);
};
// }}}
// {{{ renameNode()
class_tree_page_data.prototype.renameNode = function(node, newName) {
	class_tree_pages.prototype.renameNode.apply(this, [node, newName]);
};
// }}}
// {{{ move_in()
class_tree_page_data.prototype.move_in = function(node, targetNode) {
	class_tree_pages.prototype.move_in.apply(this, [node, targetNode]);
};
// }}}
// {{{ move_before()
class_tree_page_data.prototype.move_before = function(node, targetNode) {
	class_tree_pages.prototype.move_before.apply(this, [node, targetNode]);
};
// }}}
// {{{ move_after()
class_tree_page_data.prototype.move_after = function(node, targetNode) {
	class_tree_pages.prototype.move_after.apply(this, [node, targetNode]);
};
// }}}
// {{{ copy_in()
class_tree_page_data.prototype.copy_in = function(node, targetNode) {
	class_tree_pages.prototype.copy_in.apply(this, [node, targetNode]);
};
// }}}
// {{{ copy_before()
class_tree_page_data.prototype.copy_before = function(node, targetNode) {
	class_tree_pages.prototype.copy_before.apply(this, [node, targetNode]);
};
// }}}
// {{{ copy_after()
class_tree_page_data.prototype.copy_after = function(node, targetNode) {
	class_tree_pages.prototype.copy_after.apply(this, [node, targetNode]);
};
// }}}
// {{{ isValidMove()
class_tree_page_data.prototype.isValidMove = function(node, targetNode) {
	var validNodes, i;
	var isValid = super.isValidMove(node, targetNode);
	
	if (isValid) {
		isValid = false;
		validNodes = this.getValidParents(node.nodeName);
		for (i = 0; i < validNodes.length; i++) {
			if (validNodes[i] == "*" || validNodes[i] == targetNode.nodeName) {
				isValid = true;
			}
		}
	}
	
	return isValid
};
// }}}
// {{{ isValidCopy()
class_tree_page_data.prototype.isValidCopy = function(node, targetNode) {
	var isValid = super.isValidCopy(node, targetNode);
	
	if (isValid) {
		
	}
	
	return isValid
};
// }}}
// {{{ isValidDuplicate()
class_tree_page_data.prototype.isValidDuplicate = function(node) {
	var isValid = super.isValidDuplicate(node);
	
	if (node == this.data.getRootNode()) {
		isValid = false;
	}
	
	return isValid;
};
// }}}

/*
 *	Class TreeFiles
 *
 *	Extends class_tree()
 *	Handles Files and Directories of a Project
 */
// {{{ constructor()
class_tree_files = function() {};
class_tree_files.prototype = new class_tree();
class_tree_files.prototype.allowReordering = false;
// }}}
// {{{ getValidNameLetters()
class_tree_files.prototype.getValidNameLetters = function() {
	return "a-zA-Z0-9_.";	
};
// }}}
// {{{ init()
class_tree_files.prototype.init = function(type, projectObj) {
	super.init(type, projectObj);

	this.onChange();
		
	_root.phpConnect.msgHandler.register_func("update_tree_" + this.type, this.set_data, this);
	_root.phpConnect.msgHandler.register_func("set_activeId_" + this.type, this.setActiveIdOutside, this);
	//_root.pocketConnect.msgHandler.register_func("update_fileTree", this.set_data, this); //???
	
	this.setFileFilter();
};
// }}}
// {{{ onShow()
class_tree_files.prototype.onShow = function() {
	_root.pocketConnect.msgHandler.register_func("get_update_tree_" + this.type, this.load, this);
	_root.phpConnect.msgHandler.register_func("get_update_tree_" + this.type, this.load, this);
	_root.pocketConnect.msgHandler.register_func("get_update_prop_" + this.type, this.get_newFileProp_update, this);
	_root.phpConnect.msgHandler.register_func("get_update_prop_" + this.type, this.get_newFileProp_update, this);
	
	this.clear();
	this.onChange();
	this.load();	
};
// }}}
// {{{ onHide()
class_tree_files.prototype.onHide = function() {
	this.clear();
	this.propObj.clear();
	this.activeNode = null;
	
	_root.pocketConnect.msgHandler.unregister_func("get_update_tree_" + this.type);
	_root.pocketConnect.msgHandler.unregister_func("get_update_prop_" + this.type);
};
// }}}
// {{{ load()
class_tree_files.prototype.load = function() {
	this.selectedFile = "";
	this.loading = true;
	
	_root.phpConnect.send("get_tree", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["type", this.type]]);
};
// }}}
// {{{ get_newFileProp_update()
class_tree_files.prototype.get_newFileProp_update = function(args) {
        //alertObjInfo(args['path']);
	if (args['path'] == XPath.selectNodes(this.project.prop[this.type].data, "/" + conf.ns.project + ":files/" + conf.ns.project + ":filelist[1]/@dir")[0].nodeValue) {
		this.load_prop(args['path'], this.project.prop[this.type].data, true);
	}
};
// }}}
// {{{ setNodeIds()
class_tree_files.prototype.setNodeIds = function(actualNode, path) {
	if (actualNode == undefined) {
		this.setNodeIds(this.data.getRootNode(), "");
	} else {
		path = path + (actualNode.attributes['name'] == conf.project.name ? "" : actualNode.attributes['name']) + "/";
		actualNode.nid = path;
		actualNode = actualNode.firstChild;
		while (actualNode != null) {
			this.setNodeIds(actualNode, path);
			actualNode = actualNode.nextSibling;	
		}
	}
};
// }}}
// {{{ renameNode()
class_tree_files.prototype.renameNode = function(node, newName) {
	_root.phpConnect.send("rename_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", node.nid], ["new_name", newName], ["type", this.type]]);
	node.attributes['name'] = newName;
	this.setNodeIds(node, node.parentNode.nid);
};
// }}}
// {{{ onDelete()
class_tree_files.prototype.onDelete = function(node) {
	var idToDelete;
	if (this.isValidDelete(node)) {
		idToDelete = node.nid;
		_root.phpConnect.send("delete_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", idToDelete], ["type", this.type]]);
		node.removeIds();
		this.onChange();
	}
};
// }}}
// {{{ move_in()
class_tree_files.prototype.move_in = function(node, targetNode) {
	if (this.isValidMove(node, targetNode)) {
		_root.phpConnect.send("move_node_in", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", node.nid], ["target_id", targetNode.nid], ["type", this.type]]);
		super.move_in(node, targetNode);
		node.removeIds();
		this.onChange();
	}
};
// }}}
// {{{ copy_in()
class_tree_files.prototype.copy_in = function(node, targetNode) {
	if (this.isValidCopy(node, targetNode)) {
		_root.phpConnect.send("copy_node_in", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["id", node.nid], ["target_id", targetNode.nid], ["type", this.type], ["new_name", node.attributes.name + " " + conf.lang.tree_after_copy]]);
		super.copy_in(node, targetNode);
		node.removeIds();
		this.onChange();
	}
};
// }}}
// {{{ isTreeNode()
class_tree_files.prototype.isTreeNode = function(node) {
	return true;
};
// }}}
// {{{ isFolder()
class_tree_files.prototype.isFolder = function(node) {
	return true;
};
// }}}
// {{{ isValidDelete()
class_tree_files.prototype.isValidDelete = function(node) {
	return super.isValidDelete(node) && conf.user.mayDeleteFolder();
};
// }}}
// {{{ getAddNodes()
class_tree_files.prototype.getAddNodes = function(targetNode) {
	return [conf.lang.tree_name_new_folder];	
};
// }}}
// {{{ setFileFilter()
class_tree_files.prototype.setFileFilter = function(file_type, force_width, force_height) {
	if (file_type == undefined || file_type == "") {
		this.fileFilter = {
			file_type		: "",
			force_width		: "",
			force_height	: ""
		};
	} else {
		this.fileFilter = {
			file_type		: file_type == undefined ? "" : file_type,
			force_width		: force_width == undefined ? "" : force_width,
			force_height	: force_height == undefined ? "" : force_height
		};
	}
};
// }}}
// {{{ addNode()
class_tree_files.prototype.addNode = function(targetNode, type) {
	if (targetNode != null) {
		var newName = conf.lang.tree_name_untitled;
		this.loading = true;
		this.onChange();
		_root.phpConnect.send("add_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["target_id", targetNode.nid], ["type", this.type], ["node_type", type], ["new_name", newName]]);
	}	
};
// }}}

/*
 *	Class TreeColors
 *
 *	Extends class_tree()
 *	Handles Colorschemes
 */
// {{{ constructor()
class_tree_colors = function() {};
class_tree_colors.prototype = new class_tree();

class_tree_colors.prototype.showRootNode = false;
// }}}
// {{{ init()
class_tree_colors.prototype.init = function(type, projectObj) {
	super.init(type, projectObj);	
	_root.phpConnect.msgHandler.register_func("update_tree_" + this.type, this.set_data, this);
	_root.phpConnect.msgHandler.register_func("set_activeId_" + this.type, this.setActiveIdOutside, this);
	_root.pocketConnect.msgHandler.register_func("update_tree_" + this.type, this.set_data, this);
};
// }}}
// {{{ load()
class_tree_colors.prototype.load = function() {
	this.loading = true;
	_root.phpConnect.send("get_tree", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["type", this.type]]);
};
// }}}
// {{{ setNodeIds()
class_tree_colors.prototype.setNodeIds = class_tree_pages.prototype.setNodeIds;
// }}}
// {{{ set_data()
class_tree_colors.prototype.set_data = function(args) {
	this.loading = false;
	if (!args['error']  && this.updateEnabled) {
		this.data = new XML(args['data']);
		this.setNodeIds();
		this.onChange();
		this.project.preview();
	}
};
// }}}
// {{{ isTreeNode()
class_tree_colors.prototype.isTreeNode = function(node) {
	return node.getNameSpace() == conf.ns.project.toString() || this.isSeparatorNode(node);	
};
// }}}
// {{{ isGlobalColorscheme()
class_tree_colors.prototype.isGlobalColorscheme = function(node) {
	return node.attributes[conf.ns.database + ":name"] == "tree_name_color_global";
};
// }}}
// {{{ isRenamable()
class_tree_colors.prototype.isRenamable = function(node) {
	return !this.isGlobalColorscheme(node);	
};
// }}}
// {{{ isValidDuplicate()
class_tree_colors.prototype.isValidDuplicate = function(node) {
	return node != this.data.getRootNode() && !this.isGlobalColorscheme(node);
};
// }}}
// {{{ isValidDelete()
class_tree_colors.prototype.isValidDelete = function(node) {
	return node != this.data.getRootNode() && !this.isGlobalColorscheme(node);
};
// }}}
// {{{ isValidMove()
class_tree_colors.prototype.isValidMove = function(node, targetNode, extraNode) {
	return targetNode.isRootNode() && !this.isGlobalColorscheme(extraNode) && !this.isGlobalColorscheme(node);
};
// }}}
// {{{ isValidCopy()
class_tree_colors.prototype.isValidCopy = function(node, targetNode, extraNode) {
	return targetNode.isRootNode() && !this.isGlobalColorscheme(extraNode) && !this.isGlobalColorscheme(node);
};
// }}}
// {{{ onDelete()
class_tree_colors.prototype.onDelete = class_tree_pages.prototype.onDelete;
// }}}
// {{{ duplicate()
class_tree_colors.prototype.duplicate = class_tree_pages.prototype.duplicate;
// }}}
// {{{ renameNode()
class_tree_colors.prototype.renameNode = class_tree_pages.prototype.renameNode;
// }}}
// {{{ move_in()
class_tree_colors.prototype.move_in = class_tree_pages.prototype.move_in;
// }}} 
// {{{ move_before()
class_tree_colors.prototype.move_before = class_tree_pages.prototype.move_before;
// }}}
// {{{ move_after()
class_tree_colors.prototype.move_after = class_tree_pages.prototype.move_after;
// }}}
// {{{ copy_in()
class_tree_colors.prototype.copy_in = class_tree_pages.prototype.copy_in;
// }}}
// {{{ copy_before()
class_tree_colors.prototype.copy_before = class_tree_pages.prototype.copy_before;
// }}}
// {{{ copy_after()
class_tree_colors.prototype.copy_after = class_tree_pages.prototype.copy_after;
// }}}
// {{{ getColorschemes()
class_tree_colors.prototype.getColorschemes = function() {
	var tempNode;
	var colorArray = [];
	
	tempNode = this.data.getRootNode().firstChild;
	while (tempNode != null) {
		if (!this.isGlobalColorscheme(tempNode) && !this.isSeparatorNode(tempNode)) {
			colorArray.push(tempNode.attributes.name);
		}
	
		tempNode = tempNode.nextSibling;	
	}		
		
	return colorArray;
};
// }}}
// {{{ getColors()
class_tree_colors.prototype.getColors = function(colorschemeName) {
	var tempNode, i;
	var colorArray = [];
	
	tempNode = this.data.getRootNode().firstChild;
	while (tempNode != null) {
		if (!this.isGlobalColorscheme(tempNode) && !this.isSeparatorNode(tempNode)) {
			if (tempNode.attributes.name == colorschemeName) {
				for (i = 0; i < tempNode.childNodes.length; i++) {
					colorArray.push(tempNode.childNodes[i].attributes.value);
				}
			}
		}
	
		tempNode = tempNode.nextSibling;	
	}		
		
	return colorArray;
};
// }}}
// {{{ addColor()
class_tree_colors.prototype.addColor = function(colorschemeNode) {
	var tempXML = new XML("<color name=\"" + conf.lang.prop_tt_colorscheme_newcolor + "\" value=\"#000000\" />");
	var tempNode = tempXML.getRootNode();
	var colorschemesNode = colorschemeNode.parentNode;
	var i;
	
	if (this.isGlobalColorscheme(colorschemeNode)) {
		colorschemeNode.appendChild(tempNode);
		_root.phpConnect.send("save_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["data", colorschemeNode.toString()], ["type", this.type]]);
	} else if (!this.isSeparatorNode(colorschemeNode)) {
		for (i = 0; i < colorschemesNode.childNodes.length; i++) {
			if (!this.isGlobalColorscheme(colorschemesNode.childNodes[i]) && !this.isSeparator(colorschemesNode.childNodes[i])) {
				colorschemesNode.childNodes[i].appendChild(tempNode.cloneNode(true));
			}
		}
		_root.phpConnect.send("save_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["data", colorschemesNode.toString()], ["type", this.type]]);
	}
	//this.project.preview();
};
// }}}
// {{{ deleteColor()
class_tree_colors.prototype.deleteColor = function(colorNode) {
	var colorschemeNode = colorNode.parentNode;
	var colorschemesNode = colorschemeNode.parentNode;
	var i;
	var nodePos;
	
	if (this.isGlobalColorscheme(colorschemeNode)) {
		colorNode.removeNode();
		_root.phpConnect.send("save_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["data", colorschemeNode.toString()], ["type", this.type]]);
	} else {
		for (i = 0; i < colorschemeNode.childNodes.length; i++) {
			if (colorschemeNode.childNodes[i] == colorNode) {
				nodePos = i;
			}	
		}
		for (i = 0; i < colorschemesNode.childNodes.length; i++) {
			if (!this.isGlobalColorscheme(colorschemesNode.childNodes[i]) && !this.isSeparator(colorschemesNode.childNodes[i])) {
				colorschemesNode.childNodes[i].childNodes[nodePos].removeNode();
			}
		}
		_root.phpConnect.send("save_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["data", colorschemesNode.toString()], ["type", this.type]]);
	}
	//this.project.preview();
};
// }}}
// {{{ renameColor()
class_tree_colors.prototype.renameColor = function(colorNode, newName) {
	var colorschemeNode = colorNode.parentNode;
	var colorschemesNode = colorschemeNode.parentNode;
	var i;
	var nodePos;

	if (this.isGlobalColorscheme(colorschemeNode)) {
		colorNode.attributes.name = newName;
		_root.phpConnect.send("save_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["data", colorschemeNode.toString()], ["type", this.type]]);
	} else {
		for (i = 0; i < colorschemeNode.childNodes.length; i++) {
			if (colorschemeNode.childNodes[i] == colorNode) {
				nodePos = i;
			}	
		}
		for (i = 0; i < colorschemesNode.childNodes.length; i++) {
			if (!this.isGlobalColorscheme(colorschemesNode.childNodes[i]) && !this.isSeparator(colorschemesNode.childNodes[i])) {
				colorschemesNode.childNodes[i].childNodes[nodePos].attributes.name = newName;
			}
		}
		_root.phpConnect.send("save_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["data", colorschemesNode.toString()], ["type", this.type]]);
	}
};
// }}}
// {{{ setColor()
class_tree_colors.prototype.setColor = function(colorNode, newValue) {
	colorNode.attributes.value = newValue;
	
	_root.phpConnect.send("save_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["data", colorNode.toString()], ["type", this.type]]);
	//this.project.preview();
};
// }}}
// {{{ getAddNodes()
class_tree_colors.prototype.getAddNodes = function(targetNode) {
	return [conf.lang.tree_name_new_colorscheme];	
};
// }}}
// {{{ addNode()
class_tree_colors.prototype.addNode = function(targetNode, type) {
	var new_name = conf.lang.tree_name_untitled;
	if (type == conf.lang.tree_name_new_colorscheme) {
		type = "colorscheme";	
	}
	if (targetNode != null) {
		this.loading = true;
		this.onChange();
		_root.phpConnect.send("add_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["target_id", targetNode.nid], ["type", this.type], ["node_type", type], ["new_name", new_name]]);
	}	
	//this.project.preview();
};
// }}}

/*
 *	Class TreeTemplates
 *
 *	Extends class_tree()
 *	Handles XSLT-Templates for XML-XSL-Conversion
 */
// {{{ constructor
class_tree_tpl_templates = function() {};
class_tree_tpl_templates.prototype = new class_tree();

class_tree_tpl_templates.prototype.showRootNode = false;
class_tree_tpl_templates.prototype.showChildrenInitLevel = 2;
// }}}
// {{{ init()
class_tree_tpl_templates.prototype.init = function(type, projectObj) {
	super.init(type, projectObj);	
	_root.phpConnect.msgHandler.register_func("update_tree_" + this.type, this.set_data, this);
	_root.phpConnect.msgHandler.register_func("set_activeId_" + this.type, this.setActiveIdOutside, this);
	_root.pocketConnect.msgHandler.register_func("update_tree_" + this.type, this.set_data, this);
};
// }}}
// {{{ load()
class_tree_tpl_templates.prototype.load = function() {
	this.loading = true;
	_root.phpConnect.send("get_tree", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["type", this.type]]);
};
// }}}
// {{{ set_data()
class_tree_tpl_templates.prototype.set_data = function(args) {
	this.loading = false;
	if (!args['error'] && this.updateEnabled) {
		this.data = new XML(args['data']);
		this.setNodeIds();
		this.onChange();
	}
};
// }}}
// {{{ onShow()
class_tree_tpl_templates.prototype.onShow = function() {
	super.onShow();
	this.project.preview();	
};
// }}}
// {{{ isTreeNode()
class_tree_tpl_templates.prototype.isTreeNode = function(node) {
	return super.isTreeNode(node) && (node.nodeName == conf.ns.page + ":template" || node.nodeName == conf.ns.page + ":folder" || node.nodeName == conf.ns.project + ":tpl_templates_struct");	
};
// }}}
// {{{ setNodeIds()
class_tree_tpl_templates.prototype.setNodeIds = class_tree_pages.prototype.setNodeIds;
// }}}
// {{{ releaseTemplates()
class_tree_tpl_templates.prototype.releaseTemplates = function(template_type) {
	_root.phpConnect.send("release_templates", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["type", this.type], ["template_type", template_type]]);
};
// }}}
// {{{ onDelete()
class_tree_tpl_templates.prototype.onDelete = class_tree_pages.prototype.onDelete;
// }}}
// {{{ duplicate()
class_tree_tpl_templates.prototype.duplicate = class_tree_pages.prototype.duplicate;
// }}}
// {{{ renameNode()
class_tree_tpl_templates.prototype.renameNode = class_tree_pages.prototype.renameNode;
// }}}
// {{{ move_in()
class_tree_tpl_templates.prototype.move_in = class_tree_pages.prototype.move_in;
// }}}
// {{{ move_before()
class_tree_tpl_templates.prototype.move_before = class_tree_pages.prototype.move_before;
// }}}
// {{{ move_after()
class_tree_tpl_templates.prototype.move_after = class_tree_pages.prototype.move_after;
// }}}
// {{{ copy_in()
class_tree_tpl_templates.prototype.copy_in = class_tree_pages.prototype.copy_in;
// }}}
// {{{ copy_before()
class_tree_tpl_templates.prototype.copy_before = class_tree_pages.prototype.copy_before;
// }}}
// {{{ copy_after()
class_tree_tpl_templates.prototype.copy_after = class_tree_pages.prototype.copy_after;
// }}}
// {{{ getAddNodes()
class_tree_tpl_templates.prototype.getAddNodes = function(targetNode) {
	return [conf.lang.tree_name_new_folder, conf.lang.tree_name_new_template];	
};
// }}}
// {{{ addNode()
class_tree_tpl_templates.prototype.addNode = function(targetNode, type) {
	var new_name = conf.lang.tree_name_untitled;
	if (type == conf.lang.tree_name_new_folder) {
		type = "folder";	
		var tempNode = super.addNode(conf.ns.page + ":folder");
	} else if (type == conf.lang.tree_name_new_template) {
		type = "template";
		var tempNode = super.addNode(conf.ns.page + ":template");
	}
	if (targetNode != null) {
		tempNode.attributes.name = new_name;
		this.loading = true;
		this.onChange();
		_root.phpConnect.send("add_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["target_id", targetNode.nid], ["type", this.type], ["node_type", type], ["new_name", new_name]]);
	}	
};
// }}}

/*
 *	Class NewNodes
 *
 *	Extends class_tree()
 *	Handles new Elements for class_tree_page_data()
 */
// {{{ constructor
class_tree_tpl_newnodes = function() {};
class_tree_tpl_newnodes.prototype = new class_tree();

class_tree_tpl_newnodes.prototype.showRootNode = false;
// }}}
// {{{ init()
class_tree_tpl_newnodes.prototype.init = function(type, projectObj) {
	super.init(type, projectObj);	
	_root.phpConnect.msgHandler.register_func("update_tree_" + this.type, this.set_data, this);
	_root.phpConnect.msgHandler.register_func("set_activeId_" + this.type, this.setActiveIdOutside, this);
	_root.pocketConnect.msgHandler.register_func("update_tree_" + this.type, this.set_data, this);
};
// }}}
// {{{ load()
class_tree_tpl_newnodes.prototype.load = function() {
	this.loading = true;
	_root.phpConnect.send("get_tree", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["type", this.type]]);
};
// }}}
// {{{ set_data()
class_tree_tpl_newnodes.prototype.set_data = function(args) {
	this.loading = false;
	if (!args['error'] && this.updateEnabled) {
		this.data = new XML(args['data']);
		this.setNodeIds();
		this.onChange();
	}
};
// }}}
// {{{ isTreeNode()
class_tree_tpl_newnodes.prototype.isTreeNode = function(node) {
	return node.nodeName == conf.ns.page + ":newnode" || node.nodeName == conf.ns.project + ":tpl_newnodes";
};
// }}}
// {{{ setNodeIds()
class_tree_tpl_newnodes.prototype.setNodeIds = class_tree_pages.prototype.setNodeIds;
// }}}
// {{{ onDelete()
class_tree_tpl_newnodes.prototype.onDelete = class_tree_pages.prototype.onDelete;
// }}}
// {{{ duplicate()
class_tree_tpl_newnodes.prototype.duplicate = class_tree_pages.prototype.duplicate;
// }}}
// {{{ renameNode()
class_tree_tpl_newnodes.prototype.renameNode = class_tree_pages.prototype.renameNode;
// }}}
// {{{ move_in()
class_tree_tpl_newnodes.prototype.move_in = class_tree_pages.prototype.move_in;
// }}}
// {{{ move_before()
class_tree_tpl_newnodes.prototype.move_before = class_tree_pages.prototype.move_before;
// }}}
// {{{ move_after()
class_tree_tpl_newnodes.prototype.move_after = class_tree_pages.prototype.move_after;
// }}}
// {{{ copy_in()
class_tree_tpl_newnodes.prototype.copy_in = class_tree_pages.prototype.copy_in;
// }}}
// {{{ copy_before()
class_tree_tpl_newnodes.prototype.copy_before = class_tree_pages.prototype.copy_before;
// }}}
// {{{ copy_after()
class_tree_tpl_newnodes.prototype.copy_after = class_tree_pages.prototype.copy_after;
// }}}
// {{{ getAddNodes()
class_tree_tpl_newnodes.prototype.getAddNodes = function(targetNode) {
	return [conf.lang.tree_name_new_new_node];	
};
// }}}
// {{{ addNode()
class_tree_tpl_newnodes.prototype.addNode = function(targetNode, type) {
	var new_name = conf.lang.tree_name_untitled;
	if (type == conf.lang.tree_name_new_new_node) {
		type = "new_node";
		var tempNode = super.addNode(conf.ns.page + ":newnode");
	}
	
	targetNode = this.data.getRootNode();
	if (targetNode != null) {
		tempNode.attributes.name = new_name;
		this.loading = true;
		this.onChange();
		_root.phpConnect.send("add_node", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["target_id", targetNode.nid], ["type", this.type], ["node_type", type], ["new_name", new_name]]);
	}
};
// }}}
// {{{ isValidMove()
class_tree_tpl_newnodes.prototype.isValidMove = function(node, targetNode) {
	return targetNode.isRootNode();
};
// }}}
// {{{ isValidCopy()
class_tree_tpl_newnodes.prototype.isValidCopy = function(node, targetNode) {
	return targetNode.isRootNode();
};
// }}}

/*
 *	Class TreeSettings
 *
 *	Extends class_tree()
 *	Handles Project-Settings and its Modules
 */
// {{{ constructor()
class_tree_settings = function() {};
class_tree_settings.prototype = new class_tree();

class_tree_settings.prototype.showRootNode = false;
class_tree.prototype.showChildrenInitLevel = 2;
// }}}
// {{{ init()
class_tree_settings.prototype.init = function(type, projectObj) {
	super.init(type, projectObj);	
	_root.phpConnect.msgHandler.register_func("update_tree_" + this.type, this.set_data, this);
	_root.pocketConnect.msgHandler.register_func("update_tree_" + this.type, this.set_data, this);
};
// }}}
// {{{ load()
class_tree_settings.prototype.load = function() {
	this.loading = true;
	_root.phpConnect.send("get_tree", [["sid", conf.user.sid], ["wid", conf.user.wid], ["project_name", conf.project_name], ["type", this.type]]);
};
// }}}
// {{{ set_data()
class_tree_settings.prototype.set_data = function(args) {
	this.loading = false;
	if (!args['error'] && this.updateEnabled) {
		this.data = new XML(args['data']);
		this.setNodeIds();
		this.navigations = this.getNavigations();
		this.variables = this.getVariables();
		this.languages = this.getLanguages();
		this.templateSets = this.getTemplateSets();

                conf.project.preview_lang = this.languages[0].shortname;

		this.onChange();
	}
};
// }}}
// {{{ setNodeIds()
class_tree_settings.prototype.setNodeIds = class_tree_pages.prototype.setNodeIds;
// }}}
// {{{ onDelete()
class_tree_settings.prototype.onDelete = class_tree_pages.prototype.onDelete;
// }}}
// {{{ duplicate()
class_tree_settings.prototype.duplicate = class_tree_pages.prototype.duplicate;
// }}}
// {{{ renameNode()
class_tree_settings.prototype.renameNode = class_tree_pages.prototype.renameNode;
// }}}
// {{{ isTreeNode()
class_tree_settings.prototype.isTreeNode = function(node) {
	if (node.nodeType == 1) {
		if (node.nodeName == conf.ns.project + ":settings") {
			return true;
		} else if ((node.nodeName == conf.ns.project + ":publish" || node.nodeName == conf.ns.project + ":publish_folder") && conf.user.mayPublish()) {
			return true;
		} else if ((node.nodeName == conf.ns.project + ":template_sets" || node.nodeName == conf.ns.project + ":template_set") && conf.user.mayEditSettingsTemplateSets()) {
			return true;
		} else if ((node.nodeName == conf.ns.project + ":global_files" || node.nodeName == conf.ns.project + ":global_file") && conf.user.mayEditSettingsTemplateSets()) {
			return true;
		} else if ((node.nodeName == conf.ns.project + ":languages" || node.nodeName == conf.ns.project + ":language") && conf.user.mayEditSettingsLanguages()) {
			return true;
		} else if ((node.nodeName == conf.ns.project + ":navigations" || node.nodeName == conf.ns.project + ":navigation") && conf.user.mayEditSettingsNavigation()) {
			return true;
		} else if ((node.nodeName == conf.ns.project + ":variables" || node.nodeName == conf.ns.project + ":variable") && conf.user.mayEditSettingsVariable()) {
			return true;
		} else if ((node.nodeName == conf.ns.project + ":backup" || node.nodeName == conf.ns.project + ":backup_backup" || node.nodeName == conf.ns.project + ":backup_restore") && conf.user.mayBackup()) {
			return true;
		} else {
			return false;
		}		
	} else {
		return false;
	}
};
// }}}
// {{{ isFolder()
class_tree_settings.prototype.isFolder = function(node) {
	return node.nodeName == conf.ns.project + ":publish" || node.nodeName == conf.ns.project + ":template_sets" || node.nodeName == conf.ns.project + ":global_files" || node.nodeName == conf.ns.project + ":languages" || node.nodeName == conf.ns.project + ":navigations" || node.nodeName == conf.ns.project + ":variables" || node.nodeName == conf.ns.project + ":backup";	
};
// }}}
// {{{ isRenamable()
class_tree_settings.prototype.isRenamable = function(node) {
	if (node.nodeName == conf.ns.project + ":navigation") {
		return true;		
	} else if (node.nodeName == conf.ns.project + ":variable") {
		return true;		
	} else if (node.nodeName == conf.ns.project + ":language") {
		return true;		
	} else if (node.nodeName == conf.ns.project + ":template_set") {
		return true;		
	} else if (node.nodeName == conf.ns.project + ":global_file") {
		return true;		
	} else if (node.nodeName == conf.ns.project + ":publish_folder") {
		return true;		
	} else {
		return false;
	}
};
// }}}
// {{{ isValidMove()
class_tree_settings.prototype.isValidMove = function(node, targetNode) {
	if (node.nodeName == conf.ns.project + ":navigation" && targetNode.nodeName == conf.ns.project + ":navigations") {
		return true;		
	} else if (node.nodeName == conf.ns.project + ":variable" && targetNode.nodeName == conf.ns.project + ":variables") {
		return true;		
	} else if (node.nodeName == conf.ns.project + ":language" && targetNode.nodeName == conf.ns.project + ":languages") {
		return true;		
	} else if (node.nodeName == conf.ns.project + ":template_set" && targetNode.nodeName == conf.ns.project + ":template_sets") {
		return true;		
	} else if (node.nodeName == conf.ns.project + ":global_file" && targetNode.nodeName == conf.ns.project + ":global_files") {
		return true;		
	} else if (node.nodeName == conf.ns.project + ":publish_folder" && targetNode.nodeName == conf.ns.project + ":publish") {
		return true;		
	} else {
		return false;
	}
};
// }}}
// {{{ isValidCopy()
class_tree_settings.prototype.isValidCopy = function(node, targetNode) {
	return this.isValidMove(node, targetNode);
};
// }}}
// {{{ isValidDelete()
class_tree_settings.prototype.isValidDelete = function(node) {
	if (node.nodeName == conf.ns.project + ":navigation") {
		return true && (node.previousSibling != null || node.nextSibling != null);
	} else if (node.nodeName == conf.ns.project + ":variable") {
		return true && (node.previousSibling != null || node.nextSibling != null);		
	} else if (node.nodeName == conf.ns.project + ":language") {
		return true && (node.previousSibling != null || node.nextSibling != null);		
	} else if (node.nodeName == conf.ns.project + ":template_set") {
		return true && (node.previousSibling != null || node.nextSibling != null);		
	} else if (node.nodeName == conf.ns.project + ":global_file") {
		return true && (node.previousSibling != null || node.nextSibling != null);		
	} else if (node.nodeName == conf.ns.project + ":publish_folder") {
		return true && (node.previousSibling != null || node.nextSibling != null);		
	} else {
		return false;
	}
};
// }}}
// {{{ isValidDuplicate()
class_tree_settings.prototype.isValidDuplicate = function(node) {
	if (node.nodeName == conf.ns.project + ":navigation") {
		return true;		
	} else if (node.nodeName == conf.ns.project + ":variable") {
		return true;		
	} else if (node.nodeName == conf.ns.project + ":language") {
		return true;		
	} else if (node.nodeName == conf.ns.project + ":template_set") {
		return true;		
	} else if (node.nodeName == conf.ns.project + ":global_file") {
		return true;		
	} else if (node.nodeName == conf.ns.project + ":publish_folder") {
		return true;		
	} else {
		return false;
	}
};
// }}}
// {{{ move_in()
class_tree_settings.prototype.move_in = class_tree_pages.prototype.move_in;
// }}}
// {{{ move_before()
class_tree_settings.prototype.move_before = class_tree_pages.prototype.move_before;
// }}}
// {{{ move_after()
class_tree_settings.prototype.move_after = class_tree_pages.prototype.move_after;
// }}}
// {{{ copy_in()
class_tree_settings.prototype.copy_in = class_tree_pages.prototype.copy_in;
// }}}
// {{{ copy_before()
class_tree_settings.prototype.copy_before = class_tree_pages.prototype.copy_before;
// }}}
// {{{ copy_after()
class_tree_settings.prototype.copy_after = class_tree_pages.prototype.copy_after;
// }}}
// {{{ getNavigations()
class_tree_settings.prototype.getNavigations = function() {
	var tempNode;
	var tempObj;
	var navigations = [];
	
	tempNode = this.data.getRootNode().firstChild;
	while (tempNode != null) {
		if (tempNode.nodeName == conf.ns.project + ":navigations") {
			for (i = 0; i < tempNode.childNodes.length; i++) {
				navigations.push({
					name		: tempNode.childNodes[i].attributes.name,
					shortname	: tempNode.childNodes[i].attributes.shortname
				});
			}
			return navigations;
		}
			
		tempNode = tempNode.nextSibling;
	}
};
// }}}
// {{{ getVariables()
class_tree_settings.prototype.getVariables = function() {
	var tempNode;
	var tempObj;
	var variables = [];
	
	tempNode = this.data.getRootNode().firstChild;
	while (tempNode != null) {
		if (tempNode.nodeName == conf.ns.project + ":variables") {
			for (i = 0; i < tempNode.childNodes.length; i++) {
				variables.push({
					name		: tempNode.childNodes[i].attributes.name,
					value    	: tempNode.childNodes[i].attributes.value
				});
			}
			return variables;
		}
			
		tempNode = tempNode.nextSibling;
	}
};
// }}}
// {{{ getVariable()
class_tree_settings.prototype.getVariable = function(name) {
	var tempNode;
	var tempObj;
	var variables = [];

	tempNode = this.data.getRootNode().firstChild;
	while (tempNode != null) {
		if (tempNode.nodeName == conf.ns.project + ":variables") {
			for (i = 0; i < tempNode.childNodes.length; i++) {
                            if (tempNode.childNodes[i].attributes.name == name) {
                                return tempNode.childNodes[i].attributes.value;
                            }
			}
		}
			
		tempNode = tempNode.nextSibling;
	}

        return false;
};
// }}}
// {{{ getLanguages()
class_tree_settings.prototype.getLanguages = function() {
	var tempNode;
	var tempObj;
	var languages = [];
	
	tempNode = this.data.getRootNode().firstChild;
	while (tempNode != null) {
		if (tempNode.nodeName == conf.ns.project + ":languages") {
			for (i = 0; i < tempNode.childNodes.length; i++) {
				languages.push({
					name		: tempNode.childNodes[i].attributes.name,
					shortname	: tempNode.childNodes[i].attributes.shortname
				});
			}
			return languages;
		}
			
		tempNode = tempNode.nextSibling;
	}
};
// }}}
// {{{ getTemplateSets()
class_tree_settings.prototype.getTemplateSets = function() {
	var tempNode, i;
	var val_array = [];
		
	tempNode = this.data.getRootNode();
	tempNode = tempNode.firstChild;
	while (tempNode != null) {
		if (tempNode.nodeName == conf.ns.project + ":template_sets") {
			for (i = 0; i < tempNode.childNodes.length; i++) {
				val_array.push(tempNode.childNodes[i].attributes.name);
			}
			tempNode = null;
		} else {
			tempNode = tempNode.nextSibling;	
		}
	}
	return val_array;
};
// }}}
// {{{ getAddNodes()
class_tree_settings.prototype.getAddNodes = function(targetNode) {
	return [];	
};
// }}}

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
