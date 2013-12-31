/*
 * C L A S S 
 * Class ttRpcMsgHandler
 *
 * actionScript-library:
 * (c)2003-2010 Frank Hellenkamp [jonas@depagecms.net]
 */

/*
 *	Class ttRpcMsgHandler
 *
 *	Handles ttRpc-structured Messages
 */
// {{{ constructor
class_ttRpcMsgHandler = function (xml_ns_rpc, xml_ns_rpc_uri) {
	this.funcs = new Array();
	this.xml_ns_rpc = (xml_ns_rpc == null) ? conf.ns.rpc.toString() : xml_ns_rpc;
	this.xml_ns_rpc_uri = (xml_ns_rpc_uri == null) ? conf.ns.rpc.uri : xml_ns_rpc_uri;
};
// }}}
// {{{ create_func()
class_ttRpcMsgHandler.prototype.create_func = function(name, args) {
	var data, i;
	
	data = "<" + this.xml_ns_rpc + ":func name=\"" + name + "\">";
	for (i = 0; i < args.length; i++){
		data += "<" + this.xml_ns_rpc + ":param name=\"" + args[i][0] + "\">";
		data += args[i][1];
		data += "</" + this.xml_ns_rpc + ":param>";
	}
	data += "</" + this.xml_ns_rpc + ":func>";

	return data;
};
// }}}
// {{{ create_msg()
class_ttRpcMsgHandler.prototype.create_msg = function(funcs) {
	var data, i;
	
	data = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>";
	if (conf.global_entities.length > 0) {
		data += "<!DOCTYPE ttdoc [ ";
		for (i = 0; i < conf.global_entities.length; i++) {
			data += "<!ENTITY " + conf.global_entities[i] + " \"&amp;" + conf.global_entities[i] + ";\">";
		}
		data += " ]>";
	}

	data += "<" + this.xml_ns_rpc + ":msg";
	for (i in conf.ns) {
		if (typeof(conf.ns[i]) == "object" && conf.ns[i].prefix != undefined) {
			data += " xmlns:" + conf.ns[i] + "=\"" + conf.ns[i].uri + "\"";
		}
	}
	data += ">";
	
	if (typeof(funcs) == "array") {
		for (i = 0; i < funcs.length; i++) {
			data += funcs[i];	
		}
	} else {
		data += funcs;
	}
	data += "</" + this.xml_ns_rpc + ":msg>";
	
	return data;
};
// }}}
// {{{ register_func()
class_ttRpcMsgHandler.prototype.register_func = function(name, func, obj) {
	this.funcs[name] = {
		func	: func,
		obj		: obj
	};
};
// }}}
// {{{ unregister_func()
class_ttRpcMsgHandler.prototype.unregister_func = function(name) {
	delete this.funcs[name];	
};
// }}}
// {{{ call()
class_ttRpcMsgHandler.prototype.call = function(xmldata) {
	var funcName, func, i, j;
	var param = Array();
	var tempParam;
	
	var xmlNode;
	var xmlObj = new XML();
	
	xmlObj.parseXML(xmldata.convEntityToUnicode());
	
	if (xmlObj.status == 0) {
		xmlNode = xmlObj.getRootNode();
		if (xmlNode.nodeName == this.xml_ns_rpc + ":msg") {
			xmlNode = xmlNode.firstChild;
			while (xmlNode != null) {
				if (xmlNode.nodeName == this.xml_ns_rpc + ":func") {
					funcName = xmlNode.attributes.name;
					param = [];
					if (typeof(this.funcs[funcName].func) == "function") {
						xmlNodeParam = xmlNode.firstChild;
						while (xmlNodeParam != null) {
							tempParam = "";
							if (xmlNodeParam.nodeName == this.xml_ns_rpc + ":param") {
								for (j = 0; j < xmlNodeParam.childNodes.length; j++) {
									tempParam += xmlNodeParam.childNodes[j];	
								}
								param[xmlNodeParam.attributes.name] = tempParam;
							}
							xmlNodeParam = xmlNodeParam.nextSibling;
						}
						trace("calling " + funcName + "(" + param + ")");
						if (this.funcs[funcName].obj == undefined) {
							this.funcs[funcName].func.call(null, param);
						} else {
							this.funcs[funcName].func.call(this.funcs[funcName].obj, param);
						}
					}
				}
				xmlNode = xmlNode.nextSibling;
			}
		}
	}
};
// }}}

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
