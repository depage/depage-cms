<?php
/**
 * @file	preview.php
 *
 * Preview script
 *
 * This file handles all calls for previews and transform
 * the xml data into the wanted format.
 *
 *
 * copyright (c) 2002-2004 Frank Hellenkamp [jonas.info@gmx.net]
 *
 * @author	Frank Hellenkamp [jonas.info@gmx.net]
 *
 * @todo	change preview behaviour in the following manner:
 *			don't save xml data after every edit. instead work
 *			with a temporary copy of the data until another page
 *			is selected or the client logs out.
 *			deny editing of the same page at the same time, show
 *			only the current copy of it.
 *
 * $Id: preview.php,v 1.32 2004/11/12 19:45:31 jonas Exp $
 */

// {{{ define and require
define('IS_IN_CONTOOL', true);

require_once('lib/lib_global.php');
require_once('lib_html.php');
require_once('lib_auth.php');
require_once('lib_xmldb.php');
require_once('lib_tpl.php');
require_once('lib_pocket_server.php');
require_once('lib_files.php');
// }}}

// {{{ getParameterByUrl()
/**
 * gets given parameter by calling url
 *
 * @param	$url (string) url of call
 * @param	$project (string) name of project
 * @param	$type (string) name of template set
 * @param	$access (string) 'browse' or 'preview'
 */
function getParameterByUrl($url, $project = "", $type = "", $access = "") {
	global $conf;
	
	$param = array();
	
	if ($project == "") {
		$param['access'] = 'preview';
		
		$url = parse_url($url);
		$path = explode('/', substr($url['path'], strpos($url['path'], 'preview')));
		$param['type'] = $path[1];
		$param['sid'] = $path[2];
		$param['wid'] = $path[3];
		if ($path[4] == 'cached') {
			$param['cached'] = true;
		} else {
			$param['cached'] = false;
		}
		$pathinfo = pathinfo($url['path']);
		$param['file_name'] = $pathinfo['basename'];
		$param['file_extension'] = $pathinfo['extension'];
		foreach ($conf->output_file_types AS $file_type_name => $file_type) {
			if ($file_type['extension'] == $pathinfo['extension']) {
				$param['file_type'] = $file_type_name;
			}
		}
		$param['file_path'] = '/' . implode('/', array_slice($path, 5));
		$param['path'] = '/' . implode('/', array_slice($path, 5, -1));
		$param['lang'] = $path[6];
	} else {
		$param['project'] = $project;
		$param['type'] = $type;
		$param['sid'] = '';
		$param['wid'] = '';
		$param['cached'] = true;
		
		$url = parse_url($url);
		if (strpos($url['path'], 'dyn') === false) { 
			$param['access'] = 'index';
		} else {
			$param['access'] = $access;
			
			$path = explode('/', substr($url['path'], strpos($url['path'], 'dyn')));
			$pathinfo = pathinfo($url['path']);
			$param['file_name'] = $pathinfo['basename'];
			$param['file_extension'] = $pathinfo['extension'];
			foreach ($conf->output_file_types AS $file_type_name => $file_type) {
				if ($file_type['extension'] == $pathinfo['extension']) {
					$param['file_type'] = $file_type_name;
				}
			}
			$param['file_path'] = '/' . implode('/', array_slice($path, 0));
			$param['path'] = '/' . implode('/', array_slice($path, 0, -1));
			$param['lang'] = $path[1];
		}
		
	}
	
	return $param;
}
// }}}

/**
 * ----------------------------------------------
 */ 
headerNoCache();
set_time_limit(60);

$param = getParameterByUrl($_SERVER['REQUEST_URI'], $_GET['project'], $_GET['type'], $_GET['access']);

