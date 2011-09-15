"use strict";

$(function () {
	$(".jstree-container").each(function () {
		$(this).jstree({ 
			// the list of plugins to include
			"plugins" : ($(this).attr("data-plugins") || "themes pedantic_html_data ui crrm dnd_placeholder types_from_url hotkeys contextmenu span dblclick_rename tooltips select_created_nodes delta_updates add_marker" ).split(" "),
			// Plugin configuration
            "delta_updates" : {
                "webSocketURL" : $(this).attr("data-delta-updates-websocket-url"),
                "fallbackPollURL" : $(this).attr("data-delta-updates-fallback-poll-url"),
                "postURL" : $(this).attr("data-delta-updates-post-url"),
            },
            "contextmenu" : {
                items : function (obj) {
                    var default_items = { // Could be a function that should return an object like this one
                        "rename" : {
                            "separator_before"	: false,
                            "separator_after"	: false,
                            "label"				: "Rename",
                            "action"			: function (obj) { this.rename(obj); }
                        },
                        "remove" : {
                            "separator_before"	: false,
                            "icon"				: false,
                            "separator_after"	: false,
                            "label"				: "Delete",
                            "action"			: function (obj) { this.remove(obj); }
                        },
                        "ccp" : {
                            "separator_before"	: true,
                            "icon"				: false,
                            "separator_after"	: false,
                            "label"				: "Edit",
                            "action"			: false,
                            "submenu" : { 
                                "cut" : {
                                    "separator_before"	: false,
                                    "separator_after"	: false,
                                    "label"				: "Cut",
                                    "action"			: function (obj) { this.cut(obj); }
                                },
                                "copy" : {
                                    "separator_before"	: false,
                                    "icon"				: false,
                                    "separator_after"	: false,
                                    "label"				: "Copy",
                                    "action"			: function (obj) { this.copy(obj); }
                                },
                                "paste" : {
                                    "separator_before"	: false,
                                    "icon"				: false,
                                    "separator_after"	: false,
                                    "label"				: "Paste",
                                    "action"			: function (obj) { this.paste(obj); }
                                }
                            }
                        }
                    };

                    if (obj.attr(this._get_settings().types_from_url.type_attr) != "default") {
                        default_items = $.extend({
                            "create" : {
                                "separator_before"	: false,
                                "separator_after"	: true,
                                "label"				: "Create",
                                "action"			: function (obj) { this.create(obj); }
                            },
                        }, default_items);
                    }

                    return default_items;
                }
            },
			"ui" : {
                // TODO:
				"initially_select" : ($(this).attr("data-selected-nodes") || "").split(" ")
			},
			"core" : { 
				"initially_open" : ($(this).attr("data-open-nodes") || "").split(" ")
			},
            "themes" : {
                "url" : $(this).attr("data-theme")
            }
		});
    });
});
