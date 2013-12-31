/*
 *	Class interface
 *
 *	Main Interface-Class
 *	Handles Main-Interface and global Interface-Elements
 */
// {{{ constructor
class_interface = function() {};
// }}}
// {{{ init()
class_interface.prototype.init = function(movClip, projectType) {
	this.movClip = movClip;
	this.projectType = projectType;
	this.layouts = new Object();
	this.activeLayout = null;

	this.generateGlobalComponents();
};
// }}}
// {{{ generateGlobalComponents
class_interface.prototype.generateGlobalComponents = function() {
	if (this.projectType == "webProject") {
		this.movClip.attachMovie("rectangle_back", "back_left", 1, {
			_x	: 35,
			_y	: 0
		});
		this.movClip.back_left.setRGB(conf.interface.color_face);
		
		this.movClip.attachMovie("rectangle_back", "back_right", 2, {
			_x	: 35,
			_y	: 0
		});
		this.movClip.back_right.setRGB(conf.interface.color_face);
			
		this.movClip.attachMovie("registerBar", "register", 3, {
			_x	: 35,
			_y	: 15
		});
		
		this.movClip.attachMovie("buttonBar", "buttonBar", 4, {
			_x	: 35
		});
		
		this.movClip.attachMovie("divider", "divider_vertical", 5, {
			_x			: 144,
			_y 			: -1,
			orientation	: "vertical",
			onChangeObj	: this,
			getSize		: function() {
				return Stage.height;
			}
		});
	}
	this.initSettings();
};
// }}}
// {{{ onKeyUp()
class_interface.prototype.onKeyUp = function() {
	if (Key.isDown(Key.CONTROL) && Key.getCode() == 68) {
		conf.project.previewManual();
	}
	//updateAfterEvent();
};
// }}}
// {{{ initSettings()
class_interface.prototype.initSettings = function() {
	Key.addListener(this);
	if (this.projectType == "webProject") {
		//interface layouts
		this.layouts.editPages = new class_interfaceLayout_editPages(this, conf.lang.register_name_edit_pages);
		this.layouts.editPages.init();
		this.layouts.files = new class_interfaceLayout_files(this, conf.lang.register_name_files);
		this.layouts.files.init();
		this.layouts.colors = new class_interfaceLayout_colors(this, conf.lang.register_name_colors);
		this.layouts.colors.init();
		this.layouts.templates = new class_interfaceLayout_templates(this, conf.lang.register_name_templates);
		this.layouts.templates.init();
		this.layouts.settings = new class_interfaceLayout_settings(this, conf.lang.register_name_settings);
		this.layouts.settings.init();
		
		// DialogLayouts
		this.layouts.dlgChoose_page = new class_interfaceLayout_dlgChoose_page(this, conf.lang.register_name_edit_pages);
		this.layouts.dlgChoose_page.init();
		this.layouts.dlgChoose_file_link = new class_interfaceLayout_dlgChoose_file_link(this, conf.lang.register_name_files);
		this.layouts.dlgChoose_file_link.init();
		this.layouts.dlgChoose_files = new class_interfaceLayout_dlgChoose_files(this, conf.lang.register_name_files);
		this.layouts.dlgChoose_files.init();
		
		//register
		this.movClip.register.register = new Array();

		this.movClip.register.register.push({
			title	: this.layouts.editPages.title, 
			icon	: "mainbar_page", 
			func	: this.register_editPages, 
			funcObj	: this, 
			tooltip	: conf.lang.register_tip_edit_pages
		});
		this.movClip.register.register.push({
			title	: this.layouts.files.title, 
			icon	: "mainbar_files", 
			func	: this.register_files, 
			funcObj	: this, 
			tooltip	: conf.lang.register_tip_files
		});
		if (conf.user.mayEditColors()) {
			this.movClip.register.register.push({
				title	: this.layouts.colors.title, 
				icon	: "mainbar_colors", 
				func	: this.register_colors,
				funcObj	: this, 
				tooltip	: conf.lang.register_tip_colors
			});
		}
		if (conf.user.mayEditTemplates()) {
			this.movClip.register.register.push({
				title	: this.layouts.templates.title, 
				icon	: "mainbar_templates", 
				func	: this.register_templates, 
				funcObj	: this, 
				tooltip	: conf.lang.register_tip_templates
			});
		}
		if (conf.user.mayEditSettings()) {
			this.movClip.register.register.push({
				title	: this.layouts.settings.title, 
				icon	: "mainbar_settings", 
				func	: this.register_settings, 
				funcObj	: this, 
				tooltip	: conf.lang.register_tip_settings
			});
		}
		
		//buttonBar
		this.movClip.buttonBar.buttons = new Array();
		
		if (conf.project.preview_available && conf.standalone != "true") {
			this.movClip.buttonBar.buttons.push({
				icon			: "mainbar_preview", 
				onClickFunc		: conf.project.previewManual, 
				onClickFuncObj	: conf.project, 
				menuFunc		: this.showPreviewSettings, 
				menuFuncObj		: this, 
				tooltip			: conf.lang.register_tip_preview
			});
		}
	}
};
// }}}
// {{{ register_editPages()
class_interface.prototype.register_editPages = function() {
	if (this.activeLayout.isDialog) {
		this.layouts.dlgChoose_page.setActive();
	} else {
		this.layouts.editPages.setActive();
	}
};
// }}}
// {{{ register_files()
class_interface.prototype.register_files = function() {
	if (this.activeLayout.isDialog) {
		this.layouts.dlgChoose_file_link.setActive();
	} else {
		this.layouts.files.setActive();
	}
};
// }}}
// {{{ registerColors()
class_interface.prototype.register_colors = function() {
	this.layouts.colors.setActive();
};
// }}}
// {{{ register_templates()
class_interface.prototype.register_templates = function() {
	this.layouts.templates.setActive();
};
// }}}
// {{{ register_settings()
class_interface.prototype.register_settings = function() {
	 this.layouts.settings.setActive();
};
// }}}
// {{{ showPreviewSettings()
class_interface.prototype.showPreviewSettings = function() {
	var menu = new menuClass.menuObj(true);
	
	//preview behaviour
	menu.addHead(conf.lang.register_preview_menu_headline);
	menu.addSeparator();	
	menu.addEntry(conf.lang.register_preview_menu_man, this.toggle_preview_automatic, this, [0], (conf.user.settings.preview_automatic == 0 ? "checked" : null), true);
	menu.addEntry(conf.lang.register_preview_menu_auto_choose, this.toggle_preview_automatic, this, [1], (conf.user.settings.preview_automatic == 1 ? "checked" : null), true);
	menu.addEntry(conf.lang.register_preview_menu_auto_choose_save, this.toggle_preview_automatic, this, [2], (conf.user.settings.preview_automatic == 2 ? "checked" : null), true);
	menu.addSeparator();
	menu.addEntry(conf.lang.register_preview_menu_feedback, this.toggle_preview_feedback, this, [], (conf.user.settings.preview_feedback ? "checked" : null), true);
	menu.addSeparator();	
	
	//preview type
	var templateSets = conf.project.tree.settings.getTemplateSets();
	var subMenu = new menuClass.menuObj(true);
	for (i = 0; i < templateSets.length; i++) {
		subMenu.addEntry(templateSets[i], this.choose_preview_type, this, [], (templateSets[i] == conf.project.preview_type ? "checked" : null), true);
	}
	menu.addSubmenu(conf.lang.register_preview_menu_type, subMenu, true);
	
	menu.valign = "bottom";
	menu.show(null, null);
};
// }}}
// {{{ toggle_preview_automatic()
class_interface.prototype.toggle_preview_automatic = function(index, name, automatic) {
	conf.user.settings.preview_automatic = automatic;
};
// }}}
// {{{ toggle_preview_feedback()
class_interface.prototype.toggle_preview_feedback = function() {
	conf.user.settings.preview_feedback = !conf.user.settings.preview_feedback;
};
// }}}
// {{{ choose_preview_type()
class_interface.prototype.choose_preview_type = function(index, previewType) {
	conf.project.preview_type = previewType;
	conf.project.previewManual();
};
// }}}
// {{{ onResize()
class_interface.prototype.onResize = function() {
	if (this.projectType == "webProject") {
		if ((Stage.width - this.movClip.register._x) / 2 < this.movClip.divider_vertical._x - this.movClip.register._x || Stage.width < 475) {
			this.divider_vertical._x = int(this.register._x + (Stage.width - this.register._x) / 2);	
 		} else if (this.movClip.divider_vertical._x < int(this.movClip.register._x + 220)) {
			this.movClip.divider_vertical._x = int(this.movClip.register._x + 220);
		}
		
		for (i = 1; i < this.activeLayout.treeNum; i++) {
			if (this.activeLayout.dividerPos[i] != undefined) {
				this.movClip["treeDivider" + i]._y = int(Stage.height * this.activeLayout.dividerPos[i]);
			} else {
				this.activeLayout.dividerPos[i] = this.movClip["treeDivider" + i]._y / Stage.height;
			}
		}
		
		this.movClip.buttonBar._y = Stage.height - 24;
		
		this.setDivider();
	}
};
// }}}
// {{{ setDivider()
class_interface.prototype.setDivider = function() {
	var i;
	
	if (this.projectType == "webProject") {
		this.movClip.divider_vertical._x = int(this.movClip.divider_vertical._x);

		this.movClip.back_left._width = this.movClip.divider_vertical._x - this.movClip.register._x - 10;
		this.movClip.back_left._height = Stage.height;
		this.movClip.back_right._x = this.movClip.divider_vertical._x + 5;
		this.movClip.back_right._width = Stage.width - this.movClip.divider_vertical._x - 15;
		this.movClip.back_right._height = Stage.height;
	}
};
// }}}
// {{{ hideNonMoving()
class_interface.prototype.hideNonMoving = function() {
	_global.allowEvents = false;

	this.movClip.back_right._wasVisible = back_right._visible;
	this.movClip.back_right._visible = true;
		
	this.activeLayout.hide();
};
// }}}
// {{{ showNonMoving()
class_interface.prototype.showNonMoving = function() {
	_global.allowEvents = true;

	for (i = 1; i < this.activeLayout.treeNum; i++) {
		this.activeLayout.dividerPos[i] = this.movClip["treeDivider" + i]._y / Stage.height;
	}
	Stage.broadcastMessage("onResize");
	
	this.movClip.back_right._visible = this.movClip.back_right._wasVisible;
	this.activeLayout.show();
};
// }}}
// {{{ setActiveLayout()
class_interface.prototype.setActiveLayout = function(newLayout, returnFunc, returnFuncObj, dlgArgs) {
	var i, j;
	
	this.activeLayout.remove();
	if (newLayout.isDialog == true && this.activeLayout.isDialog == false) {
		this.returnFunc = returnFunc;
		this.returnFuncObj = returnFuncObj;
		this.dlgArgs = dlgArgs;
		
		this.oldLayout = this.activeLayout;
	} else if (this.activeLayout.isDialog == false) {
		this.oldLayout = null;
	}
	this.activeLayout = newLayout;
	
	for (i = 0; i < this.movClip.register.register.length; i++) {
		if (this.movClip.register.register[i].title == this.activeLayout.title) {
			if (i != this.movClip.register.active) {
				this.movClip.register.active = i;
			}
		}
		if (this.activeLayout.isDialog == true) {
			if (this.activeLayout.allowedRegister.searchFor(this.movClip.register.register[i].title) > -1 || this.movClip.register.register[i].title == this.activeLayout.title) {
				this.movClip.register.register[i].enabledState = true;
			} else {
				this.movClip.register.register[i].enabledState = false;
			}
		} else {
			this.movClip.register.register[i].enabledState = true;
		}
	}
	this.movClip.register.show();
	
	setTimeout(this.activeLayout.generate, this.activeLayout, 20, [this.dlgArgs], false);
};
// }}}
// {{{ returnDlg()
class_interface.prototype.returnDlg = function(doApply, returnArg) {
	if (doApply) {
		this.returnFunc.apply(this.returnFuncObj, returnArg);
	}
	this.setActiveLayout(this.oldLayout);
};
// }}}

