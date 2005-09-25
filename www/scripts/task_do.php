<?php
/**
 * @file	task_do.php
 *
 * Task Handling
 *
 * This file defines all functions for handling
 * background tasks.
 *
 *
 * copyright (c) 2002-2004 Frank Hellenkamp [jonas.info@gmx.net]
 *
 * @author	Frank Hellenkamp [jonas.info@gmx.net]
 *
 * $Id: task_do.php,v 1.42 2004/07/08 00:28:56 jonas Exp $
 */

// {{{ define and include
define('IS_IN_CONTOOL', true);

require_once('lib/lib_global.php');
require_once('lib_xmldb.php');
require_once('lib_tpl.php');
require_once('lib_project.php');
require_once('lib_tasks.php');
require_once('lib_files.php');
require_once('lib_pocket_server.php');
require_once('Archive/tar.php');
require_once('Mail.php');
// }}}

/**
 * defines functions that can be called from background tasks
 */
class rpc_bgtask_functions extends rpc_functions_class {
	// {{{ constructor	
	/**
	 * constructor
	 */
	function rpc_bgtask_functions() {
		$this->archivObj = null;
	}
	// }}}
	// {{{ wait()
	function wait($args) {
		sleep($args['duration']);
	}
	// }}}
	// {{{ do_error()
	function do_error($args) {
		if (!isset($args['error_level'])) {
			$args['error_level'] = E_USER_NOTICE;
		}
		trigger_error("this is my error", $args['error_level']);
	}
	// }}}
	// {{{ finish()
	/**
	 * ----------------------------------------------
	 * finish args:
	 */ 
	function finish($args) {
		$args['task']->set_status('finished');
	}
	// }}}
	// {{{ backup_db_init()
	/**
	 * ----------------------------------------------
	 * backup_db_init args:
	 *		project
	 *		file_path, file_name
	 */ 
	function backup_db_init($args) {
		global $conf;
		global $xml_db;
		
		$this->project = $args['project'];
		$this->project_id = $xml_db->get_doc_ic_by_name($this->project);
		
		$this->backup_xml = project::domxml_new_doc($backup_str);
		
		$rootNode = $this->backup_xml->create_element_ns($conf->ns['backup']['uri'], 'backup', $conf->ns['backup']['ns']);
		$rootNode = $this->backup_xml->append_child($rootNode);
		
		$infoNode = $this->backup_xml->create_element_ns($conf->ns['backup']['uri'], 'info', $conf->ns['backup']['ns']);
		$infoNode = $rootNode->append_child($infoNode);
		$infoNode->set_attribute('host', $args['server_name']);
		$infoNode->set_attribute('project', $this->project);
		$infoNode->set_attribute('comment', $args['comment']);
		$infoNode->set_attribute('backuptime_UTC', $conf->dateUTC($conf->date_format_UTC));
	}
	// }}}
	// {{{ backup_db_add_data_node()
	/**
	 * ----------------------------------------------
	 * backup_db_add_data_node args:
	 *		project
	 *		file_path, file_name
	 */ 
	function backup_db_add_data_node($args) {
		global $conf;
		global $xml_db;
		
		$rootNode = $this->backup_xml->document_element();
		
		$doc_id = $xml_db->get_doc_id_by_name($this->project);
		if ($args['type'] == 'settings') {
			$args['task']->set_description('%task_backup_settings%');
			$tempids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'settings');
			$tempid = $tempids[0];
		} else if ($args['type'] == 'content') {
			$args['task']->set_description('%task_backup_content%');
			$tempids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'content');
			$tempid = $tempids[0];
		} else if ($args['type'] == 'colorschemes') {
			$args['task']->set_description('%task_backup_colorschemes%');
			$tempids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'colorschemes');
			$tempid = $tempids[0];
		} else if ($args['type'] == 'templates') {
			$args['task']->set_description('%task_backup_templates%');
			$tempids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'templates_publish');
			$tempid = $tempids[0];
		} else if ($args['type'] == 'newnodes') {
			$args['task']->set_description('%task_backup_newnodes%');
			$tempids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'templates_newnodes');
			$tempid = $tempids[0];
		}
		
		$tempdoc = $xml_db->get_doc_by_id($tempid);
		
		$tempNode = $tempdoc->document_element();
		$tempNode = $tempNode->clone_node(true);
		
		$dataNode = $this->backup_xml->create_element_ns($conf->ns['backup']['uri'], 'data', $conf->ns['backup']['ns']);
		$dataNode->set_attribute('type', $args['type']);
		$dataNode = $rootNode->append_child($dataNode);
		
		$dataNode->append_child($tempNode);
	}
	// }}}
	// {{{ backup_db_end()
	/**
	 * ----------------------------------------------
	 * backup_db_end args:
	 *		project
	 *		file_path, file_name
	 */ 
	function backup_db_end($args) {
		global $conf;
		global $xml_db;
		
		$this->backup_xml->dump_file($args['file_path'] . $args['file_name'], false, true);
		if ($conf->backup_add_dev_backup) {
			$this->backup_xml->dump_file($args['file_path'] . 'backup_dev.xml', false, true);
		}
	}
	// }}}
	// {{{ backup_lib_init()
	/**
	 * ----------------------------------------------
	 * backup_lib_init args:
	 *		file_path, file_name
	 */ 
	function backup_lib_init($args) {
		global $conf, $project;
		
		$this->project = $args['project'];
		$compression = 'gz';
		if ($compression == 'gz') {
			$args['file_name'] .= '.tgz';
		} else if ($compression == 'bz2') {
			$args['file_name'] .= '.tar.bz2';
		} else {
			$compression='';
			$args['file_name'] .= '.tar';
		}
		$this->archivObj = new Archive_tar($args['file_path'] . $args['file_name'], $compression);
		$this->archivObj->_openWrite();
		
		$this->olddir = getcwd();
		chdir($project->get_project_path($this->project) . '/lib/');
	}
	// }}}
	// {{{ backup_lib_add_dir()
	/**
	 * ----------------------------------------------
	 * backup_lib_add_dir args:
	 *		file_path
	 */ 
	function backup_lib_add_dir($args) {
		$fList = array();
		$path = $args['file_path'];
		
		$args['task']->set_description('%task_backup_lib% [/' . $path . ']');
		
		if ($dir = opendir($path != '' ? $path : '.')) {
			while (($file = readdir($dir)) !== false) {
				if ($file != '.' && $file != '..') {
					if (is_file($args['file_path'] . $file)) {
						$fList[] = $args['file_path'] . $file;
					}
				}
			}  
			closedir($dir);
		}
		
		$v_header = array();
		
		for ($i = 0; $i < count($fList); $i++) {
	        $v_filename = $fList[$i];
			
	        if ($v_filename == $this->archivObj->_tarname) {
	            continue;
	        }
			
	        if ($v_filename == '') {
	            continue;
	        }
			
	        if (!file_exists($v_filename)) {
	            continue;
	        }
			
	        if (!$this->archivObj->_addFile($v_filename, $v_header, '', '')) {
	            return false;
	        }
		}
	}
	// }}}
	// {{{ backup_lib_end()
	/**
	 * ----------------------------------------------
	 * backup_lib_end args:
	 */ 
	function backup_lib_end($args) {
		chdir($this->olddir);
		
		$this->archivObj->_writeFooter();
		$this->archivObj->_close();
	}
	// }}}
	// {{{ restore_db_from_file()
	/**
	 * ----------------------------------------------
	 * restore_db_from_file args:
	 *		project, file, type, subtype
	 */ 
	function restore_db_from_file($args) {
		global $conf, $xml_db;
		
		$file_path = $conf->path_server_root . $conf->path_backup . '/' . str_replace(' ', '_', strtolower($args['project'])) . '/' . $args['file'];
		
		$xml_doc = domxml_open_file($file_path);
		$xml_doc_ctx = project::xpath_new_context($xml_doc);
		
		//get backup info
		$xfetch = xpath_eval($xml_doc_ctx, "/{$conf->ns['backup']['ns']}:backup/{$conf->ns['backup']['ns']}:info");
		$backup_info_node = $xfetch->nodeset[0];
		
		if ($args['type'] == 'all' || $args['type'] == 'data') {
			$doc_id = $xml_db->get_doc_id_by_name($args['project']);
			
			if ($args['subtype'] == 'settings') {
				$args['task']->set_description('%task_restore_db_settings%');
				
				//get project settings
				$xfetch = xpath_eval($xml_doc_ctx, "/{$conf->ns['backup']['ns']}:backup/{$conf->ns['backup']['ns']}:data[@type = 'settings']/*[1]");
				$project_settings_node = $xfetch->nodeset[0];
				
				//get xmldb-ids to overwrite
				$node_ids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'settings');
				if (count($node_ids) == 1) {
					$xml_db->replace_node($project_settings_node, $node_ids[0], $doc_id);
				} else {

				}
			} else if ($args['subtype'] == 'content') {
				$args['task']->set_description('%task_restore_db_content%');
				
				//get project content
				$xfetch = xpath_eval($xml_doc_ctx, "/{$conf->ns['backup']['ns']}:backup/{$conf->ns['backup']['ns']}:data[@type = 'content']/*[1]");
				$project_content_node = $xfetch->nodeset[0];
				
				//get xmldb-ids to overwrite
				$node_ids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'content');
				if (count($node_ids) == 1) {
					$xml_db->replace_node($project_content_node, $node_ids[0], $doc_id);
				} else {

				}
			} else if ($args['subtype'] == 'colorschemes') {
				$args['task']->set_description('%task_restore_db_colorschemes%');
				
				//get project colorschemes
				$xfetch = xpath_eval($xml_doc_ctx, "/{$conf->ns['backup']['ns']}:backup/{$conf->ns['backup']['ns']}:data[@type = 'colorschemes']/*[1]");
				$project_colorschemes_node = $xfetch->nodeset[0];
				
				//get xmldb-ids to overwrite
				$node_ids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'colorschemes');
				if (count($node_ids) == 1) {
					$xml_db->replace_node($project_colorschemes_node, $node_ids[0], $doc_id);
				} else {

				}
			} else if ($args['subtype'] == 'templates') {
				$args['task']->set_description('%task_restore_db_templates%');
				
				//get project templates
				$xfetch = xpath_eval($xml_doc_ctx, "/{$conf->ns['backup']['ns']}:backup/{$conf->ns['backup']['ns']}:data[@type = 'templates']/*[1]");
				$project_xslt_templates_node = $xfetch->nodeset[0];
				//get project newnodes
				$xfetch = xpath_eval($xml_doc_ctx, "/{$conf->ns['backup']['ns']}:backup/{$conf->ns['backup']['ns']}:data[@type = 'newnodes']/*[1]");
				$project_content_newnodes_node = $xfetch->nodeset[0];
				
				//get xmldb-ids to overwrite
				$node_ids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'templates_publish');
				if (count($node_ids) == 1) {
					$xml_db->replace_node($project_xslt_templates_node, $node_ids[0], $doc_id);
				} else {

				}
				//get xmldb-ids to overwrite
				$node_ids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'templates_newnodes');
				if (count($node_ids) == 1) {
					$xml_db->replace_node($project_content_newnodes_node, $node_ids[0], $doc_id);
				} else {

				}
			}
		}
	}
	// }}}
	// {{{ db_optimize()
	/**
	 * ----------------------------------------------
	 * db_optimize args:
	 */ 
	function db_optimize($args) {
		global $xml_db;
		
		$args['task']->set_description('%task_db_optimize%');
		$xml_db->optimize_database;
	}
	// }}}
	// {{{ restore_db_sendupdate()
	/**
	 * ----------------------------------------------
	 * restore_db_sendupdate args:
	 *		project, type
	 */ 
	function restore_db_sendupdate($args) {
		if ($args['subtype'] == 'settings') {
			tell_clients_to_update($args['project'], '', 'settings');
		} else if ($args['subtype'] == 'content') {
			tell_clients_to_update($args['project'], '', 'content');
		} else if ($args['subtype'] == 'colorschemes') {
			tell_clients_to_update($args['project'], '', 'colors');
		} else if ($args['subtype'] == 'templates') {
			tell_clients_to_update($args['project'], '', 'templates');
			tell_clients_to_update($args['project'], '', 'newnodes');
		}
	}
	// }}}
	// {{{ restore_lib_init()
	/**
	 * ----------------------------------------------
	 * restore_lib_init args:
	 *		filename, target_path, clear
	 */ 
	function restore_lib_init($args) {
		$this->archivObj = new Archive_Tar($args['filename']);
		$this->archivObj->_openRead();
		
		if ($args['clear'] == 'true') {
			$args['task']->set_description('%task_restore_clear%');
			$file_access = fs::factory('local');
			$file_access->rm($args['target_path']);
			$file_access->mk_dir($args['target_path']);
		}
	}
	// }}}
	// {{{ restore_lib_extract_dir()
	/**
	 * ----------------------------------------------
	 * restore_lib_extract_dir args:
	 *		filelist, target_path
	 */ 
	function restore_lib_extract_dir($args) {
	    if ($this->archivObj->_compress_type == 'gz') {
	        gzrewind($this->archivObj->_file);
	    } else if ($this->archivObj->_compress_type == 'bz2') {
	        
	    } else if ($this->archivObj->_compress_type == 'none') {
	        rewind($this->archivObj->_file);
	    }
	    
	    $file_access = fs::factory('local');
	    $file_access->mk_dir($args['target_path']);
	    
	    $filelistObj = domxml_open_mem($args['filelist']);
	    $extractFileList = array();
	    if ($filelistObj) {
		    $tempNode = $filelistObj->document_element();
		    
		    $tempNode = $tempNode->first_child();
		    while ($tempNode != null) {
		    	$extractFileList[] = $tempNode->get_attribute('name');
		    	$tempNode = $tempNode->next_sibling();
		    }
		    
			$args['task']->set_description('%task_restore_clear%', substr($extractFileList[0], 0, strrpos($extractFileList[0], '/') - 1));
			$this->archivObj->_extractList($args['target_path'], $detail = array(), 'partial', $extractFileList, '');
		}
	}
	// }}}
	// {{{ restore_lib_end()
	/**
	 * ----------------------------------------------
	 * restore_lib_end args:
	 */ 
	function restore_lib_end($args) {
		$this->archivObj->_close();
		
		tell_clients_to_update($args['project'], '', 'files');
	}
	// }}}
	// {{{ publish_init()
	/**
	 * ----------------------------------------------
	 * publish_init
	 */ 
	function publish_init($args) {
		global $xml_db, $log;

		$this->xml_proc = tpl_engine::factory('xslt', array('isPreview' => false));
		$GLOBALS['xml_proc'] = &$this->xml_proc;
		
		$this->project = $args['project'];
		$this->project_id = $args['project_id'];
		$this->project_id = $xml_db->get_doc_id_by_name($this->project);
		$this->cache_path = $args['cache_path'];
		$this->template_set = $args['template_set'];
		$this->output_folder = $args['output_folder'];
		$this->output_user = $args['output_user'];
		$this->output_pass = $args['output_pass'];
		
		$parsed = parse_url($this->output_folder);
		
		$this->output_protocol = $parsed['scheme'];
		if ($this->output_protocol == 'file') {
			$this->output_path = substr($this->output_folder, 7);
			if (substr($this->output_path, -1) == '/') {
				$this->output_path = substr($this->output_path, 0, -1);
			}
			
			//generate file_access_object
			$this->file_access = fs::factory('local');
		} else if ($this->output_protocol == 'ftp') {
			$this->output_host = $parsed['host'];
			$this->output_port = $parsed['port'];
			$this->output_path = $parsed['path'];
			if (substr($this->output_path, -1) == '/') {
				$this->output_path = substr($this->output_path, 0, -1);
			}
			
			//generate file_access_object
			$this->file_access = fs::factory('ftp', array(
				'host' => $this->output_host, 
				'post' => $this->output_port, 
				'user' => $this->output_user, 
				'pass' => $this->output_pass,
			));
			$log->add_entry("ftp: " . $this->output_host . " - " . $this->output_port . " - " . $this->output_user . " - " . $this->output_pass);
		}
	}
	// }}}
	// {{{ publish_init_test()
	/**
	 * ----------------------------------------------
	 * publish_init
	 */ 
	function publish_init_test($args) {
		global $log;

		$args['task']->set_description('%task_publish_testing_connection%');
		
		$log->add_entry("output_folder: " . $this->output_path);

		$this->file_access->f_write_string($this->output_path . '/connection_test.tmp', $conf->app_name . ' ' . $conf->app_version);
		$this->file_access->rm($this->output_path . '/connection_test.tmp');
	}
	// }}}
	// {{{ publish_cache_init()
	/**
	 * ----------------------------------------------
	 * publish_cache_init
	 *		project, publish_id
	 */ 
	function publish_cache_init($args) {
		/*
			TODO
			deny any changes during caching	
		*/
		
		$this->xml_proc->isPreview = true;
	}
	// }}}
	// {{{ publish_cache_xslt_templates()
	/**
	 * ----------------------------------------------
	 * publish_cache_xslt_templates
	 */ 
	function publish_cache_xslt_templates($args) {
		$args['task']->set_description('%task_publish_caching_templates%');
		
		$tempdoc = $this->xml_proc->_get_template_from_cache($this->project, $this->template_set);
		$tempdoc->dump_file($this->cache_path . 'template.xsl', false, true);
		
		$tempdoc->free();
	}
	// }}}
	// {{{ publish_cache_colorschemes()
	/**
	 * ----------------------------------------------
	 * publish_cache_colorschemes
	 *		project
	 */ 
	function publish_cache_colorschemes($args) {
		$args['task']->set_description('%task_publish_caching_colorschemes%');
		
		$tempdoc = $this->xml_proc->get_colors($this->project);
		$tempdoc->dump_file($this->cache_path . 'colors.xml', false, false);
		
		$tempdoc->free();
	}
	// }}}
	// {{{ publish_cache_languages()
	/**
	 * ----------------------------------------------
	 * publish_cache_languages
	 *		project
	 */ 
	function publish_cache_languages($args) {
		global $xml_db, $conf;
		
		$args['task']->set_description('%task_publish_caching_languages%');
		
		$tempids = $xml_db->get_node_ids_by_name($this->project_id, $conf->ns['project']['ns'], 'languages');
		$tempdoc = $xml_db->get_doc_by_id($tempids[0], null, false);
		$tempdoc->dump_file($this->cache_path . 'languages.xml', false, false);
		
		$tempdoc->free();
	}
	// }}}
	// {{{ publish_cache_navigation()
	/**
	 * ----------------------------------------------
	 * publish_cache_navigation
	 *		project, publish_id
	 */ 
	function publish_cache_navigation($args) {
		$args['task']->set_description('%task_publish_caching_navigation%');
		
		$tempdoc = $this->xml_proc->get_navigation($this->project);
		$tempdoc->dump_file($this->cache_path . 'navigation.xml', false, false);
		
		$tempdoc->free();
	}
	// }}}
	// {{{ publish_cache_settings()
	/**
	 * ----------------------------------------------
	 * publish_cache_settings
	 *		project, publish_id
	 */ 
	function publish_cache_settings($args) {
		$args['task']->set_description('%task_publish_caching_settings%');
		
		$tempdoc = $this->xml_proc->get_settings($this->project, $this->template_set);
		$tempdoc->dump_file($this->cache_path . 'settings.xml', false, false);
		
		$tempdoc->free();
	}
	// }}}
	// {{{ publish_cache_page()
	/**
	 * ----------------------------------------------
	 * publish_cache_page
	 *		project, page_id
	 */ 
	function publish_cache_page($args) {
		global $xml_db;

		$args['task']->set_description('%task_publish_caching_pages% [id ' . $args['page_id'] . ']');
		
		$tempdoc = $this->xml_proc->get_settings($this->project, $this->template_set);
		$tempdoc = $this->xml_proc->get_page($args['page_id']);
		$tempdoc->dump_file($this->cache_path . 'page' . $args['page_id'] . '.xml', false, false);
		
		$tempdoc->free();
	}
	// }}}
	// {{{ publish_cache_end()
	/**
	 * ----------------------------------------------
	 * publish_cache_end
	 *		project, publish_id
	 */ 
	function publish_cache_end($args) {
		/*
			TODO
			allow changes after caching	
		*/
		$this->xml_proc->isPreview = false;
	}
	// }}}
	// {{{ publish_process_remove_old()
	/**
	 * ----------------------------------------------
	 * publish_process_remove_old
	 */ 
	function publish_process_remove_old($args) {
		$file_access = fs::factory('local');
		$file_access->rm($this->output_path . '/dyn_publish');
	}
	// }}}
	// {{{ publish_process_page()
	/**
	 * ----------------------------------------------
	 * publish_process_page
	 */ 
	function publish_process_page($args) {
		$file_path = $this->xml_proc->get_path_by_id($args['page_id'], $args['lang'], $this->project);
		if (substr($file_path, -1) == '/') {
			$file_path .= 'index.html';
		}
		
		$args['task']->set_description('%task_publish_processing_pages% [' . substr($file_path, strpos($file_path, '/', 13)) . ']');
		
		$file_path = pathinfo($file_path);
		$this->xml_proc->actual_path = $file_path['dirname'] . '/' . $file_path['basename'];
		$transformed = $this->xml_proc->transform($this->project, $this->template_set, $args['page_id'], $args['lang'], true);
		
		$page_keys = array_keys($this->xml_proc->pages);
		foreach ($page_keys as $page) {
			$this->xml_proc->pages[$page]->free();
			unset($this->xml_proc->pages[$page]);
		}
		
		$this->file_access->f_write_string($this->output_path . $file_path['dirname'] . '/' . $file_path['basename'], $transformed['value']);
	}
	// }}}
	// {{{ publish_index_page()
	/**
	 * ----------------------------------------------
	 * publish_index_page
	 */ 
	function publish_index_page($args) {
		global $xml_db;
		
		$args['task']->set_description('%task_publish_processing_indexes%');
		
		$this->xml_proc->isPreview = true;
		$this->xml_proc->page_refs = array();
		$this->xml_proc->actual_path = '/';
		
		$transformed = $this->xml_proc->generate_page_redirect($this->project, $this->template_set, $args['lang'], true);
		
		$this->file_access->f_write_string($this->output_path . '/index_publish.html', $transformed['value']);
	}
	// }}}
	// {{{ publish_lib_dir()
	/**
	 * ----------------------------------------------
	 * publish_lib_dir args:
	 *		file_path
	 */ 
	function publish_lib_dir($args) {
		global $conf, $project;
		
		$args['task']->set_description('%task_publish_processing_lib% [/' . $args['file_path'] . ']');
		
		$fList = array();
		$project_path = $project->get_project_path($this->project);
		$path = $project_path . '/lib/' . $args['file_path'];
		
		if ($dir = opendir($path != '' ? $path : '.')) {
			while (($file = readdir($dir)) !== false) {
				if ($file != '.' && $file != '..') {
					if (is_file($path . $file)) {
						$fList[] = '/lib/' . $args['file_path'] . $file;
					}
				}
			}  
			closedir($dir);
		}
		
		for ($i = 0; $i < count($fList); $i++) {
			if (!$this->file_access->f_exists($this->output_path . $fList[$i])) {
				$do_copy = true;
			} else if (filesize($project_path . $fList[$i]) != $this->file_access->f_size($this->output_path . $fList[$i])) {
				$do_copy = true;
			} else if (filemtime($project_path . $fList[$i]) > $this->file_access->f_mtime($this->output_path . $fList[$i])) {
				$do_copy = true;
			} else {
				$do_copy = false;
			}
			if ($do_copy) {
				$this->file_access->f_write_file($this->output_path . $fList[$i], $project_path . $fList[$i]);
			}
		}
	}
	// }}}
	// {{{ publish_end()
	/**
	 * ----------------------------------------------
	 * publish_end
	 */ 
	function publish_end($args) {
		$this->file_access->f_rename($this->output_path . '/dyn', $this->output_path . '/dyn_remove');
		$this->file_access->f_rename($this->output_path . '/dyn_publish', $this->output_path . '/dyn');
		$this->file_access->rm($this->output_path . '/dyn_remove');
		$this->file_access->rm($this->output_path . '/index.html');
		$this->file_access->f_rename($this->output_path . '/index_publish.html', $this->output_path . '/index.html');
	}
	// }}}
}

/**
 * ----------------------------------------------
 */ 
set_error_handler('taskErrorHandler');

$task = new bgTasks_task($conf->db_table_tasks, $conf->db_table_tasks_threads);
$task->load_by_id($argv[1]);
if (($status = $task->get_status()) == 'wait_for_start') {
	$task->do_start($msgFunc = new rpc_bgtask_functions());
} else if ($status == 'wait_for_resume' || $status == 'wait_for_question') {
	$task->do_resume($msgFunc = new rpc_bgtask_functions());
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
