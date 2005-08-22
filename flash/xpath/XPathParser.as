/**
	Copyright (c) 2002 Neeld Tanksley.  All rights reserved.
	
	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:
	
	1. Redistributions of source code must retain the above copyright notice,
	this list of conditions and the following disclaimer.
	
	2. Redistributions in binary form must reproduce the above copyright notice,
	this list of conditions and the following disclaimer in the documentation
	and/or other materials provided with the distribution.
	
	3. The end-user documentation included with the redistribution, if any, must
	include the following acknowledgment:
	
	"This product includes software developed by Neeld Tanksley
	(http://xfactorstudio.com)."
	
	Alternately, this acknowledgment may appear in the software itself, if and
	wherever such third-party acknowledgments normally appear.
	
	4. The name Neeld Tanksley must not be used to endorse or promote products 
	derived from this software without prior written permission. For written 
	permission, please contact neeld@xfactorstudio.com.
	
	THIS SOFTWARE IS PROVIDED "AS IS" AND ANY EXPRESSED OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
	FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL NEELD TANKSLEY
	BE LIABLE FOR ANY DIRECT, INDIRECT,	INCIDENTAL, SPECIAL, EXEMPLARY, OR 
	CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE 
	GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) 
	HOWEVER CAUSED AND ON ANY THEORY OF	LIABILITY, WHETHER IN CONTRACT, STRICT 
	LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT 
	OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
**/
_global.XPathParser = function(context,query){
	this.stringQuery;
	this.parseQuery(context,query);
}

XPathParser.AxisNames = new Object();
XPathParser.AxisNames["ancestor::"] = 1;
XPathParser.AxisNames["ancestor-or-self::"] = 2;
XPathParser.AxisNames["attribute::"] = 3;
XPathParser.AxisNames["@"] = 3;
XPathParser.AxisNames["child::"] = 4;
XPathParser.AxisNames["descendant::"] = 5;
XPathParser.AxisNames["descendant-or-self::"] = 6;
XPathParser.AxisNames["//"] = 6;
XPathParser.AxisNames["following::"] = 7;
XPathParser.AxisNames["following-sibling::"] = 8;
XPathParser.AxisNames["parent::"] = 9;
XPathParser.AxisNames[".."] = 9;
XPathParser.AxisNames["preceding::"] = 10;
XPathParser.AxisNames["preceding-sibling::"] = 11;
XPathParser.AxisNames["self::"] = 12;
XPathParser.AxisNames["."] = 12;
XPathParser.AxisNames["namespace::"] = 13;

XPathParser.AxisFunctions = new Array("root",
							"ancestor",
							"ancestorOrSelf",
							"attribute",
							"child",
							"descendant",
							"descendantOrSelf",
							"following",
							"followingSibling",
							"parent",
							"preceding",
							"precedingSibling",
							"self",
							"namespace");