/*
 *	Class treeDisplayData
 *
 *	saves the information about displaing a treeObj
 *	defined in class.treeClasses.as
 */
// {{{ constructor
class_treeDisplayData = function(treeObj, interfaceObj) {
	this.treeObj = treeObj;				//this object inherits the tree data and structure
	this.activeNode = null; 			//this is the actual marked node
	this.activeNodeId = null; 			//this is the actual marked node id
	this.activeNodeIdWaiting = null		//this is the node to be loaded after adding with unknown id
	this.interfaceLayout = null;	 	//this is a link to the interfaceLayoutClass (for buttons etc)
	this.showChildren = new Object();	//this is the object with informations about children being open or not
	this.isOpen = true;					//is this tree actually open?
	this.isShown = false;				//is this tree actually displayed?
	this.interfaceObj = interfaceObj	//reference to interfaceObj	
};
// }}}
// {{{ init()
class_treeDisplayData.prototype.init = function() {
	this.treeObj.addOnChangeListener(this);
	this.defineButtons();
};

class_treeDisplayData.prototype.showRootNode = true;		//show rootNode of the tree?
class_treeDisplayData.prototype.showChildrenInitLevel = 3;	//to what level children are shown by default
// }}}
// {{{ defineButtons
class_treeDisplayData.prototype.defineButtons = function() {
	this.buttons = [];
	this.buttons[0] = [];
	
	//new button
	var tbutton = Object();
	tbutton.symbol = "icon_tree_button_new";
	tbutton.tooltip = conf.lang.buttontip_tree_new;
	tbutton.width = 19;
	tbutton.height = 17;
	// {{{ tbutton.onClick()
	tbutton.onClick = function() {
		this._parent.showAddNodeMenu();
	};
	// }}}
	// {{{ tbutton.getEnabledState()
	tbutton.getEnabledState = function() {
		return this._parent.new_nodes.length > 0;
	};
	// }}}
	this.buttons[0].push(tbutton);
	
	//duplicate tbutton
	var tbutton = Object();
	tbutton.symbol = "icon_tree_button_duplicate";
	tbutton.tooltip = conf.lang.buttontip_tree_duplicate;
	tbutton.width = 19;
	tbutton.height = 17;
	// {{{ tbutton.onClick()
	tbutton.onClick = function() {
		this._parent.onDuplicate();
	};
	// }}}
	// {{{ tbutton.getEnabledState()
	tbutton.getEnabledState = function() {
		return this._parent.treeObj.treeObj.isValidDuplicate(this._parent.treeObj.activeNode);
	};
	// }}}
	this.buttons[0].push(tbutton);
	
	//delete button
	var tbutton = Object();
	tbutton.symbol = "icon_tree_button_delete";
	tbutton.tooltip = conf.lang.buttontip_tree_delete;
	tbutton.width = 19;
	tbutton.height = 17;
	// {{{ tbutton.onClick()
	tbutton.onClick = function() {
		this._parent.onDelete();
	};
	// }}}
	// {{{ tbutton.getEnabledState()
	tbutton.getEnabledState = function() {
		return this._parent.treeObj.treeObj.isValidDelete(this._parent.treeObj.activeNode);
	};
	// }}}
	this.buttons[0].push(tbutton);
};
// }}}
// {{{ setActiveNode()
class_treeDisplayData.prototype.setActiveNode = function(node, scrollToActiveNode) {
	var tempNode;

	if (node != this.activeNode) {
		if (node != null) {
			this.activeNodeId = node.nid;
			this.activeNodeIdPrev = null;
			this.activeNodeIdNext = null;
			this.activeNodeIdParent = null;
			
			tempNode = node.previousSibling;
			while (this.activeNodeIdPrev == null && tempNode != null) {
				if (this.treeObj.isTreeNode(tempNode)) {
					this.activeNodeIdPrev = tempNode.nid;
				}
				tempNode = tempNode.previousSibling;
			}
			
			tempNode = node.nextSibling;
			while (this.activeNodeIdNext == null && tempNode != null) {
				if (this.treeObj.isTreeNode(tempNode)) {
					this.activeNodeIdNext = tempNode.nid;
				}
				tempNode = tempNode.nextSibling;
			}
			
			if (node.parentNode.nid != undefined) {
				this.activeNodeIdParent = node.parentNode.nid;
			}
			this.activeNode = node;
			this.onChangeObj.new_nodes = this.treeObj.getAddNodes(this.activeNode);
		} else {
			this.activeNode = null;
			_root.clipboard.removeHandler();
		}

		this.redraw(scrollToActiveNode);
	}
};
// }}}
// {{{ setActiveNodeById()
class_treeDisplayData.prototype.setActiveNodeById = function(id, scrollToActiveNode, actualNode) {
	var i, found;
	
	if (id == null || id == undefined || id == "") {
		return false;
	}
	if (actualNode == undefined) {
		found = this.setActiveNodeById(id, scrollToActiveNode, this.treeObj.data.getRootNode());
		
		if (found) {
			this.setActiveNode(found, scrollToActiveNode);
			return true;
		} else {
			return false;
		}
	} else {
		if (actualNode.nid == id) {
			return actualNode;
		} else {
			for (i = 0; i < actualNode.childNodes.length; i++) {
				if (this.treeObj.isTreeNode(actualNode.childNodes[i])) {
					found = this.setActiveNodeById(id, scrollToActiveNode, actualNode.childNodes[i]);
					if (found) {
						this.showChildren[actualNode.nid] = true;	
						return found;
					}
				}
			}
			return false;
		}
	}
};
// }}}
// {{{ setActiveNodeByIdWaiting()
class_treeDisplayData.prototype.setActiveNodeByIdWaiting = function(id) {
	if (!this.setActiveNodeById(id, true)) {
		this.activeNodeIdWaiting = id;
	} else if (!this.treeObj.isEmpty()) {
		this.activeNodeIdWaiting = null;
	}
};
// }}}
// {{{ onChange()
class_treeDisplayData.prototype.onChange = function(node) {
	var scrollToActiveNode = false;
	var setActiveBy = "not set";

	if (this.treeObj.data != undefined && !this.treeObj.isEmpty()) {
                this.setShowChildrenInitLevel();
		if (node != undefined) {
			this.setActiveNode(node);
			setActiveBy = "node, given directly to onChange function with id '" + node.nid + "'";
		} else if (this.activeNodeIdWaiting != null && this.setActiveNodeById(this.activeNodeIdWaiting)) {
			this.activeNodeIdWaiting = null;
			scrollToActiveNode = true;
			setActiveBy = "set by activeNodeIdWaiting with id '" + this.activeNodeIdWaiting + "'";
		} else if (this.activeNodeIdWaiting == null) {
		//} else if (!this.setActiveNodeById(this.activeNodeId)) {
			setActiveBy = "set to formerly activeNodeId '" + this.activeNodeId + "'";
			if (!this.setActiveNodeById(this.activeNodeId)) {
				setActiveBy = "set to previous node with id '" + this.activeNodeIdPrev + "'";
				if (!this.setActiveNodeById(this.activeNodeIdPrev)) {
					setActiveBy = "set to next node with id '" + this.activeNodeIdNext + "'";
					if (!this.setActiveNodeById(this.activeNodeIdNext)) {
						setActiveBy = "set to parentNode with id '" + this.activeNodeIdParent + "'";
						if (!this.setActiveNodeById(this.activeNodeIdParent)) {
							setActiveBy = "set to rootNode";
							if (this.showRootNode) {
								this.setActiveNode(this.getTreeRootNode());
							} else {
								this.setActiveNode(this.getTreeRootNode().firstChild);
							}
						}
					}
				}
			}
		} else {
			setActiveBy = "no setActive action called";
		}
	} else if (this.treeObj.isEmpty()){
		this.setActiveNode(null);
		this.interfaceObj.removeProps();
		setActiveBy = "set to null because tree is empty";
	} 
        if (this.treeObj.type == "page_data") {
            //alert(setActiveBy);
        }
	this.redraw(scrollToActiveNode);
};
// }}}
// {{{ redraw()
class_treeDisplayData.prototype.redraw = function(scrollToActiveNode) {
	if (this.onChangeObj != null && this.isShown) {
		this.lineNum = this.getLineNum();
		this.onChangeObj.onChange(scrollToActiveNode);	
	}
};
// }}}
// {{{ onShow()
class_treeDisplayData.prototype.onShow = function() {
	this.isShown = true;
	this.onChange();
};
// }}}
// {{{ onHide()
class_treeDisplayData.prototype.onHide = function() {
	this.isShown = false;
};
// }}}
// {{{ getTreeRootNode()
class_treeDisplayData.prototype.getTreeRootNode = function() {
	return this.treeObj.data.getRootNode();
};
// }}}
// {{{ getLineNum
class_treeDisplayData.prototype.getLineNum = function(actualNode) {
	var i;
	var tempNum = 1;
	
	if (!this.isOpen) {
		return 0;
	} else if (actualNode === undefined) {
		return this.getLineNum(this.getTreeRootNode());
	} else {
		if (!this.treeObj.isTreeNode(actualNode)) {
			return 0;
		} else if (!this.showChildren[actualNode.nid] || !actualNode.hasChildNodes()) {
			return 1;	
		} else {
			for (i = 0; i < actualNode.childNodes.length; i++) {
				tempNum += this.getLineNum(actualNode.childNodes[i]);
			}
			return tempNum;
		}
	}	
};
// }}}
// {{{ getTreeLines()
class_treeDisplayData.prototype.getTreeLines = function(treeLinesArray, actualChild, level) {
        var i;

	if (!this.isOpen) {
		treeLinesArray = [];
		return treeLinesArray;
	} else if (actualChild == undefined) {
		treeLinesArray = [];
		actualChild = this.getTreeRootNode();
		if (this.showRootNode) {
			return this.getTreeLines(treeLinesArray, actualChild, 0);
		} else {
			return this.getTreeLines(treeLinesArray, actualChild, -1);
		}
	} else {
		if (this.treeObj.isTreeNode(actualChild)) {
			if (level > -1) {
				treeLinesArray.push({
					node	: actualChild,
					level	: level
				});
			}
			
			if (this.showChildren[actualChild.nid] || actualChild == this.treeObj.data) {
                                for (i = 0; i < actualChild.childNodes.length; i++) {
					treeLinesArray = this.getTreeLines(treeLinesArray, actualChild.childNodes[i], level + 1);
                                }
			}
			return treeLinesArray;
		} else {
			return treeLinesArray;
		}
	}
};
// }}}
// {{{ setShowChildrenInitLevel()
class_treeDisplayData.prototype.setShowChildrenInitLevel = function(actualNode, actualLevel) {
	if (actualNode == undefined) {
		this.setShowChildrenInitLevel(this.treeObj.data, 0);
	} else {
		if (this.showChildren[actualNode.nid] == undefined) {
			if (actualLevel < this.showChildrenInitLevel) {
				this.showChildren[actualNode.nid] = true;
			} else {
				this.showChildren[actualNode.nid] = false;
			}
		}
		actualNode = actualNode.firstChild;
		while (actualNode != null) {
			this.setShowChildrenInitLevel(actualNode, actualLevel + 1);
			actualNode = actualNode.nextSibling;	
		}
	}
};
// }}}
// {{{ setShowChildren()
class_treeDisplayData.prototype.setShowChildren = function(node, status) {
	this.showChildren[node.nid] = status;
	this.redraw();	
};
// }}}

