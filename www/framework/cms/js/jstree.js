"use strict";

$(function () {
	$(".jstree-container").each(function () {
		$(this).jstree({ 
			// the list of plugins to include
			"plugins" : ($(this).attr("data-plugins") || "themes pedantic_html_data ui crrm dnd_placeholder types_from_url hotkeys contextmenu span dblclick_rename tooltips select_created_nodes delta_updates" ).split(" "),
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
			// Using types - most of the time this is an overkill
			// Still meny people use them - here is how
            // TODO:
			"types" : {
				// I set both options to -2, as I do not need depth and children count checking
				// Those two checks may slow jstree a lot, so use only when needed
				"max_depth" : -2,
				"max_children" : -2,
				// I want only `drive` nodes to be root nodes 
				// This will prevent moving or creating any other type as a root node
				"valid_children" : [ "drive" ],
				"types" : {
					// The default type
					"default" : {
						// I want this type to have no children (so only leaf nodes)
						// In my case - those are files
						"valid_children" : "all",
						// If we specify an icon for the default type it WILL OVERRIDE the theme icons
						"icon" : {
							"image" : "./file.png"
						}
					},
					// The `folder` type
					"folder" : {
						// can have files and other folders inside of it, but NOT `drive` nodes
						"valid_children" : [ "default", "folder" ],
						"icon" : {
							"image" : "./folder.png"
						}
					},
					// The `drive` nodes 
					"drive" : {
						// can have files and folders inside, but NOT other `drive` nodes
						"valid_children" : [ "default", "folder" ],
						"icon" : {
							"image" : "./root.png"
						},
						// those options prevent the functions with the same name to be used on the `drive` type nodes
						// internally the `before` event is used
						"start_drag" : false,
						"move_node" : false,
						"delete_node" : false,
						"remove" : false
					}
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
