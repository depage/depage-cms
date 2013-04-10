/**
 * @require framework/cms/js/jstree/vakata.js
 * @require framework/cms/js/jstree/jstree.js
 * @require framework/cms/js/jstree/jstree.themes.js
 * @require framework/cms/js/jstree/jstree.ui.js
 * @require framework/cms/js/jstree/jstree.dnd.js
 * @require framework/cms/js/jstree/jstree.hotkeys.js
 * @require framework/cms/js/jstree/jstree.nodeinfo.js
 * @require framework/cms/js/jstree/jstree.tooltips.js
 * @require framework/cms/js/jstree/jstree.typesfromurl.js
 * @require framework/cms/js/jstree/jstree.contextmenu.js
 * @require framework/cms/js/jstree/jstree.dblclickrename.js
 * @require framework/cms/js/jstree/jstree.deltaupdates.js
 * @require framework/cms/js/jstree/jstree.pedantic_html_data.js
 * @require framework/cms/js/jstree/jstree.toolbar.js
 * @require framework/cms/js/jstree/jstree.marker.js
 * @require framework/cms/js/jstree/doctypes/jstree.doctype.page.js
 *
 * @require framework/shared/jquery.json-2.2.js
 * @require framework/shared/jquery.gracefulWebSocket.js
 *
 * @file    depage-jstree
 *
 * Depage jstree - wraps the jstree in the depage namespace adding custom configuration and functionality.
 *
 * @copyright (c) 2006-2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author Frank Hellenkamp
 * @author Ben Wallis
 */
