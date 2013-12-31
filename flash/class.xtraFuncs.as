/**
 * F U N C T I O N S 
 * Flash-extending functions
 *
 * actionScript-library:
 * (c)2003-2010 Frank Hellenkamp [jonas@depagecms.net]
 */

/*
 *  Object
 */
// {{{ Object.copy()
Object.prototype.copy = function() {
    var _t = new this.__proto__.constructor(this) ;
    for(var i in this) {
        if (typeof this[i] == "object") {
            _t[i] = this[i].copy()
        } else {
            _t[i] = this[i];
        }
    }

    return _t;
};
ASSetPropFlags(Object.prototype,["copy"],1);
// }}}

/*
 *	colorFuncs
 */
// {{{ MovieClip.setRGB()
MovieClip.prototype.setRGB = function(value, alpha) {
	var newColor, temp;
	
	if (typeof value == "string") {
		value = value.toColor();	
	}
	newColor = new Color(this);
	
	if (alpha != undefined) {
		temp = value.toColorString();
		
		newColor.setTransform({
			ra	: 0,
			rb	: Number("0x" + temp.substr(1, 2)),
			ga	: 0,
			gb	: Number("0x" + temp.substr(3, 2)),
			ba	: 0,
			bb	: Number("0x" + temp.substr(5, 2)),
			aa	: 0,
			ab	: int((255 / 100) * alpha)
		});
	} else {
		newColor.setRGB(value);
	}
}; 
// }}}
// {{{ MovieClip.getGlobalX()
MovieClip.prototype.getGlobalX = function() {
	var tempPoint = {
		x	: 0,
		y	: 0
	};
	this.localToGlobal(tempPoint);
	
	return tempPoint.x;
};
// }}}
// {{{ MovieClip.getGlobalY()
MovieClip.prototype.getGlobalY = function() {
	var tempPoint = {
		x	: 0,
		y	: 0
	};
	this.localToGlobal(tempPoint);
	
	return tempPoint.y;
};
// }}}
// {{{ Number.toColorString()
Number.prototype.toColorString = function() {
	var i, value;
	
	value = this.toString(16);
	
	while (value.length < 6) {
		value = "0" + value.toString();	
	}
	value = value.toUpperCase();

	return "#" + value;	
};
// }}}
// {{{ String.toColor()
String.prototype.toColor = function() {
	var newValue;
		
	if (this.charAt(0) == "#") {
		newValue = this.substring(1);
		while (newValue.length < 6) {
			newValue += "0";
		}
	}
	
	if (!isNan(Number("0x" + newValue))) {
		newValue = Number("0x" + newValue);
	} else if (!isNan(Number(newValue))) {
		newValue = Number(newValue);
	} else {
		newValue = 0x000000;
	}
	
	return newValue
};
// }}}

/*
 *	pathExtensions
 */
// {{{ String.glpEncode()
String.prototype.glpEncode = function() {
	var newValue = "";
	var i;
	var charCode;
        var oldChar = "-";
        var newChar = "";
        
        //@todo fix bug with "()" in folder and page names
	
	for (i = 0; i < this.length; i++) {
		charCode = this.charCodeAt(i);
		if (this.charAt(i) == "ä" || this.charAt(i) == "Ä") {
			newChar = "ae";
		} else if (this.charAt(i) == "ö" || this.charAt(i) == "Ö") {
			newChar = "oe";
		} else if (this.charAt(i) == "ü" || this.charAt(i) == "Ü") {
			newChar = "ue";
		} else if (this.charAt(i) == "ß") {
			newChar = "ss";
                } else if (this.charAt(i) == "á" || this.charAt(i) == "Á" || this.charAt(i) == "à" || this.charAt(i) == "À" || this.charAt(i) == "â" || this.charAt(i) == "Â") {
			newChar = "a";
                } else if (this.charAt(i) == "ó" || this.charAt(i) == "Ó" || this.charAt(i) == "ò" || this.charAt(i) == "Ò" || this.charAt(i) == "ô" || this.charAt(i) == "Ô") {
			newChar = "o";
                } else if (this.charAt(i) == "ú" || this.charAt(i) == "Ú" || this.charAt(i) == "ù" || this.charAt(i) == "Ù" || this.charAt(i) == "û" || this.charAt(i) == "Û") {
			newChar = "u";
		} else if (this.charAt(i) == "ö" || this.charAt(i) == "Ö") {
			newChar = "oe";
		} else if (this.charAt(i) == "ü" || this.charAt(i) == "Ü") {
			newChar = "ue";
		} else if (this.charAt(i) == "-" || this.charAt(i) == "_" || this.charAt(i) == "." || (charCode >= 48 && charCode <= 57) || (charCode >= 97 && charCode <= 122) || (charCode >= 65 && charCode <= 90)) {
			newChar = this.charAt(i);
		} else  {
			newChar = "-";
                }

                if (oldChar != "-" || newChar != "-") {
                    newValue += newChar;
                }
                oldChar = newChar;
	}	
	
	newValue = newValue.toLowerCase();
	
	return newValue;
};
// }}}
// {{{ String.splitPath()
String.prototype.splitPath = function() {
	var tempArray;
	var tempPos;
	var returnVal = new Object();
	
	tempArray = this.split("/");
	
	returnVal.name = tempArray.pop();
	if (tempArray.length > 0) {
		tempPos = tempArray[0].indexOf(":");
		if (tempPos > -1) {
			returnVal.protocol = tempArray[0].substr(0, tempPos);
			tempArray[0] = tempArray[0].substr(tempPos + 1);
		} else {
			returnVal.protocol = "";
		}
		returnVal.path = tempArray.join("/") + "/";
	} else {
		returnVal.path= "";
	}
	
	return returnVal;
};
// }}}

