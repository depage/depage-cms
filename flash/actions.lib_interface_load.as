/* set public functions */
	public_functions = [];
	
	public_functions.push("connection_indicate_plus");
	public_functions.push("connection_indicate_minus");
	public_functions.push("connection_closed");
	
	public_functions.push("login");
	public_functions.push("relogin");
	
	public_functions.push("project_loader");
	public_functions.push("project_loader_update");
	public_functions.push("loadBox_setText");
	
	public_functions.push("start_main_interface");
/* set public functions end */

/* functions */
	function load() {
		_quality = "HIGH";
		
		define_global_textformats();
		connection_indicator._alpha = 0;
		
		waitForLoaded();
	}

	function waitForLoaded() {
		loadCall++;
		if (conf.user.sid != "null") {
			loadBox.setPercent((_parent.lib.getBytesLoaded() / _parent.lib.getBytesTotal()) * 0.4);
		} else {
			loadBox.setPercent((_parent.lib.getBytesLoaded() / _parent.lib.getBytesTotal()));
		}
		loadBox.setText(conf.lang.start_loading_version.replace([
			["%app_name%"		, conf.app_name],
			["%app_version%"	, conf.app_version],
			["%loading%"		, conf.interface_lib.substr(0, conf.interface_lib.length - 4)]
		]));
		if (_parent.lib.getBytesLoaded() != _parent.lib.getBytesTotal() || loadCall == 1) {
			percentLoaded = _parent.lib.getBytesLoaded() / _parent.lib.getBytesTotal() * 100;
			if (loadCall == 1) {
				attachMovie("load_box", "loadBox", 1, {
					_visible	: false
				});
			}
			setTimeout(waitForLoaded, null, 50, [], true);
		} else {
			_global.allowEvents = true;
			
			if (_parent.preload) {
				_parent.onLoad();
				setTimeout(reset_connection_indicator, null, 1000, true);
			} else {
				gotoAndStop("initloaded");
				setTimeout(init, null, 200, true);
			}
		}
	}

	function reset_connection_indicator() {
		connection_indicator.minus();
	}

	function init() {
		var i;

		reset_connection_indicator();
		
		for (i = 0; i < public_functions.length; i++) {
			registerObj(public_functions[i]);
		}
	
		if (_parent.param != "" && _parent.param != undefined && _parent.param != null) {
			attachMovie(_parent.param, "icon", 1);
		}
		_parent.onLoad();
	
		stop();
	}
	
	function define_global_textformats() {
		var tabStopArray = [];
		var i;
		
		for (i = 1; i < 100; i++) {
			tabStopArray.push(i * 21);
		}
		
		conf.interface.textformat = getTextFormat({
			font		: conf.interface.font,
			size		: conf.interface.font_size,
			color		: conf.interface.color_font,
			bold		: false,
			italic		: false,
			deviceFont	: conf.interface.font_device,
			lineSpacing	: conf.interface.font_linespacing
		});
		
		conf.interface.textformat_small = getTextFormat({
			font		: conf.interface.font,
			size		: conf.interface.font_size - 1,
			color		: conf.interface.color_font,
			bold		: false,
			italic		: false,
			deviceFont	: conf.interface.font_device,
			lineSpacing	: conf.interface.font_linespacing
		});
		
		conf.interface.textformat_bold = getTextFormat({
			font		: conf.interface.font,
			size		: conf.interface.font_size,
			color		: conf.interface.color_font,
			bold		: true,
			deviceFont	: conf.interface.font_device
		});
		
		conf.interface.textformat_treeline = getTextFormat({
			font		: conf.interface.font,
			size		: conf.interface.font_size,
			color		: conf.interface.color_input_font_active,
			deviceFont	: conf.interface.font_device
		});

		conf.interface.textformat_treeline_inaccessible = getTextFormat({
			font		: conf.interface.font,
			size		: conf.interface.font_size,
			color		: conf.interface.color_input_font_inactive,
			deviceFont	: conf.interface.font_device
		});
		
		conf.interface.textformat_treeheadline = getTextFormat({
			font		: conf.interface.font,
			size		: conf.interface.font_size,
			color		: conf.interface.color_treeheadline_font,
			deviceFont	: conf.interface.font_device
		});
		
		conf.interface.textformat_button = getTextFormat({
			font		: conf.interface.font,
			size		: conf.interface.font_size,
			color		: conf.interface.color_component_font,
			deviceFont	: conf.interface.font_device,
			align		: "center"
		});
		
		conf.interface.textformat_component = getTextFormat({
			font		: conf.interface.font,
			size		: conf.interface.font_size,
			color		: conf.interface.color_component_font,
			deviceFont	: conf.interface.font_device,
			align		: "left",
			lineSpacing	: conf.interface.font_linespacing
		});

		conf.interface.textformat_tooltip = getTextFormat({
			font		: conf.interface.font,
			size		: conf.interface.font_size,
			color		: conf.interface.color_tooltip_font,
			deviceFont	: conf.interface.font_device,
			align		: "left"
		});

		conf.interface.textformat_tooltipMsg = getTextFormat({
			font		: conf.interface.font,
			size		: conf.interface.font_size,
			color		: conf.interface.color_tooltipMsg_font,
			deviceFont	: conf.interface.font_device,
			align		: "left"
		});

		conf.interface.textformat_waitForParsing = getTextFormat({
			font		: conf.interface.font,
			size		: conf.interface.font_size,
			color		: conf.interface.color_font,
			bold		: false,
			italic		: true,
			align		: "center",
			deviceFont	: conf.interface.font_device
		});
		
		conf.interface.textformat_input = getTextFormat({
			font		: conf.interface.font,
			size		: conf.interface.font_size,
			color		: conf.interface.color_input_font_active,
			deviceFont	: conf.interface.font_device,
			align		: "left",
			lineSpacing	: conf.interface.font_linespacing
		});

		conf.interface.textformat_input_list = getTextFormat({
			font		: conf.interface.font,
			size		: conf.interface.font_size,
			color		: conf.interface.color_input_font_active,
			deviceFont	: conf.interface.font_device,
			align		: "left",
			lineSpacing	: conf.interface.font_linespacing,
                        bullet          : true
		});

		conf.interface.textformat_input_active = getTextFormat({
			font		: conf.interface.font,
			size		: conf.interface.font_size,
			color		: conf.interface.color_input_font_active,
			deviceFont	: conf.interface.font_device,
			align		: "left",
			italic		: false,
			lineSpacing	: conf.interface.font_linespacing
		});

		conf.interface.textformat_input_inactive = getTextFormat({
			font		: conf.interface.font,
			size		: conf.interface.font_size,
			color		: conf.interface.color_input_font_inactive,
			deviceFont	: conf.interface.font_device,
			align		: "left",
			italic		: true,
			lineSpacing	: conf.interface.font_linespacing
		});

		conf.interface.textformat_input_source = getTextFormat({
			font		: conf.interface.font_source,
			size		: conf.interface.font_source_size,
			color		: conf.interface.color_input_font_active,
			deviceFont	: conf.interface.font_source_device,
			align		: "left",
			lineSpacing	: conf.interface.font_source_linespacing,
			tabStops	: tabStopArray
		});

		conf.interface.textformat_input_small = getTextFormat({
			font		: conf.interface.font,
			size		: conf.interface.font_size - 2,
			color		: conf.interface.color_input_font_active,
			deviceFont	: conf.interface.font_device,
			align		: "left",
			lineSpacing	: conf.interface.font_linespacing
		});
		conf.interface.textformat_input_list_small = getTextFormat({
			font		: conf.interface.font,
			size		: conf.interface.font_size - 2,
			color		: conf.interface.color_input_font_active,
			deviceFont	: conf.interface.font_device,
			align		: "left",
			lineSpacing	: conf.interface.font_linespacing,
                        bullet          : true
		});


		conf.interface.textformat_register = getTextFormat({
			font		: conf.interface.font_vertical,
			size		: conf.interface.font_vertical_size,
			color		: conf.interface.color_font,
			deviceFont	: conf.interface.font_vertical_device
		});
	}
	
	function getTextFormat(formatObj) {
		var tFormat = new TextFormat();

		if (formatObj.align != undefined) tFormat.align = formatObj.align;
		if (formatObj.blockIndent != undefined) tFormat.blockIndent = formatObj.blockIndent;
		if (formatObj.bold != undefined) tFormat.bold = formatObj.bold;
		if (formatObj.bullet != undefined) tFormat.bullet = formatObj.bullet;
		if (formatObj.color != undefined) {
			if (typeof formatObj.color == "string") {
				tFormat.color = formatObj.color.toColor();
			} else {
				tFormat.color = formatObj.color;
			}
		}
		if (formatObj.font != undefined) tFormat.font = formatObj.font;
		if (formatObj.indent != undefined) tFormat.indent = formatObj.indent;
		if (formatObj.italic != undefined) tFormat.italic = formatObj.italic;
		if (formatObj.lineSpacing != undefined && formatObj.size != undefined) {
			tFormat.leading = formatObj.lineSpacing - formatObj.size;
		} else if (formatObj.leading != undefined) {
			tFormat.leading = formatObj.leading;
		}
		if (formatObj.leftMargin != undefined) tFormat.leftMargin = formatObj.leftMargin;
		if (formatObj.rightMargin != undefined) tFormat.rightMargin = formatObj.rightMargin;
		if (fromatObj.tabStops != undefined) tFormat.tabStops = formatObj.tabStops;
		if (formatObj.target != undefined) tFormat.target = formatObj.target;
		if (formatObj.size != undefined) tFormat.size = formatObj.size;
		if (formatObj.underline != undefined) tFromat.underline = formatObj.underline;
		if (formatObj.url != undefined) tFormat.url = formatObj.url;

		if (formatObj.deviceFont != undefined) {
			if (typeof formatObj.embedFonts == "string") {
				tFormat.embedFonts = !formatObj.deviceFont.toBoolean();
			} else {
				tFormat.embedFonts = !formatObj.deviceFont;
			}
		}
	
		tempFormatCounter++;
		var n = tempFormatCounter;
		var tempText = "";
		var lineSpacing;
		var height1, height2;

		_root.createTextField("textBox" + n, 10000 + n, 0, 0, 400, 400);
		_root["textBox" + n].setNewTextFormat(tFormat);

		for (i = 0; i < 1000; i++) {
			tempText += i + "\n";
		}
		_root["textBox" + n].text = tempText;
		height1 = _root["textBox" + n].textHeight;

		for (i = 0; i < 1000; i++) {
			tempText += i + "\n";
		}
		_root["textBox" + n].text = tempText + i + "\n";
		height2 = _root["textBox" + n].textHeight;
		tFormat.lineSpacing = (height2 - height1) / 1000;
		_root["textBox" + n].removeTextField();

		return tFormat;
	};

	function registerObj(objName) {
		_parent[objName] = eval(objName);
	}
