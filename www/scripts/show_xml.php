<?php

if (!($xml_doc = domxml_open_mem(implode('', file("php://input"))))) {
	exit("error parsing document");	
}

//*
header("Content-type: text/xml"); 
echo($xml_doc->dump_mem(false));
//*/
	
/*
header("Content-type: text/xml"); 
echo(implode('', file("php://input")));
//*/
?>