(function($){

    if(!$.depage){
        $.depage = {};
    };

    // {{{ jstree()
    /**
     * jstree
     *
     * @param el - file input
     * @param index
     * @param options
     */
    $.depage.jstree = function(el, index, options) {
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;

        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        // Add a reverse reference to the DOM object
        base.$el.data("depage.jstree", base);

        // {{{ init()
        /**
         * Init
         *
         * Get the plugin options.
         *
         * @return void
         */
        base.init = function(){

            base.options = $.extend({}, $.depage.jstree.defaultOptions, options);

            // init the tree
            base.jstree();
        };
        // }}}

        // {{{ jstree()
        /**
         * JsTree
         *
         * Re-init the tre plugin
         *
         */
        base.jstree = function() {
            base.$el.jstree(base.options);
        }
        // }}}

        // {{{ buildCreateMenu()
        /**
         *
         * @param available_nodes
         * @param position
         * @return {Object}
         */
        base.buildCreateMenu = function (available_nodes, position){

            available_nodes = available_nodes || {};
            position = position || 'inside';

            var sub_menu = {};

            $.each(available_nodes, function(type, node){
                sub_menu[type] = {
                    "label"             : node.name,
                    "separator_before"  : false,
                    "separator_after"   : false,
                    "action"            : function (data) {
                        base.contextCreate(data, type, position);
                    }
                }
            });

            var create_menu = {
                "create" : {
                    "_disabled"         : !$(available_nodes).size(),
                    "label"             : "Create",
                    "separator_before"  : false,
                    "separator_after"   : true,
                    "action"            : false,
                    "submenu"           : sub_menu
                }
            }

            return create_menu;
        };
        // }}}

        // {{{ keyLeft()
        /**
         * keyLeft
         *
         */
        base.keyLeft = function() {
            var o = this.data.ui.hovered || this.data.ui.last_selected;
            if(o) {
                if(o.hasClass("jstree-open")) {
                    this.close_node(o);
                }
                else {
                    base.keyUp.apply(this);
                }
            }
        };
        // }}}

        // {{{ keyRight()
        /**
         * keyRight
         *
         */
        base.keyRight = function(){
            var o = this.data.ui.hovered || this.data.ui.last_selected;

            if(o && o.length) {
                if(o.hasClass("jstree-closed")) {
                    this.open_node(o);
                }
                else {
                    base.keyDown.apply(this);
                }
            }
        };
        // }}}

        // {{{ keyUp()
        /**
         * keyUp
         *
         */
        base.keyUp = function(){
            var o = this.data.ui.hovered || this.data.ui.last_selected || -1;

            var prev = this.get_prev(o);
            if (prev.length) {
                this.deselect_node(o);
                this.select_node(prev);
            }
        };
        // }}}

        // {{{ keyDown()
        /**
         * keyDown
         *
         */
        base.keyDown = function(){
            var o = this.data.ui.hovered || this.data.ui.last_selected || -1;

            var next = this.get_next(o);
            if (next.length) {
                this.deselect_node(o);
                this.select_node(next);
            }
        },
        // }}}

        // {{{ contextDelete()
        /**
         * contextDelete
         */
        base.contextDelete = function(data) {
            var offset = data.reference.offset();

            base.confirmDelete(offset.left, offset.top, function() {
                var inst = base._reference(data.reference);

                if (inst) {
                    var obj = inst.get_node(data.reference);
                    if(inst.data.ui && inst.is_selected(obj)) {
                        obj = inst.get_selected();
                    }
                    inst.delete_node(obj);
                }
            });
        };
        // }}}

        // {{{ contextCut()
        /**
         * contextCut
         *
         * @param data
         */
        base.contextCut = function(data) {
            var inst = base._reference(data.reference);

            if (inst) { // TODO why null?
                var obj = inst.get_node(data.reference);
                if(data.ui && inst.is_selected(obj)) {
                    obj = inst.get_selected();
                }
                inst.cut(obj);
            }
        };
        // }}}

        // {{{ contextCreate()
        /**
         *
         * @param data
         * @param type
         * @param position
         */
        base.contextCreate = function(data, type, position) {
            position = position || 'inside';
            var inst = base._reference(data.reference);

            // TODO bug why is inst not defined - clicked to quickly?
            if (inst) {

                // open the node (so states are remembered after delataupdate)
                data.reference.parent('li').addClass("jstree-open");

                var obj = inst.create_node(data.reference, type, position);

                // focus for edit
                inst.edit(obj);

            }
        };
        // }}}

        // {{{ contextCopy()
        /**
         * contextCopy
         *
         * @param data
         */
        base.contextCopy = function(data) {
            var inst = base._reference(data.reference);
            if (inst){ // TODO why null? BUG after delete?
                var obj = inst.get_node(data.reference);
                if(inst.is_selected(obj)) {
                    obj = inst.get_selected();
                }
                inst.copy(obj);
            }
        };
        // }}}

        // {{{ contextDuplicate()
        /**
         * contextDuplicate
         *
         * @param data
         */
        base.contextDuplicate = function(data) {
            var inst = base._reference(data.reference);
            if (inst){ // TODO why null? BUG after delete?
                var obj = inst.get_node(data.reference);
                if(inst.is_selected(obj)) {
                    obj = inst.get_selected();
                }
                inst.duplicate(obj);
            }
        };
        // }}}

        // {{{ contextPaste()
        /**
         * contextPaste
         *
         * @param data
         * @param pos
         */
        base.contextPaste = function(data, pos) {
            pos = pos || "after";
            var inst = base._reference(data.reference);
            var obj = inst.get_node(data.reference);

            inst.paste(obj, pos);
        };
        // }}}

        // {{{ contextRename()
        /**
         * contextRename
         *
         * @param data
         */
        base.contextRename = function(data) {
            var inst = base._reference(data.reference);
            var obj = inst.get_node(data.reference);
            inst.edit(obj);
        };
        // }}}

        // {{{ confirmDelete()
        /**
         * confirmDelete
         *
         * @param left
         * @param top
         * @param delete_callback
         */
        base.confirmDelete = function(left, top, delete_callback) {
            // setup confirm on the delete context menu using shy-dialogue
            var buttons = {
                yes: {
                    click: function(e) {
                        e.stopImmediatePropagation();
                        delete_callback();
                        $("#node_1").data('depage.shyDialogue').hide();
                        return false;
                    }
                },
                no : false
            };

            $("#node_1").depageShyDialogue(
                buttons, {
                    title: "Delete?",
                    message: "Are you sure you want to delete this menu item?",
                    bind_el: false // show manually
                });

            // prevent the click event hiding the menu
            $(document).bind("click.marker", function(e) {
                e.stopImmediatePropagation();
                return false;
            });

            $("#node_1").data('depage.shyDialogue').showDialogue(left, top);
        };
        // }}}

        // go!
        base.init();

    }; // end depage.jstree
    // }}}

    // defaultOptions {{{
    /**
     * Default Options
     *
     * @var object
     */
    $.depage.jstree.defaultOptions = {
        plugins : []
    };
    // }}}

    $.fn.depageTree = function(options){
        return this.each(function(index){
            (new $.depage.jstree(this, index, options));
        });
    };

})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
