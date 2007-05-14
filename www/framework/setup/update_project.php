<?php
/**
 * @file	update_project.php
 *
 * Update Routine
 *
 * This file defines the main class for updating one depage 
 * project to another greater version. All functions for 
 * subsequent conversion are defined in another file with 
 * following naming scheme:
 *
 *		update_project_%fromversion%_%toversion%.php
 *
 * this file will be included for conversion.
 *
 *
 * copyright (c) 2002-2007 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author	Frank Hellenkamp [jonas@depagecms.net]
 *
 * $Id: lib_project.php,v 1.15 2004/11/12 19:45:31 jonas Exp $
 */
// {{{ define and includes
define("IS_IN_CONTOOL", true);

require_once("../lib/lib_global.php");
require_once("lib_project.php");
// }}}

/**
 * defines main functions for updates and includes 
 * necessary update classes
 */
class project_updater {
	var $from_version = array();
	var $to_version = array();
	var $modules = array();

	var $from;
	var $to;
	var $project_xml;
	var $project_ctx;

	// {{{ constructor
	function project_updater($to) {
		$this->get_available_update_modules();
		$this->to = $to;
	}
	// }}}
	// {{{ get_available_update_modules()
	function get_available_update_modules() {
		$files = array();

		$current_dir = opendir('.');
		while ($entryname = readdir($current_dir)) {
			if (is_file($entryname)) {
				$files[] = $entryname;
			}
		}
		closedir($current_dir);
		natcasesort($files);
		
		foreach ($files as $file) {
			if (preg_match("/^update_project_([^\_]+)_([^_]+)\.php/", $file, $matches)) {
				$this->modules[] = $matches[0];
				$this->from_version[] = $matches[1];
				$this->to_version[] = $matches[2];
			}
		}
	}
	// }}}
	// {{{ get_version_from_data()
	function get_version_from_data() {
		$node_project = $this->project_xml->document_element();

		if ($node_project->get_attribute('version') == '') {
			$this->from = '0.9.6';
		} else {
			$this->from = $node_project->get_attribute('version');
		}
	}
	// }}}
	// {{{ get_version_from_db()
	function get_version_from_db() {

	}
	// }}}
	// {{{ open_data_from_file()
	function open_data_from_file($filename) {
		$this->project_xml = domxml_open_file($filename);
		$this->project_ctx = project::xpath_new_context($this->project_xml);

		$this->get_version_from_data($this->project_xml);
	}
	// }}}
	// {{{ open_data_from_db()
	function open_data_from_db() {

	}
	// }}}
	// {{{ convert_data()
	function convert_data(&$error) {
		$start = array_search($this->from, $this->from_version);
		$end = array_search($this->to, $this->to_version);
		if ($start === false) {
			$error = "no convert module for source-version " . $this->from;
			return false;
		} elseif ($end === false) {
			$error = "no conversion-module for target-version " . $this->to;
			return false;
		} else {
			//init conversion modules
			$updaters = array();
			for ($i = $start; $i <= $end; $i++) {
				require_once($this->modules[$i]);
				$class = "project_updater_" . str_replace('.', '_', $this->from_version[$i]) . "__" . str_replace('.', '_', $this->to_version[$i]);
				$updaters[] = new $class();
			}
			foreach ($updaters as $updater) {
				echo("updating from {$updater->from} to {$updater->to}\n");
				$this->project_xml = $updater->convert_xml_data($this->project_xml);
				$updater->update_database_structure();
			}

			$error = "none";

			return true;
		}
	}
	// }}}
	// {{{ write_data_to_file()
	function write_data_to_file($filename) {
		$this->project_xml->dump_file($filename, false, true);
		echo("written\n");
	}
	// }}}
}

$updater = new project_updater("0.9.7");
$updater->open_data_from_file("{$conf->path_server_root}{$conf->path_base}/backup/xml_project_hesse_internet_0.9.6.xml");
if (!$updater->convert_data($error_str)) {
	echo($error . "\n");
} else {
	$updater->write_data_to_file('../temp/converted.xml');
}
/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