XPathParser.parseQuery = function(context,query){
	var steps = new Array();
	var collChars;
	var c;
	//var currAxis = XPathParser.AxisNames["child::"];
	var currAxis = undefined;
	var currPredicate = undefined;
	
	//var start = getTimer();
	
	for(var i=0;i<query.length;i++){
		if(query.charCodeAt(i) < 33){
			continue;
		}
		c = query.charAt(i);	
		
		switch(c){
			case "/": //end of path step || root || descendant-or-self
				if(query.charAt(i+1) != "/" && query.charAt(i-1) != "/"){ //end of path step || root
					if(steps.length == 0 && currAxis == undefined){
						steps.push({axis:0, text:"*", predicate:undefined});
					}else{
						steps.push({axis:currAxis, text:collChars, predicate:currPredicate});
						currPredicate = undefined;
						currAxis = undefined;
						collChars = "";
					}
				}else{//descendant-or-self, weird case handled outside of axis change grabber below, should revisit this code
					if(i!=0){
						steps.push({axis:currAxis, text:collChars, predicate:currPredicate});
						currPredicate = undefined;
						//currAxis = XPathParser.AxisNames["child::"];
						currAxis = undefined;
						collChars = "";
					}
					steps.push({axis:XPathParser.AxisNames["descendant-or-self::"], text:"*", predicate:undefined});
					currPredicate = undefined;
					//currAxis = XPathParser.AxisNames["child::"];
					currAxis = undefined;
					collChars = "";
					i++;
				}
				break;				
			case "[": //beginning of a test
				if(currPredicate == undefined){										
					var innerNestCount = 0;
					var foundMatching = false;
					while(i<query.length && !foundMatching){
						if(query.charAt(i) == "["){
							innerNestCount++;
						}
						if(query.charAt(i) == "]"){
							innerNestCount--;
							if(innerNestCount == 0){
								foundMatching = true;
							}
						}
						currPredicate += query.charAt(i);
						i++;
					}
					i--;
					break;
				}else{ //this only allows one predicate
					return;
				}
			default:
				collChars += c;
				break;
		}
		
		//look for axis changes
		if(XPathParser.AxisNames[collChars] != undefined){
			if(query.charAt(i+1) != "."){ // make sure this is . and not the start of ..
				currAxis = XPathParser.AxisNames[collChars];
				collChars = "";
			}
		}
	}
	
	//add any trailing path info
		if(steps[steps.length-1].axis == 0){
			collChars = "*";
		}
		steps.push({axis:currAxis, text:collChars,predicate:currPredicate});

	//for(var i=0;i<steps.length;i++){
	//	trace("step =  " + steps[i].text);
	//}

	return XPathParser.processSteps(context,steps);
}

XPathParser.processSteps = function(context,steps){
	if(context instanceof XML)
		context = XPathAxes.root(context);
	if(context instanceof XMLNode)
		context = new Array(context);
	
	var axis,text;
	var results = new Array();

	for(var i=0;i<steps.length;i++){
		axis = steps[i].axis;
		text = steps[i].text;
		predicate = steps[i].predicate;
		
		
		if((axis == XPathParser.AxisNames["self::"]||axis == XPathParser.AxisNames["parent::"]||XPathParser.AxisNames["text()"]) && text.length==0){
			text = "*";
		}
		if(axis == undefined){
			axis = XPathParser.AxisNames["child::"]
		}
		context = XPathParser.processStep(XPathAxes[XPathParser.AxisFunctions[axis]],context,text);
	
		// run test for each step
		if(predicate != undefined){
			context = XPathParser.runTest(context,predicate);
		}
	}
	
	
	
	for(var j=0;j<context.length;j++){
		results.push(context[j]);
	}
	return results;
}

XPathParser.processStep= function(axisFunction,context,name){
	var retVal = new Array();
	for(var i=0;i<context.length;i++){
		XPathUtils.appendArray(retVal,XPath.getNamedNodes(axisFunction.call(this,context[i]),name));
	}

	return retVal;
}




XPathParser.runTest = function(context, test){
	test = test.substr(1,test.length-2);
	var childIndex;
	var nodeArray = new Array();
	var steps = XPathPredicate.parse(test);
	
	for(var i=0;i<context.length;i++){
		//childIndex = XPathParser.getChildIndex(context[i]);
		//if(XPathPredicate.test(context[i],test,context,childIndex,XPathUtils.cloneArray(steps))){
		if(XPathPredicate.test(context[i],test,context,XPathUtils.cloneArray(steps))){
			nodeArray.push(context[i]);
		}
	}

	return nodeArray;
}

XPathParser.getChildIndex = function(kid){
	var bros = kid.parentNode.childNodes;
	var sibCount = 0;
	
	for(var i=0;i<bros.length;i++){
		if(bros[i].nodeName == kid.nodeName){
			sibCount++;
		}
		if(bros[i] === kid){
			return sibCount;
		}
	}
	return 0;
}

