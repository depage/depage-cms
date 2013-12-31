/*
 *	Class PropBox
 *
 *	main PropertyClass
 */
// {{{ constructor
class_propBox = function() {};
class_propBox.prototype = new MovieClip();

class_propBox.prototype.usesFirstLine = true;
class_propBox.prototype.showSaver = true;
class_propBox.prototype.multilangProp = 0;

class_propBox.prototype.propName = [];
// }}}
// {{{onLoad()
class_propBox.prototype.onLoad = function() {
	var tempArray = [];
	for (var i = 0; i < this.propName.length; i++) {
		tempArray[i] = this.propName[i];
	}
	this.propName = tempArray;
	
	this.onLoadCalled = true;
	
	this.settings = {
		border				: 5,
		gridSize			: 25,
		minInnerHeight		: 23
	};
	this.settings.explanationWidth = 6 * this.settings.gridSize;
	this.settings.OkCancelWidth = 2 * this.settings.gridSize;

	this.attachMovie("prop_back", "back", 1);

	this.generateComponents();
	this.setData();
	this.onResize();
	
	Stage.addListener(this);
};
// }}}
// {{{ onUnload()
class_propBox.prototype.onUnload = function() {
	Stage.removeListener(this);
};
// }}}
// {{{ onResize()
class_propBox.prototype.onResize = function() {
	this.width = this._parent.width;
		
	if (this.width - this.settings.explanationWidth - this.settings.OKCancelWidth < this.settings.explanationWidth) {
		this.settings.border_top = this.settings.minInnerHeight;
		this.settings.border_bottom = this.settings.border;
		this.settings.border_left = this.settings.border;
		this.settings.border_right = this.settings.border;
	} else {
		this.settings.border_top = 0;
		this.settings.border_bottom = this.settings.border;
		this.settings.border_left = this.settings.explanationWidth;
		this.settings.border_right = this.settings.OkCancelWidth + this.settings.border;
	};
	if (this.multilangProp != 2) {
		this.settings.border_top = this.settings.border_top + this.settings.border;
	}

	this.setComponents();		

	this.back.onResize();
	
	if (this.num == this._parent.propLineNum) {
		this._parent.setPropPos();	
	}
};
// }}}
// {{{ setHeight()
class_propBox.prototype.setHeight = function() {
	if (this.multilangProp == 2) {
		if (this._parent["propLine" + (this.num - 1)].multilangProp > 0) {
			this._parent["propLine" + (this.num - 1)].setHeight();
		}
	} else {
		this.back.setHeight();
	}
};
// }}}
// {{{ onChanged()
class_propBox.prototype.onChanged = function() {
	if (!this.isChanged) {
		this.back.setStatus(true);
		this.isChanged = true;

		if (this.multilangProp == 2) {
			if (this._parent["propLine" + (this.num - 1)].multilangProp > 0) {
				this._parent["propLine" + (this.num - 1)].onChanged();
			}
		}
	}
};
// }}}
// {{{ onLeave()
class_propBox.prototype.onLeave = function() {
	if (this.isChanged && allowEvents) {
		this.save();
	}
};
// }}}
// {{{ setMultilangProp()
class_propBox.prototype.setMultilangProp = function() {
	if (this.data.attributes.lang != undefined && this.data.attributes.lang != "" && this.data.attributes.lang != conf.project.tree.settings.languages[0].shortname) {
		this.propName[0] = "";
		this.multilangProp = 2;
	} else if (this.data.attributes.lang != undefined && this.data.attributes.lang != "") {
		this.multilangProp = 1;
	}
	if (this.multilangProp > 1) {
		this.showSaver = false;
	}
	this.propLang = this.data.attributes.lang;
};
// }}}
// {{{ setTitle()
class_propBox.prototype.setTitle = function(newTitle) {
	if (this.data.attributes.lang == undefined || this.data.attributes.lang == "" || this.data.attributes.lang == conf.project.tree.settings.languages[0].shortname) {
            this.propName[0] = newTitle;
            this.back.setTitle();
        }
};
// }}}
// {{{ setData()
class_propBox.prototype.setData = function() {
	this.dataBackup = this.data.cloneNode(true);
	if (this.data.attributes[conf.ns.database + ":name"] != undefined) {
		var newTitle = "%" + this.data.attributes[conf.ns.database + ":name"] + "%";
		this.setTitle(newTitle.replaceInterfaceTexts());
	} else if (this.data.attributes["name"] != undefined) {
            this.setTitle(this.data.attributes["name"]);
        }
	this.isChanged = false;
};
// }}}
// {{{ resetData()
class_propBox.prototype.resetData = function() {
	var tempXML = this.data.parentNode;
	
	tempXML.insertBefore(this.dataBackup, this.data);
	this.data.removeNode();
	this.data = this.dataBackup;
	
	this.data.setNodeIdByDBId();

	this.back.setStatus(false);
	this.setData();
	
	this.save(true);
	
	if (this.multilangProp > 0) {
		if (this._parent["propLine" + (this.num + 1)].multilangProp == 2) {
			this._parent["propLine" + (this.num + 1)].resetData();
		}
	}
};
// }}}
// {{{ save()
class_propBox.prototype.save = function(forceSave) {
	if (this.multilangProp < 2) {
		this.saveData(forceSave);
		this.saveMultilang(forceSave);
	} else {
		this._parent["propLine" + (this.num - 1)].save(forceSave);
	}
};
// }}}
// {{{ saveData()
class_propBox.prototype.saveData = function(forceSave) {
	if (this.isChanged == true || forceSave == true) {
		this._parent.propObj.save(this.data.nid);
		this.isChanged = false;
	}
	return true;
};
// }}}
// {{{ saveMultiLang()
class_propBox.prototype.saveMultilang = function(forceSave) {
	if (this.multilangProp > 0) {
		if (this._parent["propLine" + (this.num + 1)].multilangProp == 2) {
			this._parent["propLine" + (this.num + 1)].saveData(forceSave);
			this._parent["propLine" + (this.num + 1)].saveMultilang(forceSave);
		}
	}
};
// }}}
// {{{ resetButtons()
class_propBox.prototype.resetButtons = function() {
	this.back.setStatus(false);
	if (this.saveData()) {
		this.setData();
	}
	
	if (this.multilangProp > 0) {
		if (this._parent["propLine" + (this.num + 1)].multilangProp == 2) {
			this._parent["propLine" + (this.num + 1)].resetButtons();
		}
	}
};
// }}}
// {{{ setComponents()
class_propBox.prototype.setComponents = function() {
	this.innerHeight = this.settings.minInnerHeight;
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
};
// }}}
// {{{ setNewPos()
class_propBox.prototype.setNewPos = function() {

};
// }}}
// {{{ generateComponentsNoRight()
class_propBox.prototype.generateComponentsNoRight = function() {
	// {{{ this.onResize()
	this.onResize = function() {
		this.width = this._parent.width;
		
		if (this.width - this.settings.explanationWidth - this.settings.OKCancelWidth < this.settings.explanationWidth) {
			this.settings.border_top = this.settings.minInnerHeight + this.settings.border;
			this.settings.border_bottom = this.settings.border;
			this.settings.border_left = this.settings.border;
			this.settings.border_right = this.settings.border;
		} else {
			this.settings.border_top = this.settings.border;
			this.settings.border_bottom = this.settings.border;
			this.settings.border_left = this.settings.explanationWidth;
			this.settings.border_right = this.settings.OkCancelWidth;
		};

		this.setComponentsNoRight();		
		
		this.back.onResize();

		if (this.num == this._parent.propLineNum) {
			this._parent.setPropPos();	
		}
	};
	// }}}
	
	this.createTextField("textBox", 2, 0, 0, 200, 10);
	this.textBox.initFormat(conf.interface.textformat);
	this.textBox.wordWrap = true;
	this.textBox.selectable = false;
};
// }}}
// {{{ setComponentsNoRight()
class_propBox.prototype.setComponentsNoRight = function() {
	this.textBox.text = conf.lang.auth_no_right.replace("%name%", this.propName[0]);
	
	this.textBox._height = 10;
	
	this.textBox._width = this.width - this.settings.border_left - this.settings.border_right;
	this.textBox._height = (conf.interface.textformat_bold.size + 6) * (this.textBox.maxScroll + 1);
	this.textBox._x = this.settings.border_left;
	this.textBox._y = this.settings.border_top + 2;
	
	this.innerHeight = this.textBox._height;
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
};
// }}}

/*
 *	Class PropBox_edit_text_singleline
 *
 *	Extends class_propBox
 *	Main SingleLineProp
 */
// {{{ constructor
class_propBox_edit_text_singleline = function() {};
class_propBox_edit_text_singleline.prototype = new class_propBox();
// }}}
// {{{ generateComponents()
class_propBox_edit_text_singleline.prototype.generateComponents = function() {
	this.attachMovie("component_inputField", "inputBox", 2);
	this.inputBox.onChanged = function() {
		this._parent.onChanged();
	};
	this.inputBox.onKillFocus = function() {
		//this._parent.save();
		updateAfterEvent();
	};
	this.inputBox.onEnter = function() {
		this._parent.save();	
	};
	this.inputBox.onCtrlS = function() {
		this._parent.save();	
	};
};
// }}}
// {{{ setComponents()
class_propBox_edit_text_singleline.prototype.setComponents = function() {
	this.inputBox._x = this.settings.border_left;
	this.inputBox._y = this.settings.border_top;
	this.inputBox.width = this.width - this.settings.border_left - this.settings.border_right;
			
	this.setHeight();
};
// }}}
// {{{ setHeight()
class_propBox_edit_text_singleline.prototype.setHeight = function() {
	this.innerHeight = this.settings.minInnerHeight;
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
	
	super.setHeight();
	this._parent.setPropPos();
};
// }}}
// {{{ setData()
class_propBox_edit_text_singleline.prototype.setData = function() {
	super.setData();
	
	this.setMultilangProp();
	
	this.inputBox.value = this.data.attributes.value;
};
// }}}
// {{{ saveData()
class_propBox_edit_text_singleline.prototype.saveData = function(forceSave) {
	if (this.isChanged == true || forceSave == true) {
		this.data.attributes.value = this.inputBox.value;

		this._parent.propObj.save(this.data.nid);
		this.isChanged = false;
	}
	return true;
};
// }}}

/*
 *	Class PropBox_edit_text_multiline
 *
 *	Extends class_propBox
 *	Main MulitLineProp
 */
// {{{ constructor
class_propBox_edit_text_multiline = function() {};
class_propBox_edit_text_multiline.prototype = new class_propBox();

class_propBox_edit_text_multiline.prototype.usesFirstLine = false;
class_propBox_edit_text_multiline.prototype.minHeight = 100;
// }}}
// {{{ onResize()
class_propBox_edit_text_multiline.prototype.onResize = function() {
	this.width = this._parent.width;

	if (this.usesFirstLine) {
		if (this.width - this.settings.explanationWidth - this.settings.OKCancelWidth < this.settings.explanationWidth) {
			if (this.multilangProp == 2) {
				this.settings.border_top = this.settings.minInnerHeight;
			} else {
				this.settings.border_top = this.settings.minInnerHeight + this.settings.border;
			}
			this.settings.border_bottom = this.settings.border;
			this.settings.border_left = this.settings.border;
			this.settings.border_right = this.settings.border;
		} else {
			if (this.multilangProp == 2) {
				this.settings.border_top = 0;
			} else {
				this.settings.border_top = this.settings.border;
			}
			this.settings.border_bottom = this.settings.border;
			this.settings.border_left = this.settings.explanationWidth;
			this.settings.border_right = this.settings.OkCancelWidth + this.settings.border;
		}
	} else {
		if (this.multilangProp < 2) {
			this.settings.border_top = this.settings.minInnerHeight + this.settings.border;
		} else {
			this.settings.border_top = 0;
		}
		this.settings.border_bottom = this.settings.border;
		this.settings.border_left = this.settings.border;
		this.settings.border_right = this.settings.border;
	}

	this.setComponents();		
	
	this.back.onResize();

	if (this.num == this._parent.propLineNum) {
		this._parent.setPropPos();	
	}
};
// }}}
// {{{ generateComponents()
class_propBox_edit_text_multiline.prototype.generateComponents = function() {
	this.attachMovie("rectangle", "textBoxBack", 2);
	this.createTextField("textBox", 3, 0, 0, 100, 10);
	this.setTextBoxFormat();
	this.textBox.type = "input";
	this.textBox.selectable = true;
	this.textBox.multiline = true;
	this.textBox.wordwrap = true;
	this.textBox.initFormat(this.textBox.textFormat);
	this.active = false;
	// {{{ textBox.onChanged()
	this.textBox.onChanged = function() {
		this._parent.onChanged();
	};
	// }}}
	// {{{ onSetFocus()
	this.textBox.onSetFocus = function() {
		this._parent.textBoxBack.back.setRGB(conf.interface.color_input_face_active);
		Key.addListener(this);
		this.intervalID = setInterval(this, "onEditInterval", 100);
		this.timeoutObj.clear();
		this.active = true;
	};
	// }}}
	// {{{ onEditInterval()
	this.textBox.onEditInterval = function() {
		this._parent.saveSelection();
		updateAfterEvent();
	};
	// }}}
	// {{{ onKillFocus()
	this.textBox.onKillFocus = function() {
		clearInterval(this.intervalID);
		Key.removeListener(this);
		//this._parent.save();	
		this.timeoutObj = setTimeout(this.killedFocus, this, 200);
		this.active = false;
	};
	// }}}
	// {{{ killedFocus()
	this.textBox.killedFocus = function() {
		this._parent.textBoxBack.back.setRGB(conf.interface.color_input_face_inactive);
		this._parent.selectionBeginIndex = -1;
		this._parent.selectionEndIndex = -1;
	};
	// }}}
	// {{{ onScroller()
	this.textBox.onScroller = function() {
		this._parent.onScroller();
	};
	// }}}
	// {{{ onKeyDown()
	this.textBox.onKeyDown = function() {
		var keyCode = Key.getCode();

		if (keyCode == Key.TAB) {
			if (Selection.getBeginIndex() == Selection.getEndIndex()) {
				this.text = this.text.substring(0, Selection.getBeginIndex()) + "\t" + this.text.substring(Selection.getBeginIndex(), this.text.length);
				Selection.setSelection(Selection.getBeginIndex() + 1, Selection.getBeginIndex() + 1);
			}
		} else if (keyCode == 83 && Key.isDown(Key.CONTROL)) {
			this._parent.save();
			this._parent.resetButtons();
		} else if (keyCode == Key.DELETEKEY || keyCode == Key.BACKSPACE) {
			this._parent.setHeight();
		}
	};
	// }}}
	
	this.lineHeight = this.textBox.textFormat.lineSpacing;
	this.textBoxBack.outline.color = conf.interface.color_component_line;
	this.textBoxBack.back.color = conf.interface.color_input_face_inactive;
};
// }}}
// {{{ saveSelection()
class_propBox_edit_text_multiline.prototype.saveSelection = function() {

};
// }}}
// {{{ setTextBoxFormat()
class_propBox_edit_text_multiline.prototype.setTextBoxFormat = function() {
	this.textBox.html = true;
	this.textBox.textFormat = conf.interface.textformat_input_source;
};
// }}}
// {{{ setComponents()
class_propBox_edit_text_multiline.prototype.setComponents = function() {
	if (this.usesFirstLine) {
		this.textBox._x = this.settings.border_left + 4;
		this.textBoxBack._x = this.settings.border_left;
	} else {
		this.textBox._x = this.settings.border_left + this.settings.gridsize * 2 + 4;
		this.textBoxBack._x = this.settings.border_left + this.settings.gridsize * 2;
	}
	this.textBoxBack._y = this.settings.border_top;
	this.textBoxBack._width = int(this.width - this.textBoxBack._x - this.settings.border_right - 1);	

	//this.setHeight();
	this._visible = false;

	setTimeout(this.setHeight, this, 1, [], false);
};
// }}}
// {{{ onScroller()
class_propBox_edit_text_multiline.prototype.onScroller = function() {
	this.textHeight = this.textBox.textHeight;
	if (this.oldTextHeight != this.textHeight) {
		this.setHeight();
		this.oldTextHeight = this.textHeight;
	}
	if ((this.textBox.scroll > this.oldTextScroll && this.getGlobalY() + this.height > Stage.height) || (this.textBox.scroll < this.oldTextScroll)) {
		var scrollNum = this.textBox.scroll - this.oldTextScroll;
		if (scrollNum == 1 || scrollNum == -1) scrollNum *= 3;
		this._parent.setOffset(this._parent.offset + scrollNum * this.lineHeight);
		this.oldTextScroll = this.textBox.scroll;
	}
	if (this.oldTextHeight > this.textHeight && this.getGlobalY() + this.innerHeight + this.settings.border_top - 3 * this.lineHeight < 0) {
		this._parent.setOffset(this._parent.offset + (this.getGlobalY() + this.innerHeight + this.settings.border_top - 3 * this.lineHeight));
	}
};
// }}}
// {{{ setNewPos()
class_propBox_edit_text_multiline.prototype.setNewPos = function() {
	var textBoxMinY = this.settings.border_top + this.settings.minPropHeight + 2;
	var textBoxHeight = this.innerHeight + this.lineHeight;
	var textBoxAddHeight = 0;

	if (this.getGlobalY() > -textBoxMinY && this.getGlobalY() < Stage.height) {
		this.textBox._y = this.settings.border_top + this.settings.minPropHeight + 2;
		this.textBox._width = int(this.width - this.textBox._x - this.settings.border_right - 4);
		this.textBox.scroll = 1;
		this.textBox._visible = true;
	} else if (this.getGlobalY() <= -textBoxMinY && this.getGlobalY() + this.settings.border_top + this.innerHeight > 0) {
		this.textBox._y = - this.getGlobalY();
		setTimeout(this.textBoxScroll, this, 1, [], false);
		this.textBox._visible = true;
	} else {
		this.textBox._visible = false;
	}

	textBoxHeight = textBoxHeight.limit(null, Stage.height - this.textBox.getGlobalY());
	textBoxHeight = textBoxHeight.limit(null, this.innerHeight + this.settings.border_top - this.textBox._y - 2);
	this.textBox._height = textBoxHeight;

	this.oldTextScroll = this.textBox.scroll;
};
// }}}
// {{{ textBoxScroll()
class_propBox_edit_text_multiline.prototype.textBoxScroll = function() {
	var newScroll = int((- this.getGlobalY()) / this.lineHeight);

	this.oldTextScroll = newScroll;
	this.textBox.scroll = newScroll;
};
// }}}
// {{{ setHeight()
class_propBox_edit_text_multiline.prototype.setHeight = function() {
	var textBoxHeight;

	this.textHeight = this.textBox.textHeight;
	
	this.innerHeight = this.textHeight > this.minHeight ? this.textHeight + 12 : this.minHeight;
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
	
	textBoxHeight = this.innerHeight - 3 + 10;
	if (textBoxHeight > Stage.height - 60) {
		textBoxHeight = Stage.height - 60;
	}
	this.textBoxBack._height = this.innerHeight - 5;
	
	super.setHeight();
	this._parent.setPropPos();

	/* ERROR NOT FOUND -> its only a workaround for flash */
	this.textBoxBack._width = int(this.width - this.textBoxBack._x - this.settings.border_right - 1);	
	//this.textBoxBack._width = int(this.textBoxBack._width);
	
	this._visible = true;
};
// }}}

/*
 *	Class PropBox_edit_text_formatted
 *
 *	Extends class_propBox_edit_text_multiline
 *	Handles HTML-formatted Textfields
 */
// {{{ constructor
class_propBox_edit_text_formatted = function() {};
class_propBox_edit_text_formatted.prototype = new class_propBox_edit_text_multiline();

class_propBox_edit_text_formatted.prototype.propName = [];
class_propBox_edit_text_formatted.prototype.textLinks = [];
	
class_propBox_edit_text_formatted.prototype.propName[0] = conf.lang.prop_name_edit_text_formatted;
class_propBox_edit_text_formatted.prototype.minHeight = 100;

class_propBox_edit_text_formatted.prototype.textLinkIsSecondClick = false;