/*
 *	Class treeDisplayData_pages
 *
 *	saves the information about displaing a treeObj
 *	defined in class.treeClasses.as
 */
// {{{ constructor
class_treeDisplayData_pages = function(treeObj, interfaceObj) {
	super(treeObj, interfaceObj);
};
class_treeDisplayData_pages.prototype = new class_treeDisplayData();

class_treeDisplayData_pages.prototype.showRootNode = false;
class_treeDisplayData_pages.prototype.showChildrenInitLevel = 2;
// }}}
// {{{ setActiveNode()
class_treeDisplayData_pages.prototype.setActiveNode = function(node, scrollToActiveNode) {
	super.setActiveNode(node, scrollToActiveNode);
	
	if (!this.interfaceObj.isDialog) {
		this.timeoutObj.clear();
                this.timeoutObj = setTimeout(conf.project.tree.page_data.load, conf.project.tree.page_data, 200, [node.attributes[conf.ns.database + ":ref"], node]);
	
		conf.project.setPreviewNode(this.activeNode);
		conf.project.preview();
	}
};
// }}}
// {{{ onShow()
class_treeDisplayData_pages.prototype.onShow = function() {
    super.onShow();

    if (conf.startpage != "") {
        var pageid = conf.project.tree.pages.getIdByURL(conf.startpage);
        this.setActiveNodeById(pageid);
        
        conf.startpage = "";
    }
};
// }}}