/*
 *	stringExtensions
 */
// {{{ String.replace()
String.prototype.replace = function(searchStr, replaceStr) {
	var i, tempStr = this + "\u0001";
	if (typeof searchStr == "object" && searchStr.const == "RegExp") {
		return this.regreplace(searchStr, replaceStr);
	} else if (typeof searchStr == "object" && searchStr.length > 0) {
		for (i = 0; i < searchStr.length; i++) {
			var tempArray = tempStr.split(searchStr[i][0]);
			tempStr = tempArray.join(searchStr[i][1]);
		}
	} else {
		var tempArray = tempStr.split(searchStr);
		tempStr = tempArray.join(replaceStr);
	}
	return tempStr.substr(0, tempStr.length - 1);
};
// }}}
// {{{ String.replaceInterfaceTexts()
String.prototype.replaceInterfaceTexts = function() {
	var i, startPos, endPos, lastPos;
	var strArray = [];
	
	startPos = this.indexOf("%", 0);
	lastPos = 0;
	while (startPos != -1) {
		endPos = this.indexOf("%", startPos + 1) + 1;
		if (endPos == -1) {
			endPos = this.length;
		}
		strArray.push(this.substring(lastPos, StartPos));
		strArray.push(this.substring(startPos, endPos));
		lastPos = endPos;
		startPos = this.indexOf("%", lastPos);		
	}
	for (i = 0; i < strArray.length; i++) {
		if (strArray[i] == "%%") {
			strArray[i] = "%";
		} else if (strArray[i].substring(0, 1) == "%") {
			strArray[i] = conf.lang[strArray[i].substring(1, strArray[i].length - 1)];
		}
	}
	strArray.push(this.substring(lastPos, this.length));
	
	return strArray.join("");
};
// }}}
// {{{ String.removeUnwantedTags()
String.prototype.removeUnwantedTags = function(allowedTagsVar) {
	var newStr;
	var i, testStr;
	var startIndex = 0;
	var foundStartAt, foundEndAt, foundSpaceAt;
	var isAllowedTag;
	var allowedTags = [];
	
	for (i=0; i<allowedTagsVar.length; i++) {
		allowedTags.push(allowedTagsVar[i].toLowerCase());
		allowedTags.push("/" + allowedTagsVar[i].toLowerCase());
	}
	foundStartAt = this.indexOf("<", startIndex);
	foundEndAt = this.indexOf(">", foundStartAt + 1);
	while (foundStartAt > -1) {
		isAllowedTag = false;
		for (i=0; i<allowedTags.length; i++) {
			testStr = this.substring(foundStartAt + 1, foundStartAt + 1 + allowedTags[i].length);
			if(testStr.toLowerCase() == allowedTags[i]) {
				isAllowedTag = true;
				break;
			}
		}
		if (isAllowedTag) {
			foundSpaceAt = this.indexOf(" ", foundStartAt);
			if (foundSpaceAt > foundEndAt || foundSpaceAt == -1) {
				newStr += this.substring(startIndex, foundEndAt + 1).toLowerCase();
			} else {
				newStr += this.substring(startIndex, foundSpaceAt).toLowerCase() + ">";
			}
		} else {
			newStr += this.substring(startIndex, foundStartAt);			
		}
		startIndex = foundEndAt + 1;
		foundStartAt = this.indexOf("<", startIndex);
		foundEndAt = this.indexOf(">", foundStartAt + 1);
	}
	newStr += this.substring(startIndex, this.length);
	
	return newStr;
};
// }}}
// {{{ String.convENtityToUnicode()
String.prototype.convEntityToUnicode = function() {
	var newStr;
	var startIndex = 0;
	var foundStartAt, foundEndAt;

	foundStartAt = this.indexOf("&#x", startIndex);
	foundEndAt = this.indexOf(";", foundStartAt + 2);
	while (foundStartAt > -1 && foundEndAt > -1) {
		newStr += this.substring(startIndex, foundStartAt) + String.fromCharCode(parseInt("0x" + this.substring(foundStartAt + 3, foundEndAt)));
		startIndex = foundEndAt + 1;
		foundStartAt = this.indexOf("&#x", startIndex);
		foundEndAt = this.indexOf(";", foundStartAt + 2);
	}
	newStr += this.substring(startIndex, this.length);
	
	return newStr;	
};
// }}}
// {{{ String.toXMLString()
String.prototype.toXMLString = function() {
    newStr = "";

    for (var i = 0; i < this.length; i++) {
        var s = this.charAt(i);
        if (s == "&") {
            newStr += "&amp;";
        } else if (s == "<") {
            newStr += "&lt;";
        } else if (s == ">") {
            newStr += "&gt;";
        } else if (s == "\"") {
            newStr += "&quot;";
        } else {
            newStr += s;
        }
    }
	
    return newStr;	
};
// }}}
// {{{ String.trim()
String.prototype.trim = function() {
	var tempStr = this;
	
	while (tempStr.charAt(0) == " ") {
		tempStr = tempStr.substring(1);	
	}
	while (tempStr.charAt(tempStr.length - 1) == " ") {
		tempStr = tempStr.substring(0, tempStr.length - 2);	
	}
	
	return tempStr;
};
// }}}
// {{{ String.toBoolean()
String.prototype.toBoolean = function() {
	if (this.toLowerCase() == "true") {
		return true;
	} else if (this.toLowerCase() == "false") {
		return false;	
	} else {
		return false;	
	}
};
// }}}