class_propBox_edit_text_formatted.prototype.maxChars = 10000;
// }}}
// {{{ setTextBoxFormat()
class_propBox_edit_text_formatted.prototype.setTextBoxFormat = function() {
	this.textBox.html = true
	this.textBox.textFormat = conf.interface.textformat_input;
	this.textBox.textFormatSmall = conf.interface.textformat_input_small;
};
// }}}
// {{{ generateComponents()
class_propBox_edit_text_formatted.prototype.generateComponents = function() {
	super.generateComponents();
	
	for (var i = 1; i <= 4; i++) {
		this.attachMovie("component_button_symbol", "button_" + i, i + 10, {
			width	: 19,
			height	: 17
		});
	}

	this.button_1.symbol = "icon_format_bold";
	this.button_1.tooltip = conf.lang.buttontip_format_bold;
	this.button_1.enabledState = true;
	this.button_1.onClick = function() {
		setTimeout(this._parent.formatSelection, this._parent, 10, ["bold"]);
	};
	
	this.button_2.symbol = "icon_format_italic";
	this.button_2.tooltip = conf.lang.buttontip_format_italic;
	this.button_2.enabledState = true;
	this.button_2.onClick = function() {
		setTimeout(this._parent.formatSelection, this._parent, 10, ["italic"]);
	};
	
	this.button_3.symbol = "icon_format_small";
	this.button_3.tooltip = conf.lang.buttontip_format_small;
	this.button_3.enabledState = true;
	this.button_3.onClick = function() {
		setTimeout(this._parent.formatSelection, this._parent, 10, ["small"]);
	};
	
	this.button_4.symbol = "icon_format_link";
	this.button_4.tooltip = conf.lang.buttontip_format_link;
	this.button_4.enabledState = true;
	this.button_4.onClick = function() {
		setTimeout(this._parent.formatSelection, this._parent, 10, ["link"]);
	};
};
// }}}
// {{{ setComponents()
class_propBox_edit_text_formatted.prototype.setComponents = function() {
	super.setComponents();
	
	this.button_1._x = this.settings.border_left + 2;
	this.button_2._x = this.settings.border_left + 2;
	this.button_3._x = this.settings.border_left + 2;
	this.button_4._x = this.settings.border_left + 2;

        this.tooltipObj = new tooltipClass.tooltipObj();
};
// }}}
// {{{ setNewPos()
class_propBox_edit_text_formatted.prototype.setNewPos = function() {
	var bottomBorder = this.height - this.settings.border_bottom - 2;
	
	super.setNewPos();
	
	if (this.getGlobalY() + bottomBorder > Stage.height - 7) {
		bottomBorder = Stage.height - 7 - this.getGlobalY();	
	}
	if (bottomBorder < this.settings.border_top + 85) {
		bottomBorder = this.settings.border_top + 85;
	}
		
	this.button_1._y = bottomBorder - 76
	this.button_2._y = bottomBorder - 59
	this.button_3._y = bottomBorder - 38;
	this.button_4._y = bottomBorder - 17;
};
// }}}
// {{{ setButtons()
class_propBox_edit_text_formatted.prototype.setButtons = function() {
	var i;
	
	for (var i = 1; i <= 4; i++) {
		this["button_" + i].setStatus(this["button_" + i].enabledState);
	}
};
// }}}
// {{{ onChanged()
class_propBox_edit_text_formatted.prototype.onChanged = function() {
    super.onChanged();

    var warningMaxChars = this.maxChars * 0.98;
    var errorText = conf.lang.prop_tt_text_formatted_maxchars;

    if (this.textBox.text.length > warningMaxChars) {
        // show info
        errorText = errorText.replace([
            ["%chars%"	 , this.maxChars - this.textBox.text.length],
            ["%maxchars%", this.maxChars]
        ]);
        this.tooltipObj.setText(errorText);

        if (!this.tooltipObj.shown) {
            setTimeout(this.tooltipObj.show, this.tooltipObj, 1, [this.button_1.getGlobalX(), this.button_1.getGlobalY() - 30], true);
        }
    } else {
        // hide info
        if (this.tooltipObj.shown) {
            this.tooltipObj.hide();
        }
    }
};
// }}}
// {{{ setData()
class_propBox_edit_text_formatted.prototype.setData = function() {
	var i, tempText;
	
	super.setData();
	
	this.setMultilangProp();
	
	tempText = "";
	for (var i = 0; i < this.data.childNodes.length; i++) {
		this.data.childNodes[i].stripXMLDbIds();
		tempText += this.data.childNodes[i].toString();
	}
	tempText = this.textBox.prepareHtmlText(tempText);
	
	if (this.textBox.text == "" && tempText.length > 600) {
		this.textBox.type = "dynamic";
		this.textBox.htmlText = "<p> </p><p>" + conf.lang.prop_tt_text_formatted_loading + "</p>";
		this.textBox.initFormat(conf.interface.textformat_waitForParsing);
	
		setTimeout(this.setDataNow, this, 10, [tempText]);
	} else {
		this.setDataNow(tempText);
	}
};
// }}}
// {{{ setDataNow()
class_propBox_edit_text_formatted.prototype.setDataNow = function(tempText) {
	this.textBox.type = "input";
	
        this.textBox.htmlText = tempText;
        this.textBox.maxChars = this.maxChars;

	//this.textBox.initFormat(this.textBox.textFormat);

        for (var i = 0; i < this.textBox.text.length; i++) {
            tf = this.textBox.getTextFormat(i, i + 1);
            if (tf.size == this.textBox.textFormatSmall.size) {
                this.textBox.setTextFormat(i, this.textBox.textFormatSmall);
            } else {
                this.textBox.setTextFormat(i, this.textBox.textFormat);
            }
        }
        this.textBox.setNewTextFormat(this.textBox.textFormat);

	this.textBox.htmlText = this.textBox.htmlText.replace([
		["<I></I>"    , ""],
		["<B></B>"    , ""]
	]);

	this.onScroller();
};
// }}}
// {{{ saveData()
class_propBox_edit_text_formatted.prototype.saveData = function(forceSave) {
	var tempXML = new XML("<root>" + this.textBox.reducedHtmlText() + "</root>");
	var tempNode = tempXML.firstChild;
	
	while (this.data.hasChildNodes()) {
		this.data.firstChild.removeNode();
	}
	for (var i = 0; i < tempNode.childNodes.length; i++) {
		this.data.appendChild(tempNode.childNodes[i].cloneNode(true));
	}
	
	return super.saveData(forceSave);
};
// }}}
// {{{ saveSelection()
class_propBox_edit_text_formatted.prototype.saveSelection = function() {
	var beginIndex = Selection.getBeginIndex();
	var endIndex = Selection.getEndIndex();
	
	if (beginIndex > endIndex) {
		var temp = beginIndex;
		beginIndex = endIndex;
		endIndex = temp;
	}
	
	if (beginIndex != -1) {
		this.selectionBeginIndex = beginIndex;
	}
	if (endIndex != -1) {
		this.selectionEndIndex = endIndex;
	}
};
// }}}
// {{{ formatSelection()
class_propBox_edit_text_formatted.prototype.formatSelection = function(type) {
	var tempGetFormat;
	var tempSetFormat = new TextFormat();
	
	if (this.selectionBeginIndex != -1 && this.selectionEndIndex != -1) {
		tempGetFormat = this.textBox.getTextFormat(this.selectionBeginIndex, this.selectionEndIndex);
		replaceText = false;
		if (type == "bold") {
			if (tempGetFormat.bold == false) {
				tempSetFormat.bold = true;
			} else {
				tempSetFormat.bold = false;
			}
		} else if (type == "italic") {
			if (tempGetFormat.italic == false) {
				tempSetFormat.italic = true;
			} else {
				tempSetFormat.italic = false;
			}
		} else if (type == "small") {
			if (tempGetFormat.size != this.textBox.textFormatSmall.size) {
				tempSetFormat.size = this.textBox.textFormatSmall.size;
			} else {
				tempSetFormat.size = this.textBox.textFormat.size;
			}
		} else if (type == "link") {
			if (tempGetFormat.url == "") {
                                this.textLinkDoubleClick(-1);
			} else {
				replaceText = true;
			}
		}
		if (!replaceText) {
			this.textBox.setTextFormat(this.selectionBeginIndex, this.selectionEndIndex, tempSetFormat);
		}
		
		Selection.setFocus(this.textBox);
		Selection.setSelection(this.selectionBeginIndex, this.selectionEndIndex);

		if (replaceText) {
			this.textBox.replaceSel(this.textBox.text.substring(this.selectionBeginIndex, this.SelectionEndIndex));
			Selection.setSelection(this.selectionBeginIndex, this.selectionEndIndex);
		}
		
		this.onChanged();
	}
};
// }}}
// {{{ textLinkClick()
class_propBox_edit_text_formatted.prototype.textLinkClick = function(num) {
        this.textLinkDoubleClick(num);
        /*
	if (this.textLinkIsSecondClick) {
		this.textLinkDoubleClick(num);
	} else {
		this.textLinkIsSecondClick = true;
		setTimeout(this.textLinkResetDoubleClick, this, 300);
	}
        */
};
// }}}
// {{{ textLinkResetDoubleClick()
class_propBox_edit_text_formatted.prototype.textLinkResetDoubleClick = function() {
	this.textLinkIsSecondClick = false;
};
// }}}
// {{{ textLinkDoubleClick()
class_propBox_edit_text_formatted.prototype.textLinkDoubleClick = function(num) {
	linkChooser = new tooltipClass.linkChooserObj(this.textLinks[num][0], this);
        linkChooser.setOKFunc(this.setLink, this, [num, this.selectionBeginIndex, this.selectionEndIndex]);
	linkChooser.show(_root._xmouse, _root._ymouse);
};
// }}}
// {{{ setLink()
class_propBox_edit_text_formatted.prototype.setLink = function(num, selBeginIndex, selEndIndex, newURL) {
	var tempSetFormat = new TextFormat();

        if (num == -1) {
            // new link
            num = this.textLinks.length;

            tempSetFormat.url = "asfunction:textlink," + (num) + "," + targetPath(this);
            tempSetFormat.underline = true;

            this.textBox.setTextFormat(selBeginIndex, selEndIndex, tempSetFormat);
		
            Selection.setFocus(this.textBox);
            Selection.setSelection(selBeginIndex, selEndIndex);
        }
        this.textLinks[num] = [newURL, ""]

        this.onChanged();
};
// }}}
// {{{ _global.textLink()
_global.textlink = function(args) {
	args = args.split(",");
	tempObj = eval(args[1]);
	tempObj.textLinkClick(args[0]);
};
// }}}

/*
 *	Class PropBox_edit_list_formatted
 *
 *	Extends class_propBox_edit_text_multiline
 *	Handles HTML-formatted Textfields
 */
// {{{ constructor
class_propBox_edit_list_formatted = function() {};
class_propBox_edit_list_formatted.prototype = new class_propBox_edit_text_formatted();

class_propBox_edit_list_formatted.prototype.propName = [];
class_propBox_edit_list_formatted.prototype.propName[0] = conf.lang.prop_name_edit_list_formatted;
// }}}
// {{{ setTextBoxFormat()
class_propBox_edit_list_formatted.prototype.setTextBoxFormat = function() {
	this.textBox.html = true
	this.textBox.textFormat = conf.interface.textformat_input_list;
	this.textBox.textFormatSmall = conf.interface.textformat_input_list_small;
};
// }}}
/*
 *	Class PropBox_edit_text_headline
 *
 *	Extends class_propBox_edit_text_multiline
 *	Handles HTML Headlines
 */
// {{{ constructor
class_propBox_edit_text_headline = function() {};
class_propBox_edit_text_headline.prototype = new class_propBox_edit_text_multiline();

class_propBox_edit_text_headline.prototype.propName = [];
class_propBox_edit_text_headline.prototype.propName[0] = conf.lang.prop_name_edit_text_headline;
class_propBox_edit_text_headline.prototype.minHeight = 26;
// }}}
// {{{ setTextBoxFormat()
class_propBox_edit_text_headline.prototype.setTextBoxFormat = function() {
	this.textBox.html = true
	this.textBox.textFormat = conf.interface.textformat_input;
};
// }}}
// {{{ setData()
class_propBox_edit_text_headline.prototype.setData = function() {
	var i, tempText;
	
	super.setData();
	
	this.setMultilangProp();

	tempText = "";
	for (var i = 0; i < this.data.childNodes.length; i++) {
		this.data.childNodes[i].stripXMLDbIds();
		tempText += this.data.childNodes[i].toString();
	}
	tempText = this.textBox.prepareHtmlText(tempText);
	
	if (this.textBox.text == "" && tempText.length > 300) {
		this.textBox.type = "dynamic";
		this.textBox.htmlText = "<p> </p><p><i>" + conf.lang.prop_tt_text_formatted_loading + "</i></p>";
		this.textBox.initFormat(conf.interface.textformat_waitForParsing);
	
		setTimeout(this.setDataNow, this, 10, [tempText]);
	} else {
		this.setDataNow(tempText);
	}
};
// }}}
// {{{ setDataNow
class_propBox_edit_text_headline.prototype.setDataNow = function(tempText) {
	this.textBox.type = "input";
	
	this.textBox.htmlText = tempText;
	
	this.textBox.initFormat(this.textBox.textFormat);
		
	this.textBox.onSroller();
};
// }}}
// {{{ saveData()
class_propBox_edit_text_headline.prototype.saveData = class_propBox_edit_text_formatted.prototype.saveData;
// }}}
// {{{ saveSelection()
class_propBox_edit_text_headline.prototype.saveSelection = class_propBox_edit_text_formatted.prototype.saveSelection;
// }}}
// {{{ formatSelection()
class_propBox_edit_text_headline.prototype.formatSelection = class_propBox_edit_text_formatted.prototype.formatSelection;
// }}}

/*
 *	Class PropBox_edit_table
 *
 *	Extends class_propBox_edit_table
 *	Handles HTML-formatted Tables
 */
// {{{ constructor
class_propBox_edit_table = function() {};
class_propBox_edit_table.prototype = new class_propBox_edit_text_multiline();

class_propBox_edit_table.prototype.propName = [];
class_propBox_edit_table.prototype.propName[0] = conf.lang.prop_name_edit_table;
class_propBox_edit_table.prototype.minHeight = 100;

class_propBox_edit_table.prototype.textLinks = [];

class_propBox_edit_table.prototype.textLinkIsSecondClick = false;
// }}}
// {{{ setTextBoxFormat()
class_propBox_edit_table.prototype.setTextBoxFormat = function() {
	this.textBox.html = true
	this.textBox.textFormat = conf.interface.textformat_input;
	this.textBox.textFormatSmall = conf.interface.textformat_input_small;
};
// }}}
// {{{ generateComponents()
class_propBox_edit_table.prototype.generateComponents = function() {
	super.generateComponents();
	
	for (var i = 1; i <= 4; i++) {
		this.attachMovie("component_button_symbol", "button_" + i, i + 10, {
			width	: 19,
			height	: 17
		});
	}
		
	this.button_1.symbol = "icon_format_bold";
	this.button_1.tooltip = conf.lang.buttontip_format_bold;
	this.button_1.enabledState = true;
	this.button_1.onClick = function() {
		setTimeout(this._parent.formatSelection, this._parent, 10, ["bold"]);
	};
	
	this.button_2.symbol = "icon_format_italic";
	this.button_2.tooltip = conf.lang.buttontip_format_italic;
	this.button_2.enabledState = true;
	this.button_2.onClick = function() {
		setTimeout(this._parent.formatSelection, this._parent, 10, ["italic"]);
	};
	
	this.button_3.symbol = "icon_format_small";
	this.button_3.tooltip = conf.lang.buttontip_format_small;
	this.button_3.enabledState = true;
	this.button_3.onClick = function() {
		setTimeout(this._parent.formatSelection, this._parent, 10, ["small"]);
	};
	
	this.button_4.symbol = "icon_format_link";
	this.button_4.tooltip = conf.lang.buttontip_format_link;
	this.button_4.enabledState = true;
	this.button_4.onClick = function() {
		setTimeout(this._parent.formatSelection, this._parent, 10, ["link"]);
	};
};
// }}}
// {{{ generateTableCells()
class_propBox_edit_table.prototype.generateTableCells = function() {
    removeMovieClip("textBox");

    var row = 0;
    this.depth = 100;

    for (var i = 0; i < this.data.childNodes.length; i++) {
        if (this.data.childNodes[i].localName == "tr") { 
            //rows
            this.cells[row] = new Array();
            var col = 0;

            for (var j = 0; j < this.data.childNodes[i].childNodes.length; j++) {
                if (this.data.childNodes[i].childNodes[j].localName == "td" || this.data.childNodes[i].childNodes[j].localName == "th") { 
                    this.cells[row][col] = this.generateTableCell(this.data.childNodes[i].childNodes[j]);

                    this.attachMovie("component_button_symbol", "button_table_row_add_" + row, row + 1200, {
                        width	: 14,
                        height	: 14
                    });
                    this["button_table_row_add_" + row].symbol = "icon_table_row_add";
                    this["button_table_row_add_" + row].tooltip = conf.lang.buttontip_table_row_add;
                    this["button_table_row_add_" + row].enabledState = true;
                    this["button_table_row_add_" + row].row = row;
                    this["button_table_row_add_" + row].onClick = function() {
                        this._parent.addTableRow(this.row);
                    };

                    this.attachMovie("component_button_symbol", "button_table_row_del_" + row, row + 1400, {
                        width	: 14,
                        height	: 14
                    });
                    this["button_table_row_del_" + row].symbol = "icon_table_row_del";
                    this["button_table_row_del_" + row].tooltip = conf.lang.buttontip_table_row_del;
                    this["button_table_row_del_" + row].enabledState = true;
                    this["button_table_row_del_" + row].row = row;
                    this["button_table_row_del_" + row].onClick = function() {
                        this._parent.delTableRow(this.row);
                    };
                    col++;
                }
            }
            row++;
        }
    }
    this.setComponents();
    //this.onScroller();
};
// }}}
// {{{ generateTableCell()
class_propBox_edit_table.prototype.generateTableCell = function(dataNode) {
    var cell;

    this.depth++;
    // create Textboxes
    this.createTextField("textBox" + this.depth, this.depth, 0, 0, 100, 50),
    cell = {
        textBoxName: "textBox" + this.depth,
        textBox: this["textBox" + this.depth],
        node: dataNode
    };
    cell.textBox.type = "input";
    cell.textBox.selectable = true;
    cell.textBox.multiline = true;
    cell.textBox.wordwrap = true;
    cell.textBox.html = true;
    cell.textBox.border = true;
    cell.textBox.borderColor = conf.interface.color_input_face_inactive.toColor();
    cell.textBox.textFormat = conf.interface.textformat_input;
    cell.textBox.textFormatSmall = conf.interface.textformat_input_small;
    // {{{ textBox.onChanged()
    cell.textBox.onChanged = function() {
            this._parent.onChanged();
    };
    // }}}
    // {{{ onSetFocus()
    cell.textBox.onSetFocus = function() {
            this._parent.textBoxBack.back.setRGB(conf.interface.color_input_face_active);
            this._parent.textBox = this;
            Key.addListener(this);
            this.intervalID = setInterval(this, "onEditInterval", 100);
            this.timeoutObj.clear();
            this.active = true;
    };
    // }}}
    // {{{ onEditInterval()
    cell.textBox.onEditInterval = function() {
            this._parent.saveSelection();
            updateAfterEvent();
    };
    // }}}
    // {{{ onKillFocus()
    cell.textBox.onKillFocus = function() {
            clearInterval(this.intervalID);
            Key.removeListener(this);
            //this._parent.save();	
            this.timeoutObj = setTimeout(this.killedFocus, this, 200);
            this.active = false;
    };
    // }}}
    // {{{ killedFocus()
    cell.textBox.killedFocus = function() {
        if (!this._parent.textBox.active) {
            this._parent.textBoxBack.back.setRGB(conf.interface.color_input_face_inactive);
            this._parent.selectionBeginIndex = -1;
            this._parent.selectionEndIndex = -1;
        }
    };
    // }}}
    // {{{ onScroller()
    cell.textBox.onScroller = function() {
            this._parent.onScroller();
    };
    // }}}
    // {{{ onKeyDown()
    cell.textBox.onKeyDown = function() {
            var keyCode = Key.getCode();

            if (keyCode == Key.TAB) {
                    if (Selection.getBeginIndex() == Selection.getEndIndex()) {
                            this.text = this.text.substring(0, Selection.getBeginIndex()) + "\t" + this.text.substring(Selection.getBeginIndex(), this.text.length);
                            Selection.setSelection(Selection.getBeginIndex() + 1, Selection.getBeginIndex() + 1);
                    }
            } else if (keyCode == 83 && Key.isDown(Key.CONTROL)) {
                    this._parent.save();
                    this._parent.resetButtons();
            } else if (keyCode == Key.DELETEKEY || keyCode == Key.BACKSPACE) {
                    this._parent.setHeight();
            }
    };
    // }}}

    // prepare Data
    tempText = "";
    for (var k = 0; k < dataNode.childNodes.length; k++) {
        dataNode.childNodes[k].stripXMLDbIds();
        tempText += dataNode.childNodes[k].toString();
    }
    tempText = cell.textBox.prepareHtmlText(tempText);

    this.setCellData(cell.textBox, tempText);

    return cell;
};
// }}}
// {{{ removeTableCells()
class_propBox_edit_table.prototype.removeTableCells = function() {
    for (var i = 0; i < this.cells.length; i++) {
        for (var j = 0; j < this.cells[i].length; j++) {
            this[this.cells[i][j].textBoxName].removeTextField();
        }
        this["button_table_row_add_" + i].removeMovieClip();
        this["button_table_row_del_" + i].removeMovieClip();
    }
    this.cells = new Array();
};
// }}}
// {{{ addTableRow()
class_propBox_edit_table.prototype.addTableRow = function(row) {
    newRow = new Array();
    for (var i = 0; i < this.cells[row].length; i++) {
        newRow.push(new Object());
    }
    this.cells.splice(row + 1, 0, newRow);

    this.isChanged = true;
    this.saveData();
    this.removeTableCells();
    setTimeout(this.generateTableCells, this, 1, [], false);
};
// }}}
// {{{ delTableRow()
class_propBox_edit_table.prototype.delTableRow = function(row) {
    for (var j = 0; j < this.cells[row].length; j++) {
        this[this.cells[row][j].textBoxName].removeTextField();
    }
    this["button_table_row_add_" + row].removeMovieClip();
    this["button_table_row_del_" + row].removeMovieClip();

    this.cells.splice(row, 1);

    this.isChanged = true;
    this.saveData();
    this.removeTableCells();
    setTimeout(this.generateTableCells, this, 1, [], false);
};
// }}}
// {{{ setComponents()
class_propBox_edit_table.prototype.setComponents = function() {
	if (this.usesFirstLine) {
		this.tableX = this.settings.border_left + 4;
		this.textBoxBack._x = this.settings.border_left;
	} else {
		this.tableX = this.settings.border_left + this.settings.gridsize * 2 + 4;
		this.textBoxBack._x = this.settings.border_left + this.settings.gridsize * 2;
	}

	this.textBoxBack._y = this.settings.border_top;
	this.textBoxBack._width = int(this.width - this.textBoxBack._x - this.settings.border_right - 1);	
        this.tableWidth = this.textBoxBack._width - 6 - 14;

        for (var i = 0; i < this.cells.length; i++) {
            var cellWidth = int(this.tableWidth / this.cells[i].length);
            for (var j = 0; j < this.cells[i].length; j++) {
                this.cells[i][j].textBox._x = int(this.tableX + j * cellWidth) - 1;
                this.cells[i][j].textBox._width = cellWidth;
            }
            this["button_table_row_add_" + i]._x = this.tableX + this.tableWidth + 1;
            this["button_table_row_del_" + i]._x = this.tableX + this.tableWidth + 1;
        }

	this._visible = false;

	this.button_1._x = this.settings.border_left + 2;
	this.button_2._x = this.settings.border_left + 2;
	this.button_3._x = this.settings.border_left + 2;
	this.button_4._x = this.settings.border_left + 2;

	setTimeout(this.setHeight, this, 1, [], false);
};
// }}}
// {{{ onScroller()
class_propBox_edit_table.prototype.onScroller = function() {
    this.newTextHeight = this.textBox.textHeight;
    if (this.oldTextHeight != this.newTextHeight) {
            this.setHeight();
            this.oldTextHeight = this.newTextHeight;
    }
    if ((this.textBox.scroll > this.oldTextScroll && this.getGlobalY() + this.height > Stage.height) || (this.textBox.scroll < this.oldTextScroll)) {
            var scrollNum = this.textBox.scroll - this.oldTextScroll;
            if (scrollNum == 1 || scrollNum == -1) scrollNum *= 3;
            this._parent.setOffset(this._parent.offset + scrollNum * this.lineHeight);
            this.oldTextScroll = this.textBox.scroll;
    }
    if (this.oldTextHeight > this.newTextHeight && this.getGlobalY() + this.innerHeight + this.settings.border_top - 3 * this.lineHeight < 0) {
            this._parent.setOffset(this._parent.offset + (this.getGlobalY() + this.innerHeight + this.settings.border_top - 3 * this.lineHeight));
    }
};
// }}}
// {{{ setHeight()
class_propBox_edit_table.prototype.setHeight = function() {
	var textBoxHeight;
	var rowStart = this.settings.border_top + 3;

	//this.textHeight = this.textBox.textHeight;
        for (var i = 0; i < this.cells.length; i++) {
            var rowHeight = 0;
            for (var j = 0; j < this.cells[i].length; j++) {
                var cellHeight = this.cells[i][j].textBox.textHeight;
            
                if (rowHeight < cellHeight) {
                    rowHeight = cellHeight;
                }
            }
            rowHeight += 5;
            for (var j = 0; j < this.cells[i].length; j++) {
                this.cells[i][j].textBox._y = rowStart;
                this.cells[i][j].textBox._height = rowHeight;
            }
            this["button_table_row_add_" + i]._y = rowStart;
            this["button_table_row_del_" + i]._y = rowStart + 14;

            rowStart += rowHeight;
        }
	
	this.innerHeight = rowStart > this.minHeight ? rowStart + 12 : this.minHeight;
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
	
	textBoxHeight = this.innerHeight - 3 + 10;
	if (textBoxHeight > Stage.height - 60) {
		textBoxHeight = Stage.height - 60;
	}
	this.textBoxBack._height = this.innerHeight - 5;
	
	//super.setHeight();
	this._parent.setPropPos();

	/* ERROR NOT FOUND -> its only a workaround for flash */
	this.textBoxBack._width = int(this.width - this.textBoxBack._x - this.settings.border_right - 1);	
	//this.textBoxBack._width = int(this.textBoxBack._width);
	
	this._visible = true;
};
// }}}
// {{{ setNewPos()
class_propBox_edit_table.prototype.setNewPos = function() {
	var bottomBorder = this.height - this.settings.border_bottom - 2;
	
        // @todo rewrite for table
	//super.setNewPos();
	
	if (this.getGlobalY() + bottomBorder > Stage.height - 7) {
		bottomBorder = Stage.height - 7 - this.getGlobalY();	
	}
	if (bottomBorder < this.settings.border_top + 85) {
		bottomBorder = this.settings.border_top + 85;
	}
		
	this.button_1._y = bottomBorder - 76
	this.button_2._y = bottomBorder - 59
	this.button_3._y = bottomBorder - 38;
	this.button_4._y = bottomBorder - 17;
};
// }}}
// {{{ setButtons()
class_propBox_edit_table.prototype.setButtons = function() {
	for (var i = 1; i <= 4; i++) {
		this["button_" + i].setStatus(this["button_" + i].enabledState);
	}
};
// }}}
// {{{ setData()
class_propBox_edit_table.prototype.setData = function() {
	var i, tempText;
	
	super.setData();
	
	this.setMultilangProp();
	
        this.removeTableCells();
	setTimeout(this.generateTableCells, this, 1, [], false);
};
// }}}
// {{{ setCellData()
class_propBox_edit_table.prototype.setCellData = function(tB, tempText) {
	tB.type = "input";
	
        tb.htmlText = tempText;

	//this.textBox.initFormat(this.textBox.textFormat);

        for (var i = 0; i < tB.text.length; i++) {
            tf = tB.getTextFormat(i, i + 1);
            if (tf.size == tB.textFormatSmall.size) {
                tB.setTextFormat(i, tB.textFormatSmall);
            } else {
                tB.setTextFormat(i, tB.textFormat);
            }
            tB.setNewTextFormat(tB.textFormat);

            tB.htmlText = tB.htmlText.replace([
                    ["<I></I>"    , ""],
                    ["<B></B>"    , ""]
            ]);
        }
};
// }}}
// {{{ saveData()
class_propBox_edit_table.prototype.saveData = function(forceSave) {
    if (!forceSave) {
        var tempText = "";

        for (var i = 0; i < this.cells.length; i++) {
            tempText += "<tr>";
            for (var j = 0; j < this.cells[i].length; j++) {
                tempText += "<td>";
                if (this.cells[i][j].textBox != undefined) {
                    tempText += this.cells[i][j].textBox.reducedHtmlText();
                } else {
                    tempText += "<p></p>";
                }
                tempText += "</td>";
            }
            tempText += "</tr>";
        }

	var tempXML = new XML("<root>" + tempText + "</root>");
	var tempNode = tempXML.firstChild;
	
	while (this.data.hasChildNodes()) {
		this.data.firstChild.removeNode();
	}
	for (var i = 0; i < tempNode.childNodes.length; i++) {
		this.data.appendChild(tempNode.childNodes[i].cloneNode(true));
	}
    }
    return super.saveData(forceSave);
};
// }}}
// {{{ resetData() 
class_propBox_edit_table.prototype.resetData = function() {
    super.resetData();
}
// }}}
// {{{ saveSelection()
class_propBox_edit_table.prototype.saveSelection = class_propBox_edit_text_formatted.prototype.saveSelection;
// }}}
// {{{ formatSelection()
class_propBox_edit_table.prototype.formatSelection = class_propBox_edit_text_formatted.prototype.formatSelection;
// }}}
// {{{ textLinkClick()
class_propBox_edit_table.prototype.textLinkClick = class_propBox_edit_text_formatted.prototype.textLinkClick;
// }}}
// {{{ textLinkResetDoubleClick()
class_propBox_edit_table.prototype.textLinkResetDoubleClick = class_propBox_edit_text_formatted.prototype.textLinkResetDoubleClick;
// }}}
// {{{ textLinkDoubleClick()
class_propBox_edit_table.prototype.textLinkDoubleClick = class_propBox_edit_text_formatted.prototype.textLinkDoubleClick;
// }}}
// {{{ setLink()
class_propBox_edit_table.prototype.setLink = class_propBox_edit_text_formatted.prototype.setLink;
// }}}