/*
 *	Class treeDisplayData_page_data
 *
 *	saves the information about displaing a treeObj
 *	defined in class.treeClasses.as
 */
// {{{ constructor
class_treeDisplayData_page_data = function(treeObj) {
	super(treeObj);
};
class_treeDisplayData_page_data.prototype = new class_treeDisplayData();

class_treeDisplayData_page_data.prototype.showRootNode = false;
class_treeDisplayData_page_data.prototype.showChildrenInitLevel = 5;
// }}}
// {{{ getTreeRootNode()
class_treeDisplayData_page_data.prototype.getTreeRootNode = function() {
	return this.treeObj.data.getRootNode();
};
// }}}
// {{{ setActiveNode()
class_treeDisplayData_page_data.prototype.setActiveNode = function(node, scrollToActiveNode) {
	super.setActiveNode(node, scrollToActiveNode);

	this.treeObj.load_prop(this.activeNode.nid, this.activeNode);
};
// }}}
// {{{ onChange()
class_treeDisplayData_page_data.prototype.onChange = function(node) {
	super.onChange(node);
	if (!this.treeObj.isEmpty()) {
		//conf.project.preview();
	}
};
// }}}

/*
 *	Class treeDisplayData_files
 *
 *	saves the information about displaing a treeObj
 *	defined in class.treeClasses.as
 */