/*
 *	ArrayExtensions
 */  
// {{{ Array.searchFor()
Array.prototype.searchFor = function(element) {
	var i;
	
	for (i = 0; i < this.length; i++) {
		if (this[i] == element) {
			return i;
		}
	}	
	return -1;
};
// }}}

/*
 *	timeout
 */
// {{{ setTimeout()
_global.setTimeout = function(func, obj, msec, param, update) {
	if (func != undefined) {
		var TimeoutObj = {
			func	: func,
			param	: param,
			obj		: obj
		};
		if (update == undefined || update == null) {
			TimeoutObj.update = true;
		} else {
			TimeoutObj.update = Boolean(update);
		}
		TimeoutObj.exec = function() {
			this.func.apply(this.obj, this.param);
			if (this.update) {
				updateAfterEvent();
			}
			this.clear();
		};
		TimeoutObj.clear = function() {
			clearInterval(this.IntervalID);			
		};
		TimeoutObj.IntervalID = setInterval( TimeoutObj, "exec", msec);
		
		return TimeoutObj;
	}
};
// }}}

/*
 *	updateDisplay
 */
// {{{ updateDisplay()
_global.updateDisplay = function(timeout) {
	var nullFunc = function() {};
	
	if (timeout == undefined || timeout < 1) {
		timeout = 1
	}
	setTimeout(nullFunc, null, timeout, true);
};
// }}}

/*
 *	alert
 */
// {{{ alertObjInfo()
_global.alertObjInfo = function(Obj) {
	if (Obj === undefined) {
		alert("undefined");	
	} else if (Obj === null) {
		alert("null");	
	} else if (typeof Obj == "object" || typeof Obj == "movieclip") {
		var p, i;
		var infoString = "";
		var varArray = [];
		var objArray = [];
		var funcArray = [];
		var clipArray = [];
	
		ASSetPropFlags(Obj,null,8,1);

		for(p in Obj) {
			infoString = Obj[p].toString();
			if (infoString.length > 100) {
				infoString = infoString.substring(0, 100) + "...";	
			}
			
			if (typeof Obj[p] == "function") {
				funcArray.push(p + "()");
			} else if (typeof Obj[p] == "object") {
				objArray.push(p + " = " + infoString);
			} else if (typeof Obj[p] == "movieclip") {
				clipArray.push(p + " = " + infoString);
			} else if (typeof Obj[p] == "string") {
				varArray.push(p + " (" + typeof Obj[p] + ") = \"" + infoString + "\"");
			} else if (typeof Obj[p] == "null") {
				varArray.push(p + " = null");
			} else {
				varArray.push(p + " (" + typeof Obj[p] + ") = " + infoString);
			}
		}
		varArray.sort();
		objArray.sort();
		clipArray.sort();
		funcArray.sort();
		for (i = 0; i < funcArray.length; i++) {
			if (funcArray[i].substring(0, 1) != funcArray[i + 1].substring(0, 1)) {
				funcArray[i] += ",\n";
			} else {
				funcArray[i] += ", ";
			}
		}
		alert("FUNCTIONS:\n" + funcArray.join("") + "\nOBJECTS:\n" + objArray.join("\n") + "\n\nMOVIECLIPS:\n" + clipArray.join("\n") + "\n\nVARIABLES:\n" + varArray.join("\n"));
	} else if (typeof Obj == "string") {
		alert("(" + typeof Obj + ")\n\"" + Obj.toString().substring(0, 1000) + "\"");	
	} else {
		alert("(" + typeof Obj + ")\n" + Obj.toString().substring(0, 1000));	
	}
};
// }}}
// {{{ alert()
_global.alert = function(message) {
	message = message.toString();
	if (message != undefined) {
		trace(message);
		message = message.replace([
			["\r"	, "<br>"],
			["\n"	, "<br>"],
			["'"	, "&apos;"],
			["\""	, "&quot;"],
			["ä"	, "&auml;"],
			["Ä"	, "&Auml;"],
			["ö"	, "&ouml;"],
			["Ö"	, "&Ouml;"],
			["ü"	, "&uuml;"],
			["Ü"	, "&Uuml;"],
			["ß"	, "&szlig;"]
		]);
		call_jsfunc("msg('" + escape(message) + "')");
	}
};
// }}}
// {{{ status()
_global.status = function(message) {
	trace(message);
	call_jsfunc("set_status('" + escape(message) + "')");
};
// }}}