/*
 *	Class PropBox_edit_type
 *
 *	Extends class_propBox
 *	Handles combobox-chooser inside a page
 */
// {{{ constructor
class_propBox_edit_type = function() {};
class_propBox_edit_type.prototype = new class_propBox();

class_propBox_edit_type.prototype.propName = [];
class_propBox_edit_type.prototype.propName[0] = conf.lang.prop_name_edit_type;
// }}}
// {{{ generateComponents()
class_propBox_edit_type.prototype.generateComponents = function() {
        val = Array();
	this.attachMovie("component_comboBox", "comboBox", 2, {
		values	: val
	});
	this.comboBox.onChanged = function() {
		this._parent.onChanged();
	};
};
// }}}
// {{{ setData()
class_propBox_edit_type.prototype.setData = function() {
	super.setData();

        setTimeout(this.setDataNow, this, 10, [tempText]);
};
// }}}
// {{{ setDataNow()
class_propBox_edit_type.prototype.setDataNow = function() {
	var i;
	var j;
        var options;
        var variables;
	
        options = this.data.attributes['options'];

        if (options.substring(0, 5) == "%var_") {
            options = conf.project.tree.settings.getVariable(options.substring(5, options.length - 1));
        }

        options = options.split(",");

        this.comboBox.setValues(options);

	for (var i = 0; i < this.comboBox.values.length; i++) {
		if (this.comboBox.values[i] == this.data.attributes['value']) {
			this.comboBox.selected = i;	
		}
	}
	if (this.comboBox.selected == null) {
		this.comboBox.selected = 0;
		this.save();	
	}
	this.comboBox.select();
};
// }}}
// {{{ saveData()
class_propBox_edit_type.prototype.saveData = function(forceSave) {
	if (this.isChanged == true || forceSave == true) {
            this.data.attributes.value = this.comboBox.values[this.comboBox.selected];

            this._parent.propObj.save(this.data.nid);
            this.isChanged = false;
	}
	return true;
};
// }}}
// {{{ setComponents()
class_propBox_edit_type.prototype.setComponents = function() {
	this.comboBox._x = this.settings.border_left;
	this.comboBox._y = this.settings.border_top;
        this.comboBox.width = this.width - this.settings.border_left - this.settings.border_right - 5;
			
	this.innerHeight = this.settings.minInnerHeight;
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
};
// }}}

/*
 *	Class PropBox_edit_colorscheme
 *
 *	Extends class_propBox
 *	Handles Colorscheme inside a page
 */
// {{{ constructor
class_propBox_edit_colorscheme = function() {};
class_propBox_edit_colorscheme.prototype = new class_propBox();

class_propBox_edit_colorscheme.prototype.propName = [];
class_propBox_edit_colorscheme.prototype.propName[0] = conf.lang.prop_name_page_colorscheme;
// }}}
// {{{ generateComponents()
class_propBox_edit_colorscheme.prototype.generateComponents = function() {
        val = Array(conf.lang.prop_name_edit_colorscheme_none);
        val = val.concat(conf.project.tree.colors.getColorschemes());
	this.attachMovie("component_comboBox", "comboBox", 2, {
		values	: val
	});
	this.comboBox.onChanged = function() {
		this._parent.onChanged();
	};
	
	this.attachMovie("prop_page_colorscheme_preview", "preview", 3);
};
// }}}
// {{{ setData()
class_propBox_edit_colorscheme.prototype.setData = function() {
	var i;
	
	super.setData();
	
	for (var i = 0; i < this.comboBox.values.length; i++) {
		if (this.comboBox.values[i] == this.data.attributes['colorscheme']) {
			this.comboBox.selected = i;	
		}
	}
	if (this.comboBox.selected == null) {
		this.comboBox.selected = 0;
		this.save();	
	}
	this.comboBox.select();
	
	this.preview.colors = conf.project.tree.colors.getColors(this.comboBox.values[this.comboBox.selected]);
	this.preview.showColors();
};
// }}}
// {{{ onChanged()
class_propBox_edit_colorscheme.prototype.onChanged = function() {
	super.onChanged();

	this.preview.colors = conf.project.tree.colors.getColors(this.comboBox.values[this.comboBox.selected]);
	this.preview.showColors();
};
// }}}
// {{{ saveData()
class_propBox_edit_colorscheme.prototype.saveData = function(forceSave) {
	if (this.isChanged == true || forceSave == true) {
            if (this.comboBox.values[this.comboBox.selected] == conf.lang.prop_name_edit_colorscheme_none) {
                this.data.attributes.colorscheme = "";
            } else {
                this.data.attributes.colorscheme = this.comboBox.values[this.comboBox.selected];
            }

            this._parent.propObj.save(this.data.nid);
            this.isChanged = false;
	}
	return true;
};
// }}}
// {{{ setComponents()
class_propBox_edit_colorscheme.prototype.setComponents = function() {
        if (this.comboBox.values[this.comboBox.selected] == conf.lang.prop_name_edit_colorscheme_none) {
            this.preview._visible = false;
            previewWidth = 0;
        } else {
            this.preview._visible = true;

            this.preview.setColorPos((this.width - this.settings.border_left - this.settings.border_right) / 2);
            
            this.preview._x = this.width - this.settings.border_right - this.preview.width;
            this.preview._y = this.settings.border_top;
            previewWidth = this.preview.width;
        }
	
	this.comboBox._x = this.settings.border_left;
	this.comboBox._y = this.settings.border_top;
        this.comboBox.width = this.width - this.settings.border_left - this.settings.border_right - previewWidth - 5;
			
	this.innerHeight = this.settings.minInnerHeight > this.preview.height ? this.settings.minInnerHeight : this.preview.height;
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
};
// }}}

/*
 *	Class PropBox_edit_icon
 *
 *	Extends class_propBox
 *	Handles icon inside a page
 */
// {{{ constructor
class_propBox_edit_icon = function() {};
class_propBox_edit_icon.prototype = new class_propBox();

class_propBox_edit_icon.prototype.propName = [];
//class_propBox_edit_icon.prototype.propName[0] = conf.lang.prop_name_page_icon;
class_propBox_edit_icon.prototype.propName[0] = "";
// }}}
// {{{ generateComponents()
class_propBox_edit_icon.prototype.generateComponents = function() {
        val = Array(conf.lang.prop_name_edit_icon_default);
        val = val.concat(Array(
            "color",
            "edit_a",
            "edit_audio",
            "edit_headline",
            "edit_img",
            "edit_imgtext",
            "edit_source",
            "edit_text",
            "edit_unknown",
            "edit_video",
            "folder",
            "page_portrait",
            "sec_aside_left",
            "sec_aside_right",
            "sec_section",
            "sec_section_2col",
            "sec_section_2col_1",
            "sec_section_2col_2",
            "sec_section_2col_xl",
            "sec_section_2col_xl_1",
            "sec_section_2col_xl_2",
            "sec_section_3col",
            "sec_section_3col_1",
            "sec_section_3col_2",
            "sec_section_3col_3"
        ));
	this.attachMovie("component_comboBox", "comboBox", 2, {
		values	: val
	});
	this.comboBox.onChanged = function() {
		this._parent.onChanged();
	};
	
	this.attachMovie("tree_icon", "preview", 3);
};
// }}}
// {{{ setData()
class_propBox_edit_icon.prototype.setData = function() {
	var i;
	
	super.setData();
	
	for (var i = 0; i < this.comboBox.values.length; i++) {
		if (this.comboBox.values[i] == this.data.attributes['icon']) {
			this.comboBox.selected = i;	
		}
	}
	if (this.comboBox.selected == null) {
		this.comboBox.selected = 0;
		this.save();	
	}
	this.comboBox.select();
};
// }}}
// {{{ onChanged()
class_propBox_edit_icon.prototype.onChanged = function() {
	super.onChanged();

	this.preview.loadIcon(this.comboBox.values[this.comboBox.selected]);
};
// }}}
// {{{ saveData()
class_propBox_edit_icon.prototype.saveData = function(forceSave) {
	if (this.isChanged == true || forceSave == true) {
            if (this.comboBox.values[this.comboBox.selected] == conf.lang.prop_name_edit_icon_default) {
                this.data.attributes.icon = "";
                this.data.dataNode.attributes.icon = "";
            } else {
                this.data.attributes.icon = this.comboBox.values[this.comboBox.selected];
                this.data.dataNode.attributes.icon = this.comboBox.values[this.comboBox.selected];
            }

            this._parent.propObj.save(this.data.nid);
            this.isChanged = false;
	}
	return true;
};
// }}}
// {{{ setComponents()
class_propBox_edit_icon.prototype.setComponents = function() {
        var datainfo = this.preview.getIconType(this.data.dataNode);
        this.preview.loadIcon(datainfo.icon);
            
        this.preview._x = this.settings.border_top + 7;
        this.preview._y = this.settings.border_top + 7;
        //this.preview._xscale = 200;
        //this.preview._yscale = 200;
	
        this.comboBox._visible = conf.user.mayEditTemplates();

	this.comboBox._x = this.settings.border + this.settings.gridsize * 2;
	this.comboBox._y = this.settings.border_top;
        this.comboBox.width = this.width - this.comboBox._x - this.settings.border_right - 5;
			
	this.innerHeight = this.settings.minInnerHeight > this.preview.height ? this.settings.minInnerHeight : this.preview.height;
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
};
// }}}

/*
 *	Class PropBox_page_date
 *
 *	Extends class_propBox
 *	Shows last change date
 */
// {{{ constructor
class_propBox_pg_date = function() {};
class_propBox_pg_date.prototype = new class_propBox();

class_propBox_pg_date.prototype.propName = [];
class_propBox_pg_date.prototype.propName[0] = conf.lang.prop_name_page_date;
class_propBox_pg_date.prototype.showSaver = false;
// }}}
// {{{ generateComponents()
class_propBox_pg_date.prototype.generateComponents = function() {
	this.createTextField("dateBox", 2, 0, 0, 100, 100);
	this.dateBox.initFormat(conf.interface.textformat);
	this.dateBox.selectable = false;
};
// }}}
// {{{ setData()
class_propBox_pg_date.prototype.setData = function() {
	var actUser;

	if (this.data.attributes.lastchange_UTC != undefined) {
		actUser = conf.user.userlist[this.data.attributes.lastchange_uid][1];
		if (actUser == null) {
			actUser = conf.lang.user_unknown;
		}
		this.dateBox.text = getLocalDate(this.data.attributes.lastchange_UTC) + "\n" + conf.lang.changed_by + " " + actUser;
	} else {
		this.dateBox.text = "-";
	}
};
// }}}
// {{{ saveData()
class_propBox_pg_date.prototype.saveData = function(forceSave) {

};
// }}}
// {{{ setComponents()
class_propBox_pg_date.prototype.setComponents = function() {
	this.dateBox._x = this.settings.border_left;
	this.dateBox._y = this.settings.border_top + 2;
	this.dateBox._width = this.width - this.settings.border_left - this.settings.border_right;
	this.dateBox._height = this.settings.minInnerHeight + 15;

	this.innerHeight = this.dateBox._height;
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
};
// }}}

/*
 *	Class PropBox_page_colorscheme
 *
 *	Extends class_propBox
 *	Handles Colorscheme of a Page
 */
// {{{ constructor
class_propBox_pg_colorscheme = function() {};
class_propBox_pg_colorscheme.prototype = new class_propBox_edit_colorscheme();
// }}}
// {{{ generateComponents()
class_propBox_pg_colorscheme.prototype.generateComponents = function() {
        val = conf.project.tree.colors.getColorschemes();
	this.attachMovie("component_comboBox", "comboBox", 2, {
		values	: val
	});
	this.comboBox.onChanged = function() {
		this._parent.onChanged();
	};
	
	this.attachMovie("prop_page_colorscheme_preview", "preview", 3);
};
// }}}
// {{{ saveData()
class_propBox_pg_colorscheme.prototype.saveData = function(forceSave) {
	if (this.isChanged == true || forceSave == true) {
		this.data.attributes.colorscheme = this.comboBox.values[this.comboBox.selected];

		this._parent.propObj.save(this.data.nid, this.data, "colorscheme");
		this.isChanged = false;
	}
	return true;
};
// }}}

/*
 *	Class PropBox_page_navigation
 *
 *	Extends class_propBox
 *	Handles Navigations of a Page
 */
// {{{ constructor
class_propBox_pg_navigation = function() {};
class_propBox_pg_navigation.prototype = new class_propBox();

class_propBox_pg_navigation.prototype.propName = [];
class_propBox_pg_navigation.prototype.propName[0] = conf.lang.prop_name_page_navigation;
// }}}
// {{{ generateComponents()
class_propBox_pg_navigation.prototype.generateComponents = function() {
	var tempNode, i;
	var navigations = [];
	
	this.attachMovie("rectangle", "textBoxBack", 2);
	this.textBoxBack.back.setRGB(conf.interface.color_component_face);
	this.textBoxBack.outline.setRGB(conf.interface.color_component_line);
	
	this.navigations = conf.project.tree.settings.navigations;
	for (var i = 1; i <= this.navigations.length; i++) {
		this.attachMovie("component_checkBox", "checkBox" + i, i + 2);
		this["checkBox" + i].onChanged = function() {
			this._parent.onChanged();
		};
	}	
};
// }}}
// {{{ setData()
class_propBox_pg_navigation.prototype.setData = function() {
	var i, tempNode;
	
	super.setData();
        this.setTitle(conf.lang.prop_name_page_navigation);
	
	for (var i = 1; i <= this.navigations.length; i++) {
		this["checkBox" + i].caption = this.navigations[i - 1].name;
		if (this.data.attributes["nav_" + this.navigations[i - 1].shortname] == "true") {
			this["checkBox" + i].value = true;
		} else {
			this["checkBox" + i].value = false;
		}
	}	
};
// }}}
// {{{ saveData()
class_propBox_pg_navigation.prototype.saveData = function(forceSave) {
	if (this.isChanged == true || forceSave == true) {
		var tempNode;
		var tempXML = new XML();

		for (var i = 1; i <= this.navigations.length; i++) {
			if (this["checkBox" + i].value) {
				this.data.attributes["nav_" + this.navigations[i - 1].shortname] = "true";
			} else {
				this.data.attributes["nav_" + this.navigations[i - 1].shortname] = "false";
			}
		}	

		this._parent.propObj.save(this.data.nid, this.data, "navigation");
		this.isChanged = false;
	}
	return true;
};
// }}}
// {{{ setComponents()
class_propBox_pg_navigation.prototype.setComponents = function() {
	var colNum, actualCol, actualRow, rows; 
	
	this.textBoxBack._x = this.settings.border_left;
	this.textBoxBack._y = this.settings.border_top;
	this.textBoxBack._width = this.width - this.settings.border_left - this.settings.border_right;	
	
	for (var i = 1; i <= this.navigations.length; i++) {
		this["checkBox" + i]._x = this.settings.border_left + 5;
		this["checkBox" + i]._y = this.settings.border_top + (i - 1) * conf.interface.menu_line_height;
		this["checkBox" + i].width = this.width - (this.settings.border_left + this.settings.border_right + 10);
		this["checkBox" + i].setWidth();
	}	
	
	this.setHeight();
};
// }}}
// {{{ setHeight()
class_propBox_pg_navigation.prototype.setHeight = function() {
	var temp;

	this.innerHeight = this.navigations.length * conf.interface.menu_line_height + 8;
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
	
	this.textBoxBack._height = this.innerHeight - 5;
	
	super.setHeight();
	this._parent.setPropPos();
};
// }}}

/*
 *	Class PropBox_page_file
 *
 *	Extends class_propBox
 *	Handles file-options of a Page
 */
// {{{ constructor
class_propBox_pg_file = function() {};
class_propBox_pg_file.prototype = new class_propBox();

class_propBox_pg_file.prototype.propName = [];
class_propBox_pg_file.prototype.propName[0] = conf.lang.prop_name_page_file;
// }}}
// {{{ generateComponents()
class_propBox_pg_file.prototype.generateComponents = function() {
	var i;

	this.attachMovie("component_comboBox", "comboBox", 3);
	this.comboBox.values = [];
	for (var i = 0; i < conf.output_file_types.length; i++) {
		this.comboBox.values.push(conf.output_file_types[i].name);
	}
	this.comboBox.onChanged = function() {
		this._parent.onChanged();
	};

	this.attachMovie("component_checkBox", "checkBox", 4, {
		caption	: conf.lang.prop_page_file_multilang
	});
	this.checkBox.onChanged = function() {
		this._parent.onChanged();
	};
};
// }}}
// {{{ setData()
class_propBox_pg_file.prototype.setData = function() {
	if (conf.user.mayEditSourceCode()) {
		var i;

		super.setData();
                this.setTitle(conf.lang.prop_name_page_file);

		for (var i = 0; i < this.comboBox.values.length; i++) {
			if (this.comboBox.values[i] == this.data.attributes.file_type) {
				this.comboBox.selected = i;	
			}
		}

		if (this.comboBox.selected == null) {
			this.comboBox.selected = 0;
			this.save();	
		}
		this.comboBox.select();

		this.checkBox.value = this.data.attributes.multilang.toBoolean();	
	}
};
// }}}
// {{{ saveData()
class_propBox_pg_file.prototype.saveData = function(forceSave) {
	if (this.isChanged == true || forceSave == true) {
		this.data.attributes.file_type = this.comboBox.values[this.comboBox.selected];
		this.data.attributes.multilang = this.checkBox.value.toString();

		this._parent.propObj.save(this.data.nid, this.data, "file");
		this.isChanged = false;
	}
	return true;
};
// }}}
// {{{ setComponents()
class_propBox_pg_file.prototype.setComponents = function() {
	if (conf.user.mayEditSourceCode()) {
		this.preview.setColorPos((this.width - this.settings.border_left - this.settings.border_right) / 2);
		
		this.comboBox.width = this.settings.explanationWidth;
		this.comboBox._x = this.settings.border_left;
		this.comboBox._y = this.settings.border_top;
		
		this.checkBox._x = this.settings.border_left + this.comboBox.width + 2*this.settings.border;
		this.checkBox._y = this.settings.border_top;
		this.checkBox.width = this.width - this.checkBox._x - this.settings.border_right - this.settings.OkCancelWidth;
				
		this.innerHeight = this.settings.minInnerHeight;
		this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
	} else {
		this.setComponentsNoRight();
	}
};
// }}}

/*
 *	Class PropBox_page_title
 *
 *	Extends class_propBox
 *	Handles Title of a Page
 */
// {{{ constructor
class_propBox_pg_title = function() {};
class_propBox_pg_title.prototype = new class_propBox_edit_text_singleline();

class_propBox_pg_title.prototype.propName = [];
class_propBox_pg_title.prototype.propName[0] = conf.lang.prop_name_page_title;
// }}}
	
/*
 *	Class PropBox_edit_date
 *
 *	Extends class_propBox_edit_text_singleline
 *	Handles Title of a Page
 */
// {{{ constructor()
class_propBox_edit_date = function() {};
class_propBox_edit_date.prototype = new class_propBox_edit_text_singleline();

class_propBox_edit_date.prototype.propName = [];
class_propBox_edit_date.prototype.propName[0] = conf.lang.prop_name_edit_date;
// }}}
// {{{ setData()
class_propBox_edit_date.prototype.setData = function() {
	super.setData();
	if (this.data.attributes.value != "" && this.data.attributes.value != undefined) {
		var formattedDate = conf.lang.date_format_short;
		var newDate = new Date(this.data.attributes.value.substr(0,4), this.data.attributes.value.substr(5,2) - 1, this.data.attributes.value.substr(8,2), 0, 0, 0);
		
		formattedDate = formattedDate.replace([
			["%d%"	, setLeadingZero(newDate.getDate(), 2)],
			["%M%"	, setLeadingZero((newDate.getMonth() + 1), 2)],
			["%y%"	, newDate.getFullYear()]
		]);
		
		this.inputBox.value = formattedDate;
	} else {
		this.inputBox.value = "";
	}
};
// }}}
// {{{ saveData()
class_propBox_edit_date.prototype.saveData = function(forceSave) {
	if (this.isChanged == true || forceSave == true) {
		var newDate = new Date();
		var formattedDate = conf.lang.date_format_short;
		var saveDate = "%y%/%M%/%d%"
		
		newDate = newDate.parseDate(this.inputBox.value);
		
		formattedDate = formattedDate.replace([
			["%d%"	, setLeadingZero(newDate.getDate(), 2)],
			["%M%"	, setLeadingZero((newDate.getMonth() + 1), 2)],
			["%y%"	, newDate.getFullYear()]
		]);
		
		saveDate = saveDate.replace([
			["%d%"	, setLeadingZero(newDate.getDate(), 2)],
			["%M%"	, setLeadingZero((newDate.getMonth() + 1), 2)],
			["%y%"	, newDate.getFullYear()]
		]);
		
		this.inputBox.value = formattedDate;
		this.data.attributes.value = saveDate;
		
		this._parent.propObj.save(this.data.nid);
		this.isChanged = false;
	}
	return true;
};
// }}}

