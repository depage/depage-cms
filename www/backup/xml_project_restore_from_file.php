<?php
define("IS_IN_CONTOOL", true);

require_once("../framework/lib/lib_global.php");
require_once("lib_project.php");
require_once("lib_tpl.php");
require_once("lib_pocket_server.php");
	
set_time_limit(0);
ignore_user_abort();

if ($_GET['project'] == '') {
	$projects = $project->xmldb->get_docs();
	echo("restore project:<br/>");
	foreach ($projects AS $thisproject => $project_id) {
		echo("<a href=\"{$_SERVER['PHP_SELF']}?project=" . urlencode($thisproject) . "\">$thisproject</a><br/>");
	}
} else if ($_GET['project'] != '') {
	$project_id = $project->xmldb->get_doc_id_by_name($_GET['project']);
	if ($project_id !== false) {
		$filename = 'xml_project_' . str_replace(' ', '_', strtolower($_GET['project'])) . '.xml';
		if (!($new_xml_doc = domxml_open_file("{$conf->path_server_root}{$conf->path_base}/backup/{$filename}"))) {
			exit("error parsing \"{$filename}\"");	
		}

		//save doc	
		$error = $project->xmldb->save_node($new_xml_doc->document_element(), $project_id);
		
		/*
		$XSLTProc = new XSLT_Processor();
		$XSLTProc->cache_template($project, "html");

		$pocket_client = new PocketClient($_SERVER["SERVER_ADDR"], $conf->pocket_port);
		if ($pocket_client->connect()) {
			$xml_def = $XSLTProc->get_navigation($project);
			
			$data['data'] = $xml_def->dump_node($xml_def->document_element());
			$func = new ttRpcFunc("update_contentTree", $data);
			$pocket_client->send_to_clients($func, $project);
			
			$pocket_client->close();
		}
		*/

		//$xml_db->optimize_database();

		echo("restored \"{$_GET['project']}\" from \"{$filename}\"");
	}
}

?>