/*
 *	callJSFunction
 */
jsfunctions = [];

// {{{ call_jsfunc()
_global.call_jsfunc = function(func) {
	if (func != undefined) {
		jsfunctions.push(func);
	}
	if (jsfunctions.length == 1) {
		setTimeout(call_jsfunctions, null, 30, [], false);	
	}
}
// }}}
// {{{ call_jsfunctions()
function call_jsfunctions() {
	getURL ("javascript:" + jsfunctions.shift() + ";");
	if (jsfunctions.length > 0) {
		setTimeout(call_jsfunctions, null, 30, [], false);	
	}
}
// }}}

/*
 *	eval
 */
// {{{ parseExpr()
_global.parseExpr = function(expr) {
	var operators = new Array("+", "-", "*", "/");
	if (expr != undefined) {
		var i, j, k;
		var value, value1, value2;
		var parentOpenPos = -1;
		var parentClosePos;
		var operatorPos = new Array;
		var isOperator, operator;
	
		if (isNaN(expr)) {
			if (expr == "" && expr == null && expr == undefined) {
				value = "0";
			} else if (expr.indexOf("(") > -1) {
				while (expr.indexOf("(", parentOpenPos+1) > -1 && i<100) {
					parentOpenPos = expr.indexOf("(", parentOpenPos+1);
					i++;
				}
				parentClosePos = expr.indexOf(")", parentOpenPos);
				value = evaluate(expr.substring(0, parentOpenPos) + evaluate(expr.substring(parentOpenPos+1, parentClosePos)) + expr.substring(parentClosePos+1, expr.length));
			} else if ((expr.indexOf(operators[0]) > -1) && (expr.indexOf(operators[1]) > -1) && (expr.indexOf(operators[2]) > -1) && (expr.indexOf(operators[3]) > -1)) {
				operatorPos[0] = 0;
				for (i in operators) {
					for (j=1; j<=expr.length; j++) {
						if (expr.substr(j, 1) == operators[i]) {
							isOperator = true;
							for (k in operators) {
								if (expr.substr(j-1, 1) == operators[k]) isOperator = false;
							}
							if (isOperator) {
								operatorPos[1] = j;
								i = 10000;
								j = 10000;
							}
						}
					}
				}
				for (j=operatorPos[1] + 2; j<=expr.length; j++) {
					for (i in operators) {
						if (expr.substr(j, 1) == operators[i]) {
							operatorPos[2] = j;
							i = 10000;
							j = 10000;
						}
					}
				}
				
				value1 = expr.substring(operatorPos[0], operatorPos[1]);
				operator = expr.substr(operatorPos[1], 1);
				value2 = expr.substring(operatorPos[1]+1, expr.length);
				
				if (operator == "+") {
					value = evaluate(value1) + evaluate(value2);
				} else if (operator == "-") {
					value = evaluate(value1) - evaluate(value2);
				} else if (operator == "*") {
					value = evaluate(value1) * evaluate(value2);
				} else if (operator == "/") {
					value = evaluate(value1) / evaluate(value2);
				}
			} else {
				value = eval(expr);
			}
		} else {
			value = expr;
		}
		
		return Number(value);
	}
};
// }}}
// {{{ evaluate()
_global.evaluate = function(expr) {
	if (expr != undefined) {
		var pos;
		var value;
		
		if (isNaN(expr)) {
			if (expr == "" && expr == null && expr == undefined) {
				expr = "0";
			}
			
			while (expr.indexOf(" ") > -1) {
				pos = expr.indexOf(" ");
				expr = expr.substring(0, pos) + expr.substring(pos + 1, expr.length);
			}
			value = parseExpr(expr);
		} else {
			value = Number(expr);
		}
	
		return value;
	}
};
// }}}
// {{{ backupListeners
_global.backupListeners = function(backupArray, Obj) {
	var i;
	
	backupArray.splice(0, backupArray.length - 1);
	
	for (i = 0; i < Obj._listeners.length; i++) {
		backupArray[i] = Obj._listeners[i];
	}
	for (i = 0; i < backupArray.length; i++) {
		Obj.removeListener(backupArray[i]);
	}
};
// }}}
// {{{ restoreListeners()
_global.restoreListeners = function(backupArray, Obj) {
	var i;	
	
	for (i = 0; i < backupArray.length; i++) {
		Obj.addListener(backupArray[i]);
	}
};
// }}}
// {{{ backupEnabled()
_global.backupEnabled = function(backupArray, Obj) {
	var i;
	
	if (Obj.enabled == true) {
		backupArray.push(Obj);	
		Obj.enabled = false;
	}
	
	for (p in Obj) {
		if (typeof p != "string") {
			alert("type" + (typeof p));
		}
		if ((typeof(Obj[p]) == "movieclip" || typeof(Obj[p]) == "button") && Obj == Obj[p]._parent) {
			backupEnabled(backupArray, Obj[p]);	
		}
	}
};
// }}}
// {{{ restoreEnabled()
_global.restoreEnabled = function(backupArray) {
	var i;
	
	for (i = 0; i < backupArray.length; i++) {
		backupArray[i].enabled = true;	
	}
};
// }}}