/*
 *	Class PropBox_edit_time
 *
 *	Extends class_propBox_edit_text_singleline
 *	Handles Title of a Page
 */
// {{{ constructor()
class_propBox_edit_time = function() {};
class_propBox_edit_time.prototype = new class_propBox_edit_date();

class_propBox_edit_time.prototype.propName = [];
class_propBox_edit_time.prototype.propName[0] = conf.lang.prop_name_edit_time;
// }}}
// {{{ generateComponents()
class_propBox_edit_time.prototype.generateComponents = function() {
    super.generateComponents();
    this.inputBox.restrict = "0123456789:";
};
// }}}
// {{{ setData()
class_propBox_edit_time.prototype.setData = function() {
	super.setData();
	if (this.data.attributes.value != "" && this.data.attributes.value != undefined) {
		this.inputBox.value = this.data.attributes.value;
	} else {
		this.inputBox.value = "";
	}
};
// }}}
// {{{ saveData()
class_propBox_edit_time.prototype.saveData = function(forceSave) {
	if (this.isChanged == true || forceSave == true) {
		var formattedTime = conf.lang.date_time_format_short;
		
		newTime = this.inputBox.value.split(":");
		
		formattedTime = formattedTime.replace([
			["%h%"	, setLeadingZero(newTime[0].substr(0, 2), 2)],
			["%m%"	, setLeadingZero(newTime[1].substr(0, 2), 2)]
		]);
		
		this.inputBox.value = formattedTime;
		this.data.attributes.value = formattedTime;
		
		this._parent.propObj.save(this.data.nid);
		this.isChanged = false;
	}
	return true;
};
// }}}

/*
 *	Class PropBox_page_linkdesc
 *
 *	Extends class_propBox
 *	Handles Link Description of a Page
 */
// {{{ constructor
class_propBox_pg_linkdesc = function() {};
class_propBox_pg_linkdesc.prototype = new class_propBox_edit_text_singleline();

class_propBox_pg_linkdesc.prototype.propName = [];
class_propBox_pg_linkdesc.prototype.propName[0] = conf.lang.prop_name_page_linkdesc;
// }}}
// {{{ setData()
class_propBox_pg_linkdesc.prototype.setData = function() {
	super.setData();
	
	this.setMultilangProp();
	
	this.inputBox.value = this.data.attributes.value;
};
// }}}
// {{{ saveData()
class_propBox_pg_linkdesc.prototype.saveData = function(forceSave) {
	if (this.isChanged == true || forceSave == true) {
		this.data.attributes.value = this.inputBox.value;

		this._parent.propObj.save(this.data.nid);
		this.isChanged = false;
	}
	return true;
};
// }}}

/*
 *	Class PropBox_page_desc
 *
 *	Extends class_propBox_edit_text_multiline
 *	Handles Description of a Page
 */
// {{{ constructor
class_propBox_pg_desc = function() {};
class_propBox_pg_desc.prototype = new class_propBox_edit_text_multiline();

class_propBox_pg_desc.prototype.propName = [];
class_propBox_pg_desc.prototype.propName[0] = conf.lang.prop_name_page_desc;
class_propBox_pg_desc.prototype.usesFirstLine = true;
class_propBox_pg_desc.prototype.minHeight = 26;
// }}}
// {{{ setTextBoxFormat()
class_propBox_pg_desc.prototype.setTextBoxFormat = function() {
	this.textBox.html = true
	this.textBox.textFormat = conf.interface.textformat_input;
};
// }}}
// {{{ setData()
class_propBox_pg_desc.prototype.setData = function() {
	super.setData();
	
	this.setMultilangProp();
	
	this.textBox.text = this.data.firstChild.nodeValue;
};
// }}}
// {{{ saveData()
class_propBox_pg_desc.prototype.saveData = function(forceSave) {
	if (this.isChanged == true || forceSave == true) {
		var tempXML = new XML();
		var tempNode = tempXML.createTextNode(this.textBox.text.replace([
			["\r"	, " "],
			["\n"	, " "]
		]));
	
		this.data.firstChild.removeNode();
		this.data.appendChild(tempNode);
		
		this._parent.propObj.save(this.data.nid);
		this.isChanged = false;
	}
	return true;
};
// }}}

/*
 *	Class PropBox_edit_plain_source
 *
 *	Extends class_propBox
 *	Handles Source-Elements
 */
// {{{ constructor
class_propBox_edit_plain_source = function() {};
class_propBox_edit_plain_source.prototype = new class_propBox_edit_text_multiline();

class_propBox_edit_plain_source.prototype.propName = [];
class_propBox_edit_plain_source.prototype.propName[0] = conf.lang.prop_name_edit_plain_source;
// }}}
// {{{ generateComponents()
class_propBox_edit_plain_source.prototype.generateComponents = function() {
	if (conf.user.mayEditSourceCode()) {
		super.generateComponents();	
	} else {
		this.generateComponentsNoRight();
	}
};
// }}}
// {{{ setComponents()
class_propBox_edit_plain_source.prototype.setComponents = function() {
	if (conf.user.mayEditSourceCode()) {
		super.setComponents();	
	} else {
		this.setComponentsNoRight();
	}
};
// }}}
// {{{ setData()
class_propBox_edit_plain_source.prototype.setData = function() {
	var newText;
	if (conf.user.mayEditSourceCode()) {
		super.setData();

		newText = this.data.firstChild.nodeValue;

		if (this.data.firstChild.nodeType == 3) {	
			this.textBox.text = newText;
		} else {
			this.textBox.text = "";
		}
		this._parent.setHeight();
	}
};
// }}}
// {{{ saveData()
class_propBox_edit_plain_source.prototype.saveData = function(forceSave) {
	var newText;
	var tempXML = new XML();
	
	newText = this.textBox.htmlText.removeUnwantedTags(["p"])
	newText = newText.replace([
		["<p>"		, ""],
		["</p>"		, "\n"],
		["&lt;"		, "<"],
		["&gt;"		, ">"],
		["&quot;"	, "\""],
		["&apos;"	, "'"],
		["&amp;"	, "&"]
	]);
		
	this.data.firstChild.removeNode();
	this.data.appendChild(tempXML.createTextNode(newText));

	return super.saveData(forceSave);
};
// }}}

/*
 *	Class PropBox_edit_element_source
 *
 *	Extends class_propBox_edit_plain_source
 *	Handles Source-Elements
 */
// {{{ constructor
class_propBox_edit_element_source = function() {};
class_propBox_edit_element_source.prototype = new class_propBox_edit_plain_source();

class_propBox_edit_element_source.prototype.propName = [];
class_propBox_edit_element_source.prototype.propName[0] = conf.lang.prop_name_edit_plain_source;
// }}}
// {{{ generateComponents()
class_propBox_edit_element_source.prototype.generateComponents = function() {
	if (conf.user.mayEditSourceCode()) {
		super.generateComponents();	
	} else {
		this.generateComponentsNoRight();
	}
};
// }}}
// {{{ setComponents()
class_propBox_edit_element_source.prototype.setComponents = function() {
	if (conf.user.mayEditSourceCode()) {
		super.setComponents();	
	} else {
		this.setComponentsNoRight();
	}
};
// }}}
// {{{ setData()
class_propBox_edit_element_source.prototype.setData = function() {
	var newText;
        var tempNode;
        var tempNodeClone;

	if (conf.user.mayEditSourceCode()) {
		super.setData();

                tempNode = this.data.firstChild;
                while (tempNode != null) {
                    tempNodeClone = tempNode.cloneNode(true);
                    tempNodeClone.removeIdAttribute();
                    newText += tempNodeClone.toString();
                    tempNode = tempNode.nextSibling;
                }
                this.textBox.text = newText;
		this._parent.setHeight();
	}
};
// }}}
// {{{ saveData()
class_propBox_edit_element_source.prototype.saveData = function(forceSave) {
	var newText;
        newText = this.textBox.htmlText.removeUnwantedTags(["p"])
        newText = newText.replace([
                ["<p>"		, ""],
                ["</p>"		, "\n"],
                ["&lt;"		, "<"],
                ["&gt;"		, ">"],
                ["&quot;"	, "\""],
                ["&apos;"	, "'"],
                ["&amp;"	, "&"]
        ]);

	var tempXML = new XML("<temp>" + newText + "</temp>");
	var tempNode = tempXML.firstChild;
	var i;
	
	if (tempXML.status == 0) {
		while (this.data.hasChildNodes()) {
			this.data.firstChild.removeNode();
		}
                for (var i = 0; i < tempNode.childNodes.length; i++) {
                    this.data.appendChild(tempNode.childNodes[i].cloneNode(true));
                }
                if (this.isChanged == true || forceSave == true) {
                        this._parent.propObj.save(this.data.nid);
                        this.isChanged = false;
                }
                return true;
	} else {
		alert(conf.lang.error_prop_xslt_template + "\n\n" + conf.lang["error_parsexml" + tempXML.status]);

		return false;
	}
};
// }}}

/*
 *	Class PropBox_proj_language
 *
 *	Extends class_propBox
 *	Handles Settings-Languages
 */
// {{{ constructor
class_propBox_proj_language = function() {};
class_propBox_proj_language.prototype = new class_propBox_edit_text_singleline();

class_propBox_proj_language.prototype.propName = [];
class_propBox_proj_language.prototype.propName[0] = conf.lang.prop_name_proj_language;
// }}}
// {{{ setData()
class_propBox_proj_language.prototype.setData = function() {
	super.setData();
	
	this.inputBox.value = this.data.attributes.shortname;
};
// }}}
// {{{ generateComponents()
class_propBox_proj_language.prototype.generateComponents = function() {
	super.generateComponents();
	this.inputBox.restrict = "a-z\\-";	
	this.inputBox.maxChars = 5;
};
// }}}
// {{{ saveData()
class_propBox_proj_language.prototype.saveData = function(forceSave) {
	this.data.attributes.shortname = this.inputBox.value.toLowerCase();

	return super.saveData(forceSave);
};
// }}}

/*
 *	Class PropBox_proj_navigation
 *
 *	Extends class_propBox
 *	Handles Settings-Navigations
 */
// {{{ constructor()
class_propBox_proj_navigation = function() {};
class_propBox_proj_navigation.prototype = new class_propBox_proj_language();

class_propBox_proj_navigation.prototype.propName = [];
class_propBox_proj_navigation.prototype.propName[0] = conf.lang.prop_name_proj_navigation;
// }}}
// {{{ generateComponents()
class_propBox_proj_navigation.prototype.generateComponents = function() {
	super.generateComponents();
	this.inputBox.restrict = "a-zA-Z0-9_";	
	this.inputBox.maxChars = null;
};
// }}}
// {{{ saveData()
class_propBox_proj_navigation.prototype.saveData = function(forceSave) {
	this.data.attributes.shortname = this.inputBox.value;

	return super.saveData(forceSave);
};
// }}}

/*
 *	Class PropBox_proj_variable
 *
 *	Extends class_propBox
 *	Handles Settings-Variables
 */
// {{{ constructor()
class_propBox_proj_variable = function() {};
class_propBox_proj_variable.prototype = new class_propBox_edit_text_singleline();

class_propBox_proj_variable.prototype.propName = [];
class_propBox_proj_variable.prototype.propName[0] = conf.lang.prop_name_proj_variable;
// }}}
// {{{ generateComponents()
class_propBox_proj_variable.prototype.generateComponents = function() {
	super.generateComponents();
	this.inputBox.restrict = "";	
	this.inputBox.maxChars = null;
};
// }}}
// {{{ setData()
class_propBox_proj_variable.prototype.setData = function() {
	super.setData();
	
	this.inputBox.value = this.data.attributes.value;
};
// }}}
// {{{ saveData()
class_propBox_proj_variable.prototype.saveData = function(forceSave) {
	this.data.attributes.value = this.inputBox.value;

	return super.saveData(forceSave);
};
// }}}

/*
 *	Class PropBox_edit_a
 *
 *	Extends class_propBox
 *	Handles an Link
 */
// {{{ constructor()
class_propBox_edit_a = function() {};
class_propBox_edit_a.prototype = new class_propBox();

class_propBox_edit_a.prototype.propName = [];
class_propBox_edit_a.prototype.propName[0] = conf.lang.prop_name_edit_a;
// }}}
// {{{ generateComponents()
class_propBox_edit_a.prototype.generateComponents = function() {
	super.generateComponents();
	
	this.attachMovie("component_inputField", "inputBox", 2);
	this.inputBox.onChanged = function() {
		this._parent.onChanged();
	};
	this.inputBox.onKillFocus = function() {
		//this._parent.save();	
	};
	this.inputBox.onEnter = function() {
		this._parent.save();	
	};
	this.inputBox.onCtrlS = function() {
		this._parent.save();	
	};
	
	this.attachMovie("component_inputField", "hrefBox", 3);
	this.hrefBox.onChanged = function() {
		this._parent.onChanged();
	};
	this.hrefBox.onKillFocus = function() {
		//this._parent.save();	
	};
	this.hrefBox.onEnter = function() {
		this._parent.save();	
	};
	this.hrefBox.onCtrlS = function() {
		this.hrefBox.save();	
	};
	this.hrefBox.onDrop = function(droppedObj) {
		this._parent.hrefBox.value = conf.project.tree.pages.getUriById(droppedObj.nid);
		this._parent.onChanged();
		this._parent.save();
	};
	this.hrefBox.isValidDrop = function(draggedObj) {
		return conf.project.tree.pages.isTreeNode(draggedObj);
	};
	
	this.attachMovie("icon_format_link", "hrefIcon", 5);
	
	this.attachMovie("component_button", "buttonHref", 7, {
		enabledState	: true,
		caption			: conf.lang.prop_tt_img_choose,
		align			: "TR"
	});
	
	this.buttonHref.onClick = function() {
		var href_id = "";
		if (this._parent.hrefBox.value.substring(0, conf.url_page_scheme_intern.length + 2) == conf.url_page_scheme_intern + ":/") {
			href_id = conf.project.tree.pages.getIdByUri(this._parent.hrefBox.value);
			_root.mainInterface.interface.layouts.dlgChoose_page.setActive(this._parent._parent.propObj.saveFileRef, this._parent._parent.propObj, [href_id, this._parent.data.nid]);
		} else if (this._parent.hrefBox.value.substring(0, conf.url_lib_scheme_intern.length + 2) == conf.url_lib_scheme_intern + ":/") {
			href_id = this._parent.hrefBox.value.substr(conf.url_lib_scheme_intern.length + 1);
			_root.mainInterface.interface.layouts.dlgChoose_file_link.setActive(this._parent._parent.propObj.saveFileRef, this._parent._parent.propObj, [href_id, this._parent.data.nid]);
		} else {
			_root.mainInterface.interface.layouts.dlgChoose_page.setActive(this._parent._parent.propObj.saveFileRef, this._parent._parent.propObj, [href_id, this._parent.data.nid]);
		}
	}
	
	this.attachMovie("component_button_symbol", "buttonTarget", 8, {
		width			: 24,
		height			: 18,
		tooltip			: conf.lang.buttontip_link_target,
		onClick			: this.toggleRefTarget,
		onClickObj		: this,
		onHold			: this.showRefTargetMenu,
		onHoldObj		: this,
		enabledState	: true
	});
};
// }}}
// {{{ toggleRefTarget()
class_propBox_edit_a.prototype.toggleRefTarget = function() {
	if (this.data.attributes.target == "") {
		this.setRefTarget(null, null, "_blank");
	} else if (this.data.attributes.target == "_blank") {
		this.setRefTarget(null, null, "");
	}
};
// }}}
// {{{ showRefTargetMenu()
class_propBox_edit_a.prototype.showRefTargetMenu = function() {
	var menu = new menuClass.menuObj(true);
	
	//preview behaviour
	menu.addHead(conf.lang.buttontip_link_target);
	menu.addSeparator();	
	menu.addEntry(conf.lang.button_link_target_self, this.setRefTarget, this, [""], (this.data.attributes.target == "" ? "checked" : null), true);
	menu.addEntry(conf.lang.button_link_target_blank, this.setRefTarget, this, ["_blank"], (this.data.attributes.target == "_blank" ? "checked" : null), true);

	menu.valign = "bottom";
	menu.halign = "right";
	menu.show(null, null);
};
// }}}
// {{{ setRefTarget()
class_propBox_edit_a.prototype.setRefTarget = function(id, name, newTarget) {
	this.data.attributes.target = newTarget;
	this.onChanged();
	this.save();
	
	if (this.data.attributes.target == "") {
		this.buttonTarget.symbol = "icon_link_target_self";
	} else if (this.data.attributes.target == "_blank") {
		this.buttonTarget.symbol = "icon_link_target_blank";
	}
	this.buttonTarget.init();
};
// }}}
// {{{ setComponents()
class_propBox_edit_a.prototype.setComponents = function() {
	super.setComponents();
	
	this.inputBox._x = this.settings.border_left;
	this.inputBox._y = this.settings.border_top;
	this.inputBox.width = this.width - this.settings.border_left - this.settings.border_right;
	
	this.buttonHref._x = this.width - this.settings.border_right;
	this.buttonHref._y = this.settings.border_top + int(conf.interface.component_height) + this.settings.border + 2;
	this.buttonHref.width = 26;
	
	this.hrefBox._x = this.settings.border_left + 30;
	this.hrefBox._y = this.settings.border_top + int(conf.interface.component_height) + this.settings.border + 2;
	this.hrefBox.width = this.width - this.settings.border_left - this.settings.border_right - ((this.data.attributes.target != undefined ? 2 : 1) * 30) - this.buttonHref.width - this.settings.border;
	
	this.hrefIcon._x = this.settings.border_left + this.settings.border;
	this.hrefIcon._y = this.settings.border_top + int(conf.interface.component_height * 1.5) + this.settings.border + 2;
	
	this.buttonTarget._x = this.width - this.settings.border_right - 45 - 2 * this.settings.border;
	this.buttonTarget._y = this.settings.border_top + int(conf.interface.component_height * 1) + this.settings.border + 4;
	
	this.innerHeight = this.settings.border_top + int(conf.interface.component_height) * 2 + this.settings.border;
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
};
// }}}
// {{{ setData()
class_propBox_edit_a.prototype.setData = function() {
	super.setData();
	
	this.setMultilangProp();
	
	this.inputBox.value = this.data.firstChild.nodeValue;
	this.inputBox.explain = conf.lang.prop_tt_a_name;
	
	if (this.data.attributes.href != undefined) {
		this.hrefBox.value = this.data.attributes.href;
	} else if (this.data.attributes.href_id != undefined) {
		this.hrefBox.value = conf.project.tree.pages.getUriById(this.data.attributes.href_id);
	}
	this.hrefBox.explain = conf.lang.prop_tt_img_href;

	if (this.data.attributes.target != undefined) {
		this.buttonTarget._visible = true;
		if (this.data.attributes.target == "") {
			this.buttonTarget.symbol = "icon_link_target_self";
		} else if (this.data.attributes.target == "_blank") {
			this.buttonTarget.symbol = "icon_link_target_blank";
		}
	} else {
		this.buttonTarget._visible = false;
	}
};
// }}}
// {{{ saveData()
class_propBox_edit_a.prototype.saveData = function() {
	var tempXML = new XML();
	var tempNode = tempXML.createTextNode(this.inputBox.value);

	this.data.firstChild.removeNode();
	this.data.appendChild(tempNode);
	if (this.hrefBox.value.substring(0, conf.url_page_scheme_intern.length + 2) == conf.url_page_scheme_intern + ":/") {
		delete(this.data.attributes.href);
		this.data.attributes.href_id = conf.project.tree.pages.getIdByUri(this.hrefBox.value);
	} else {
		delete(this.data.attributes.href_id);
		this.data.attributes.href = this.hrefBox.value;
	}
	
	return super.saveData();
};
// }}}

/*
 *	Class PropBox_edit_img
 *
 *	Extends class_propBox
 *	Handles an Image or Image-Link
 */
// {{{ constructor
class_propBox_edit_img = function() {};
class_propBox_edit_img.prototype = new class_propBox();

