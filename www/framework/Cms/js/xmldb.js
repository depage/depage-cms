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
        createNode: function() {
        },
        // }}}
        // {{{ copyNode()
        copyNode: function() {
        },
        // }}}
        // {{{ moveNode()
        moveNode: function(nodeId, targetId, position) {
            this.ajaxCall("moveNode", {
                "id" : nodeId,
                "target_id" : targetId,
                "position" : position
            });
        },
        // }}}
        // {{{ renameNode()
        renameNode: function() {
        },
        // }}}
        // {{{ deleteNode()
        deleteNode: function(nodeId) {
            this.ajaxCall("deleteNode", {
                "id" : nodeId
            });
        },
        // }}}
        // {{{ duplicateNode()
        duplicateNode: function() {
        },
        // }}}

        // {{{ ajaxCall()
        ajaxCall: function(operation, data) {
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
                success: function(data, status) {
                    console.log(status);
                    console.log(data);
                }
            });
        },
        // }}}
    };

    return Xmldb;
})();

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