// {{{ constructor
class_treeDisplayData_files = function(treeObj, interfaceObj) {
	super(treeObj, interfaceObj);
};
class_treeDisplayData_files.prototype = new class_treeDisplayData();

class_treeDisplayData_files.prototype.showChildrenInitLevel = 2;
// }}}
// {{{ init()
class_treeDisplayData_files.prototype.init = function() {
	this.defineButtons();
	this.onChange();
};
// }}}
// {{{ onShow()
class_treeDisplayData_files.prototype.onShow = function() {
	this.treeObj.addOnChangeListener(this);
	super.onShow();
	this.treeObj.onShow();
};
// }}}
// {{{ onHide()
class_treeDisplayData_files.prototype.onHide = function() {
	this.treeObj.removeOnChangeListener(this);
	super.onHide();
	this.treeObj.onHide();
};
// }}}
// {{{ setActiveNode()
class_treeDisplayData_files.prototype.setActiveNode = function(node, scrollToActiveNode) {
	super.setActiveNode(node, scrollToActiveNode);

	this.treeObj.load_prop(this.activeNode.nid, this.activeNode);
};
// }}}
// {{{ setActiveNodeById()
class_treeDisplayData_files.prototype.setActiveNodeById = function(id) {
	var i, j;
	var ids = id.split("/");
	var targetNode;
	var tempNode;
	
	if (id == null) {
		targetNode = null;
		return false
	} else if (ids.length == 2) {
		targetNode = this.treeObj.data.getRootNode();
	} else {
		targetNode = this.treeObj.data.getRootNode();
		for (i = 1; i < ids.length - 1; i++) {
			tempNode = targetNode.firstChild;
			while (tempNode != null && tempNode.attributes.name != ids[i]) {
				tempNode = tempNode.nextSibling;
			}
			if (tempNode == null) {
				break;
			} else {
				this.showChildren[tempNode.nid] = true;
				targetNode = tempNode;	
			}
		}
	}
	
	this.setActiveNode(targetNode);
	
	return true;
};
// }}}
// {{{ defineButtons()
class_treeDisplayData_files.prototype.defineButtons = function() {
	this.buttons = [];
	this.buttons[0] = [];
	
	//new folder button
	var tbutton = Object();
	tbutton.symbol = "icon_tree_button_new_folder";
	tbutton.tooltip = conf.lang.buttontip_tree_newfolder;
	tbutton.width = 19;
	tbutton.height = 17;
	// {{{ tbutton.onClick()
	tbutton.onClick = function() {
		this._parent.addNode();
	};
	// }}}
	// {{{ tbutton.getEnabledState()
	tbutton.getEnabledState = function() {
		return this._parent.new_nodes.length > 0 && !this._parent.treeObj.treeObj.isEmpty();
	};
	// }}}
	this.buttons[0].push(tbutton);
	
	//delete button
	var tbutton = Object();
	tbutton.symbol = "icon_tree_button_delete";
	tbutton.tooltip = conf.lang.buttontip_tree_delete;
	tbutton.width = 19;
	tbutton.height = 17;
	// {{{ tbutton.onClick()
	tbutton.onClick = function() {
		this._parent.onDelete();
	};
	// }}}
	// {{{ tbutton.getEnabledState()
	tbutton.getEnabledState = function() {
		return this._parent.treeObj.treeObj.isValidDelete(this._parent.treeObj.activeNode);
	};
	// }}}
	this.buttons[0].push(tbutton);
	
	//upload new file button
	var tbutton = Object();
	tbutton.symbol = "icon_tree_button_upload";
	tbutton.tooltip = conf.lang.buttontip_tree_upload;
	tbutton.text = conf.lang.buttontext_tree_upload;
	tbutton.width = 19;
	tbutton.height = 17;
	// {{{ tbutton.onClick()
	tbutton.onClick = function() {
		call_jsfunc("open_upload('" + conf.user.sid + "', '" + conf.user.wid + "', '" + escape(this._parent.treeObj.activeNodeId) + "')");
	};
	// }}}
	// {{{ tbutton.getEnabledState()
	tbutton.getEnabledState = function() {
		return this._parent.treeObj.activeNode != null && !this._parent.treeObj.treeObj.isEmpty();
	};
	// }}}
	this.buttons[0].push(tbutton);
};
// }}}

/*
 *	Class treeDisplayData_colors
 *
 *	saves the information about displaing a treeObj
 *	defined in class.treeClasses.as
 */
// {{{ constructor
class_treeDisplayData_colors = function(treeObj, interfaceObj) {
	super(treeObj, interfaceObj);
};
class_treeDisplayData_colors.prototype = new class_treeDisplayData();

class_treeDisplayData_colors.prototype.showRootNode = false;
// }}}
// {{{ setActiveNode()
class_treeDisplayData_colors.prototype.setActiveNode = function(node, scrollToActiveNode) {
	super.setActiveNode(node, scrollToActiveNode);

	this.treeObj.load_prop(this.activeNode.nid, this.activeNode);
};
// }}}
	
/*
 *	Class treeDisplayData_settings
 *
 *	saves the information about displaing a treeObj
 *	defined in class.treeClasses.as
 */
// {{{ constructor
class_treeDisplayData_settings = function(treeObj, interfaceObj) {
	super(treeObj, interfaceObj);
};
class_treeDisplayData_settings.prototype = new class_treeDisplayData();

class_treeDisplayData_settings.prototype.showRootNode = false;
class_treeDisplayData_settings.prototype.showChildrenInitLevel = 2;
// }}}
// {{{ setActiveNode()
class_treeDisplayData_settings.prototype.setActiveNode = function(node, scrollToActiveNode) {
	super.setActiveNode(node, scrollToActiveNode);

	this.treeObj.load_prop(this.activeNode.nid, this.activeNode);
};
// }}}
	
/*
 *	Class treeDisplayData_templates
 *
 *	saves the information about displaing a treeObj
 *	defined in class.treeClasses.as
 */
// {{{ constructor
class_treeDisplayData_templates = function(treeObj, interfaceObj) {
	super(treeObj, interfaceObj);
};
class_treeDisplayData_templates.prototype = new class_treeDisplayData();