class_propBox_edit_img.prototype.propName = [];
class_propBox_edit_img.prototype.propName[0] = conf.lang.prop_name_edit_img;
class_propBox_edit_img.prototype.isImageProp = true;
// }}}
// {{{ onResize()
class_propBox_edit_img.prototype.onResize = function() {
	this.width = this._parent.width;

	if (this.width - this.settings.explanationWidth - this.settings.OKCancelWidth < this.settings.explanationWidth) {
		if (this.multilangProp == 2) {
			this.settings.border_top = this.settings.minInnerHeight;
		} else {
			this.settings.border_top = this.settings.minInnerHeight + this.settings.border;
		}
		this.settings.border_bottom = this.settings.border;
		this.settings.border_left = this.settings.border;
		this.settings.border_right = this.settings.border;
	} else {
		if (this.multilangProp == 2) {
			this.settings.border_top = 0;
		} else {
			this.settings.border_top = this.settings.border;
		}
		this.settings.border_bottom = this.settings.border;
		this.settings.border_left = 2 * this.settings.gridSize + this.settings.border;
		this.settings.border_right = this.settings.OkCancelWidth + this.settings.border;
	};

	this.setComponents();		
	
	this.back.onResize();

	if (this.num == this._parent.propLineNum) {
		this._parent.setPropPos();	
	}
};
// }}}
// {{{ generateComponents()
class_propBox_edit_img.prototype.generateComponents = function() {
	super.generateComponents();
	
	this.attachMovie("component_inputField", "inputBox", 2);
	this.inputBox.onChanged = function() {
		this._parent.onChanged();
	};
	this.inputBox.onKillFocus = function() {
		//this._parent.save();	
	};
	this.inputBox.onEnter = function() {
		this._parent.save();	
	};
	this.inputBox.onCtrlS = function() {
		this._parent.save();	
	};
	
	this.attachMovie("component_inputField", "hrefBox", 3);
	this.hrefBox.onChanged = function() {
		this._parent.onChanged();
	};
	this.hrefBox.onKillFocus = function() {
		//this._parent.save();	
	};
	this.hrefBox.onEnter = function() {
		this._parent.save();	
	};
	this.hrefBox.onCtrlS = function() {
		this.hrefBox.save();	
	};
	this.hrefBox.onDrop = function(droppedObj) {
		this._parent.hrefBox.value = conf.project.tree.pages.getUriById(droppedObj.nid);
		this._parent.onChanged();
		this._parent.save();
	};
	this.hrefBox.isValidDrop = function(draggedObj) {
		return conf.project.tree.pages.isTreeNode(draggedObj);
	};
	
	this.attachMovie("component_inputField", "altBox", 4);
	this.altBox.onChanged = function() {
		this._parent.onChanged();
	};
	this.altBox.onKillFocus = function() {
		//this._parent.save();	
	};
	this.altBox.onEnter = function() {
		this._parent.save();	
	};
	this.altBox.onCtrlS = function() {
		this._parent.save();	
	};

	this.attachMovie("icon_format_link", "hrefIcon", 5);
	
	this.attachMovie("component_button", "buttonImg", 6, {
		enabledState	: true,
		caption			: conf.lang.prop_tt_img_choose,
		align			: "TR"
	});
	
	this.attachMovie("component_button", "buttonHref", 7, {
		enabledState	: true,
		caption			: conf.lang.prop_tt_img_choose,
		align			: "TR"
	});
	
	this.createTextField("altDesc", 8, 0, 0, 50, 20);
	this.altDesc.text = conf.lang.prop_tt_img_altdesc;
	this.altDesc.initFormat(conf.interface.textformat);
	
	this.attachMovie("prop_tt_img_thumbnail", "thumb", 9);
	
	this.attachMovie("component_button_symbol", "buttonTarget", 10, {
		width			: 24,
		height			: 18,
		tooltip			: conf.lang.buttontip_link_target,
		onClick			: this.toggleRefTarget,
		onClickObj		: this,
		onHold			: this.showRefTargetMenu,
		onHoldObj		: this,
		enabledState	: true
	});
};
// }}}
// {{{ toggleRefTarget()
class_propBox_edit_img.prototype.toggleRefTarget = class_propBox_edit_a.prototype.toggleRefTarget;
// }}}
// {{{ showRefTargetMenu()
class_propBox_edit_img.prototype.showRefTargetMenu = class_propBox_edit_a.prototype.showRefTargetMenu;
// }}}
// {{{ setRefTarget()
class_propBox_edit_img.prototype.setRefTarget = class_propBox_edit_a.prototype.setRefTarget;
// }}}
// {{{ setComponents()
class_propBox_edit_img.prototype.setComponents = function() {
	super.setComponents();
	
	this.thumb._x = this.settings.border_left + 1;
	this.thumb._y = this.settings.border_top + 1;
	
	this.buttonImg._x = this.width - this.settings.border_right;
	this.buttonImg._y = this.settings.border_top;
	this.buttonImg.width = 26;
	
	this.inputBox._x = this.settings.border_left + 2 * this.settings.border + int(conf.thumb_width);
	this.inputBox._y = this.settings.border_top;
	this.inputBox.width = this.width - this.settings.border_left - this.settings.border_right - (2 * this.settings.border + int(conf.thumb_width)) - this.buttonImg.width - this.settings.border;
	
	this.buttonHref._x = this.width - this.settings.border_right;
	this.buttonHref._y = this.settings.border_top + int(conf.interface.component_height) + this.settings.border + 2;
	this.buttonHref.width = this.buttonImg.width;
	
	this.hrefBox._x = this.settings.border_left + 2 * this.settings.border + int(conf.thumb_width) + 30;
	this.hrefBox._y = this.settings.border_top + int(conf.interface.component_height) + this.settings.border + 2;
	this.hrefBox.width = this.width - this.settings.border_left - this.settings.border_right - (2 * this.settings.border + int(conf.thumb_width)) - ((this.data.attributes.target != undefined ? 2 : 1) * 30) - this.buttonImg.width - this.settings.border;
	
	this.hrefIcon._x = this.settings.border_left + 2 * this.settings.border + int(conf.thumb_width) + this.settings.border;
	this.hrefIcon._y = this.settings.border_top + int(conf.interface.component_height * 1.5) + this.settings.border + 2;
	
	this.buttonTarget._x = this.width - this.settings.border_right - 45 - 2 * this.settings.border;
	this.buttonTarget._y = this.settings.border_top + int(conf.interface.component_height * 1) + this.settings.border + 4;
	
	this.altBox._x = this.settings.border_left + 2 * this.settings.border + int(conf.thumb_width) + 30;
	this.altBox._y = this.settings.border_top + 2 * (int(conf.interface.component_height) + this.settings.border + 2);
	this.altBox.width = this.width - this.settings.border_left - this.settings.border_right - (2 * this.settings.border + int(conf.thumb_width)) - 30;
	
	this.altDesc._x = this.settings.border_left + 2 * this.settings.border + int(conf.thumb_width) + this.settings.border + 1;
	this.altDesc._y = this.settings.border_top + 2 * (int(conf.interface.component_height) + this.settings.border + 2) + 2;
	this.altDesc._width = 20;
	
	this.innerHeight = int(conf.thumb_height) + 7;
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
};
// }}}
// {{{ setData()
class_propBox_edit_img.prototype.setData = function() {
	super.setData();

        this.setMultilangProp();
	
	this.inputBox.value = this.data.attributes.src;
	this.inputBox.explain = conf.lang.prop_tt_img_filepath;
	
	if (this.data.attributes.href != undefined || this.data.attributes.href_id != undefined) {
		if (this.data.attributes.href != undefined) {
			this.hrefBox.value = this.data.attributes.href;
		} else if (this.data.attributes.href_id != undefined) {
			this.hrefBox.value = conf.project.tree.pages.getUriById(this.data.attributes.href_id);
		}
		this.hrefBox._visible = true;
		this.hrefIcon._visible = true;
		this.buttonHref._visible = true;
	} else {
		this.hrefBox._visible = false;
		this.hrefIcon._visible = false;
		this.buttonHref._visible = false;
	}
	this.hrefBox.explain = conf.lang.prop_tt_img_href;
	
	this.altBox.value = this.data.attributes.alt;
	this.altBox.explain = conf.lang.prop_tt_img_alt;
	
	if (this.data.attributes.target != undefined) {
		this.buttonTarget._visible = true;
		if (this.data.attributes.target == "") {
			this.buttonTarget.symbol = "icon_link_target_self";
		} else if (this.data.attributes.target == "_blank") {
			this.buttonTarget.symbol = "icon_link_target_blank";
		}
	} else {
		this.buttonTarget._visible = false;
	}
	
	setTimeout(this.load_thumb, this, 10);
};
// }}}
// {{{ load_thumb()
class_propBox_edit_img.prototype.load_thumb = function() {
	var filedata = this.inputBox.value.splitPath();
	
	this.thumb.filename = filedata.name;
	this.thumb.filepath = filedata.path;
	this.thumb.load_thumb();
	
	this._parent.propObj.getImageProp(filedata.path, filedata.name, this.setImageProp, this);

	this.buttonImg.onClick = function() {
            var force_width = "";
            var force_height = "";
            var force = this._parent.data.attributes.force_size;

            if (force.substring(0, 5) == "%var_") {
                force = conf.project.tree.settings.getVariable(force.substring(5, force.length - 1));
            }

            force = force.split("x");

            if (force.length == 2) {
                if (int(force[0]) > 0) {
                    force_width = force[0];
                }
                if (int(force[1]) > 0) {
                    force_height = force[1];
                }
            } else {
                force_width = this._parent.data.attributes.force_width;
                force_height =  this._parent.data.attributes.force_height;
            }

            _root.mainInterface.interface.layouts.dlgChoose_files.setActive(this._parent._parent.propObj.saveFilePath, this._parent._parent.propObj, [this._parent.thumb.filepath, this._parent.data.nid, "jpg,jpeg,gif,png", force_width, force_height]);
	}
	this.buttonHref.onClick = function() {
            var href_id = "";
            if (this._parent.hrefBox.value.substring(0, conf.url_page_scheme_intern.length + 2) == conf.url_page_scheme_intern + ":/") {
                    href_id = conf.project.tree.pages.getIdByUri(this._parent.hrefBox.value);
            }
            _root.mainInterface.interface.layouts.dlgChoose_page.setActive(this._parent._parent.propObj.saveFileRef, this._parent._parent.propObj, [href_id, this._parent.data.nid]);
	}
};
// }}}
// {{{ setImageProp()
class_propBox_edit_img.prototype.setImageProp = function(iwidth, iheigth, isize, idate) {
	this.thumb.filesize = isize;
	this.thumb.filedate = idate;
	this.thumb.filetype = "unknown";
	this.thumb.imagesize = iwidth + "x" + iheigth;

	this.thumb.init_tooltip();
};
// }}}
// {{{ saveData()
class_propBox_edit_img.prototype.saveData = function() {
	this.data.attributes.src = this.inputBox.value;
	if (this.hrefBox._visible == true) {
		if (this.hrefBox.value.substring(0, conf.url_page_scheme_intern.length + 2) == conf.url_page_scheme_intern + ":/") {
			delete(this.data.attributes.href);
			this.data.attributes.href_id = conf.project.tree.pages.getIdByUri(this.hrefBox.value);
		} else {
			delete(this.data.attributes.href_id);
			this.data.attributes.href = this.hrefBox.value;
		}
	}
	this.data.attributes.alt = this.altBox.value;
	
	this.load_thumb();
	
	return super.saveData();
};
// }}}

/*
 *	Class PropBox_edit_audio
 *
 *	Extends class_propBox
 *	Handles an audio-element
 */
// {{{ constructor
class_propBox_edit_audio = function() {};
class_propBox_edit_audio.prototype = new class_propBox();

class_propBox_edit_audio.prototype.propName = [];
class_propBox_edit_audio.prototype.propName[0] = conf.lang.prop_name_edit_audio;
// }}}
// {{{ onResize()
class_propBox_edit_audio.prototype.onResize = function() {
	this.width = this._parent.width;

	if (this.width - this.settings.explanationWidth - this.settings.OKCancelWidth < this.settings.explanationWidth) {
		if (this.multilangProp == 2) {
			this.settings.border_top = this.settings.minInnerHeight;
		} else {
			this.settings.border_top = this.settings.minInnerHeight + this.settings.border;
		}
		this.settings.border_bottom = this.settings.border;
		this.settings.border_left = this.settings.border;
		this.settings.border_right = this.settings.border;
	} else {
		if (this.multilangProp == 2) {
			this.settings.border_top = 0;
		} else {
			this.settings.border_top = this.settings.border;
		}
		this.settings.border_bottom = this.settings.border;
		this.settings.border_left = 2 * this.settings.gridSize + this.settings.border;
		this.settings.border_right = this.settings.OkCancelWidth + this.settings.border;
	};

	this.setComponents();		
	
	this.back.onResize();

	if (this.num == this._parent.propLineNum) {
		this._parent.setPropPos();	
	}
};
// }}}
// {{{ generateComponents()
class_propBox_edit_audio.prototype.generateComponents = function() {
	super.generateComponents();
	
	this.attachMovie("component_inputField", "inputBox", 2);
	this.inputBox.onChanged = function() {
		this._parent.onChanged();
	};
	this.inputBox.onKillFocus = function() {
		//this._parent.save();	
	};
	this.inputBox.onEnter = function() {
		this._parent.save();	
	};
	this.inputBox.onCtrlS = function() {
		this._parent.save();	
	};
	
	
	this.attachMovie("component_inputField", "altBox", 4);
	this.altBox.onChanged = function() {
		this._parent.onChanged();
	};
	this.altBox.onKillFocus = function() {
		//this._parent.save();	
	};
	this.altBox.onEnter = function() {
		this._parent.save();	
	};
	this.altBox.onCtrlS = function() {
		this._parent.save();	
	};

	this.attachMovie("component_button", "buttonAudio", 6, {
		enabledState	: true,
		caption			: conf.lang.prop_tt_img_choose,
		align			: "TR"
	});
	
	this.createTextField("altDesc", 8, 0, 0, 50, 20);
	this.altDesc.text = conf.lang.prop_tt_img_altdesc;
	this.altDesc.initFormat(conf.interface.textformat);
	
	//this.attachMovie("prop_tt_img_thumbnail", "thumb", 9);
};
// }}}
// {{{ setComponents()
class_propBox_edit_audio.prototype.setComponents = function() {
	super.setComponents();
	
	//this.thumb._x = this.settings.border_left + 1;
	//this.thumb._y = this.settings.border_top + 1;
	
	this.buttonAudio._x = this.width - this.settings.border_right;
	this.buttonAudio._y = this.settings.border_top;
	this.buttonAudio.width = 26;
	
	this.inputBox._x = this.settings.border_left + 2 * this.settings.border + int(conf.thumb_width);
	this.inputBox._y = this.settings.border_top;
	this.inputBox.width = this.width - this.settings.border_left - this.settings.border_right - (2 * this.settings.border + int(conf.thumb_width)) - this.buttonAudio.width - this.settings.border;
	
	this.buttonHref._x = this.width - this.settings.border_right;
	this.buttonHref._y = this.settings.border_top + int(conf.interface.component_height) + this.settings.border + 2;
	this.buttonHref.width = this.buttonAudio.width;
	
	this.altBox._x = this.settings.border_left + 2 * this.settings.border + int(conf.thumb_width) + 30;
	this.altBox._y = this.settings.border_top + 1 * (int(conf.interface.component_height) + this.settings.border + 2);
	this.altBox.width = this.width - this.settings.border_left - this.settings.border_right - (2 * this.settings.border + int(conf.thumb_width)) - 30;
	
	this.altDesc._x = this.settings.border_left + 2 * this.settings.border + int(conf.thumb_width) + this.settings.border + 1;
	this.altDesc._y = this.settings.border_top + 1 * (int(conf.interface.component_height) + this.settings.border + 2) + 2;
	this.altDesc._width = 20;
	
	//this.innerHeight = int(conf.thumb_height) + 7;
	this.innerHeight = this.settings.border_top + 2 * (int(conf.interface.component_height) + this.settings.border);
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
};
// }}}
// {{{ setData()
class_propBox_edit_audio.prototype.setData = function() {
	super.setData();
	
	this.inputBox.value = this.data.attributes.src;
	this.inputBox.explain = conf.lang.prop_tt_audio_filepath;
	
	this.altBox.value = this.data.attributes.alt;
	this.altBox.explain = conf.lang.prop_tt_img_alt;
	
        this.load_thumb();
};
// }}}
// {{{ load_thumb()
class_propBox_edit_audio.prototype.load_thumb = function() {
	var filedata = this.inputBox.value.splitPath();
	
	this.buttonAudio.onClick = function() {
		_root.mainInterface.interface.layouts.dlgChoose_files.setActive(this._parent._parent.propObj.saveFilePath, this._parent._parent.propObj, [this._parent.thumb.filepath, this._parent.data.nid, "mp3"]);
	}
};
// }}}
// {{{ saveData()
class_propBox_edit_audio.prototype.saveData = function() {
	this.data.attributes.src = this.inputBox.value;
	if (this.hrefBox._visible == true) {
		if (this.hrefBox.value.substring(0, conf.url_page_scheme_intern.length + 2) == conf.url_page_scheme_intern + ":/") {
			delete(this.data.attributes.href);
			this.data.attributes.href_id = conf.project.tree.pages.getIdByUri(this.hrefBox.value);
		} else {
			delete(this.data.attributes.href_id);
			this.data.attributes.href = this.hrefBox.value;
		}
	}
	this.data.attributes.alt = this.altBox.value;
	
	return super.saveData();
};
// }}}

/*
 *	Class PropBox_edit_video
 *
 *	Extends class_propBox
 *	Handles an video-element
 */
// {{{ constructor
class_propBox_edit_video = function() {};
class_propBox_edit_video.prototype = new class_propBox();

class_propBox_edit_video.prototype.propName = [];
class_propBox_edit_video.prototype.propName[0] = conf.lang.prop_name_edit_video;
// }}}
// {{{ onResize()
class_propBox_edit_video.prototype.onResize = function() {
	this.width = this._parent.width;

	if (this.width - this.settings.explanationWidth - this.settings.OKCancelWidth < this.settings.explanationWidth) {
		if (this.multilangProp == 2) {
			this.settings.border_top = this.settings.minInnerHeight;
		} else {
			this.settings.border_top = this.settings.minInnerHeight + this.settings.border;
		}
		this.settings.border_bottom = this.settings.border;
		this.settings.border_left = this.settings.border;
		this.settings.border_right = this.settings.border;
	} else {
		if (this.multilangProp == 2) {
			this.settings.border_top = 0;
		} else {
			this.settings.border_top = this.settings.border;
		}
		this.settings.border_bottom = this.settings.border;
		this.settings.border_left = 2 * this.settings.gridSize + this.settings.border;
		this.settings.border_right = this.settings.OkCancelWidth + this.settings.border;
	};

	this.setComponents();		
	
	this.back.onResize();

	if (this.num == this._parent.propLineNum) {
		this._parent.setPropPos();	
	}
};
// }}}
// {{{ generateComponents()
class_propBox_edit_video.prototype.generateComponents = function() {
	super.generateComponents();
	
	this.attachMovie("component_inputField", "inputBox", 2);
	this.inputBox.onChanged = function() {
		this._parent.onChanged();
	};
	this.inputBox.onKillFocus = function() {
		//this._parent.save();	
	};
	this.inputBox.onEnter = function() {
		this._parent.save();	
	};
	this.inputBox.onCtrlS = function() {
		this._parent.save();	
	};
	
	
	this.attachMovie("component_inputField", "altBox", 4);
	this.altBox.onChanged = function() {
		this._parent.onChanged();
	};
	this.altBox.onKillFocus = function() {
		//this._parent.save();	
	};
	this.altBox.onEnter = function() {
		this._parent.save();	
	};
	this.altBox.onCtrlS = function() {
		this._parent.save();	
	};

	this.attachMovie("component_button", "buttonVideo", 6, {
		enabledState	: true,
		caption			: conf.lang.prop_tt_img_choose,
		align			: "TR"
	});
	
	this.createTextField("altDesc", 8, 0, 0, 50, 20);
	this.altDesc.text = conf.lang.prop_tt_img_altdesc;
	this.altDesc.initFormat(conf.interface.textformat);
	
	//this.attachMovie("prop_tt_img_thumbnail", "thumb", 9);
};
// }}}
// {{{ setComponents()
class_propBox_edit_video.prototype.setComponents = function() {
	super.setComponents();
	
	//this.thumb._x = this.settings.border_left + 1;
	//this.thumb._y = this.settings.border_top + 1;
	
	this.buttonVideo._x = this.width - this.settings.border_right;
	this.buttonVideo._y = this.settings.border_top;
	this.buttonVideo.width = 26;
	
	this.inputBox._x = this.settings.border_left + 2 * this.settings.border + int(conf.thumb_width);
	this.inputBox._y = this.settings.border_top;
	this.inputBox.width = this.width - this.settings.border_left - this.settings.border_right - (2 * this.settings.border + int(conf.thumb_width)) - this.buttonVideo.width - this.settings.border;
	
	this.buttonHref._x = this.width - this.settings.border_right;
	this.buttonHref._y = this.settings.border_top + int(conf.interface.component_height) + this.settings.border + 2;
	this.buttonHref.width = this.buttonVideo.width;
	
	this.altBox._x = this.settings.border_left + 2 * this.settings.border + int(conf.thumb_width) + 30;
	this.altBox._y = this.settings.border_top + 1 * (int(conf.interface.component_height) + this.settings.border + 2);
	this.altBox.width = this.width - this.settings.border_left - this.settings.border_right - (2 * this.settings.border + int(conf.thumb_width)) - 30;
	
	this.altDesc._x = this.settings.border_left + 2 * this.settings.border + int(conf.thumb_width) + this.settings.border + 1;
	this.altDesc._y = this.settings.border_top + 1 * (int(conf.interface.component_height) + this.settings.border + 2) + 2;
	this.altDesc._width = 20;
	
	//this.innerHeight = int(conf.thumb_height) + 7;
	this.innerHeight = this.settings.border_top + 2 * (int(conf.interface.component_height) + this.settings.border);
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
};
// }}}
// {{{ setData()
class_propBox_edit_video.prototype.setData = function() {
	super.setData();
	
	this.inputBox.value = this.data.attributes.src;
	this.inputBox.explain = conf.lang.prop_tt_video_filepath;
	
	this.altBox.value = this.data.attributes.alt;
	this.altBox.explain = conf.lang.prop_tt_img_alt;
	
        this.load_thumb();
};
// }}}
// {{{ load_thumb()
class_propBox_edit_video.prototype.load_thumb = function() {
	var filedata = this.inputBox.value.splitPath();
	
	this.buttonVideo.onClick = function() {
		_root.mainInterface.interface.layouts.dlgChoose_files.setActive(this._parent._parent.propObj.saveFilePath, this._parent._parent.propObj, [this._parent.thumb.filepath, this._parent.data.nid, ",mp4,m4v,flv"]);
	}
};
// }}}
// {{{ saveData()
class_propBox_edit_video.prototype.saveData = function() {
	this.data.attributes.src = this.inputBox.value;
	if (this.hrefBox._visible == true) {
		if (this.hrefBox.value.substring(0, conf.url_page_scheme_intern.length + 2) == conf.url_page_scheme_intern + ":/") {
			delete(this.data.attributes.href);
			this.data.attributes.href_id = conf.project.tree.pages.getIdByUri(this.hrefBox.value);
		} else {
			delete(this.data.attributes.href_id);
			this.data.attributes.href = this.hrefBox.value;
		}
	}
	this.data.attributes.alt = this.altBox.value;
	
	return super.saveData();
};
// }}}

/*
 *	Class PropBox_edit_flash
 *
 *	Extends class_propBox
 *	Handles an video-element
 */
// {{{ constructor
class_propBox_edit_flash = function() {};
class_propBox_edit_flash.prototype = new class_propBox();

