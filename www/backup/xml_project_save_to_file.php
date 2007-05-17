<?php
define("IS_IN_CONTOOL", true);

require_once("../framework/lib/lib_global.php");
require_once("lib_project.php");
	
set_time_limit(0);
ignore_user_abort();

if ($_GET['project'] == '') {
	$projects = $project->xmldb->get_docs();
	echo("save project:<br/>");
	foreach ($projects AS $thisproject => $project_id) {
		echo("<a href=\"{$_SERVER['PHP_SELF']}?project=" . urlencode($thisproject) . "\">$thisproject</a><br/>");
	}
} else if ($_GET['project'] != '') {
	//get doc
	$doc_id = $project->xmldb->get_doc_id_by_name($_GET['project']);
	if ($doc_id !== false) {
		$xml_doc = $project->xmldb->get_doc_by_id($doc_id);
		
		$filename = 'xml_project_' . str_replace(' ', '_', strtolower($_GET['project'])) . '.xml';

		$xml_doc->dump_file("{$conf->path_server_root}{$conf->path_base}/backup/{$filename}", false, true);

		echo("saved {$_GET['project']} to \"{$filename}\"");
	} else {
		echo("unknown project");
	}
}

?>