/*
 *	getMovieParameter
 */
// {{{ getMovieParam()
function getMovieParam(obj) {
	var url = obj._url;
	var params = [];
	var paramsTemp, paramTemp, i;
	
	if (url.indexOf("?") == -1) {
		url = "";	
	} else {
		url = url.substring(url.indexOf("?") + 1, url.length);
	}
	paramsTemp = url.split("&");
	for (i = 0; i < paramsTemp.length; i++) {
		paramTemp = paramsTemp[i].split("=");
		params[paramTemp[0]] = unescape(paramTemp[1]);
	}

	return params;
}
// }}}

/**
 * date
 */
// {{{ getLocalDate()
_global.getLocalDate = function(dateStr) {
	var formattedDate = conf.lang.date_format;
	var localDate = new Date();
	
	localDate.setUTCFullYear(dateStr.substr(0, 4));
	localDate.setUTCMonth((dateStr.substr(5, 2) - 1));
	localDate.setUTCDate(dateStr.substr(8, 2));
	localDate.setUTCHours(dateStr.substr(11, 2));
	localDate.setUTCMinutes(dateStr.substr(14, 2));
	localDate.setUTCSeconds(dateStr.substr(17, 2));
	
	
	if (dateStr.substr(0, 4) == undefined) {
		formattedDate = "";
	} else {
		formattedDate = formattedDate.replace([
			["%D%"	, conf.lang["date_day_" + localDate.getDay()]],
			["%d%"	, setLeadingZero(localDate.getDate(), 2)],
			["%MM%"	, conf.lang["date_month_" + (localDate.getMonth() + 1)]],
			["%y%"	, localDate.getFullYear()],
			["%h%"	, setLeadingZero(localDate.getHours(), 2)],
			["%m%"	, setLeadingZero(localDate.getMinutes(), 2)],
			["%s%"	, setLeadingZero(localDate.getSeconds(), 2)]
		]);
	}

	return formattedDate;
};
// }}}
// {{{ setLeadingZero()
_global.setLeadingZero = function(value, num) {
	while (value.toString().length < num) {
		value = "0" + value;
	}
	
	return value;
};
// }}}
// {{{ Date.parseDate()
Date.prototype.parseDate = function(dateStr) {
	// Create local variables to hold the year, month, date of month, hour, minute, and
	// second. Assume that there are no milliseconds in the date string.
	var year, month, monthDate, hour, minute, second;

	// Use a regular expression to test whether the string uses ActionScript's date
	// string format. Other languages and applications may use the same format. For
	// example: Thu Dec 5 06:36:03 GMT-0800 2002
	var re = new RegExp(
	"[a-zA-Z]{3} [a-zA-Z]{3} [0-9]{1,2} [0-9]{2}:[0-9]{2}:[0-9]{2} .* [0-9]{4}","g");
	var match = re.exec(dateStr);

	// If the date string matches the pattern, parse the date from it and return a new
	// Date object with the extracted value.
	if (match != null) {
		// Split the match into an Array of strings. Split it on the spaces so the
		// elements will be day, month, date of month, time, timezone, year.
		var dateAr = match[0].regsplit(" ");
		// Set the month to the second element of the Array. This is the abbreviated name
		// of the month, but we want the number of the month (from 0 to 11), so loop
		// through the Date.months Array until you find the matching element, and set
		// month to that index.
		month = dateAr[1];
		for (var i = 0; i < Date.months.length; i++) {
			if (Date.months[i].indexOf(month) != -1) {
				month = i;
				break;
			}
		}
		// Convert the monthDate and year from the Array from strings to numbers.
		monthDate = Number(dateAr[2]);
		year = Number(dateAr[dateAr.length - 1]);
		// Extract the hour, minute, and second from the time element of the Array.
		var timeVals = dateAr[3].split(":");
		hour   = Number(timeVals[0]);
		minute = Number(timeVals[1]);
		second = Number(timeVals[2]);
		// If the Array has six elements, there is a timezone offset included (some date
		// strings in this format omit the timezone offset).
		if (dateAr.length == 6) {
			var timezone = dateAr[4];
			// Multiply the offset (in hours) by 60 to get minutes.
			var offset = 60 * Number(timezone.substr(3, 5))/100;
			// Calculate the timezone difference between the client's computer and the
			// offset extracted from the date string.
			var offsetDiff = offset + new Date().getTimezoneOffset(  );
			// Add the timezone offset, in minutes. If the date string and the client
			// computer are in the same timezone, the difference is 0.
			minute += offsetDiff;
		}
		// Return the new date.
		return new Date(year, month, monthDate, hour, minute, second);
	}

	// If the date string didn't match the standard date string format, test whether it
	// includes either MM-dd-yy(yy) or MM/dd/yy(yy).
	re = new RegExp("[0-9]{2}(/|-)[0-9]{2}(/|-)[0-9]{2,}", "g");
	match = re.exec(dateStr);
	if (match != null) {
		// Get the month, date, and year from the match. First, use the forward slash as
		// the delimiter. If that returns an Array of only one element, use the dash
		// delimiter instead.
		var mdy = match[0].regsplit("/");
	    if (mdy.length == 1) {
			mdy = match[0].regsplit("-");
	    }

		// Extract the month number and day-of-month values from the date string.
		month = Number(mdy[0]) - 1;
		monthDate = Number(mdy[1]);

		// If the year value is two characters, then we must add the century to it. 
		if (mdy[2].length == 2) {
			twoDigitYear = Number(mdy[2]);
			// Assumes that years less than 50 are in the 21st century
			year = (twoDigitYear < 50) ? twoDigitYear + 2000 : twoDigitYear + 1900;
		} else {
			// Extract the four-digit year
			year = mdy[2];
		}
		// Return the new date.
		return new Date(year, month, monthDate, hour, minute, second);
	}

	// If the date string didn't match the standard date string format, test whether it
	// includes either dd.MM.yy(yy).
	re = new RegExp("[0-9]{1,2}(\\.)[0-9]{1,2}(\\.)[0-9]{2,}", "g");
	match = re.exec(dateStr);
	if (match != null) {
		// Get the month, date, and year from the match. First, use the forward slash as
		// the delimiter. If that returns an Array of only one element, use the dash
		// delimiter instead.
		var mdy = match[0].regsplit(".");

		// Extract the month number and day-of-month values from the date string.
		monthDate = Number(mdy[0]);
		month = Number(mdy[1]) - 1;

		// If the year value is two characters, then we must add the century to it. 
		if (mdy[2].length == 2) {
			twoDigitYear = Number(mdy[2]);
			// Assumes that years less than 50 are in the 21st century
			year = (twoDigitYear < 50) ? twoDigitYear + 2000 : twoDigitYear + 1900;
		} else {
			// Extract the four-digit year
			year = mdy[2];
		}
		// Return the new date.
		return new Date(year, month, monthDate, hour, minute, second);
	}

	// Check whether the string includes a time value of the form of h(h):mm(:ss).
	re = new RegExp("[0-9]{1,2}:[0-9]{2}(:[0-9]{2,})?", "g");
	match = re.exec(dateStr);
	if (match != null) {
		// If the length is 4, the time is given as h:mm. If so, then the length of the
		// first part of the time (hours) is only one character. Otherwise, it is two
		// characters in length.
		var firstLength = 2;
		if (match[0].length == 4) {
			firstLength = 1;
		}
		// Extract the hour and minute parts from the date string. If the length of the
		// match is greater than five, assume that it includes seconds.
		hour = Number(dateStr.substr(match.index, firstLength));
		minute = Number(dateStr.substr(match.index + firstLength + 1, 2));
		if (match[0].length > 5) {
			second = Number(dateStr.substr(match.index + firstLength + 4, 2));
		}
		// Return the new date.
		return new Date(year, month, monthDate, hour, minute, second);
	}
	return new Date(year, month, monthDate, hour, minute, second);
};
// }}}