class_propBox_edit_flash.prototype.propName = [];
class_propBox_edit_flash.prototype.propName[0] = conf.lang.prop_name_edit_flash;
// }}}
// {{{ onResize()
class_propBox_edit_flash.prototype.onResize = function() {
	this.width = this._parent.width;

	if (this.width - this.settings.explanationWidth - this.settings.OKCancelWidth < this.settings.explanationWidth) {
		if (this.multilangProp == 2) {
			this.settings.border_top = this.settings.minInnerHeight;
		} else {
			this.settings.border_top = this.settings.minInnerHeight + this.settings.border;
		}
		this.settings.border_bottom = this.settings.border;
		this.settings.border_left = this.settings.border;
		this.settings.border_right = this.settings.border;
	} else {
		if (this.multilangProp == 2) {
			this.settings.border_top = 0;
		} else {
			this.settings.border_top = this.settings.border;
		}
		this.settings.border_bottom = this.settings.border;
		this.settings.border_left = 2 * this.settings.gridSize + this.settings.border;
		this.settings.border_right = this.settings.OkCancelWidth + this.settings.border;
	};

	this.setComponents();		
	
	this.back.onResize();

	if (this.num == this._parent.propLineNum) {
		this._parent.setPropPos();	
	}
};
// }}}
// {{{ generateComponents()
class_propBox_edit_flash.prototype.generateComponents = function() {
	super.generateComponents();
	
	this.attachMovie("component_inputField", "inputBox", 2);
	this.inputBox.onChanged = function() {
		this._parent.onChanged();
	};
	this.inputBox.onKillFocus = function() {
		//this._parent.save();	
	};
	this.inputBox.onEnter = function() {
		this._parent.save();	
	};
	this.inputBox.onCtrlS = function() {
		this._parent.save();	
	};
	
	
	this.attachMovie("component_inputField", "altBox", 4);
	this.altBox.onChanged = function() {
		this._parent.onChanged();
	};
	this.altBox.onKillFocus = function() {
		//this._parent.save();	
	};
	this.altBox.onEnter = function() {
		this._parent.save();	
	};
	this.altBox.onCtrlS = function() {
		this._parent.save();	
	};

	this.attachMovie("component_button", "buttonFlash", 6, {
		enabledState	: true,
		caption			: conf.lang.prop_tt_img_choose,
		align			: "TR"
	});
	
	this.createTextField("altDesc", 8, 0, 0, 50, 20);
	this.altDesc.text = conf.lang.prop_tt_img_altdesc;
	this.altDesc.initFormat(conf.interface.textformat);
	
	//this.attachMovie("prop_tt_img_thumbnail", "thumb", 9);
};
// }}}
// {{{ setComponents()
class_propBox_edit_flash.prototype.setComponents = function() {
	super.setComponents();
	
	//this.thumb._x = this.settings.border_left + 1;
	//this.thumb._y = this.settings.border_top + 1;
	
	this.buttonFlash._x = this.width - this.settings.border_right;
	this.buttonFlash._y = this.settings.border_top;
	this.buttonFlash.width = 26;
	
	this.inputBox._x = this.settings.border_left + 2 * this.settings.border + int(conf.thumb_width);
	this.inputBox._y = this.settings.border_top;
	this.inputBox.width = this.width - this.settings.border_left - this.settings.border_right - (2 * this.settings.border + int(conf.thumb_width)) - this.buttonFlash.width - this.settings.border;
	
	this.buttonHref._x = this.width - this.settings.border_right;
	this.buttonHref._y = this.settings.border_top + int(conf.interface.component_height) + this.settings.border + 2;
	this.buttonHref.width = this.buttonFlash.width;
	
	this.altBox._x = this.settings.border_left + 2 * this.settings.border + int(conf.thumb_width) + 30;
	this.altBox._y = this.settings.border_top + 1 * (int(conf.interface.component_height) + this.settings.border + 2);
	this.altBox.width = this.width - this.settings.border_left - this.settings.border_right - (2 * this.settings.border + int(conf.thumb_width)) - 30;
	
	this.altDesc._x = this.settings.border_left + 2 * this.settings.border + int(conf.thumb_width) + this.settings.border + 1;
	this.altDesc._y = this.settings.border_top + 1 * (int(conf.interface.component_height) + this.settings.border + 2) + 2;
	this.altDesc._width = 20;
	
	//this.innerHeight = int(conf.thumb_height) + 7;
	this.innerHeight = this.settings.border_top + 2 * (int(conf.interface.component_height) + this.settings.border);
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
};
// }}}
// {{{ setData()
class_propBox_edit_flash.prototype.setData = function() {
	super.setData();
	
	this.inputBox.value = this.data.attributes.src;
	this.inputBox.explain = conf.lang.prop_tt_flash_filepath;
	
	this.altBox.value = this.data.attributes.alt;
	this.altBox.explain = conf.lang.prop_tt_img_alt;
	
        this.load_thumb();
};
// }}}
// {{{ load_thumb()
class_propBox_edit_flash.prototype.load_thumb = function() {
	var filedata = this.inputBox.value.splitPath();
	
	this.buttonFlash.onClick = function() {
		_root.mainInterface.interface.layouts.dlgChoose_files.setActive(this._parent._parent.propObj.saveFilePath, this._parent._parent.propObj, [this._parent.thumb.filepath, this._parent.data.nid, "swf"]);
	}
};
// }}}
// {{{ saveData()
class_propBox_edit_flash.prototype.saveData = function() {
	this.data.attributes.src = this.inputBox.value;
	if (this.hrefBox._visible == true) {
		if (this.hrefBox.value.substring(0, conf.url_page_scheme_intern.length + 2) == conf.url_page_scheme_intern + ":/") {
			delete(this.data.attributes.href);
			this.data.attributes.href_id = conf.project.tree.pages.getIdByUri(this.hrefBox.value);
		} else {
			delete(this.data.attributes.href_id);
			this.data.attributes.href = this.hrefBox.value;
		}
	}
	this.data.attributes.alt = this.altBox.value;
	
	return super.saveData();
};
// }}}

/*
 *	Class PropBox_proj_filelist
 *
 *	Extends class_propBox
 *	Handles Files in a directory
 */
// {{{ constructor
class_propBox_proj_filelist = function() {};
class_propBox_proj_filelist.prototype = new class_propBox();

class_propBox_proj_filelist.prototype.showSaver = false;
class_propBox_proj_filelist.prototype.showDeactiveFiles = false;
// }}}
// {{{ onResize()
class_propBox_proj_filelist.prototype.onResize = function() {
	this.width = this._parent.width;
	
	this.settings.border_top = this.settings.border;
	this.settings.border_bottom = this.settings.border;
	this.settings.border_left = this.settings.border;
	this.settings.border_right = this.settings.border;

	this.setComponents();		
	
	this._parent.setPropPos();	

	this.back.onResize();
	
	for (var i = 1; i <= this.filelist.length; i++) {
		this["thumb" + i].onResize();
	}	
};
// }}}
// {{{ generateComponents()
class_propBox_proj_filelist.prototype.generateComponents = function() {
	var fileTypes;
	var addThis;
	
	this.tooltipMsg = new tooltipClass.tooltipMsgObj();

	this.back.buttonOk._visible = false;
	this.back.buttonCancel._visible = false;
	
	this.filelist = [];
	this.filelist_disabled = [];

	var fileTypes = this._parent.propObj.treeObj.fileFilter.file_type.split(",");
	for (var i = 0; i < this.data.childNodes.length; i++) {
		addThis = false;
		if (this._parent.propObj.treeObj.fileFilter.file_type == "") {
			addThis = true;
		} else {
			if (fileTypes.searchFor(this.data.childNodes[i].attributes.extension) != -1) {
				if ((this._parent.propObj.treeObj.fileFilter.force_width == "" || this._parent.propObj.treeObj.fileFilter.force_width == this.data.childNodes[i].attributes.width) && (this._parent.propObj.treeObj.fileFilter.force_height == "" || this._parent.propObj.treeObj.fileFilter.force_height == this.data.childNodes[i].attributes.height)) {
					addThis = true;
				}
			}
		} 
		if (addThis == false) {
			this.filelist_disabled.push({
				filename	: this.data.childNodes[i].attributes.name,
				filepath	: this.data.attributes.dir,
				filesize	: this.data.childNodes[i].attributes.size,
				filedate	: this.data.childNodes[i].attributes.date,
				filetype	: this.data.childNodes[i].attributes.type,
				imagesize	: this.data.childNodes[i].attributes.width != undefined ? this.data.childNodes[i].attributes.width + "x" + this.data.childNodes[i].attributes.height : null,
				selected	: false,
				selectable	: false
			});
		} else {
			this.filelist.push({
				filename	: this.data.childNodes[i].attributes.name,
				filepath	: this.data.attributes.dir,
				filesize	: this.data.childNodes[i].attributes.size,
				filedate	: this.data.childNodes[i].attributes.date,
				filetype	: this.data.childNodes[i].attributes.type,
				imagesize	: this.data.childNodes[i].attributes.width != undefined ? this.data.childNodes[i].attributes.width + "x" + this.data.childNodes[i].attributes.height : null,
				selected	: false,
				selectable	: true
			});
		}
	}
	for (var i = 1; i <= 3; i++) {
		this.attachMovie("component_button_symbol", "button_" + i, i + 10, {
			width	: 19,
			height	: 17
		});
	}
		
	this.button_1.symbol = "icon_filelist_thumbnail";
	this.button_1.tooltip = conf.lang.buttontip_filelist_thumbnail;
	this.button_1.enabledState = this.filelist.length > 0 || this.filelist_disabled.length > 0;
	this.button_1.onClick = function() {
		conf.user.settings.filelistType = "thumbs";
		this._parent.removeFileList();
	};
	this.button_2.symbol = "icon_filelist_detail";
	this.button_2.tooltip = conf.lang.buttontip_filelist_detail;
	this.button_2.enabledState = this.filelist.length > 0 || this.filelist_disabled.length > 0;
	this.button_2.onClick = function() {
		conf.user.settings.filelistType = "details";
		this._parent.removeFileList();
	};
	this.button_3.symbol = "icon_tree_button_delete";
	this.button_3.tooltip = conf.lang.buttontip_tree_delete;
	this.button_3.enabledState = (this.filelist.length > 0 || this.filelist_disabled.length > 0) && conf.user.mayDeleteFiles();
	this.button_3.onClick = function() {
		this._parent.deleteFiles();
	};

	this.createTextField("deactiveMessage", 8, 0, 0, 50, 20);
	this.deactiveMessage.multiline = true;
	//this.deactiveMessage.text = conf.lang.prop_tt_img_altdesc;
	this.deactiveMessage.initFormat(conf.interface.textformat);
	this.deactiveMessage.selectable = false;

	this.attachMovie("prop_tt_filelist_deactiveFilesOpener", "deactiveFilesOpener", 15);

	this.attachMovie("rectangle_back", "deactiveFilesDivider", 16);
	this.deactiveFilesDivider._alpha = 30;
	this.deactiveFilesDivider._height = 1;
	this.deactiveFilesDivider.setRGB(conf.interface.color_background);
	this.deactiveFilesDivider._x = this.settings.border + this.settings.gridSize * 2;

	this.removeFileList();
};
// }}}
// {{{ deleteFiles()
class_propBox_proj_filelist.prototype.deleteFiles = function() {
	var i, name = [], fileArray = [];
	
	for (var i = 0; i < this.filelist.length; i++) {
		if (this.filelist[i].selected) {
			name.push(this.filelist[i].filename);
			fileArray.push(this.filelist[i].filepath + this.filelist[i].filename);
		}
	}
	
	var name = name.join(", ");
	if (name.length > 20) {
		name = name.substr(0, 17) + "...";
	}

	if (fileArray.length > 0) {
		this.tooltipMsg.text = conf.lang.msg_delete_from_tree.replace("%name%", name);
		this.tooltipMsg.type = "OkCancel";
		this.tooltipMsg.setOkFunc(this._parent.propObj.deleteFiles, this._parent.propObj, [fileArray]);
		this.tooltipMsg.setCancelFunc(null, null, []);
		this.tooltipMsg.show(this.button_3.getGlobalX() + 24, this.button_3.getGlobalY() + 6);
	}
};
// }}}
// {{{ removeFileList()
class_propBox_proj_filelist.prototype.removeFileList = function() {
	for (var i = 1; i <= this.filelist.length; i++) {
		this["thumb" + i].removeMovieClip();
	} 
	for (var i = 1; i <= this.filelist_disabled.length; i++) {
		this["thumb_disabled" + i].removeMovieClip();
	} 
	setTimeout(this.generateFilelist, this, 30);
}
// }}}
// {{{ generateFileList()
class_propBox_proj_filelist.prototype.generateFilelist = function() {
	if (conf.user.settings.filelistType == "thumbs") {
		//generate filelist as thumbnails
		for (var i = 1; i <= this.filelist.length; i++) {
			this.attachMovie("prop_tt_filelist_thumbnail", "thumb" + i, i + 20,{
				n			: i,
				fileobj		: this.filelist[i - 1],
				_visible	: false
			});
		}
		for (var i = 1; i <= conf.thumb_load_num && i <= this.filelist.length; i++) {
			setTimeout(this.load_thumb, this, 200, [i], false);
		}
		if (this.showDeactiveFiles) {
			//generate filelist as thumbnails
			for (var i = 1; i <= this.filelist_disabled.length; i++) {
				this.attachMovie("prop_tt_filelist_thumbnail", "thumb_disabled" + i, i + 20 + this.filelist.length,{
					n			: i,
					fileobj		: this.filelist_disabled[i - 1],
					_visible	: false
				});
			}
			for (var i = 1; i <= conf.thumb_load_num && i <= this.filelist_disabled.length; i++) {
				setTimeout(this.load_thumb_disabled, this, 200, [i], false);
			}
		}
	} else {
		//generate filelist as details
		for (var i = 1; i <= this.filelist.length; i++) {
			this.attachMovie("prop_tt_filelist_detail", "thumb" + i, i + 20,{
				n			: i,
				fileobj		: this.filelist[i - 1],
				_visible	: false
			});
		}
		if (this.showDeactiveFiles) {
			for (var i = 1; i <= this.filelist_disabled.length; i++) {
				this.attachMovie("prop_tt_filelist_detail", "thumb_disabled" + i, i + 20 + this.filelist.length,{
					n			: i,
					fileobj		: this.filelist_disabled[i - 1],
					_visible	: false
				});
			}
		}
	}
	this.onResize();
};
// }}}
// {{{ load_thumb()
class_propBox_proj_filelist.prototype.load_thumb = function(id) {
	if (id <= this.filelist.length) {
		this["thumb" + id].load_thumb(id, false);
	}
};
// }}}
// {{{ load_thumb_disabled()
class_propBox_proj_filelist.prototype.load_thumb_disabled = function(id) {
	if (id <= this.filelist_disabled.length) {
		this["thumb_disabled" + id].load_thumb(id, true);
	}
};
// }}}
// {{{ select()
class_propBox_proj_filelist.prototype.select = function(id, type) {
	var i, startId, endId;

	if (type == "single") {
		for (var i = 0; i < this.filelist.length; i++) {
			this.filelist[i].selected = false;
		}
		this.filelist[id - 1].selected = true;
		this.lastSelected = id;
	} else if (type == "multi") {
		this.filelist[id - 1].selected = !this.filelist[id - 1].selected;
		if (this.filelist[id - 1].selected) {
			this.lastSelected = id;
		}
	} else if (type == "multi_in_line") {
		if (id > this.lastSelected) {
			startId = this.lastSelected;
			endId = id;
		} else if (id < this.lastSelected) {
			startId = id;
			endId = this.lastSelected;
		} else {
			startId = 1;
			endId = 0;
		}
		for (var i = startId; i <= endId; i++) {
			this.filelist[i - 1].selected = true;
		}
	}
	for (var i = 1; i <= this.filelist.length; i++) {
		this["thumb" + i].setSelected();
	}
	this._parent.propObj.treeObj.selectedFile = "";
	for (var i = 0; i < this.filelist.length && this._parent.propObj.treeObj.selectedFile == ""; i++) {
		if (this.filelist[i].selected) {
			this._parent.propObj.treeObj.selectedFile = this.filelist[i].filepath + this.filelist[i].filename;
		}
	}
};
// }}}
// {{{ setComponents()
class_propBox_proj_filelist.prototype.setComponents = function() {
	var i, j, xNum, yNum;
		
	if (conf.user.settings.filelistType == "thumbs") {
		//set filelist as thumbs
		xNum = int((this.width - this.settings.border_left - this.settings.border_right - 2 * this.settings.gridSize) / (int(conf.thumb_width) + this.settings.border + 2 + 4));
		yNum = int(this.filelist.length / xNum) + (this.filelist.length % xNum > 0 ? 1 : 0);
		
		for (var i = 0; i < xNum; i++) {
			for (var j = 0; j < yNum; j++) {
				with (this["thumb" + (i + j * xNum + 1)]) {
					_x = this.settings.border_left + 2 * this.settings.gridSize + i * (int(conf.thumb_width) + this.settings.border + 6);
					_y = this.settings.border_top + j * (int(conf.thumb_height) + this.settings.border + 6 + 24);
				}
			}
		}
		this.innerHeight = yNum * (int(conf.thumb_height) + this.settings.border + 24) + 10;
		if (this.filelist_disabled.length > 0) {
			this.innerHeight += 35;
		}
		
		this.deactiveFilesDivider._y = this.innerHeight - 30;
		this.deactiveFilesDivider._width = this.width - this.settings.border * 2 - this.settings.gridSize * 2;

		this.deactiveFilesOpener._x = this.settings.border_left + 2 * this.settings.gridSize;
		this.deactiveFilesOpener._y = this.innerHeight - 20;
		this.deactiveFilesOpener.onResize();

		this.deactiveMessage._x = this.deactiveFilesOpener._x + 20;
		this.deactiveMessage._y = this.deactiveFilesOpener._y - 1;
		this.deactiveMessage._width = this.width - this.deactiveMessage._x - this.settings.border_right;
		this.deactiveMessage._height = 100;

		if (this.filelist_disabled.length > 0) {
			this.deactiveFilesDivider._visible = true;
			this.deactiveFilesOpener._visible = true;
			this.deactiveMessage._visible = true;
			if (this.showDeactiveFiles) {
				this.deactiveMessage.text = conf.lang.prop_proj_filelist_hidefiles;
				yNum = int(this.filelist_disabled.length / xNum) + (this.filelist_disabled.length % xNum > 0 ? 1 : 0);
				
				for (var i = 0; i < xNum; i++) {
					for (var j = 0; j < yNum; j++) {
						with (this["thumb_disabled" + (i + j * xNum + 1)]) {
							_x = this.settings.border_left + 2 * this.settings.gridSize + i * (int(conf.thumb_width) + this.settings.border + 6);
							_y = this.innerHeight + this.settings.border_top + j * (int(conf.thumb_height) + this.settings.border + 6 + 24);
						}
					}
				}
				this.innerHeight += yNum * (int(conf.thumb_height) + this.settings.border + 24) + 10;
			} else {
				this.deactiveMessage.text = conf.lang.prop_proj_filelist_showfiles;
			}
		} else {
			this.deactiveFilesDivider._visible = false;
			this.deactiveFilesOpener._visible = false;
			this.deactiveMessage._visible = false;
		}
	} else {
		//set filelist as details
		for (var i = 1; i <= this.filelist.length; i++) {
			with (this["thumb" + i]) {
				_x = this.settings.border_left + 2 * this.settings.gridSize;
				_y = this.settings.border_top + (i - 1) * 20;
			}
		}
		this.innerHeight = this.filelist.length * 20 + 10;
		if (this.filelist_disabled.length > 0) {
			this.innerHeight += 35;
		}

		this.deactiveFilesDivider._y = this.innerHeight - 30;
		this.deactiveFilesDivider._width = this.width - this.settings.border * 2 - this.settings.gridSize * 2;

		this.deactiveFilesOpener._x = this.settings.border_left + 2 * this.settings.gridSize;
		this.deactiveFilesOpener._y = this.innerHeight - 20;
		this.deactiveFilesOpener.onResize();

		this.deactiveMessage._x = this.deactiveFilesOpener._x + 20;
		this.deactiveMessage._y = this.deactiveFilesOpener._y - 1;
		this.deactiveMessage._width = this.width - this.deactiveMessage._x - this.settings.border_right;
		this.deactiveMessage._height = 100;

		if (this.filelist_disabled.length > 0) {
			this.deactiveFilesDivider._visible = true;
			this.deactiveFilesOpener._visible = true;
			this.deactiveMessage._visible = true;
			if (this.showDeactiveFiles) {
				this.deactiveMessage.text = conf.lang.prop_proj_filelist_hidefiles;
				for (var i = 1; i <= this.filelist_disabled.length; i++) {
					with (this["thumb_disabled" + i]) {
						_x = this.settings.border_left + 2 * this.settings.gridSize;
						_y = this.settings.border_top + (i - 1) * 20 + this.innerHeight;
					}
				}
				this.innerHeight += this.filelist_disabled.length * 20;
			} else {
				this.deactiveMessage.text = conf.lang.prop_proj_filelist_showfiles;
			}
		} else {
			this.deactiveFilesDivider._visible = false;
			this.deactiveFilesOpener._visible = false;
			this.deactiveMessage._visible = false;
		}
	}
	
        this.innerHeight = int(this.innerHeight * 1.1);
	this.innerHeight = this.innerHeight.limit(this.settings.border_top + 75 + this.settings.border);
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
	
	this.button_1._x = this.settings.border_left + 2;
	this.button_2._x = this.settings.border_left + 2;
	this.button_3._x = this.settings.border_left + 2;
};
// }}}
// {{{ setNewPos()
class_propBox_proj_filelist.prototype.setNewPos = function() {
	var bottomBorder = this.height - this.settings.border_bottom - 2;
	
	super.setNewPos();
	
	if (this.getGlobalY() + bottomBorder > Stage.height - 7) {
		bottomBorder = Stage.height - 7 - this.getGlobalY();	
	}
	if (bottomBorder < this.settings.border_top + 85) {
		bottomBorder = this.settings.border_top + 85;
	}
		
	this.button_1._y = bottomBorder - 66;
	this.button_2._y = bottomBorder - 45;
	this.button_3._y = bottomBorder - 17;
};
// }}}
// {{{ setButtons()
class_propBox_proj_filelist.prototype.setButtons = function() {
	var i;
	
	for (var i = 1; i <= 3; i++) {
		this["button_" + i].setStatus(this["button_" + i].enabledState);
	}
};
// }}}

/*
 *	Class PropBox_edit_xslt_template
 *
 *	Extends class_propBox_edit_text_multiline
 *	Handles an XSLT-Template-Source
 */
// {{{ constructor
class_propBox_edit_template = function() {};
class_propBox_edit_template.prototype = new class_propBox_edit_text_multiline();

class_propBox_edit_template.prototype.propName = [];
class_propBox_edit_template.prototype.propName[0] = conf.lang.prop_name_xslt_template;
// }}}
// {{{ setData()
class_propBox_edit_template.prototype.setData = function() {
	super.setData();

	if (this.data.firstChild.nodeType == 3) {	
		this.textBox.text = this.data.firstChild.nodeValue;
	} else {
		this.textBox.text = "";
	}
};
// }}}
// {{{ saveData()
class_propBox_edit_template.prototype.saveData = function(forceSave) {
	var newText;
	var tempXML = new XML("<temp>" + this.textBox.text + "</temp>");
	var tempNode;
	var i;
	
	if (tempXML.status == 0) {
		while (this.data.hasChildNodes()) {
			this.data.firstChild.removeNode();
		}
		
		newText = this.textBox.htmlText.removeUnwantedTags(["p"])
		newText = newText.replace([
			["<p>"		, ""],
			["</p>"		, "\n"],
			["&lt;"		, "<"],
			["&gt;"		, ">"],
			["&quot;"	, "\""],
			["&apos;"	, "'"],
			["&amp;"	, "&"]
		]);
		
		this.data.appendChild(tempXML.createTextNode(newText));

		return super.saveData(forceSave);
	} else {
		alert(conf.lang.error_prop_xslt_template + "\n\n" + conf.lang["error_parsexml" + tempXML.status]);

		return false;
	}
};
// }}}

/*
 *	Class PropBox_pg_xslt
 *
 *	Extends class_propBox
 *	Handles Settings of an XSLT-Template
 */
// {{{ constructor
class_propBox_pg_template_data = function() {};
class_propBox_pg_template_data.prototype = new class_propBox();

class_propBox_pg_template_data.prototype.propName = [];
class_propBox_pg_template_data.prototype.propName[0] = conf.lang.prop_name_pg_template;
// }}}
// {{{ saveData()
class_propBox_pg_template_data.prototype.saveData = function(forceSave) {
	if (this.isChanged == true || forceSave == true) {
		this.isChanged = false;
	}
	return true;
};
// }}}
// {{{ resetData()
class_propBox_pg_template_data.prototype.resetData = function() {
	super.resetData();
	
	this.data.setNodeIdByDBId();
	
	this._parent.propObj.setTemplatePropType(this.data.nid, this.comboBox.values[this.comboBox.selected]);
	this._parent.propObj.setTemplatePropActive(this.data.nid, this.checkBox.value);
};
// }}}
// {{{ setData()
class_propBox_pg_template_data.prototype.setData = function() {
	super.setData();
	
	this.comboBox.selected = 0;
	for (var i = 0; i < this.comboBox.values.length; i++) {
		if (this.comboBox.values[i] == this.data.attributes.type) {
			this.comboBox.selected = i;
		}
	}	
	this.comboBox.select();
	
	this.checkBox.value = this.data.attributes.active.toBoolean();
};
// }}}
// {{{ generateComponents()
class_propBox_pg_template_data.prototype.generateComponents = function() {
	var i;
	
	this.attachMovie("component_comboBox", "comboBox", 2);
	this.comboBox.values = ["[" + conf.lang.output_type_none + "]"];
	this.comboBox.values = this.comboBox.values.concat(conf.project.tree.settings.templateSets);
	this.comboBox.onChanged = function() {
		if (this.selected > 0) {
			this._parent._parent.propObj.setTemplatePropType(this._parent.data.nid, this.values[this.selected]);
		} else {
			this._parent._parent.propObj.setTemplatePropType(this._parent.data.nid, "");
		}
		this._parent.onChanged();
	};
	
	this.attachMovie("component_checkBox", "checkBox", 3, {
		caption	: conf.lang.prop_tt_xslt_active
	});
	this.checkBox.onChanged = function() {
		this._parent._parent.propObj.setTemplatePropActive(this._parent.data.nid, this.value);
		this._parent.onChanged();
	};
};
// }}}
// {{{ setComponents()
class_propBox_pg_template_data.prototype.setComponents = function() {
	this.comboBox._x = this.settings.border_left;
	this.comboBox._y = this.settings.border_top;
	this.comboBox.width = this.settings.explanationWidth;
	
	this.checkBox._x = this.settings.border_left + this.comboBox.width + 2*this.settings.border;
	this.checkBox._y = this.settings.border_top;
	this.checkBox.width = this.width - this.settings.border_left - this.settings.border_right - this.comboBox.width + 2*this.settings.border;
			
	this.innerHeight = this.settings.minInnerHeight;
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
};
// }}}

