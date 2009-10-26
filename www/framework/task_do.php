<?php
/**
 * @file    task_do.php
 *
 * Task Handling
 *
 * This file defines all functions for handling
 * background tasks.
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
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
require_once('lib_publish.php');
require_once('lib_sitemap.php');
require_once('lib_atom.php');
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
     *        project
     *        file_path, file_name
     */ 
    function backup_db_init($args) {
        global $conf;
        global $xml_db;
        global $project;
        
        $this->project = $args['project'];
        $project->_set_project($project_name);
        $this->project_id = $xml_db->get_doc_id_by_name($this->project);
        
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
     *        project
     *        file_path, file_name
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
        } else if ($args['type'] == 'pages') {
            $args['task']->set_description('%task_backup_content%');
            $tempids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'pages');
            $tempid = $tempids[0];
        } else if ($args['type'] == 'pages_struct') {
            $args['task']->set_description('%task_backup_content%');
            $tempids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'pages_struct');
            $tempid = $tempids[0];
        } else if ($args['type'] == 'colorschemes') {
            $args['task']->set_description('%task_backup_colorschemes%');
            $tempids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'colorschemes');
            $tempid = $tempids[0];
        } else if ($args['type'] == 'tpl_templates') {
            $args['task']->set_description('%task_backup_templates%');
            $tempids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'tpl_templates');
            $tempid = $tempids[0];
        } else if ($args['type'] == 'tpl_templates_struct') {
            $args['task']->set_description('%task_backup_templates%');
            $tempids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'tpl_templates_struct');
            $tempid = $tempids[0];
        } else if ($args['type'] == 'tpl_newnodes') {
            $args['task']->set_description('%task_backup_newnodes%');
            $tempids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'tpl_newnodes');
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
     *        project
     *        file_path, file_name
     */ 
    function backup_db_end($args) {
        global $conf;
        global $xml_db;
        
        $fs = fs::factory('local');
        $fs->mk_dir($args['file_path']);
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
     *        file_path, file_name
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
     *        file_path
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
     *        project, file, type, subtype
     */ 
    function restore_db_from_file($args) {
        global $conf, $xml_db, $log;
        
        $file_path = $conf->path_server_root . $conf->path_backup . '/' . str_replace(' ', '_', strtolower($args['project'])) . '/' . $args['file'];
        
        $xml_doc = domxml_open_file($file_path);
        $xml_doc_ctx = project::xpath_new_context($xml_doc);
        
        //get backup info
        $xfetch = xpath_eval($xml_doc_ctx, "/{$conf->ns['backup']['ns']}:backup/{$conf->ns['backup']['ns']}:info");
        $backup_info_node = $xfetch->nodeset[0];
        
        if ($args['type'] == 'all' || $args['type'] == 'data') {
            $doc_id = $xml_db->get_doc_id_by_name($args['project']);
            
            // {{{ settings
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
                    $log->add_entry("could not restore project settings");
                }
            // }}}
            // {{{ pages
            } else if ($args['subtype'] == 'content') {
                $args['task']->set_description('%task_restore_db_content%');
                
                //get project pages_struct
                $xfetch = xpath_eval($xml_doc_ctx, "/{$conf->ns['backup']['ns']}:backup/{$conf->ns['backup']['ns']}:data[@type = 'pages_struct']/*[1]");
                $project_pages_struct_node = $xfetch->nodeset[0];
                //get project pages
                $xfetch = xpath_eval($xml_doc_ctx, "/{$conf->ns['backup']['ns']}:backup/{$conf->ns['backup']['ns']}:data[@type = 'pages']/*[1]");
                $project_pages_node = $xfetch->nodeset[0];
                
                //get xmldb-ids to overwrite
                $node_ids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'pages_struct');
                if (count($node_ids) == 1) {
                    $xml_db->replace_node($project_pages_struct_node, $node_ids[0], $doc_id);
                } else {
                    $log->add_entry("could not restore project page structure");
                }
                //get xmldb-ids to overwrite
                $node_ids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'pages');
                if (count($node_ids) == 1) {
                    $xml_db->replace_node($project_pages_node, $node_ids[0], $doc_id);
                } else {
                    $log->add_entry("could not restore project page data");
                }
            // }}}
            // {{{ colorschemes
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
                    $log->add_entry("could not restore project colorschemes");
                }
            // }}}
            // {{{ templates
            } else if ($args['subtype'] == 'templates') {
                $args['task']->set_description('%task_restore_db_templates%');
                
                //get project templates
                $xfetch = xpath_eval($xml_doc_ctx, "/{$conf->ns['backup']['ns']}:backup/{$conf->ns['backup']['ns']}:data[@type = 'tpl_templates']/*[1]");
                $project_xslt_templates_node = $xfetch->nodeset[0];
                //get project templates_struct
                $xfetch = xpath_eval($xml_doc_ctx, "/{$conf->ns['backup']['ns']}:backup/{$conf->ns['backup']['ns']}:data[@type = 'tpl_templates_struct']/*[1]");
                $project_xslt_templates_struct_node = $xfetch->nodeset[0];
                //get project newnodes
                $xfetch = xpath_eval($xml_doc_ctx, "/{$conf->ns['backup']['ns']}:backup/{$conf->ns['backup']['ns']}:data[@type = 'tpl_newnodes']/*[1]");
                $project_content_newnodes_node = $xfetch->nodeset[0];
                
                //get xmldb-ids to overwrite
                $node_ids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'tpl_templates');
                if (count($node_ids) == 1) {
                    $xml_db->replace_node($project_xslt_templates_node, $node_ids[0], $doc_id);
                } else {
                    $log->add_entry("could not restore project tpl_template data");
                }
                //get xmldb-ids to overwrite
                $node_ids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'tpl_templates_struct');
                if (count($node_ids) == 1) {
                    $xml_db->replace_node($project_xslt_templates_struct_node, $node_ids[0], $doc_id);
                } else {
                    $log->add_entry("could not restore project tpl_template structure");
                }
                //get xmldb-ids to overwrite
                $node_ids = $xml_db->get_node_ids_by_name($doc_id, $conf->ns['project']['ns'], 'tpl_newnodes');
                if (count($node_ids) == 1) {
                    $xml_db->replace_node($project_content_newnodes_node, $node_ids[0], $doc_id);
                } else {
                    $log->add_entry("could not restore project tpl_newnodes");
                }
            }
            // }}}
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
     *        project, type
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
     *        filename, target_path, clear
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
     *        filelist, target_path
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
        global $project;

        $project_name = $args['project'];

        $this->xml_proc = tpl_engine::factory('xslt', array('isPreview' => false));
        $GLOBALS['xml_proc'] = &$this->xml_proc;
        
        $this->xml_proc->_set_project($project_name);

        $this->project = $project_name;
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
            $this->output_path = $parsed['path'];

            if (substr($this->output_path, -1) == '/') {
                $this->output_path = substr($this->output_path, 0, -1);
            }
            if (substr($this->output_path, 0, 2) == '//') {
                $this->output_path = substr($this->output_path, 1);
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
     *        project, publish_id
     */ 
    function publish_cache_init($args) {
        /*
            TODO
            deny any changes during caching    
        */
        
        $this->xml_proc->isPreview = true;

        $fs = fs::factory("local");
        $fs->mk_dir($this->cache_path . 'xml/');
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
        
        //$tempdoc->free();
    }
    // }}}
    // {{{ publish_cache_colorschemes()
    /**
     * ----------------------------------------------
     * publish_cache_colorschemes
     *        project
     */ 
    function publish_cache_colorschemes($args) {
        $args['task']->set_description('%task_publish_caching_colorschemes%');
        
        $tempdoc = $this->xml_proc->get_colors($this->project);
        $tempdoc->dump_file($this->cache_path . 'colors.xml', false, false);
        
        //$tempdoc->free();
    }
    // }}}
    // {{{ publish_cache_languages()
    /**
     * ----------------------------------------------
     * publish_cache_languages
     *        project
     */ 
    function publish_cache_languages($args) {
        global $xml_db, $conf, $project;
        
        $args['task']->set_description('%task_publish_caching_languages%');
        
        $project->_set_project($this->project);

        $tempids = $xml_db->get_node_ids_by_name($this->project_id, $conf->ns['project']['ns'], 'languages');
        $tempdoc = $xml_db->get_doc_by_id($tempids[0], null, false);
        $tempdoc->dump_file($this->cache_path . 'languages.xml', false, false);
        
        //$tempdoc->free();
    }
    // }}}
    // {{{ publish_cache_navigation()
    /**
     * ----------------------------------------------
     * publish_cache_navigation
     *        project, publish_id
     */ 
    function publish_cache_navigation($args) {
        $args['task']->set_description('%task_publish_caching_navigation%');
        
        $tempdoc = $this->xml_proc->get_navigation($this->project);
        $tempdoc->dump_file($this->cache_path . 'navigation.xml', false, false);
        
        //$tempdoc->free();
    }
    // }}}
    // {{{ publish_cache_settings()
    /**
     * ----------------------------------------------
     * publish_cache_settings
     *        project, publish_id
     */ 
    function publish_cache_settings($args) {
        $args['task']->set_description('%task_publish_caching_settings%');
        
        $tempdoc = $this->xml_proc->get_settings($this->project, $this->template_set);
        $tempdoc->dump_file($this->cache_path . 'settings.xml', false, false);
        
        //$tempdoc->free();
    }
    // }}}
    // {{{ publish_cache_page()
    /**
     * ----------------------------------------------
     * publish_cache_page
     *        project, page_id
     */ 
    function publish_cache_page($args) {
        global $log;

        $args['task']->set_description('%task_publish_caching_pages% [id ' . $args['page_id'] . ']');
        
        $this->xml_proc->_set_project($this->project);
        $tempdoc = $this->xml_proc->get_page($args['page_id']);
        $tempdoc->dump_file($this->cache_path . 'xml/page_' . $args['page_id'] . '.xml', false, false);
        
        //$tempdoc->free();
    }
    // }}}
    // {{{ publish_cache_end()
    /**
     * ----------------------------------------------
     * publish_cache_end
     *        project, publish_id
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
        $lang = $args['lang'];
        $this->file_access->rm("{$this->output_path}/{$lang}_publish");
    }
    // }}}
    // {{{ publish_process_page()
    /**
     * ----------------------------------------------
     * publish_process_page
     */ 
    function publish_process_page($args) {
        global $log, $db;

        $this->xml_proc = tpl_engine::factory('xslt', array('isPreview' => false));
        $GLOBALS['xml_proc'] = &$this->xml_proc;
        $this->xml_proc->_set_project($this->project);
        
        $file_path = $this->xml_proc->get_path_by_id($args['page_id'], $args['lang'], $this->project);
        if (substr($file_path, -1) == '/') {
            $file_path .= 'index.html';
        }
        
        $args['task']->set_description('%task_publish_processing_pages% [' . substr($file_path, strpos($file_path, '/', 9)) . ']');
        
        $file_path = pathinfo($file_path);

        $this->xml_proc->actual_path = $file_path['dirname'] . '/' . $file_path['basename'];
        $this->xml_proc->isPreview = true;
        $transformed = $this->xml_proc->transform($this->project, $this->template_set, $args['page_id'], $args['lang'], true);
        $this->xml_proc->isPreview = false;
        
        $fs = fs::factory("local");
        $filename = $this->cache_path . $args['publish_id'] . '/' . $args['lang'] . '/page_' . $args['page_id'] . '_' . $file_path['basename'];
        $fs->f_write_string($filename, $transformed['value']);
    }
    // }}}
    // {{{ publish_page_file()
    /**
     * ----------------------------------------------
     * publish_page_file
     */ 
    function publish_page_file($args) {
        global $log;

        $this->xml_proc->isPreview = true;
        $file_path = $this->xml_proc->get_path_by_id($args['page_id'], $args['lang'], $this->project);
        if (substr($file_path, -1) == '/') {
            $file_path .= 'index.html';
        }
        $this->xml_proc->isPreview = false;
        
        $args['task']->set_description('%task_publish_publishing_pages% [' . substr($file_path, strpos($file_path, '/', 9)) . ']');

        $file_path = pathinfo($file_path);
        $filename = $this->cache_path . $args['publish_id'] . '/' . $args['lang'] . '/page_' . $args['page_id'] . '_' . $file_path['basename'];

        $file = new publish_file($file_path['dirname'] . '/', $file_path['basename']);
        $file->sha1 = sha1_file($filename);
        
        $pb = new publish($this->project, $args['publish_id']);
        if ($pb->file_changed($file)) {
            if ($this->file_access->f_write_file($this->output_path . $file->get_fullname(), $filename)) {
                $log->add_entry($file->sha1 . " " . $file->get_fullname());
                $pb->add_file_to_db($file);
            } else {
                $log->add_entry("Could not write: " . $file->get_fullname());
            }
        } else {
            $pb->set_file_exists($file);
        }
    }
    // }}}
    // {{{ publish_index_page()
    /**
     * ----------------------------------------------
     * publish_index_page
     */ 
    function publish_index_page($args) {
        $args['task']->set_description('%task_publish_processing_indexes%');
        
        $this->xml_proc->isPreview = true;
        $this->xml_proc->page_refs = array();
        $this->xml_proc->actual_path = '/';
        
        $transformed = $this->xml_proc->generate_page_redirect($this->project, $this->template_set, $args['lang'], true);
        
        $this->file_access->f_write_string($this->output_path . '/index.html', $transformed['value']);

        $htaccess = "";

        $htaccess .= "AddCharset UTF-8 .html\n";

        $this->file_access->f_write_string($this->output_path . '/.htaccess', $htaccess);
    }
    // }}}
    // {{{ publish_htaccess()
    /**
     * ----------------------------------------------
     * publish_htaccess
     */ 
    function publish_htaccess($args) {
        global $project, $log;
        
        // generate autolanguage-switch
        $page_struct = $project->get_page_struct($this->project);
        $node = $page_struct->document_element();
        while ($node != null && $node->tagname != "page") {
            $node = $node->first_child();
        }
        if ($node == null) {
            return;
        }

        $autolang = file_get_contents("php/autolang_tpl.php");
        $autolang .= "<" . "?php\n";
            $autolang .= "\$languages = array(\n";
            $autolang .= $args['languages'];
            $autolang .= ");\n";

            $autolang .= "\$lang_location = get_language_by_browser(\$languages);\n";

            $autolang .= "\$base_location = \"http://{\$_SERVER['HTTP_HOST']}{\$_SERVER['REQUEST_URI']}\";\n";
            $autolang .= "if (substr(\$base_location, -1, 1) != \"/\") {\n";
            $autolang .= "\t\$base_location .= \"/\";\n";
            $autolang .= "}\n";
            $autolang .= "\$document = \"" . $node->get_attribute("url") . "\";\n";
            $autolang .= "\$location = \"{\$base_location}{\$lang_location}{\$document}\";\n\n";

            $autolang .= "header(\"Location: \$location\");\n";
        $autolang .= "?" . ">";

        $this->file_access->f_write_string($this->output_path . '/index.php', $autolang);

        // generate htaccess file
        $project_path = $project->get_project_path($this->project);
        if (file_exists("{$project_path}/lib/htaccess")) {
            $htaccess = file_get_contents("{$project_path}/lib/htaccess") . "\n\n";
        } else {
            $htaccess = "";
        }

        // get encoding
        $this->xml_proc = tpl_engine::factory('xslt', array('isPreview' => false));
        $this->xml_proc->_set_project($this->project);

        $settings = $this->xml_proc->get_settings($this->project, $this->template_set);
        $tempNode = $settings->document_element();
        
        $method = $tempNode->get_attribute('method');
        $content_encoding = $tempNode->get_attribute('encoding');

        if ($content_encoding == "UTF-8") {
            $htaccess .= "AddCharset UTF-8 .html\n\n";
        }

        // @todo add option to exclude rewrite conditions
        //$htaccess .= "<IfModule mod_rewrite.c>\n";
        //$htaccess .= "\tRewriteEngine       on\n\n";

        if ($method == "xhtml") {
            $htaccess .= "RewriteCond %{HTTP_ACCEPT} application/xhtml\+xml\n";
            $htaccess .= "RewriteRule \.html$ - [T=application/xhtml+xml]\n\n";
        }

        if ($args['lang_num'] > 1) {
            $htaccess .= "RewriteRule         ^$              index.php [last]\n";
        } else {
            $htaccess .= "RewriteRule         ^$              {$args['lang_default']}" . $node->get_attribute("url") . " [last]\n";
            $htaccess .= "RedirectMatch       ^/$             {$args['baseurl']}/{$args['lang_default']}" . $node->get_attribute("url") . "\n";
        }
        
        //$htaccess .= "</IfModule>\n";

        @$this->file_access->f_write_string($this->output_path . '/.htaccess', $htaccess);
    }
    // }}}
    // {{{ publish_lib_file()
    /**
     * ----------------------------------------------
     * publish_lib_file args:
     *        path
     *        filename
     *        sha1
     *        publish_id
     */ 
    function publish_lib_file($args) {
        global $conf, $project, $log;
        
        $file = new publish_file($args['path'], $args['filename']);
        $file->sha1 = $args['sha1'];

        $args['task']->set_description('%task_publish_processing_lib% [/' . $file->get_fullname() . ']');
        
        $project_path = $project->get_project_path($this->project);
        if ($this->file_access->f_write_file($this->output_path . $file->get_fullname(), $project_path . $file->get_fullname())) {
            $log->add_entry($file->sha1 . " " . $file->get_fullname());
            $pb = new publish($this->project, $args['publish_id']);
            $pb->add_file_to_db($file);
        } else {
            $log->add_entry("Could not write: " . $file->get_fullname());
        }
    }
    // }}}
    // {{{ publish_lib_dir()
    /**
     * ----------------------------------------------
     * publish_lib_dir args:
     *        file_path
     */ 
    function publish_lib_dir($args) {
        global $conf, $project;
        
        $args['task']->set_description('%task_publish_processing_lib% [/' . $args['file_path'] . ']');
        
        $fList = array();
        $project_path = $project->get_project_path($this->project);
        $path = $project_path . $args['file_path'];
        
        if ($dir = opendir($path != '' ? $path : '.')) {
            while (($file = readdir($dir)) !== false) {
                if ($file != '.' && $file != '..') {
                    if (is_file($path . $file)) {
                        $fList[] = $args['file_path'] . $file;
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
    // {{{ publish_feeds()
    function publish_feeds($args) {
        global $conf, $project, $log;
        
        $pb = new publish($this->project, $args['publish_id']);
        $args['task']->set_description('%task_publish_feeds%');
        
        $feed = new atom($args['baseurl'], $args['title']);

        $xmlstr = $feed->generate();
        if (!$this->file_access->f_write_string($this->output_path . "/" . $lang . "/atom.xml", $xmlstr)) {
            $log->add_entry("Could not write atom-feed");
        }
    }
    // }}}
    // {{{ publish_sitemap()
    /**
     * ----------------------------------------------
     * publish_lib_file args:
     *        path
     *        filename
     *        sha1
     *        publish_id
     */ 
    function publish_sitemap($args) {
        global $conf, $project, $log;
        
        $pb = new publish($this->project, $args['publish_id']);
        $args['task']->set_description('%task_publish_sitemap%');
        
        $sitemap = new sitemap($this->project);
        // @todo add real baseurl instead of the dummy-url
        $xmlstr = $sitemap->generate($args['publish_id'], $args['baseurl']);

        if (!$this->file_access->f_write_string($this->output_path . "/sitemap.xml", $xmlstr)) {
            $log->add_entry("Could not write sitemap");
        }
    }
    // }}}
    // {{{ publish_end()
    /**
     * ----------------------------------------------
     * publish_end
     */ 
    function publish_end($args) {
        global $log;

        $pb = new publish($this->project, $args['publish_id']);

        $files = $pb->get_deleted_files();
        foreach ($files as $file) {
            $log->add_entry("removing: " . $file->get_fullname());
            $this->file_access->rm($this->output_path . $file->get_fullname());
        }
        $pb->clear_deleted_files();
    }
    // }}}
}

/**
 * ----------------------------------------------
 */ 
set_error_handler('taskErrorHandler');

if (!$conf->pocket_use) {
    //init objects
    $task_control = new bgTasks_control($conf->db_table_tasks, $conf->db_table_tasks_threads);
    $pocket_server = "";
    register_shutdown_function(array($task_control, "handle_tasks"), array($pocket_server));
}

$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cli') {
    $arg = $argv[1];
} else {
    $arg = $_GET['arg'];
}

$task = new bgTasks_task($conf->db_table_tasks, $conf->db_table_tasks_threads);
$task->load_by_id($arg);
if (($status = $task->get_status()) == 'wait_for_start') {
    $task->do_start($msgFunc = new rpc_bgtask_functions());
} else if ($status == 'wait_for_resume' || $status == 'wait_for_question') {
    $task->do_resume($msgFunc = new rpc_bgtask_functions());
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
