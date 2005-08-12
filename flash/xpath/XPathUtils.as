_global.XPathUtils = function(){

}
XPathUtils.cloneArray = function(obj){
	var retArr = new Array();
	for(var i=0;i<obj.length;i++){
		retArr.push(obj[i]);
	}
	return retArr;
}

/**
*	appends values from array2 to array1	
**/
XPathUtils.appendArray = function(array1,array2){
	for(var j=0;j<array2.length;j++){
			array1.push(array2[j]);
	}	
	return array1;
}
/**
	checkEmpty

	This method was adapted from
	//--------------------------------------------------
	// XMLnitro v 2.1
	//--------------------------------------------------
	// Branden J. Hall
	// Fig Leaf Software
	// October 1, 2001
**/
XPathUtils.checkEmpty = function(contextNode){
	var text = contextNode.nodeValue;
	var max = text.length;
	var empty = true;
	for (var i=0;i<max;++i){
		if (ord(substring(text, i+i, 1))>32){
			empty = false;
			break;
		}
	}
	return empty;
}