/*
 *	Class PropBox_edit_newnode_data
 *
 *	Extends class_propBox_pg_template_tempate
 *	Handles an newNode-Source
 */
// {{{ constructor
class_propBox_edit_newnode = function() {};
class_propBox_edit_newnode.prototype = new class_propBox_edit_template();

class_propBox_edit_newnode.prototype.propName = [];
class_propBox_edit_newnode.prototype.propName[0] = conf.lang.prop_name_xslt_newnode;
// }}}

/*
 *	Class PropBox_edit_newnode_valid_parents
 *
 *	Extends class_propBox_edit_text_singleline
 *	Handles an newNode-Settings
 */
// {{{ constructor
class_propBox_edit_newnode_valid_parents = function() {};
class_propBox_edit_newnode_valid_parents.prototype = new class_propBox_edit_text_singleline();

class_propBox_edit_newnode_valid_parents.prototype.propName = [];
class_propBox_edit_newnode_valid_parents.prototype.propName[0] = conf.lang.prop_name_xslt_valid_parent;
// }}}
// {{{ generateComponents()
class_propBox_edit_newnode_valid_parents.prototype.generateComponents = function() {
	super.generateComponents();
	this.inputBox.restrict = " a-zA-Z0-9\\-:_,;*";
}
// }}}
// {{{ setData()
class_propBox_edit_newnode_valid_parents.prototype.setData = function() {
	super.super.setData();
	this.inputBox.value = this.data.firstChild.nodeValue;
};
// }}}
// {{{ saveData()
class_propBox_edit_newnode_valid_parents.prototype.saveData = function(forceSave) {
	if (this.isChanged == true || forceSave == true) {
		if (this.data.firstChild != null) {
			this.data.firstChild.nodeValue = this.inputBox.value;
		} else {
			var tempXML = new XML();
			var tempNode;
			
			tempNode = tempXML.createTextNode(this.inputBox.value);
			this.data.appendChild(tempNode);
		}

		this._parent.propObj.save(this.data.nid);
		this.isChanged = false;
	}
	return true;
};
// }}}

/*
 *	Class PropBox_proj_colorscheme
 *
 *	Extends class_propBox
 *	Handles an Colorscheme-Settings
 */
// {{{ constructor
class_propBox_proj_colorscheme = function() {};
class_propBox_proj_colorscheme.prototype = new class_propBox();

class_propBox_proj_colorscheme.prototype.propName = [];
class_propBox_proj_colorscheme.prototype.propName[0] = conf.lang.prop_name_proj_colorscheme;
class_propBox_proj_colorscheme.prototype.minColorWidth = 150;
class_propBox_proj_colorscheme.prototype.activeColor = null;
// }}}
// {{{ onResize()
class_propBox_proj_colorscheme.prototype.onResize = function() {
	this.width = this._parent.width;
	
	this.settings.border_top = this.settings.minInnerHeight + this.settings.border;
	this.settings.border_bottom = this.settings.border;
	this.settings.border_left = this.settings.border;
	this.settings.border_right = this.settings.border;

	this.setComponents();		
	
	this.back.onResize();

	if (this.num == this._parent.propLineNum) {
		this._parent.setPropPos();	
	}
}
// }}}
// {{{ generateComponents()
class_propBox_proj_colorscheme.prototype.generateComponents = function() {
	var tempNode, tempStr, i;
	
	this.attachMovie("rectangle", "textBoxBack", 2);
	this.textBoxBack.back.setRGB(conf.interface.color_component_face);
	this.textBoxBack.outline.setRGB(conf.interface.color_component_line);
	
	this.colorLineNum = 0;
	this.generateColors();
	
	for (var i = 1; i <= 2; i++) {
		this.attachMovie("component_button_symbol", "button_" + i, i + 10, {
			width	: 19,
			height	: 17
		});
	}
		
	this.button_1.symbol = "icon_tree_button_new";
	this.button_1.tooltip = conf.lang.buttontip_tree_new;
	this.button_1.onClick = function() {
		this._parent.addColor();
	};
	
	this.button_2.symbol = "icon_tree_button_delete";
	this.button_2.tooltip = conf.lang.buttontip_tree_delete;
	this.button_2.onClick = function() {
		this._parent.deleteColor();
	};
};
// }}}
// {{{ generateColors()
class_propBox_proj_colorscheme.prototype.generateColors = function() {
	var tempNode, tempVal;
	var i = 0;
	var nodeArray = [];

	tempNode = this.data.firstChild;
	while (tempNode != null) {
		nodeArray.push(tempNode);
			
		tempNode = tempNode.nextSibling;	
	}
	nodeArray.sort(this.sortColors);
		
	for (var i = 1; i <= nodeArray.length; i++) {
		if (i > this.colorLineNum) {
			this.colorLineNum++;
			this.attachMovie("prop_tt_colorscheme_color", "colorBox" + this.colorLineNum, this.colorLineNum + 20);
			this["colorBox" + i].colorNode = nodeArray[i - 1];
		} else {
			this["colorBox" + i].colorNode = nodeArray[i - 1];
			this["colorBox" + i].onLoad();
		}
	}	
	
	tempVal = this.colorLineNum;
	this.colorLineNum = i - 1;
	for (var i; i <= tempVal; i++) {
		this["colorBox" + i].removeMovieClip();	
	}
};
// }}}
// {{{ sortColors
class_propBox_proj_colorscheme.prototype.sortColors = function(node1, node2) {
	if (node1.attributes.name.toLowerCase() > node2.attributes.name.toLowerCase()) {
		return 1;
	} else if (node1.attributes.name.toLowerCase() < node2.attributes.name.toLowerCase()) {
		return -1;
	} else {
		return 0;
	}
};
// }}}
// {{{ setComponents()
class_propBox_proj_colorscheme.prototype.setComponents = function() {
	var i, colNum, actualCol, actualRow, rows; 
	
	this.textBoxBack._x = this.settings.border_left + this.settings.gridsize * 2;
	this.textBoxBack._y = this.settings.border_top;
	this.textBoxBack._width = this.width - this.settings.border_left - this.settings.border_right - this.settings.gridsize * 2 - 1;	
	
	colNum = int(this.textBoxBack._width / this.minColorWidth);
	if (colNum == 0) {
		colNum = 1;	
	}
	this.colorWidth = int(this.textBoxBack._width / colNum);
	
	this.rowNum = Math.floor((this.colorLineNum - 1) / colNum) + 1;
	if (this.rowNum < 2) {
		this.rowNum = 2;	
	}
	for (var i = 1; i <= this.colorLineNum; i++) {
		actualCol = Math.floor((i - 1) / this.rowNum);
		actualRow = (i - 1) % this.rowNum;
		this["colorBox" + i]._x = this.settings.border_left + 5 + this.settings.gridsize * 2 + actualCol * this.colorWidth;
		this["colorBox" + i]._y = this.settings.border_top + 3 + actualRow * conf.interface.menu_line_height;
	}

	this.setHeight();
	this.setButtons();
};
// }}}
// {{{ setActiveColor()
class_propBox_proj_colorscheme.prototype.setActiveColor = function(colorNode) {
	var i;
	
	this.activeColor = colorNode;
	for (var i = 0; i <= this.colorLineNum; i++) {
		this["colorBox" + i].setStatus();
	}	
	this.setButtons();
};
// }}}
// {{{ setHeight()
class_propBox_proj_colorscheme.prototype.setHeight = function() {
	var temp;

	this.innerHeight = this.rowNum * conf.interface.menu_line_height + 7;
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
	
	this.textBoxBack._height = this.innerHeight - 5;
	
	this.button_1._x = this.settings.border_left + 2;
	this.button_2._x = this.settings.border_left + 2;
	
	super.setHeight();
	this._parent.setPropPos();
};
// }}}
// {{{ setNewPos()
class_propBox_proj_colorscheme.prototype.setNewPos = function() {
	var bottomBorder = this.height - this.settings.border_bottom - 2;
	
	super.setNewPos();
	
	if (this.getGlobalY() + bottomBorder > Stage.height - 7) {
		bottomBorder = Stage.height - 7 - this.getGlobalY();	
	}
	if (bottomBorder - 40 < this.settings.border_top) {
		bottomBorder = this.settings.border_top + 40;
	}
		
	this.button_1._y = bottomBorder - 38;
	this.button_2._y = bottomBorder - 17;
};
// }}}
// {{{ setButtons()
class_propBox_proj_colorscheme.prototype.setButtons = function() {
	var i;
	if (conf.user.mayAddDeleteColors()) {
		this.button_1.enabledState = true;
		if (this.activeColor != null) {
			this.button_2.enabledState = true;
		} else {
			this.button_2.enabledState = false;	
		}
	} else {
		this.button_1.enabledState = false;	
		this.button_2.enabledState = false;	
	}
	
	for (var i = 1; i <= 2; i++) {
		this["button_" + i].setStatus(this["button_" + i].enabledState);
	}
};
// }}}
// {{{ addColor()
class_propBox_proj_colorscheme.prototype.addColor = function() {
	if (conf.user.mayAddDeleteColors()) {
		conf.project.tree.colors.addColor(this.data);
	}
};
// }}}
// {{{ deleteColor()
class_propBox_proj_colorscheme.prototype.deleteColor = function() {
	if (conf.user.mayAddDeleteColors()) {
		conf.project.tree.colors.deleteColor(this.activeColor);
	}
};
// }}}

/*
 *	Class PropBox_proj_template_set
 *
 *	Extends class_propBox
 *	Interface to edit template_set settings
 */
// {{{ constructor
class_propBox_proj_template_set = function() {};
class_propBox_proj_template_set.prototype = new class_propBox();

class_propBox_proj_template_set.prototype.propName = [];
class_propBox_proj_template_set.prototype.propName[0] = conf.lang.prop_name_proj_template_set_encoding;
class_propBox_proj_template_set.prototype.propName[1] = conf.lang.prop_name_proj_template_set_method;
// }}}
// {{{ generateComponents()
class_propBox_proj_template_set.prototype.generateComponents = function() {
	this.attachMovie("component_comboBox", "comboBoxEncoding", 2, {
		values		: conf.output_encodings,
		selected	: 0
	});
	this.comboBoxEncoding.onChanged = function() {
		this._parent.onChanged();
		this._parent.save();
	};

	this.attachMovie("component_comboBox", "comboBoxMethod", 3, {
		values		: conf.output_methods,
		selected	: 0
	});
	this.comboBoxMethod.onChanged = function() {
		this._parent.onChanged();
		this._parent.save();
	};

	this.createTextField("explain", 5, 0, 0, 100, 100);
	this.explain.text = this.propName[1];
	this.explain.initFormat(conf.interface.textformat_component);
	
	this.attachMovie("component_checkBox", "checkBoxIndent", 6, {
		caption	: conf.lang.prop_tt_template_set_indent
	});
	this.checkBoxIndent.onChanged = function() {
		this._parent.onChanged();
		this._parent.save();
	};
};
// }}}
// {{{ setComponents()
class_propBox_proj_template_set.prototype.setComponents = function() {
	this.comboBoxEncoding._x = this.settings.border_left;
	this.comboBoxEncoding._y = this.settings.border_top;
	this.comboBoxEncoding.width = this.settings.gridSize * 6 - this.settings.border;
	
	this.comboBoxMethod._x = this.settings.border_left;
	this.comboBoxMethod._y = this.settings.border_top + int(conf.interface.component_height) + this.settings.border;
	this.comboBoxMethod.width = this.settings.gridSize * 6 - this.settings.border;
	
	this.checkBoxIndent._x = this.settings.border_left + this.settings.gridSize * 6 + this.settings.border;
	this.checkBoxIndent._y = this.settings.border_top + int(conf.interface.component_height) + this.settings.border;
	this.checkBoxIndent.width = this.settings.gridSize * 6 - this.settings.border;
	
	this.explain._x = this.settings.border;
	this.explain._y = this.settings.border_top + int(conf.interface.component_height) + this.settings.border;
	this.explain._width = this.settings.explanationWidth;

	this.innerHeight = (int(conf.interface.component_height) + this.settings.border) * 2;
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
};
// }}}
// {{{ setData()
class_propBox_proj_template_set.prototype.setData = function() {
	var i;

	super.setData();
	
	for (var i = 0; i < this.comboBoxEncoding.values.length; i++) {
		if (this.comboBoxEncoding.values[i] == this.data.attributes.encoding) {
			this.comboBoxEncoding.selected = i;
		}
	}
	this.comboBoxEncoding.select(this.comboBoxEncoding.selected);
	
	for (var i = 0; i < this.comboBoxMethod.values.length; i++) {
		if (this.comboBoxMethod.values[i] == this.data.attributes.method) {
			this.comboBoxMethod.selected = i;
		}
	}
	this.comboBoxMethod.select(this.comboBoxMethod.selected);
	
	this.checkBoxIndent.value = this.data.attributes.indent == "yes";
};
// }}}
// {{{ saveData()
class_propBox_proj_template_set.prototype.saveData = function(forceSave) {
	this.data.attributes.encoding = this.comboBoxEncoding.values[this.comboBoxEncoding.selected];
	this.data.attributes.method = this.comboBoxMethod.values[this.comboBoxMethod.selected];
	this.data.attributes.indent = this.checkBoxIndent.value ? "yes" : "no";

	return super.saveData(forceSave);
};
// }}}

/*
 *	Class PropBox_proj_global_file
 *
 *	Extends class_propBox
 *	Interface to edit global_file settings
 */
// {{{ constructor
class_propBox_proj_global_file = function() {};
class_propBox_proj_global_file.prototype = new class_propBox();

class_propBox_proj_global_file.prototype.propName = [];
class_propBox_proj_global_file.prototype.propName[0] = conf.lang.prop_name_proj_global_file_path; //@todo add name
class_propBox_proj_global_file.prototype.propName[1] = conf.lang.prop_name_proj_global_file_xsl_template; //@todo add name
// }}}
// {{{ generateComponents()
class_propBox_proj_global_file.prototype.generateComponents = function() {
	this.attachMovie("component_inputField", "inputBoxPath", 2);
	this.inputBoxPath.onChanged = function() {
		this._parent.onChanged();
	};
	this.inputBoxPath.onKillFocus = function() {
		//this._parent.save();
		updateAfterEvent();
	};
	this.inputBoxPath.onEnter = function() {
		this._parent.save();	
	};
	this.inputBoxPath.onCtrlS = function() {
		this._parent.save();	
	};
	this.attachMovie("component_inputField", "inputBoxXSLTemplate", 3);
	this.inputBoxXSLTemplate.onChanged = function() {
		this._parent.onChanged();
	};
	this.inputBoxXSLTemplate.onKillFocus = function() {
		//this._parent.save();
		updateAfterEvent();
	};
	this.inputBoxXSLTemplate.onEnter = function() {
		this._parent.save();	
	};
	this.inputBoxXSLTemplate.onCtrlS = function() {
		this._parent.save();	
	};

	this.createTextField("explain", 5, 0, 0, 100, 100);
	this.explain.text = this.propName[1];
	this.explain.initFormat(conf.interface.textformat_component);
};
// }}}
// {{{ setComponents()
class_propBox_proj_global_file.prototype.setComponents = function() {
	this.inputBoxPath._x = this.settings.border_left;
	this.inputBoxPath._y = this.settings.border_top;
	this.inputBoxPath.width = this.width - this.settings.border_left - this.settings.border_right;
			
	this.inputBoxXSLTemplate._x = this.settings.border_left;
	this.inputBoxXSLTemplate._y = this.settings.border_top + int(conf.interface.component_height) + this.settings.border;
	this.inputBoxXSLTemplate.width = this.width - this.settings.border_left - this.settings.border_right;
	
	this.explain._x = this.settings.border;
	this.explain._y = this.settings.border_top + int(conf.interface.component_height) + this.settings.border;
	this.explain._width = this.settings.explanationWidth;
			
	this.setHeight();
};
// }}}
// {{{ setHeight()
class_propBox_proj_global_file.prototype.setHeight = function() {
	this.innerHeight = 2 * this.settings.minInnerHeight;
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
	
	super.setHeight();
	this._parent.setPropPos();
};
// }}}
// {{{ setData()
class_propBox_proj_global_file.prototype.setData = function() {
	var i;

	super.setData();
	
        //this.setTitle(this.data.attributes.name);
	this.inputBoxPath.value = this.data.attributes.path;
	this.inputBoxXSLTemplate.value = this.data.attributes.xsl_template;
};
// }}}
// {{{ saveData()
class_propBox_proj_global_file.prototype.saveData = function(forceSave) {
	this.data.attributes.path = this.inputBoxPath.value;
	this.data.attributes.xsl_template = this.inputBoxXSLTemplate.value;

	return super.saveData(forceSave);
};
// }}}

/*
 *	Class PropBox_proj_publish_folder
 *
 *	Extends class_propBox
 *	Interface to edit publish settings and to publish
 */
// {{{ constructor
class_propBox_proj_publish_folder = function() {};
class_propBox_proj_publish_folder.prototype = new class_propBox();

class_propBox_proj_publish_folder.prototype.propName = [];
class_propBox_proj_publish_folder.prototype.propName[0] = conf.lang.prop_name_proj_publish;
// }}}
// {{{ generateComponents()
class_propBox_proj_publish_folder.prototype.generateComponents = function() {
	this.attachMovie("component_inputField", "inputBoxTargetPath", 2, {
		explain	: conf.lang.prop_tt_publish_folder_targetpath
	});
	this.inputBoxTargetPath.onChanged = function() {
		this._parent.onChanged();
	};
	this.inputBoxTargetPath.onKillFocus = function() {
		//this._parent.save();	
	};
	this.inputBoxTargetPath.onEnter = function() {
		this._parent.save();	
	};
	this.inputBoxTargetPath.onCtrlS = function() {
		this._parent.save();	
	};

	this.attachMovie("component_inputField", "inputBoxBaseURL", 3, {
		explain	: conf.lang.prop_tt_publish_folder_baseurl
	});
	this.inputBoxBaseURL.onChanged = function() {
		this._parent.onChanged();
	};
	this.inputBoxBaseURL.onKillFocus = function() {
		//this._parent.save();	
	};
	this.inputBoxBaseURL.onEnter = function() {
		this._parent.save();	
	};
	this.inputBoxBaseURL.onCtrlS = function() {
		this._parent.save();	
	};


	this.attachMovie("component_inputField", "inputBoxUser", 4, {
		explain	: conf.lang.prop_tt_publish_folder_user
	});
	this.inputBoxUser.onChanged = function() {
		this._parent.onChanged();
	};
	this.inputBoxUser.onKillFocus = function() {
		//this._parent.save();	
	};
	this.inputBoxUser.onEnter = function() {
		this._parent.save();	
	};
	this.inputBoxUser.onCtrlS = function() {
		this._parent.save();	
	};

	this.attachMovie("component_inputField", "inputBoxPass", 5, {
		explain		: conf.lang.prop_tt_publish_folder_pass,
		password	: true
	});
	this.inputBoxPass.onChanged = function() {
		this._parent.onChanged();
	};
	this.inputBoxPass.onKillFocus = function() {
		//this._parent.save();	
	};
	this.inputBoxPass.onEnter = function() {
		this._parent.save();	
	};
	this.inputBoxPass.onCtrlS = function() {
		this._parent.save();	
	};

	this.attachMovie("component_comboBox", "comboBoxTemplateSet", 6, {
		values		: conf.project.tree.settings.templateSets,
		selected	: 0
	});
	this.comboBoxTemplateSet.onChanged = function() {
		this._parent.onChanged();
		this._parent.save();
	};
	
	this.attachMovie("component_button", "buttonStart", 7);
	this.buttonStart.onClick = function() {
		this._parent.handleTaskProgress({
			progress_percent	: 0,
			time_until_end		: -1000000000
		});
		
		conf.project.publishProject(this._parent.data.nid);
	};

        this.attachMovie("component_checkBox", "checkBoxModRewrite", 8);
        this.checkBoxModRewrite.onChanged = function() {
		this._parent.onChanged();
		this._parent.save();
        }
	
	this.attachMovie("component_progress_bar", "progressBar", 10);
	
	this.createTextField("progressField", 11, 0, 0, 50, 100);
	this.progressField.initFormat(conf.interface.textformat);
	this.progressField.type = "dynamic";
	this.progressField.html = true;
	this.progressField.wordWrap = true;
	
	conf.project.addTaskHandler(this, this.handleTaskProgress, "publish project");
	
	activeTasks = conf.project.getActiveTasks("publish project");
	if (activeTasks.length > 0) {
		this.handleTaskProgress(activeTasks[0]);
	} else {
		this.handleTaskProgress();
	}
};
// }}}
// {{{ handleTaskProgress()
class_propBox_proj_publish_folder.prototype.handleTaskProgress = function(taskHandler) {
	if (taskHandler == undefined) {
		this.buttonStart._visible = true;
		this.inputBoxTargetPath._visible = true;
		this.inputBoxBaseURL._visible = true;
		this.inputBoxUser._visible = true;
		this.inputBoxPass._visible = true;
		this.comboBoxTemplateSet._visible = true;
                this.checkBoxModRewrite._visible = true;
		this.progressBar._visible = false;
		this.progressField._visible = false;
	} else {
		this.progressBar.taskHandler = taskHandler;
		this.progressBar.setStatus();
		
		var progressText = conf.lang.prop_tt_publish_folder_progress + "\n";
		var timeRemaining = taskHandler.time_until_end;
		if (timeRemaining < 0) {
			timeRemaining = conf.lang.time_calculating;
		} else if (timeRemaining > 120) {
			timeRemaining = int(timeRemaining / 60) + " " + conf.lang.time_min;
		} else {
			timeRemaining = int(timeRemaining) + " " + conf.lang.time_sec;
		}
		progressText = progressText.replace([
			["%description%"	, taskHandler.description.replaceInterfaceTexts()],
			["%remaining%"		, timeRemaining],
			["%percent%"		, int(taskHandler.progress_percent) + "%"]
		]);
		
		this.progressField.htmlText = progressText;
		this.progressField.initFormat(conf.interface.textformat);
		
		if (taskHandler.progress_percent == 100) {
			setTimeout(this.handleTaskProgress, this, 3000);
		}
		
		this.buttonStart._visible = false;
		this.inputBoxTargetPath._visible = false;
		this.inputBoxBaseURL._visible = false;
		this.inputBoxUser._visible = false;
		this.inputBoxPass._visible = false;
		this.comboBoxTemplateSet._visible = false;
                this.checkBoxModRewrite._visible = false;
		this.progressBar._visible = true;
		this.progressField._visible = true;
	}
};
// }}}
// {{{ setComponents()
class_propBox_proj_publish_folder.prototype.setComponents = function() {
    //targetPath
    this.inputBoxTargetPath._x = this.settings.border_left;
    this.inputBoxTargetPath._y = this.settings.border_top;
    this.inputBoxTargetPath.width = this.width - this.settings.border_left - this.settings.border_right;
    
    // baseURL
    this.inputBoxBaseURL._x = this.settings.border_left;
    this.inputBoxBaseURL._y = this.settings.border_top + int(conf.interface.component_height) + this.settings.border;
    this.inputBoxBaseURL.width = this.width - this.settings.border_left - this.settings.border_right;

    //user
    this.inputBoxUser._x = this.settings.border_left;
    this.inputBoxUser._y = this.settings.border_top + 2 * (int(conf.interface.component_height) + this.settings.border);
    this.inputBoxUser.width = (this.width - this.settings.border_left - this.settings.border_right) / 2;
    
    //pass
    this.inputBoxPass._x = this.inputBoxUser._x + this.inputBoxUser.width + 6;
    this.inputBoxPass._y =  this.settings.border_top + 2 * (int(conf.interface.component_height) + this.settings.border);
    this.inputBoxPass.width = (this.width - this.settings.border_left - this.settings.border_right) / 2 - 6;
    
    //templateset
    this.comboBoxTemplateSet._x = this.settings.border_left;
    this.comboBoxTemplateSet._y = this.settings.border_top + 4 * (int(conf.interface.component_height) + this.settings.border);
    this.comboBoxTemplateSet.width = this.inputBoxUser.width;

    //modrewrite
    this.checkBoxModRewrite._x = this.settings.border_left;
    this.checkBoxModRewrite._y = this.settings.border_top + 5 * (int(conf.interface.component_height) + this.settings.border);
    this.checkBoxModRewrite.width = this.settings.gridSize * 6 - this.settings.border;
    this.checkBoxModRewrite.caption = "mod_rewrite";

    //other
    this.buttonStart._x = this.width - this.settings.border_right;
    this.buttonStart._y = this.settings.border_top + 7 * (int(conf.interface.component_height) + 7);
    this.buttonStart.caption = conf.lang.prop_tt_publish_folder_button_start;
    this.buttonStart.align = "TR";
    
    this.progressBar._x = this.settings.border_left;
    this.progressBar._y = this.settings.border_top + 2;
    this.progressBar.width = this.width - this.settings.border_left - this.settings.border_right;
    this.progressBar.setWidth();
    
    this.progressField._x = this.settings.border_left;
    this.progressField._y = this.settings.border_top + int(conf.interface.component_height) + 10;
    this.progressField._width = this.width - this.settings.border_left - this.settings.border_right;
    
    this.innerHeight = this.settings.border_top + (int(conf.interface.component_height) + this.settings.border) * 8.5;
    this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
};
// }}}
// {{{ setData()
class_propBox_proj_publish_folder.prototype.setData = function() {
	var i;

	this.inputBoxTargetPath.value = this.data.attributes.output_folder;
	this.inputBoxBaseURL.value = this.data.attributes.baseurl;
	this.inputBoxUser.value = this.data.attributes.output_user;
	this.inputBoxPass.value = this.data.attributes.output_pass;
	
	for (var i = 0; i < this.comboBoxTemplateSet.values.length; i++) {
		if (this.data.attributes.template_set == this.comboBoxTemplateSet.values[i]) {
			this.comboBoxTemplateSet.selected = i;
		}
	}
	this.comboBoxTemplateSet.select(this.comboBoxTemplateSet.selected);

        this.checkBoxModRewrite.value = this.data.attributes.mod_rewrite == "true";
};
// }}}
// {{{ saveData()
class_propBox_proj_publish_folder.prototype.saveData = function(forceSave) {
	this.data.attributes.output_folder = this.inputBoxTargetPath.value;
	this.data.attributes.baseurl = this.inputBoxBaseURL.value;
	this.data.attributes.output_user = this.inputBoxUser.value;
	this.data.attributes.output_pass = this.inputBoxPass.value;
	this.data.attributes.template_set = this.comboBoxTemplateSet.values[this.comboBoxTemplateSet.selected];
        if (this.checkBoxModRewrite.value) {
            this.data.attributes.mod_rewrite = "true";
        } else {
            this.data.attributes.mod_rewrite = "false";
        }

	 return super.saveData(forceSave);
};
// }}}
// {{{ onUnload()
class_propBox_proj_publish_folder.prototype.onUnload = function() {
	conf.project.removeTaskHandler(this, this.handleTaskProgress);
	
	this._parent.onUnload();
};
// }}}

