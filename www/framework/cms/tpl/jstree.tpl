<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>jsTree v.1.0 - full featured demo</title>

    <base href="<?php html::base(); ?>">

    <?php // TODO: use jquery 1.4.4
        $this->include_js("jquery", array(
        "../framework/shared/jquery-1.4.2.js",
        "../framework/shared/jquery.cookie.js",
        "../framework/shared/jquery.hotkeys.js",
    )); ?>
    <?php $this->include_js("jstree", array(
        "../framework/cms/js/jquery.jstree.js",
        "../framework/cms/js/jquery.jstree.plugins.js",
        "../framework/shared/jquery.json-2.2.js",
        "../framework/shared/jquery.gracefulWebSocket.js",
    )); ?>

	<style type="text/css">
	html, body { margin:0; padding:0; }
	body, td, th, pre, code, select, option, input, textarea { font-family:verdana,arial,sans-serif; font-size:10px; }
	.demo, .demo input, .jstree-dnd-helper, #vakata-contextmenu { font-size:12px; font-family:Verdana; }
    .jstree-default a { border:1px solid white; border-color: transparent; }
    .jstree li { line-height: 24px !important; min-height: 24px !important;}
    .jstree a { line-height: 24px !important; min-height: 24px !important;}
    .jstree span { margin-left: 10em; font-style: italic; color: #666; font-size: 8px;}
	/*#container { width:780px; margin:10px auto; overflow:hidden; position:relative; }*/
	#demo { width:auto; height:auto; overflow:auto; border:1px solid gray; }

	#text { margin-top:1px; }

	#alog { font-size:9px !important; margin:5px; border:1px solid silver; }
	</style>
</head>
<body>
<div id="container">

<div id="mmenu" style="height:30px; overflow:hidden;">
<input type="button" id="add_folder" value="add folder" style="display:block; float:left;"/>
<input type="button" id="add_default" value="add file" style="display:block; float:left;"/>
<input type="button" id="rename" value="rename" style="display:block; float:left;"/>
<input type="button" id="remove" value="remove" style="display:block; float:left;"/>
<input type="button" id="cut" value="cut" style="display:block; float:left;"/>
<input type="button" id="copy" value="copy" style="display:block; float:left;"/>
<input type="button" id="paste" value="paste" style="display:block; float:left;"/>
</div>

<div id="notification"></div>
<!-- the tree container (notice NOT an UL node) -->
<div id="demo" class="demo" data-doc_id="<?php echo $this->doc_id; ?>" data-seq_nr="<?php echo $this->seq_nr; ?>">
<?php echo $this->nodes; ?>
</div>

<script type="text/javascript">
<!-- JavaScript neccessary for the tree -->
$(function () {
    // TODO: remove if using jquery >= 1.4.4
    $("#demo").data('doc_id', <?php echo $this->doc_id; ?>).data('seq_nr', <?php echo $this->seq_nr; ?>);

	// Settings up the tree - using $(selector).jstree(options);
	// All those configuration options are documented in the _docs folder
	$("#demo")
		.jstree({ 
			// the list of plugins to include
			//"plugins" : [ "themes", "html_data", "ui", "crrm", "cookies", "dnd", "types", "hotkeys", "contextmenu" ],
			"plugins" : [ "themes", "pedantic_html_data", "ui", "crrm", "dnd_placeholder", "types", "hotkeys", "contextmenu", "span", "dblclick_rename", "tooltips", "select_created_nodes", "delta_updates" ],
			// Plugin configuration
            "delta_updates" : {
                "webSocketURL" : "ws://127.0.0.1:8000/jstree/" + <?php echo $this->doc_id; ?>,
                "fallbackPollURL" : "./fallback/updates/",
                "postURL" : "./",
            },
            // example for dynamic contextmenu based on type
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

                    if (obj.attr(this._get_settings().types.type_attr) != "default") {
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
						"valid_children" : "none",
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
			// For UI & core - the nodes to initially select and open will be overwritten by the cookie plugin

			// the UI plugin - it handles selecting/deselecting/hovering nodes
			"ui" : {
				// this makes the node with ID node_4 selected onload
                // TODO:
				"initially_select" : [ "node_4" ]
			},
			// the core plugin - not many options here
			"core" : { 
                // TODO:
				// just open those two nodes up
				// as this is an AJAX enabled tree, both will be downloaded from the server
				"initially_open" : [ "node_0" , "node_1", "node_16", "node_17" ] 
			},
            "themes" : {
                "url" : "../framework/cms/css/jstree.css"
            }
		})
        // TODO: move to plugin
		.bind("create.jstree", function (e, data) {
                        $("#notification").text("created node for parent " + data.rslt.parent.attr("id") + ", pos: " + data.rslt.position + ", title: " + data.rslt.name);
                        
			$.post(
				"./server.php", 
				{ 
					"operation" : "create_node", 
					"id" : data.rslt.parent.attr("id").replace("node_",""), 
					"position" : data.rslt.position,
					"title" : data.rslt.name,
					"type" : data.rslt.obj.attr("rel"),
                    "parent" : data.rslt.parent,
				}, 
				function (r) {
					if(r.status) {
						$(data.rslt.obj).attr("id", "node_" + r.id);
					}
					else {
						$.jstree.rollback(data.rlbk);
					}
				}
			);
		})
		.bind("remove.jstree", function (e, data) {
                        var not = "";
			data.rslt.obj.each(function () {
                            not += "removed node " + this.id;
                        });
                        $("#notification").text(not);
		})
		.bind("rename.jstree", function (e, data) {
                        $("#notification").text("renamed node " + data.rslt.obj.attr("id") + " from " + data.rslt.old_name + " to " + data.rslt.new_name + "\n");
		})

});

</script>
<script type="text/javascript">
$(function () { 
	$("#mmenu input").click(function () {
		switch(this.id) {
			case "add_default":
			case "add_folder":
				$("#demo").jstree("create", null, "last", { "attr" : { "rel" : this.id.toString().replace("add_", "") } });
				break;
			default:
				$("#demo").jstree(this.id);
				break;
		}
	});
});
</script>

</div>

</body>
</html>