/* functions end */

/* exportable functions */
	function login(prevUser, prevProject) {
		if (loadCall >= 1) {
			removeMovieClip("loadBox");
		}
		attachMovie("login_box", "loginBox", 2, {
			_visible	: false,
			prevUser	: prevUser,
			prevProject	: prevProject
		});
	}
	
	function relogin() {
		loginBox.relogin();
	}
	
	function project_loader() {
		if (loadCall >= 1) {
			removeMovieClip("loginBox");
		}
		if (loadBox == undefined) {
			attachMovie("load_box", "loadBox", 1, {
				_visible	: false
			});
			percentLoaded = 0;
		} else {
			percentLoaded = 0.5;
		}
	}
	
	function project_loader_update(newPercent) {
		if (percentLoaded == 0.5) {
			loadBox.setPercent(newPercent * 0.5 + 0.5);
		} else {
			loadBox.setPercent(newPercent * 0.8 + 0.1);
		}
		loadBox.setText(conf.lang.start_loading_version.replace([
			["%app_name%"		, conf.app_name],
			["%app_version%"	, conf.app_version],
			["%loading%"		, conf.lang.start_projectdata]
		]));
	}

	function loadBox_setText(text) {
		loadBox.setText(conf.lang.start_loading_version.replace([
			["%app_name%"		, conf.app_name],
			["%app_version%"	, conf.app_version],
			["%loading%"		, text]
		]));
	}	
	
	function start_main_interface() {
		connection_indicator._alpha = 100;
		removeMovieClip("loadBox");
		
		attachMovie("main_interface", "mainInterface", 3, {
			_visible	: false
		});
	}
	
	function connection_indicate_plus() {
		connection_indicator.plus();
	}

	function connection_indicate_minus() {
		connection_indicator.minus();
	}
	
/* exportable functions end */

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
