<?php
/*
 * $Id: test.php,v 1.52 2004/11/12 19:45:31 jonas Exp $
 */
	// {{{ define and includes
	define("IS_IN_CONTOOL", true);
	
	require_once("lib/lib_global.php");
	require_once("lib_auth.php");
	require_once("lib_tpl.php");
	require_once("lib_project.php");
	require_once("lib_pocket_server.php");
	require_once("lib_tasks.php");
	require_once("lib_files.php");
	require_once("Archive/tar.php");
	// }}}
	
	// {{{ diff_xml()
	/**
	 * diffs two xml document objects and
	 * returns the nodes and attributes, which
	 * changed from document1 to document2
	 *
	 * @param	$xml1 (xml object) original xml object
	 * @param	$xml2 (xml object) changed xml object
	 *
	 * @return	$diff (array) array of changed nodes
	 */
	function diff_xml(&$xml1, &$xml2) {
		$diff = array();
		$diff['nodes'] = array();
		$diff['attr'] = array();

	};
	// }}}

	$project_name = 'Dokumentation';
	
	/*
	// define test xml documents
	$xml1 = $project->xmldb->get_doc_by_id(10000);

	$xmlstr2 = "<?xml version=\"1.0\" ?>
		<bla>
			<blub test=\"miao\">
			</blub>
		</bla>";

	$project->copy_element_in("Dokumentation", 5468, 3130, "in");
	
	$result = null;

	// print results
	if (is_callable(array($result, 'dump_mem'))) {
		echo($result->dump_mem(true));
	} else {
		var_dump($result);
	}
	*/
	phpinfo();

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
