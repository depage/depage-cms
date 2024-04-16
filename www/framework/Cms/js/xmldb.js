/*
 * @require framework/shared/jquery-1.12.3.min.js
 *
 *
 * @file    js/xmldb.js
 *
 * copyright (c) 2015 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

var DepageXmldb = (function() {
    "use strict";
    /*jslint browser: true*/
    /*global $:false */

    function Xmldb(baseUrl, projectName, docName) {
        this.projectName = projectName;
        this.docName = docName;
        this.baseUrl = baseUrl;
        this.postUrl = baseUrl + "project/" + this.projectName + "/tree/" + this.docName;
    }
    Xmldb.prototype = {
        // {{{ createNode()
        createNode: function(node, targetId, position, success, extra) {
            this.ajaxCall("createNode", {
                "node" : node,
                "target_id" : targetId,
                "position" : position,
                "extra" : extra
            }, success);
        },
        // }}}
        // {{{ createNodeIn()
        createNodeIn: function(node, targetId, success, extra) {
            this.ajaxCall("createNodeIn", {
                "node" : node,
                "target_id" : targetId,
                "extra" : extra
            }, success);
        },
        // }}}
        // {{{ createNodeBefore()
        createNodeBefore: function(node, targetId, success, extra) {
            this.ajaxCall("createNodeBefore", {
                "node" : node,
                "target_id" : targetId,
                "extra" : extra
            }, success);
        },
        // }}}
        // {{{ createNodeAfter()
        createNodeAfter: function(node, targetId, success, extra) {
            this.ajaxCall("createNodeAfter", {
                "node" : node,
                "target_id" : targetId,
                "extra" : extra
            }, success);
        },
        // }}}
        // {{{ copyNode()
        copyNode: function(nodeId, targetId, position, success) {
            this.ajaxCall("copyNode", {
                "id" : nodeId,
                "target_id" : targetId,
                "position" : position
            }, success);
        },
        // }}}
        // {{{ copyNodeIn()
        copyNodeIn: function(nodeId, targetId, success) {
            this.ajaxCall("copyNodeIn", {
                "id" : nodeId,
                "target_id" : targetId
            }, success);
        },
        // }}}
        // {{{ copyNodeBefore()
        copyNodeBefore: function(nodeId, targetId, success) {
            this.ajaxCall("copyNodeBefore", {
                "id" : nodeId,
                "target_id" : targetId
            }, success);
        },
        // }}}
        // {{{ copyNodeAfter()
        copyNodeAfter: function(nodeId, targetId, success) {
            this.ajaxCall("copyNodeAfter", {
                "id" : nodeId,
                "target_id" : targetId
            }, success);
        },
        // }}}
        // {{{ moveNode()
        moveNode: function(nodeId, targetId, position, success) {
            this.ajaxCall("moveNode", {
                "id" : nodeId,
                "target_id" : targetId,
                "position" : position
            }, success);
        },
        // }}}
        // {{{ moveNodeIn()
        moveNodeIn: function(nodeId, targetId, success) {
            this.ajaxCall("moveNodeIn", {
                "id" : nodeId,
                "target_id" : targetId
            }, success);
        },
        // }}}
        // {{{ moveNodeBefore()
        moveNodeBefore: function(nodeId, targetId, success) {
            this.ajaxCall("moveNodeBefore", {
                "id" : nodeId,
                "target_id" : targetId
            }, success);
        },
        // }}}
        // {{{ moveNodeAfter()
        moveNodeAfter: function(nodeId, targetId, success) {
            this.ajaxCall("moveNodeAfter", {
                "id" : nodeId,
                "target_id" : targetId
            }, success);
        },
        // }}}
        // {{{ renameNode()
        renameNode: function(nodeId, name, success) {
            this.ajaxCall("renameNode", {
                "id" : nodeId,
                "name" : name
            }, success);
        },
        // }}}
        // {{{ deleteNode()
        deleteNode: function(nodeId, success) {
            this.ajaxCall("deleteNode", {
                "id" : nodeId
            }, success);
        },
        // }}}
        // {{{ duplicateNode()
        duplicateNode: function(nodeId, success) {
            this.ajaxCall("copyNodeAfter", {
                "id" : nodeId,
                "target_id" : nodeId
            }, success);
        },
        // }}}
        // {{{ deleteDocument()
        deleteDocument: function(success) {
            this.ajaxCall("deleteDocument", {
                "docName": this.docName
            }, success);
        },
        // }}}
        // {{{ duplicateDocument()
        duplicateDocument: function(success) {
            this.ajaxCall("duplicateDocument", {
                "docName": this.docName
            }, success);
        },
        // }}}
        // {{{ releaseDocument()
        releaseDocument: function(success) {
            this.ajaxCall("releaseDocument", {}, success);
        },
        // }}}
        // {{{ rollbackDocument()
        rollbackDocument: function(timestamp, success) {
            this.ajaxCall("rollbackDocument", {
                timestamp: timestamp,
            }, success);
        },
        // }}}
        // {{{ setAttribute()
        setAttribute: function(nodeId, name, value, success) {
            this.ajaxCall("setAttribute", {
                "id" : nodeId,
                "name" : name,
                "value" : value
            }, success);
        },
        // }}}

        // {{{ ajaxCall()
        ajaxCall: function(operation, data, callback) {
            $.ajax({
                async: true,
                type: 'POST',
                url: this.postUrl + '/' +  operation + '/',
                data: data,
                dataType: 'json',
                error: function(e) {
                    console.log("error");
                    console.log(e);
                },
                success: callback
            });
        },
        // }}}
    };

    return Xmldb;
})();

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