$user = new ttUser();
if (($project_name = $user->is_valid_user($param['sid'], $param['wid'], $_SERVER['REMOTE_ADDR'])) || $param['project'] != "") {
	if ($project_name === false) {
		$project_name = $param['project'];
	}
	$xml_proc = tpl_engine::factory('xslt', $param);
	if ($param['access'] == 'browse' || $param['access'] == 'preview') {
		$id = $xml_proc->get_id_by_path($param['file_path'], $project_name);
		if ($project_name && $id != null) {
			$data['lang'] = $param['lang'];
			if (!$param['cached']) {
				$transformed = $xml_proc->transform($project_name, $param['type'], $id, $param['lang'], $param['cached']);
			} else if (($transformed = $xml_proc->get_from_transform_cache($project_name, $param['type'], $id, $param['lang'], $param['access'])) === false) {
				$transformed = $xml_proc->transform($project_name, $param['type'], $id, $param['lang'], $param['cached']);
				if ($transformed != false) {
					$xml_proc->add_to_transform_cache($project_name, $param['type'], $id, $param['lang'], $param['access'], $transformed, $xml_proc->ids_used);
				}
			}

			if ($param['disableCallback'] != true) {
				$data['id'] = $id;
			} else {
				$data['id'] = 'false';
			}
			$data['error'] = $xml_proc->error;
			
			if ($param['access'] == 'preview') {
				$func = new ttRpcFunc('preview_loaded', $data);
				if (strpos($_SERVER['HTTP_REFERER'], 'http://' . $_SERVER['HTTP_HOST'] . $conf->path_projects . '/') !== false) {
					$pocket_client = new PocketClient('127.0.0.1', $conf->pocket_port);
					if ($pocket_client->connect()) {
						$pocket_client->send_to_client($func, $param['sid'], $param['wid']);
					}
				}
			}
			
			if ($transformed) {
				if (!$conf->output_file_types[$param['file_type']]['dynamic']) {
					headerType($transformed['content_type'], $transformed['content_encoding']);
					echo($transformed['value']);
				} else {
					$cache_path = substr($param['path'], strpos($param['path'], '/', 2));
					$file_path = $project->get_project_path($project_name) . "/cache_{$param['type']}{$cache_path}/{$param['file_name']}";
					$file_access = fs::factory('local');
					$file_access->f_write_string($file_path, $transformed['value']);
					$file_access->ch_dir($project->get_project_path($project_name) . "/cache_{$param['type']}{$cache_path}");
					virtual("{$conf->path_projects}/" . str_replace(' ', '_', strtolower($project_name)) . "/cache_{$param['type']}{$cache_path}/{$param['file_name']}");
					//$file_access->rm($file_path);
				}
			} else {
				$settings = $conf->getScheme($conf->interface_scheme);
				$lang = $conf->getTexts($conf->interface_language, 'inhtml');
				
				echo("<html><head>");
				htmlout::echoStyleSheet();
				echo("</head><body bgcolor=\"" . $settings['color_background'] . "\">");
				htmlout::echoMsg($lang['inhtml_preview_error'], $data['error']);
				echo("</body></html>");
			}
		} else {
			die_error("not a valid id");
		}
	} else if ($param['access'] == 'index') {
		$xml_proc->actual_path = '/';
		$id = 0;
		if (!$param['cached']) {
			$transformed = $xml_proc->generate_page_redirect($project_name, $param['type'], $param['lang'], $param['cached']);
		} else if (($transformed = $xml_proc->get_from_transform_cache($project_name, $param['type'], $id, $param['lang'], $param['access'])) === false) {
			$transformed = $xml_proc->generate_page_redirect($project_name, $param['type'], $param['lang'], $param['cached']);
			$xml_proc->add_to_transform_cache($project_name, $param['type'], $id, $param['lang'], $param['access'], $transformed, $xml_proc->ids_used);
		}
		headerType($transformed['content_type'], $transformed['content_encoding']);
		echo($transformed['value']);
	}
} else {
	die_error('you are not logged in' );
	//die_error('you are not logged in', '../');
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