class_treeDisplayData_templates.prototype.showRootNode = false;
class_treeDisplayData_templates.prototype.showChildrenInitLevel = 2;
// }}}
// {{{ defineButtons
class_treeDisplayData_templates.prototype.defineButtons = function() {
	super.defineButtons();
	
	//release templates button
	var tbutton = Object();
	tbutton.symbol = "icon_tree_button_release_templates";
	tbutton.tooltip = conf.lang.buttontip_tree_releasetemp;
	tbutton.text = conf.lang.buttontext_tree_releasetemp;
	tbutton.width = 19;
	tbutton.height = 17;
	// {{{ tbutton.onClick()
	tbutton.onClick = function() {
		this._parent.showReleaseTempMenu();
	};
	// }}}
	// {{{ tbutton.getEnabledState()
	tbutton.getEnabledState = function() {
		return true;
	};
	// }}}
	this.buttons[0].push(tbutton);
};
// }}}
// {{{ setActiveNode()
class_treeDisplayData_templates.prototype.setActiveNode = function(node, scrollToActiveNode) {
	super.setActiveNode(node, scrollToActiveNode);

	this.treeObj.load_prop(this.activeNode.attributes[conf.ns.database + ":ref"], this.activeNode);
};
// }}}
	
/*
 *	Class treeDisplayData_newnodes
 *
 *	saves the information about displaing a treeObj
 *	defined in class.treeClasses.as
 */
// {{{ constructor
class_treeDisplayData_newnodes = function(treeObj, interfaceObj) {
	super(treeObj, interfaceObj);
};
class_treeDisplayData_newnodes.prototype = new class_treeDisplayData();

class_treeDisplayData_newnodes.prototype.showRootNode = false;
class_treeDisplayData_newnodes.prototype.showChildrenInitLevel = 3;
// }}}
// {{{ setActiveNode()
class_treeDisplayData_newnodes.prototype.setActiveNode = function(node, scrollToActiveNode) {
	super.setActiveNode(node, scrollToActiveNode);

	this.treeObj.load_prop(this.activeNode.nid, this.activeNode);
};
// }}}
	
/*
 *	Class interfaceLayout
 *
 *	Main InterfaceLayout-Class
 */
// {{{ constructor
class_interfaceLayout = function(interfaceObj, title) {
	this.interfaceObj = interfaceObj;
	this.movClip = interfaceObj.movClip;
	this.title = title;
	this.activeTree = null;
	this.dividerPos = [];
	this.allowedRegister = [];
};

class_interfaceLayout.prototype.treeNum = 0;
class_interfaceLayout.prototype.isDialog = false;
class_interfaceLayout.prototype.treeTopOffset = 0;
class_interfaceLayout.prototype.propTopOffset = 0;
// }}}
// {{{ setActive
class_interfaceLayout.prototype.setActive = function(returnFunc, returnFuncObj, dlgArgs) {
	this.interfaceObj.setActiveLayout(this, returnFunc, returnFuncObj, dlgArgs);
};
// }}}
// {{{ treeSetActive()
class_interfaceLayout.prototype.treeSetActive = function() {
	if (this.activeTree == null) {
		this.movClip.treeBox1.treeSetActive(true);
	} else {
		this.movClip["treeBox" + this.activeTree].treeSetActive(true);
	}
};
// }}}
// {{{ generate()
class_interfaceLayout.prototype.generate = function(dlgArgs) {
	this.generateTrees();
	setTimeout(this.show, this, 30);
};
// }}}
// {{{ generateTrees()
class_interfaceLayout.prototype.generateTrees = function() {
	var i;
	
	for (i = 1; i <= this.treeNum; i++) {
		//tree
		this.movClip.attachMovie("tree_box", "treeBox" + i, 200 + i*2 - 1, {
			treeId		: i,
			_visible	: false
		});
		
		if (i > 1) {
			//divider
			this.movClip.attachMovie("divider", "treeDivider" + (i - 1), 200 + i*2, {
				_x			: this.movClip.register._x,
				orientation	: "horizontal",
				onChangeObj	: this.interfaceObj,
				_visible	: false
			});
			this.movClip["treeDivider" + (i - 1)].getSize = function() {
				return this._parent.divider_vertical._x - this._parent.register._x - 10;
			};
			if (this.dividerPos[i - 1] == undefined) {
				this.movClip["treeDivider" + (i - 1)]._y = int((Stage.height - this.treeTopOffset) / this.treeNum) * (i - 1) + this.treeTopOffset;
			} else {
				this.movClip["treeDivider" + (i - 1)]._y = int((Stage.height - this.treeTopOffset) * this.dividerPos[i - 1]) + this.treeTopOffset;
			}
			this.dividerPos[i - 1] = this.movClip["treeDivider" + (i - 1)]._y / Stage.height;
		}
	}
};
// }}}
// {{{ generateProps()
class_interfaceLayout.prototype.generateProps = function(propObj) {
	setTimeout(this.generatePropsNow, this, 1, [propObj], false);
};
// }}}
// {{{ generatePropsNow()
class_interfaceLayout.prototype.generatePropsNow = function(propObj) {
	if (this.movClip.propBox.propObj != propObj) {
		if (this.movClip.propBox != undefined) {
			this.removeProps(propObj);
		} else {
			//properties
			this.movClip.attachMovie("prop_box", "propBox", 10, {
				propObj	: propObj
			});
		}
	}
};
// }}}
// {{{ remove()
class_interfaceLayout.prototype.remove = function() {
	this.removeTrees();
	this.removeProps();
};
// }}}
// {{{ removeTrees()
class_interfaceLayout.prototype.removeTrees = function() {
	var i;
	
	for (i = 1; i <= this.treeNum; i++) {
		this.movClip["treeBox" + i].removeMovieClip();
		if (i > 1) {
			this.movClip["treeDivider" + (i - 1)].removeMovieClip();
		}
	}
};
// }}}
// {{{ removeProps()
class_interfaceLayout.prototype.removeProps = function(propObj) {
	this.movClip.back_right._visible = true;
	this.movClip.propBox.removeMovieClip();
	updateDisplay();
	if (propObj != undefined) {
		setTimeout(this.generatePropsNow, this, 1, [propObj], false);
	}
};
// }}}
// {{{ show()
class_interfaceLayout.prototype.show = function() {
	for (i = 1; i <= this.treeNum; i++) {
		this.movClip["treeBox" + i]._visible = true;
		this.movClip["treeDivider" + i]._visible = this["treeObj" + i].isOpen && (i == this.treeNum ? true : this["treeObj" + (i + 1)].isOpen);
	}
	this.movClip.propBox._visible = true;
};
// }}}
// {{{ hide()
class_interfaceLayout.prototype.hide = function() {
	for (i = 1; i <= this.treeNum; i++) {
		this.movClip["treeBox" + i]._visible = false;
		
		if (i > 1) {
			this.movClip["treeDivider" + i]._visible = false;
		}
	}
	this.movClip.propBox._visible = false;
};
// }}}

