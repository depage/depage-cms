<?php
define("IS_IN_CONTOOL", true);

require_once('../framework/lib/lib_global.php');
require_once('lib_project.php');

set_time_limit(0);
ignore_user_abort();

if ($_GET['project'] == '') {
	$projects = $project->xmldb->get_docs();
	echo("show project:<br/>");
	foreach ($projects AS $thisproject => $project_id) {
		echo("<a href=\"{$_SERVER['PHP_SELF']}?project=" . urlencode($thisproject) . "\">$thisproject</a><br/>");
	}
} else if ($_GET['project'] != '') {
	//get doc
	$doc_id = $xml_db->get_doc_id_by_name($_GET['project']);
	if ($doc_id !== false) {
		$xml_doc = $xml_db->get_doc_by_id($doc_id);
		
		header("Content-type: text/xml"); 
		echo($xml_doc->dump_mem(true));
	} else {
		echo("unknown project");
	}
}

?>
