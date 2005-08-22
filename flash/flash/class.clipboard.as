/*
 *	Class Clipboard
 *
 *	Handles clipboard cut, copy and paste via System-Clipboard
 *	Clipboard-Content has to be ttRpcMessageHandler-formatted
 */
// {{{ constructor
class_clipboard = function(clipboardField) {
	_root.activeComponent = null;
	this.clipboardField = clipboardField;
	this.clipboardField.type = "input";
	this.clipboardField.setHandler = function(handler, value) {
		this.obj.setHandler(handler, value);
	};
	this.clipboardField.removeHandler = function() {
		this.obj.removeHandler();
	};
};
// }}}
// {{{ setHandler()
class_clipboard.prototype.setHandler = function(handler, value) {
	if (handler == null) {
		this.removeHandler();	
	} else {
		this.handler = handler;
		this.value = value;
		this.clipboardField.text = this.value;
		Mouse.addListener(this);
		Key.addListener(this);
	
		this.setSelectInterval();
	}
};
// }}}
// {{{ setSelectInterval()
class_clipboard.prototype.setSelectInterval = function() {
	if (this.intervalId == null) {
		this.intervalId = setInterval(this, "selectAll", 50);
	}
};
// }}}
// {{{ clearSelectInterval()
class_clipboard.prototype.clearSelectInterval = function() {
	clearInterval(this.intervalId);
	this.intervalId = null;
};
// }}}
// {{{ removeHandler()
class_clipboard.prototype.removeHandler = function() {
	this.clearSelectInterval();
	Mouse.removeListener(this);
	Key.removeListener(this);
	this.handler = null;
	this.value = "";
	this.clipboardField.text = "";
};
// }}}
// {{{ onMouseDown()
class_clipboard.prototype.onMouseDown = function() {
	this.clearSelectInterval();
};
// }}}
// {{{ onMouseUp()
class_clipboard.prototype.onMouseUp = function() {
	if (_root.activeComponent != null && this.handler != null) {
		this.selectClipboard();		
		this.setSelectInterval();
	} else {
		this.removeHandler();
	}
	//status("activeComponent: " + _root.activeComponent + " - " + this.handler);
}; 
// }}}
// {{{ onKeyUp()
class_clipboard.prototype.onKeyUp = function() {
	if (Key.getCode() == Key.BACKSPACE || Key.getCode() == Key.DELETEKEY) {
		this.onDelete();
	} else if (Key.isDown(Key.CONTROL)) {
		if (Key.getCode() == 67) {
			this.onCopy();
		} else if (Key.getCode() == 88) {
			this.onCut();
		} else if (Key.getCode() == 86) {
			this.onPaste();
		} else {
			this.clipboardField.text = this.value;
		}
	} else {
		this.clipboardField.text = this.value;
	}
	updateAfterEvent();
};
// }}}
// {{{ selectAll()
class_clipboard.prototype.selectAll = function() {
	Selection.setSelection(0, this.clipboardField.text.length);
};
// }}}
// {{{isValidInsert()
class_clipboard.prototype.isValidInsert = function() {
	var testXML = new XML(this.clipboardField.text);
	
	if (testXML.status == 0) {
		testXML = testXML.getRootNode();
		if (testXML.nodeName == conf.nsrpc + ":msg") {
			return true;	
		} else {
			return false;	
		}
	} else {
		return false;	
	}
};
// }}}
// {{{ onPaste()
class_clipboard.prototype.onPaste = function() {
	this.handler.onPaste(this.clipboardField.text);
	alert(":: " + this.clipboardField.text);
};
// }}}
// {{{ onCut()
class_clipboard.prototype.onCut = function() {
	this.handler.onCut();
};
// }}}
// {{{ onnDelete()
class_clipboard.prototype.onDelete = function() {
	this.handler.onDelete();
};
// }}}
// {{{ onCopy()
class_clipboard.prototype.onCopy = function() {
	this.handler.onCopy();
};
// }}}
// {{{ selectClipboard()
class_clipboard.prototype.selectClipboard = function() {
	Selection.setFocus(this.clipboardField);
};
// }}}

/**
 * functions
 */
// {{{ createClipboard()
function createClipboard(level) {
	createTextField("clipboard", level, 10, -40, 1, 1);
	clipboard.obj = new class_clipboard(clipboard);
}
// }}}

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
