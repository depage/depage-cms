/**
 * C L A S S 
 * Class pocketConnect
 *
 * actionScript-library:
 * (c)2003-2010 Frank Hellenkamp [jonas@depagecms.net]
 */

#include "class.ttRpcMsgHandler.as"

/*
 *	Class pocketConnect
 */
// {{{ constructor
class_pocketConnect = function(host, port, ns_rpc, ns_rpc_uri) {
	this.connection = new XMLSocket();
	this.host = host;
	this.port = port;
	this.connected = false;
	this.onSuccessHandler = null;
	this.onFaultHandler = null;
	this.onDataHandler = null;
	this.onCloseHandler = null;
	this.connectFaults = 0;
	this.msgHandler = new class_ttRpcMsgHandler(ns_rpc, ns_rpc_uri);
}
// }}}
// {{{ connect()
class_pocketConnect.prototype.connect = function() {
	if (!this.connected) {
		this.connection.onConnect = this.onConnect;
		this.connection.onData = this.onData;
		this.connection.onClose = this.onClose;
		this.connection.connObj = this;
		this.connection.connect(this.host, this.port);
	}
}
// }}}
// {{{ close()
class_pocketConnect.prototype.close = function() {
	this.connected = false;
	this.connection.close();	
}
// }}}
// {{{ onConnect()
class_pocketConnect.prototype.onConnect = function(success) {
	if (success) {
		this.connObj.connected = true;
		this.connObj.onSuccessHandler();
	} else {
		this.connObj.connectFaults++;
		this.connObj.onFaultHandler();	
	}
	updateAfterEvent();
}
// }}}
// {{{ send()
class_pocketConnect.prototype.send = function(name, args) {
	var func = this.msgHandler.create_func(name, args)
	var msg = this.msgHandler.create_msg([func]);
	
	if (this.connected) {
		if (name != "sendKeepAlive") {
			_root.interface.connection_indicate_plus();
			setTimeout(_root.interface.connection_indicate_minus, null, 300);
		}
		this.connection.send(msg);
	}
}
// }}}
// {{{ onData()
class_pocketConnect.prototype.onData = function(msg) {
	_root.interface.connection_indicate_plus();
	setTimeout(_root.interface.connection_indicate_minus, null, 50, false);
	this.connObj.msgHandler.call(msg);
	updateAfterEvent();
}
// }}}
// {{{ onClose()
class_pocketConnect.prototype.onClose = function() {
	this.connected = false;
	this.connObj.onCloseHandler();
	updateAfterEvent();
}
// }}}

/*
 *	Class phpConnect
 */
// {{{ constructor
class_phpConnect = function(ns_rpc, ns_rpc_uri) {
	this.msgHandler = new class_ttRpcMsgHandler(ns_rpc, ns_rpc_uri);
	this.request = new XML();
	this.answer = new XML();
	this.answer.phpConnect = this;
	this.answer.onData = this.onData;
	this.toSend = [];
}
// }}}
// {{{ send()
class_phpConnect.prototype.send = function(name, args, debug) {
	var func = this.msgHandler.create_func(name, args)
		
	this.debug = debug;
	if (this.toSend.length == 0) {
		setTimeout(this.sendNow, this, 1000);	
	}
	
	this.toSend.push(func);
}
// }}}
// {{{ sendNow()
class_phpConnect.prototype.sendNow = function() {
	var msg = this.msgHandler.create_msg(this.toSend);
	this.toSend = [];
	
	this.requestXML = new XML(msg);
	
	if (this.debug) {
		this.requestXML.send("rpc/", "_blank");
	} else {
		_root.interface.connection_indicate_plus();
		this.requestXML.sendAndLoad("rpc/", this.answer);
	}
};
// }}}
// {{{ onData()
class_phpConnect.prototype.onData = function(msg) {
	this.phpConnect.msgHandler.call(msg);
	_root.interface.connection_indicate_minus();
	updateAfterEvent();
}
// }}}

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
