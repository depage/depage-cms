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
/////////////////////////////////
//
//     XMLNode XPath wrapper methods
//
//     These are a series of methods that are added to the 
//     XML object to run XPath querys and for convieniant 
//     access to the axis types.
//
////////////////////////////////
XMLNode.prototype.selectNodes = function(query){
	return XPath.selectNodes(this,query)
}
XMLNode.prototype.ancestor = function(){
	return XPathAxes.ancestor(this)
}
XMLNode.prototype.ancestorOrSelf = function(){
	return XPathAxes.ancestorOrSelf(this)
}
XMLNode.prototype.attribute = function(){
	return XPathAxes.attribute(this)
}
XMLNode.prototype.child = function(){
	return XPathAxes.child(this)
}
XMLNode.prototype.descendant = function(){
	return XPathAxes.descendant(this)
}
XMLNode.prototype.descendantOrSelf = function(){
	return XPathAxes.descendantOrSelf(this)
}
XMLNode.prototype.following = function(){
	return XPathAxes.following(this)
}
XMLNode.prototype.followingSibling = function(){
	return XPathAxes.followingSibling(this)
}
XMLNode.prototype.parent = function(){
	return XPathAxes.parent(this)
}
XMLNode.prototype.preceding = function(){
	return XPathAxes.preceding(this)
}
XMLNode.prototype.precedingSibling = function(){
	return XPathAxes.precedingSibling(this)
}
XMLNode.prototype.self = function(){
	return XPathAxes.self(this)
}
XMLNode.prototype.namespace = function(){
	return XPathAxes.namespace(this)
}
XMLNode.prototype.root = function(){
	return XPathAxes.root(this)
}
XMLNode.prototype.getNamedNodes = function(name){
	return XPathAxes.getNamedNodes(this.childNodes,name);
}
XMLNode.prototype.stringValue = function(){
	return XPathAxes.stringValue(this);
}
XMLNode.prototype.name = function(){
	return XPathFunctions.name([[this]]);
}
XMLNode.prototype.localName = function(){
	return XPathFunctions.localName([[this]]);
}
XMLNode.prototype.namespaceURI = function(){
	return XPathFunctions.namespaceURI([[this]]);
}