/*
 *	Class attachLib
 *
 *	Loads an SWF-Movie like an Movieclip
 *	call like: MovieClip.attachLibrary("lib_icons.swf", "mylib", 1, "login");
 */
MovieClip.prototype.tabEnabled = false;
    
// {{{ MovieClip.attachLibrary()
MovieClip.prototype.attachLibrary = function(libName, newName, depth, param, onLoadFunc) {
	status("loading " + libName + " - " + newName);
	var initObj = {
		libName		: libName,
		param		: param,
		onLoadFunc	: onLoadFunc,
		preload		: false
	};
	
	this.attachMovie("loadLib", newName, depth, initObj);
};
// }}}
// {{{ MovieClip.preloadLibrary()
MovieClip.prototype.preloadLibrary = function(libName, newName, depth, onLoadFunc) {
	var initObj = {
		libName		: libName,
		onLoadFunc	: onLoadFunc,
		preload		: true
	};
	
	this.attachMovie("loadLib", newName, depth, initObj);
};
// }}}

/*
 *	Class XML
 */
// {{{ XML.getRootNode()
XML.prototype.getRootNode = function() {
	tempNode = this.firstChild;
	
	while (tempNode != null && tempNode.nodeType != 1) {
		tempNode = tempNode.nextSibling;
	}
	return tempNode;
};
// }}}
// {{{ XML.isRootNode()
XMLNode.prototype.isRootNode = function() {
	return (this.parentNode.parentNode == null && this.parentNode != null);
};
// }}}