/*
 *	Class PropBox_proj_backup_backup
 *
 *	Extends class_propBox
 *	Interface to backup Database/Library
 */
// {{{ constructor
class_propBox_proj_backup_backup = function() {};
class_propBox_proj_backup_backup.prototype = new class_propBox();

class_propBox_proj_backup_backup.prototype.propName = [];
class_propBox_proj_backup_backup.prototype.propName[0] = conf.lang.prop_name_proj_bak_backup_auto;
class_propBox_proj_backup_backup.prototype.propName[1] = conf.lang.prop_name_proj_bak_backup_man;

class_propBox_proj_backup_backup.prototype.showSaver = false;
// }}}
// {{{ generateComponents()
class_propBox_proj_backup_backup.prototype.generateComponents = function() {
	var activeTasks;

	this.attachMovie("component_button", "buttonStart", 2);
	this.buttonStart.onClick = function() {
		this._parent.handleTaskProgress({
			progress_percent	: 0,
			time_until_end		: -1000000000
		});		

		var type;
		if (this._parent.comboBox.selected == 1) {
			type = "data";
		} else if (this._parent.comboBox.selected == 2) {
			type = "lib";
		} else {
			type = "all";
		}
		
		conf.project.backupProject(type, this._parent.inputBox.value);
	};
	
	this.attachMovie("component_inputField", "inputBox", 3, {
		explain	: conf.lang.all_comment
	});
	this.inputBox.onChanged = function() {
	};
	this.inputBox.onKillFocus = function() {
	};
	this.inputBox.onEnter = function() {
	};
	this.inputBox.onCtrlS = function() {
	};
	
	this.attachMovie("component_comboBox", "comboBox", 4, {
		values		: [conf.lang.prop_tt_bak_backup_type_all, conf.lang.prop_tt_bak_backup_type_data, conf.lang.prop_tt_bak_backup_type_lib],
		selected	: 0
	});
	this.comboBox.onChanged = function() {
	};
	
	this.attachMovie("component_progress_bar", "progressBar", 10);
	
	this.createTextField("progressField", 11, 0, 0, 50, 100);
	this.progressField.initFormat(conf.interface.textformat);
	this.progressField.type = "dynamic";
	this.progressField.html = true;
	this.progressField.wordWrap = true;
	
	conf.project.addTaskHandler(this, this.handleTaskProgress, "backup project");
	
	activeTasks = conf.project.getActiveTasks("backup project");
	if (activeTasks.length > 0) {
		this.handleTaskProgress(activeTasks[0]);
	} else {
		this.handleTaskProgress();
	}
};
// }}}
// {{{ handleTaskProgress()
class_propBox_proj_backup_backup.prototype.handleTaskProgress = function(taskHandler) {
	if (taskHandler == undefined) {
		this.buttonStart._visible = true;
		this.inputBox._visible = true;
		this.comboBox._visible = true;
		this.progressBar._visible = false;
		this.progressField._visible = false;
	} else {
		this.progressBar.taskHandler = taskHandler;
		this.progressBar.setStatus();
		
		var progressText = conf.lang.prop_tt_bak_backup_progress;
		var timeRemaining = int(taskHandler.time_until_end);
		if (timeRemaining < 0) {
			timeRemaining = conf.lang.time_calculating;
		} else if (timeRemaining > 120) {
			timeRemaining = int(timeRemaining / 60) + " " + conf.lang.time_min;
		} else {
			timeRemaining = int(timeRemaining) + " " + conf.lang.time_sec;
		}
		progressText = progressText.replace([
			["%description%"	, taskHandler.description.replaceInterfaceTexts()],
			["%remaining%"		, timeRemaining],
			["%percent%"		, int(taskHandler.progress_percent) + "%"]
		]);
		
		this.progressField.htmlText = progressText;
		this.progressField.initFormat(conf.interface.textformat);
		
		if (taskHandler.progress_percent == 100) {
			setTimeout(this.handleTaskProgress, this, 3000);
		}
		
		this.buttonStart._visible = false;
		this.inputBox._visible = false;
		this.comboBox._visible = false;
		this.progressBar._visible = true;
		this.progressField._visible = true;
	}
};
// }}}
// {{{ setComponents()
class_propBox_proj_backup_backup.prototype.setComponents = function() {
	this.buttonStart._x = this.width - this.settings.border_right;
	this.buttonStart._y = this.settings.border_top + 2*(int(conf.interface.component_height) + 7);
	this.buttonStart.caption = conf.lang.prop_tt_bak_backup_button_start;
	this.buttonStart.align = "TR";
	
	this.inputBox._x = this.settings.border_left;
	this.inputBox._y = this.settings.border_top;
	this.inputBox.width = this.width - this.settings.border_right - this.settings.border_left;
	
	this.comboBox._x = this.settings.border_left;
	this.comboBox._y = this.settings.border_top + int(conf.interface.component_height) + this.settings.border;
	this.comboBox.width = this.width - this.settings.border_right - this.settings.border_left - this.settings.explanationWidth;
	
	this.progressBar._x = this.settings.border_left;
	this.progressBar._y = this.settings.border_top + 2;
	this.progressBar.width = this.width - this.settings.border_left - this.settings.border_right;
	this.progressBar.setWidth();
	
	this.progressField._x = this.settings.border_left;
	this.progressField._y = this.settings.border_top + int(conf.interface.component_height) + 10;
	this.progressField._width = this.width - this.settings.border_left - this.settings.border_right;
	
	this.innerHeight = (int(conf.interface.component_height) + this.settings.border) * 3;
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
};
// }}}
// {{{ onUnload()
class_propBox_proj_backup_backup.prototype.onUnload = function() {
	conf.project.removeTaskHandler(this, this.handleTaskProgress);
	
	this._parent.onUnload();
};
// }}}

/*
 *	Class PropBox_proj_backup_restore
 *
 *	Extends class_propBox
 *	Interface to restore Database/Library
 */
// {{{ constructor
class_propBox_proj_backup_restore = function() {};
class_propBox_proj_backup_restore.prototype = new class_propBox();

class_propBox_proj_backup_restore.prototype.propName = [];
class_propBox_proj_backup_restore.prototype.propName[0] = conf.lang.prop_name_proj_bak_restore_data;
class_propBox_proj_backup_restore.prototype.propName[1] = conf.lang.prop_name_proj_bak_restore_lib;

class_propBox_proj_backup_restore.prototype.showSaver = false;
// }}}
// {{{ generateComponents()
class_propBox_proj_backup_restore.prototype.generateComponents = function() {
	var i;
	
	conf.project.getBackupFiles(this, this.setMenuData);
	
	this.restoreDBTypes = ["settings", "pages", "colorschemes", "templates"];
	this.backupsDB = backupsDB;
	this.backupsLib = backupsLib;

	//restore DB	
	this.attachMovie("component_button", "buttonStartDB", 2);
	this.buttonStartDB.onClick = function() {
		this._parent.restoreDB();
	};
	this.buttonStartDB.enabledState = false;
	
	this.attachMovie("component_comboBox", "chooseFileDB", 3, {
		selected		: 0,
		values			: [conf.lang.tree_nodata],
		enabledState	: false
	});
	this.chooseFileDB.onChanged = function() {
	
	};
	
	this.attachMovie("rectangle", "textBoxBack", 4);
	this.textBoxBack.back.setRGB(conf.interface.color_component_face);
	this.textBoxBack.outline.setRGB(conf.interface.color_component_line);
	
	for (var i = 1; i <= this.restoreDBTypes.length; i++) {
		this.attachMovie("component_checkBox", "checkBox" + i, i + 4, {
			caption			: conf.lang["prop_tt_bak_restore_db_" + this.restoreDBTypes[i - 1]],
			enabledState	: false
		});
		this["checkBox" + i].onChanged = function() {
			
		};
	}	
	
	//restore Lib
	this.createTextField("textBox", 19, 0, 0, 200, 20);
	this.textBox.initFormat(conf.interface.textformat);
	this.textBox.wordWrap = false;
	this.textBox.selectable = false;
	this.textBox.text = this.propName[1];
	
	this.attachMovie("component_button", "buttonStartLib", 20, {
		enabledState	: false
	});
	this.buttonStartLib.onClick = function() {
		this._parent.restoreLib();
	};

	this.attachMovie("component_comboBox", "chooseFileLib", 21, {
		selected		: 0,
		values			: [conf.lang.tree_nodata],
		enabledState	: false
	});
	this.chooseFileLib.onChanged = function() {
	
	};
	
	this.attachMovie("component_checkBox", "checkBoxClear", 22, {
		caption			: conf.lang.prop_tt_bak_restore_overwrite,
		enabledState	: false
	});
	this.checkBoxClear.onChanged = function() {
		
	};
	
	//progress
	this.attachMovie("component_progress_bar", "progressBar", 30);
	
	this.createTextField("progressField", 11, 0, 0, 50, 100);
	this.progressField.initFormat(conf.interface.textformat);
	this.progressField.type = "dynamic";
	this.progressField.html = true;
	this.progressField.wordWrap = true;
	
	conf.project.addTaskHandler(this, this.handleTaskProgress, "restore project");
	
	activeTasks = conf.project.getActiveTasks("restore project");
	if (activeTasks.length > 0) {
		this.handleTaskProgress(activeTasks[0]);
	} else {
		this.handleTaskProgress();
	}
};
// }}}
// {{{ setMenuData()
class_propBox_proj_backup_restore.prototype.setMenuData = function(backupsDB, backupsLib) {
	var i;

	this.backupsDB = backupsDB;
	this.backupsLib = backupsLib;

	this.chooseFileDB.values = [];
	for (var i = 0; i < this.backupsDB.length; i++) {
		if (this.backupsDB[i].name == "backup_dev.xml") {
			this.chooseFileDB.values.push("developer backup");
		} else {
			var comment = this.backupsDB[i].comment;
			this.chooseFileDB.values.push(getLocalDate(this.backupsDB[i].date) + " " + (comment != "" ? "[\"" + comment + "\"]" : ""));
		}
	}

	this.chooseFileLib.values = [];
	for (var i = 0; i < this.backupsLib.length; i++) {
		this.chooseFileLib.values.push(getLocalDate(this.backupsLib[i].date));
	}
	
	this.chooseFileDB.enabledState = true;
	this.chooseFileDB.init();
	this.chooseFileLib.enabledState = true;
	this.chooseFileLib.init();
	
	this.buttonStartDB.setEnabled(true);
	for (var i = 1; i <= this.restoreDBTypes.length; i++) {
		this["checkBox" + i].setEnabled(true);
	}
	
	this.buttonStartLib.setEnabled(true);
	this.checkBoxClear.setEnabled(true);
};
// }}}
// {{{ handleTaskProgress()
class_propBox_proj_backup_restore.prototype.handleTaskProgress = function(taskHandler) {
	if (taskHandler == undefined) {
		this.buttonStartDB._visible = true;
		this.chooseFileDB._visible = true;
		this.textBoxBack._visible = true;
		for (var i = 1; i <= this.restoreDBTypes.length; i++) {
			this["checkBox" + i]._visible = true;
		}
		this.buttonStartLib._visible = true;
		this.chooseFileLib._visible = true;
		this.checkBoxClear._visible = true;
		
		this.progressBar._visible = false;
		this.progressField._visible = false;
	} else {
		this.progressBar.taskHandler = taskHandler;
		this.progressBar.setStatus();
		
		var progressText = conf.lang.prop_tt_bak_restore_progress;
		var timeRemaining = int(taskHandler.time_until_end);
		if (timeRemaining < 0) {
			timeRemaining = conf.lang.time_calculating;
		} else if (timeRemaining > 120) {
			timeRemaining = int(timeRemaining / 60) + " " + conf.lang.time_min;
		} else {
			timeRemaining = int(timeRemaining) + " " + conf.lang.time_sec;
		}
		progressText = progressText.replace([
			["%description%"	, taskHandler.description.replaceInterfaceTexts()],
			["%remaining%"		, timeRemaining],
			["%percent%"		, int(taskHandler.progress_percent) + "%"]
		]);
	
		this.buttonStartDB._visible = false;
		this.chooseFileDB._visible = false;
		this.textBoxBack._visible = false;
		for (var i = 1; i <= this.restoreDBTypes.length; i++) {
			this["checkBox" + i]._visible = false;
		}
		this.buttonStartLib._visible = false;
		this.chooseFileLib._visible = false;
		this.checkBoxClear._visible = false;
		
		this.progressBar._visible = true;
		this.progressField._visible = true;
	}
};
// }}}
// {{{ setComponents()
class_propBox_proj_backup_restore.prototype.setComponents = function() {
	//restore DB
	this.chooseFileDB._x = this.settings.border_left;
	this.chooseFileDB._y = this.settings.border_top;
	this.chooseFileDB.width = this.width - this.settings.border_right - this.settings.border_left;
	
	this.textBoxBack._x = this.settings.border_left;
	this.textBoxBack._y = this.settings.border_top + int(conf.interface.component_height) + 7;
	this.textBoxBack._width = this.width - this.settings.border_right - this.settings.border_left;
	this.textBoxBack._height = this.restoreDBTypes.length * conf.interface.menu_line_height + 3;

	for (var i = 1; i <= this.restoreDBTypes.length; i++) {
		this["checkBox" + i]._x = this.settings.border_left + 5;
		this["checkBox" + i]._y = this.settings.border_top + (i - 1) * conf.interface.menu_line_height + int(conf.interface.component_height) + 7;
		this["checkBox" + i].width = this.width - (this.settings.border_left + this.settings.border_right + 10);
		this["checkBox" + i].setWidth();
		this["checkBox" + i].value = true;
	}
	
	this.buttonStartDB._x = this.width - this.settings.border_right;
	this.buttonStartDB._y = this.settings.border_top + this.textBoxBack._height + (int(conf.interface.component_height) + 7) + 7;
	this.buttonStartDB.caption = conf.lang.prop_tt_bak_restore_button_start;
	this.buttonStartDB.align = "TR";

	//restoreLib
	var restoreLibOffset = this.settings.border_top + this.textBoxBack._height + 2 * (int(conf.interface.component_height) + 7) + 7;
	
	this.textBox._x = 5;
	this.textBox._y = 3 + this.settings.border_top + restoreLibOffset;
	this.textBox._width = this.settings.border_left - 10;

	this.chooseFileLib._x = this.settings.border_left;
	this.chooseFileLib._y = this.settings.border_top + restoreLibOffset;
	this.chooseFileLib.width = this.width - this.settings.border_right - this.settings.border_left;
	
	this.checkBoxClear._x = this.settings.border_left;
	this.checkBoxClear._y = this.settings.border_top + restoreLibOffset + (int(conf.interface.component_height) + 7);
	this.checkBoxClear.width = this.width - this.settings.border_right - this.settings.border_left;
	this.checkBoxClear.setWidth();
	
	this.buttonStartLib._x = this.width - this.settings.border_right;
	this.buttonStartLib._y = this.settings.border_top + 2*(int(conf.interface.component_height) + this.settings.border) + restoreLibOffset;
	this.buttonStartLib.caption = conf.lang.prop_tt_bak_restore_button_start;
	this.buttonStartLib.align = "TR";
	
	//progress
	this.progressBar._x = this.settings.border_left;
	this.progressBar._y = this.settings.border_top + 2;
	this.progressBar.width = this.width - this.settings.border_left - this.settings.border_right;
	this.progressBar.setWidth();
	
	this.progressField._x = this.settings.border_left;
	this.progressField._y = this.settings.border_top + int(conf.interface.component_height) + 10;
	this.progressField._width = this.width - this.settings.border_left - this.settings.border_right;
	
	this.innerHeight = (int(conf.interface.component_height) + this.settings.border) * 3 + restoreLibOffset;
	this.height = this.innerHeight + this.settings.border_top + this.settings.border_bottom;
};
// }}}
// {{{ restoreDB()
class_propBox_proj_backup_restore.prototype.restoreDB = function() {
	var options = [];
	
	for (var i = 1; i <= this.restoreDBTypes.length; i++) {
		if (this["checkBox" + i].value) {
			options.push(this.restoreDBTypes[i - 1]);
		}
	}
	conf.project.restoreProject("data", this.backupsDB[this.chooseFileDB.selected].name, options);
};
// }}}
// {{{ restoreLib()
class_propBox_proj_backup_restore.prototype.restoreLib = function() {
	var options = [];
	
	if (this.checkBoxClear.value) {
		options.push("clear");
	}
	conf.project.restoreProject("lib", this.backupsLib[this.chooseFileLib.selected].name, options);
};
// }}}

/*
 *	register Prop Classes
 */
// {{{ page_data
Object.registerClass("prop_pg_date", class_propBox_pg_date);
Object.registerClass("prop_pg_colorscheme", class_propBox_pg_colorscheme);
Object.registerClass("prop_pg_navigation", class_propBox_pg_navigation);
Object.registerClass("prop_pg_file", class_propBox_pg_file);

Object.registerClass("prop_pg_title", class_propBox_pg_title);
Object.registerClass("prop_pg_linkdesc", class_propBox_pg_linkdesc);
Object.registerClass("prop_pg_desc", class_propBox_pg_desc);

Object.registerClass("prop_edit_text_singleline", class_propBox_edit_text_singleline);
Object.registerClass("prop_edit_text_multiline", class_propBox_edit_text_multiline);

Object.registerClass("prop_edit_plain_source", class_propBox_edit_plain_source);
Object.registerClass("prop_edit_text_formatted", class_propBox_edit_text_formatted);
Object.registerClass("prop_edit_list_formatted", class_propBox_edit_list_formatted);
Object.registerClass("prop_edit_text_headline", class_propBox_edit_text_headline);
Object.registerClass("prop_edit_a", class_propBox_edit_a);
Object.registerClass("prop_edit_img", class_propBox_edit_img);
Object.registerClass("prop_edit_audio", class_propBox_edit_audio);
Object.registerClass("prop_edit_video", class_propBox_edit_video);
Object.registerClass("prop_edit_flash", class_propBox_edit_flash);
Object.registerClass("prop_edit_date", class_propBox_edit_date);
Object.registerClass("prop_edit_time", class_propBox_edit_time);
Object.registerClass("prop_edit_colorscheme", class_propBox_edit_colorscheme);
Object.registerClass("prop_edit_table", class_propBox_edit_table);
Object.registerClass("prop_edit_type", class_propBox_edit_type);
Object.registerClass("prop_edit_icon", class_propBox_edit_icon);
// }}}
// {{{ colorschemes
Object.registerClass("prop_proj_colorscheme", class_propBox_proj_colorscheme);
// }}}
// {{{ files
Object.registerClass("prop_proj_filelist", class_propBox_proj_filelist);
// }}}
// {{{ templates
Object.registerClass("prop_edit_template", class_propBox_edit_template);
Object.registerClass("prop_pg_template_data", class_propBox_pg_template_data);
// }}}
// {{{ newnodes
Object.registerClass("prop_edit_newnode", class_propBox_edit_newnode);
Object.registerClass("prop_edit_newnode_valid_parents", class_propBox_edit_newnode_valid_parents);
// }}}
// {{{ settings
Object.registerClass("prop_proj_language", class_propBox_proj_language);
Object.registerClass("prop_proj_navigation", class_propBox_proj_navigation);
Object.registerClass("prop_proj_variable", class_propBox_proj_variable);
Object.registerClass("prop_proj_template_set", class_propBox_proj_template_set);
Object.registerClass("prop_proj_global_file", class_propBox_proj_global_file);
Object.registerClass("prop_proj_publish_folder", class_propBox_proj_publish_folder);
Object.registerClass("prop_proj_backup_backup", class_propBox_proj_backup_backup);
Object.registerClass("prop_proj_backup_restore", class_propBox_proj_backup_restore);
// }}}

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