/*
 *	Class interfaceLayout_editPages
 *
 *	Extends class_interfaceLayout
 *	Defines Layout-Elements for editing Site-Structure and Pages
 */
// {{{ constructor
class_interfaceLayout_editPages = function(interfaceObj, title) {
	super(interfaceObj, title);
};
class_interfaceLayout_editPages.prototype = new class_interfaceLayout();
// }}}
// {{{ init()
class_interfaceLayout_editPages.prototype.init = function() {
	this.treeObj1 = new class_treeDisplayData_pages(conf.project.tree.pages, this);
	this.treeObj2 = new class_treeDisplayData_page_data(conf.project.tree.page_data, this);
	this.treeObj1.init();
	this.treeObj2.init();
	
	//set reference to allow propObj to change active node attributes
	this.treeObj1.treeObj.propObj.tree_displayObj = this.treeObj1;
};
// }}}
// {{{ generate()
class_interfaceLayout_editPages.prototype.generate = function(dlgArgs) {
	this.treeNum = 2;

	super.generate(dlgArgs);
	
	if (conf.project.previewDisableCache) {
		conf.project.previewDisableCache = false;
		conf.project.preview();
	}
	
	this.movClip.treeBox1.treeObj = this.treeObj1;
	this.movClip.treeBox2.treeObj = this.treeObj2;
	
	setTimeout(this.treeSetActive, this, 10, [true]);
};
// }}}
	
/*
 *	Class interfaceLayout_dlgChoose_page
 *
 *	Extends class_interfaceLayout
 *	Defines Layout-Elements choosing a page for internal link
 */
// {{{ constructor
class_interfaceLayout_dlgChoose_page = function(interfaceObj, title) {
	super(interfaceObj, title);
	this.allowedRegister.push(conf.lang.register_name_files);
};
class_interfaceLayout_dlgChoose_page.prototype = new class_interfaceLayout();

class_interfaceLayout_dlgChoose_page.prototype.isDialog = true;
class_interfaceLayout_dlgChoose_page.prototype.treeTopOffset = 90;
// }}}
// {{{ init()
class_interfaceLayout_dlgChoose_page.prototype.init = function() {
	this.treeObj1 = new class_treeDisplayData_pages(conf.project.tree.pages, this);
	this.treeObj1.init();
};
// }}}
// {{{ generate()
class_interfaceLayout_dlgChoose_page.prototype.generate = function(dlgArgs) {
	this.treeNum = 1;

	super.generate(dlgArgs);
	
	conf.project.previewDisableCache = false;
	
	this.movClip.treeBox1.treeObj = this.treeObj1;
	
	if (dlgArgs[0] != "") {
		this.movClip.treeBox1.treeObj.setActiveNodeById(dlgArgs[0]);
		this.movClip.treeBox1.treeObj.setActiveNodeByIdWaiting(dlgArgs[0]);
	}
	this.dataNodeId = dlgArgs[1];
	setTimeout(this.treeSetActive, this, 10, [true]);
	
	this.movClip.attachMovie("tree_box_dlg", "dlgBox", 50, {
		text	: conf.lang.msg_choose_page,
		type	: "OkCancel"
	});
	this.movClip.dlgBox.getX = function() {
		return this._parent.register._x + 7;
	};
	this.movClip.dlgBox.getY = function() {
		return 15;
	};
	this.movClip.dlgBox.getHeight = function() {
		return this._parent.interface.activeLayout.treeTopOffset - 15;
	};
	this.movClip.dlgBox.getWidth = function() {
		return this._parent.divider_vertical._x - 38 - 21;
	};
	this.movClip.dlgBox.onOk = function() {
		this._parent.interface.returnDlg(true, [this._parent.treeBox1.treeObj.activeNodeId, this._parent.interface.activeLayout.dataNodeId]);
	};
	this.movClip.dlgBox.onCancel = function() {
		this._parent.interface.returnDlg(false);
	};
};
// }}}
// {{{ generateProps()
class_interfaceLayout_dlgChoose_page.prototype.generateProps = function() {

};
// }}}
// {{{ remove()
class_interfaceLayout_dlgChoose_page.prototype.remove = function() {
	super.remove();
	
	this.movClip.dlgBox.removeMovieClip();
};
// }}}
// {{{ show()
class_interfaceLayout_dlgChoose_page.prototype.show = function() {
	super.show();
	
	this.movClip.dlgBox._visible = true;
};
// }}}
// {{{ hide()
class_interfaceLayout_dlgChoose_page.prototype.hide = function() {
	super.hide();
	
	this.movClip.dlgBox._visible = false;
};
// }}}

/*
 *	Class interfaceLayout_files
 *
 *	Extends class_interfaceLayout
 *	Defines Layout-Elements browsing project files
 */
// {{{ constructor
class_interfaceLayout_files = function(interfaceObj, title) {
	super(interfaceObj, title);
};
class_interfaceLayout_files.prototype = new class_interfaceLayout();
// }}}
// {{{ init()
class_interfaceLayout_files.prototype.init = function() {
	this.treeObj1 = new class_treeDisplayData_files(conf.project.tree.files, this);
	this.treeObj1.init();
};
// }}}
// {{{ generate()
class_interfaceLayout_files.prototype.generate = function(dlgArgs) {
	this.treeNum = 1;

	super.generate(dlgArgs);
	
	conf.project.previewDisableCache = false;
	
	this.movClip.treeBox1.treeObj = this.treeObj1;
	this.movClip.treeBox1.treeObj.setFileFilter();
	
	setTimeout(this.treeSetActive, this, 10, [true]);
};
// }}}

/*
 *	Class interfaceLayout_dlgChoose_files
 *
 *	Extends class_interfaceLayout_files
 *	Defines Layout-Elements browsing project files
 */
// {{{ constructor
class_interfaceLayout_dlgChoose_files = function(interfaceObj, title) {
	super(interfaceObj, title);
};
class_interfaceLayout_dlgChoose_files.prototype = new class_interfaceLayout_files();