// {{{ XMLNode.replaceChildren()
XMLNode.prototype.replaceChildren = function(newXML) {
	while (this.firstChild != null) {	
		this.firstChild.removeNode();
	}
	
	while (newXML.firstChild != null) {
		this.appendChild(newXML.firstChild);	
	}
};
// }}}
// {{{ XMLNode.isParentOf()
XMLNode.prototype.isParentNodeOf = function(node) {
	//status(this);
	if (this == node.parentNode) {
		return true;
	} else if (node.parentNode == undefined) {
		return false;
	} else {
		return this.isParentNodeOf(node.parentNode);	
	}
};
// }}}
// {{{  XMLNode.getName()
XMLNode.prototype.getName = function() {
	if (this.nodeName.indexOf(":") == -1) {
		return this.nodeName;
	} else {
		return this.nodeName.substring(this.nodeName.indexOf(":") + 1);
	}
};
// }}}
// {{{ XMLNode.getNameSpace()
XMLNode.prototype.getNameSpace = function() {
	if (this.nodeName.indexOf(":") == -1) {
		return "";
	} else {
		return this.nodeName.substring(0, this.nodeName.indexOf(":"));
	}
};
// }}}
// {{{ XMLNode.stripXMLDbIds()
XMLNode.prototype.stripXMLDbIds = function() {
	var i;
	
	if (this.attributes[conf.ns.database + ':id'] != undefined) {
		delete this.attributes[conf.ns.database + ':id'];	
	}
	for (i = 0; i < this.childNodes.length; i++) {
		this.childNodes[i].stripXMLDbIds();	
	}
};
// }}}
// {{{  XMLNode.setNodeIdByDBId()
XMLNode.prototype.setNodeIdByDBId = function() {
	var i;
	
	if (this.nodeType == 1) {
		this.nid = this.getNodeDBId();
		for (i = 0; i < this.childNodes.length; i++) {
			this.childNodes[i].setNodeIdByDBId();	
		}
	}
};
// }}}
// {{{ XMLNode.getNodeDBId()
XMLNode.prototype.getNodeDBId = function() {
	return this.attributes[conf.ns.database + ":id"];	
};
// }}}
// {{{ XMLNode.removeIds();
XMLNode.prototype.removeIds = function() {
	var i;
	
	if (this.nodeType == 1) {
		delete this.nid
		for (i = 0; i < this.childNodes.length; i++) {
			this.childNodes[i].removeIds();	
		}
	}
};
// }}}
// {{{ XMLNode.removeIdAttribute();
XMLNode.prototype.removeIdAttribute = function() {
	var i;
	
	if (this.nodeType == 1) {
                delete this.attributes[conf.ns.database + ":id"];	
		delete this.nid
		for (i = 0; i < this.childNodes.length; i++) {
			this.childNodes[i].removeIdAttribute();	
		}
	}
};
// }}}
// {{{ XMLNode.searchForId()
XMLNode.prototype.searchForId = function(id) {
	var i, val;

	if (this.nid == id) {
		return this;
	} else {
		if (this.nodeType == 1) {
			for (i = 0; i < this.childNodes.length; i++) {
				val = this.childNodes[i].searchForId(id);	
				if (val != null) {
					return val;
				}
			}
		}
		return null;
	}
};
// }}}
XMLNode.prototype.nid = null;
/*
XMLNode.prototype.nnid = null;

// {{{ XMLNode.getID()
XMLNode.prototype.getID = function() {
        if (this.nnid == null) {
            this.nnid = this.attributes[conf.ns.database + ":id"];	
        }
        return this.nnid;
};
// }}}
// {{{ XMLNode.setID()
XMLNode.prototype.setID = function(value) {
    this.nnid = value;
};
// }}}
XMLNode.prototype.addProperty("nid", XMLNode.prototype.getID, XMLNode.prototype.setID);
/**/

/*
 *	Class TextField
 */
TextField.prototype.tabEnabled = false;
// {{{ TextField.localToGlobal()
TextField.prototype.localToGlobal = MovieClip.prototype.localToGlobal;
// }}}
// {{{ TextField.getGlobalX()
TextField.prototype.getGlobalX = Movieclip.prototype.getGlobalX;
// }}}
// {{{ TextField.getGlobalY()
TextField.prototype.getGlobalY = Movieclip.prototype.getGlobalY;
// }}}
// {{{ TextField.initFormat()
TextField.prototype.initFormat = function(tFormat) {
	this.embedFonts = tFormat.embedFonts;
	this.setNewTextFormat(tFormat);
	this.setTextFormat(tFormat);
};
// }}}
// {{{ TextField.prepareHtmlText()
TextField.prototype.prepareHtmlText = function(htmlString) {
	var linkEndIndex = 0;
    var newURL = "";

	htmlString = htmlString.replace("<p />", "<p> </p>");
	htmlString = htmlString.replace("<li />", "<li> </li>");
	htmlString = htmlString.replace("<a", "<u><a");
	htmlString = htmlString.replace("</a>", "</a></u>");
        htmlString = htmlString.replace("<small>", "<font size=\"" + this.textFormatSmall.size + "\">");
        htmlString = htmlString.replace("</small>", "</font>");

	do {
		//get link target
		linkStartIndex = htmlString.indexOf("<a href=\"", linkEndIndex);
		if (linkStartIndex != -1) {
			linkEndIndex = htmlString.indexOf("\"", linkStartIndex + 9);
			targetStartIndex = htmlString.indexOf("target=\"", linkEndIndex);
			targetEndIndex = htmlString.indexOf("\"", targetStartIndex + 8);

            newURL = htmlString.substring(linkStartIndex + 9, linkEndIndex);
            if (newURL.substring(0, 8) == "pageref:") {
                newURL = conf.project.tree.pages.getUriById(newURL.substring(8));
            }
			this._parent.textLinks.push([newURL, htmlString.substring(targetStartIndex + 8, targetEndIndex)]);

			//insert as link
			newurl = "asfunction:textlink," + (this._parent.textLinks.length - 1) + "," + targetPath(this._parent);
			htmlString = htmlString.substring(0, linkStartIndex + 9) + newurl + htmlString.substring(linkEndIndex);
			diffLength = this._parent.textLinks[this._parent.textLinks.length - 1].length - newurl.length;
			linkStartIndex = linkStartIndex - diffLength;
			linkEndIndex = linkEndIndex - diffLength;
		} 
	} while (linkStartIndex != -1)

    return htmlString;
};
// }}}
// {{{ TextField.reducedHtmlText()
TextField.prototype.reducedHtmlText = function() {
    var tempXML = new XML("<root>" + this.htmlText + "</root>");
    newStr = this.reducedHtmlXML(tempXML.firstChild);

    newStr = newStr.replace([
        ["<i></i>"	  , ""],
        ["<b></b>"	  , ""],
        ["<small></small>", ""],
        ["</ul><ul>", ""]
    ]);
    
    return newStr;
};
// }}}
// {{{ TextField.reducedHtmlXML()
TextField.prototype.reducedHtmlXML = function(node) {
    var newStr = "";

    if (node.nodeType == 1) { // XML Element
        var nodeName = node.nodeName.toLowerCase();
        var startTag = "";
        var endTag = "";

        if (nodeName == "p" || nodeName == "b" || nodeName == "i") {
            startTag = "<" + nodeName + ">";
            endTag = "</" + nodeName + ">";
        } else if (nodeName == "li") {
            startTag = "<ul><li>";
            endTag = "</li></ul>";
        } else if (nodeName == "font") {
            if (node.attributes.size == conf.interface.textformat_input_small.size) {
                startTag = "<small>";
                endTag = "</small>";
            }
        } else if (nodeName == "a") {
            var link = node.attributes.href;
            linkStartIndex = link.indexOf("asfunction:textlink,");
            linkEndIndex = link.indexOf(",", linkStartIndex + 21);
            linkIndex = link.substring(linkStartIndex + 20, linkEndIndex);

            if (this._parent.textLinks[linkIndex][0].substring(0, 8) == "pageref:") {
                newURL = "pageref:" + conf.project.tree.pages.getIdByUri(this._parent.textLinks[linkIndex][0].substring(8));
            } else {
                newURL = this._parent.textLinks[linkIndex][0];
            }

            startTag += "<a ";
            startTag += "href=\"" + newURL.toXMLString() + "\" ";
            startTag += "target=\"" + this.textLinks[linkIndex][1].toXMLString() + "\">";
            endTag += "</a>";
        }

        newStr += startTag;
        for (var i = 0; i < node.childNodes.length; i++) {
            newStr += this.reducedHtmlXML(node.childNodes[i]);
        }
        newStr += endTag;
    } else if (node.nodeType == 3) { // Text-Node
        newStr += node.nodeValue.toXMLString();
    }

    return newStr;
};
// }}}

/*
 *  Class TextFormat
 */
TextFormat.prototype.embedFonts = null;
TextFormat.prototype.lineSpacing = null;

/*
 *  Class Number
 */
// {{{ Number.limit()
Number.prototype.limit = function(min, max) {
	if (min != null && min != undefined && this < min) {
		return min;
	} else if (max != null && max != undefined && this > max) {
		return max;
	} else {
		return this;
	}
};
// }}}

/*
 *	Class Button
 */
Button.prototype.tabEnabled = false;

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