class_interfaceLayout_dlgChoose_files.prototype.isDialog = true;
class_interfaceLayout_dlgChoose_files.prototype.treeTopOffset = 90;
// }}}
// {{{ generate()
class_interfaceLayout_dlgChoose_files.prototype.generate = function(dlgArgs) {
	super.generate(dlgArgs);
	
	conf.project.previewDisableCache = false;
	
	if (dlgArgs[0] != "") {
		this.movClip.treeBox1.treeObj.activeNodeIdWaiting = dlgArgs[0];
		this.movClip.treeBox1.treeObj.setActiveNodeById(dlgArgs[0]);
	}
	this.dataNodeId = dlgArgs[1];
	this.movClip.treeBox1.treeObj.treeObj.setFileFilter(dlgArgs[2], dlgArgs[3], dlgArgs[4]);

        this.treeTopOffset = 90;
        var extraText = conf.lang.msg_choose_file + "\n";
        if (dlgArgs[2] != "") {
            extraText += "\n" + conf.lang.msg_choose_file_filter_type + dlgArgs[2];
            this.treeTopOffset += 22;
        }
        if (int(dlgArgs[3]) > 0) {
            extraText += "\n" + conf.lang.msg_choose_file_filter_width + dlgArgs[3] + "px";
            this.treeTopOffset += 22;
        }
        if (int(dlgArgs[4]) > 0) {
            extraText += "\n" + conf.lang.msg_choose_file_filter_height + dlgArgs[4] + "px";
            this.treeTopOffset += 22;
        }

	this.movClip.attachMovie("tree_box_dlg", "dlgBox", 50, {
		text	: extraText,
		type	: "OkCancel"
	});
	this.movClip.dlgBox.getX = function() {
		return this._parent.register._x + 7;
	};
	this.movClip.dlgBox.getY = function() {
		return 15;
	};
	this.movClip.dlgBox.getHeight = function() {
		return this._parent.interface.activeLayout.treeTopOffset - 15;
	};
	this.movClip.dlgBox.getWidth = function() {
		return this._parent.divider_vertical._x - 38 - 21;
	};
	this.movClip.dlgBox.onOk = function() {
		this._parent.interface.returnDlg(true, [conf.url_lib_scheme_intern + ":" + this._parent.treeBox1.treeObj.treeObj.selectedFile, this._parent.interface.activeLayout.dataNodeId]);
	};
	this.movClip.dlgBox.onCancel = function() {
		this._parent.interface.returnDlg(false);
	};
};
// }}}
// {{{ remove()
class_interfaceLayout_dlgChoose_files.prototype.remove = function() {
	super.remove();
	
	this.movClip.treeBox1.treeObj.treeObj.setFileFilter();
	
	this.movClip.dlgBox.removeMovieClip();
};
// }}}
// {{{ show()
class_interfaceLayout_dlgChoose_files.prototype.show = function() {
	super.show();
	
	this.movClip.dlgBox._visible = true;
};
// }}}
// {{{ hide()
class_interfaceLayout_dlgChoose_files.prototype.hide = function() {
	super.hide();
	
	this.movClip.dlgBox._visible = false;
};
// }}}

/*
 *	Class interfaceLayout_dlgChoose_file_link
 *
 *	Extends class_interfaceLayout_file_link
 *	Defines Layout-Elements browsing project files
 */
// {{{ constructor
class_interfaceLayout_dlgChoose_file_link = function(interfaceObj, title) {
	super(interfaceObj, title);
	this.allowedRegister.push(conf.lang.register_name_edit_pages);
};
class_interfaceLayout_dlgChoose_file_link.prototype = new class_interfaceLayout_dlgChoose_files();
// }}}
// {{{ generate()
class_interfaceLayout_dlgChoose_file_link.prototype.generate = function(dlgArgs) {
	super.generate(dlgArgs);
	this.movClip.dlgBox.text = conf.lang.msg_choose_file_link;
}
// }}}

/*
 *	Class interfaceLayout_colors
 *
 *	Extends class_interfaceLayout_files
 *	Defines Layout-Elements for editing colors
 */
// {{{ constructor
class_interfaceLayout_colors = function(interfaceObj, title) {
	super(interfaceObj, title);
};
class_interfaceLayout_colors.prototype = new class_interfaceLayout();
// }}}
// {{{ init()
class_interfaceLayout_colors.prototype.init = function() {
	this.treeObj1 = new class_treeDisplayData_colors(conf.project.tree.colors, this);
	this.treeObj1.init();
};
// }}}
// {{{ generate()
class_interfaceLayout_colors.prototype.generate = function(dlgArgs) {
	this.treeNum = 1;

	super.generate(dlgArgs);

	conf.project.previewDisableCache = false;
	
	this.movClip.treeBox1.treeObj = this.treeObj1;
			
	setTimeout(this.treeSetActive, this, 10, [true]);
};
// }}}
	
/*
 *	Class interfaceLayout_settings
 *
 *	Extends class_interfaceLayout_settings
 *	Defines Layout-Elements for editing colors
 */
// {{{ constructor
class_interfaceLayout_settings = function(interfaceObj, title) {
	super(interfaceObj, title);
};
class_interfaceLayout_settings.prototype = new class_interfaceLayout_files();
// }}}
// {{{ init()
class_interfaceLayout_settings.prototype.init = function() {
	this.treeObj1 = new class_treeDisplayData_settings(conf.project.tree.settings, this);
	this.treeObj1.init();
};
// }}}
// {{{ generate()
class_interfaceLayout_settings.prototype.generate = function(dlgArgs) {
	this.treeNum = 1;

	super.generate(dlgArgs);
	
	conf.project.previewDisableCache = false;
	
	this.movClip.treeBox1.treeObj = this.treeObj1;
			
	setTimeout(this.treeSetActive, this, 10, [true]);
};
// }}}
	
/*
 *	Class interfaceLayout_editTemplates
 *
 *	Extends class_interfaceLayout_editPages
 *	Defines Layout-Elements for editing Templates
 */
// {{{ constructor
class_interfaceLayout_templates = function(interfaceObj, title) {
	super(interfaceObj, title);
};
class_interfaceLayout_templates.prototype = new class_interfaceLayout_editPages();
// }}}
// {{{ init()
class_interfaceLayout_templates.prototype.init = function() {
	this.treeObj1 = new class_treeDisplayData_templates(conf.project.tree.tpl_templates, this);
	this.treeObj2 = new class_treeDisplayData_newnodes(conf.project.tree.tpl_newnodes, this);
	this.treeObj1.init();
	this.treeObj2.init();
};
// }}}
// {{{ generate()
class_interfaceLayout_templates.prototype.generate = function(dlgArgs) {
	this.treeNum = 2;

	super.generate(dlgArgs);
	
	if (!conf.project.previewDisableCache) {
		conf.project.previewDisableCache = true;
		conf.project.preview();
	}
	
	this.movClip.treeBox1.treeObj = this.treeObj1;
	this.movClip.treeBox2.treeObj = this.treeObj2;
	
	setTimeout(this.treeSetActive, this, 10, [true]);
};
// }}}

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
