<?php
/**
 * @file    call_func.php
 *
 * remote functions script
 *
 * This file handles all remote functions called by the flash
 * interface.
 *
 *
 * copyright (c) 2002-2007 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 *
 * $Id: call_func.php,v 1.73 2004/11/12 19:45:31 jonas Exp $
 */
// {{{ define and require
define('IS_IN_CONTOOL', true);

require_once('lib/lib_global.php');
require_once('lib_auth.php');
require_once('lib_xmldb.php');
require_once('lib_tpl.php');
require_once('lib_project.php');
require_once('lib_pocket_server.php');
require_once('lib_tasks.php');
require_once('lib_files.php');
require_once('lib_media.php');
require_once('lib_publish.php');
require_once('Archive/tar.php');
// }}}

/**
 * handles all remote function calls, called from flash interface
 */
class rpc_phpConnect_functions extends rpc_functions_class {
    // {{{ login()
    /**
     * logs user in to server and registers a window to user. so a message will 
     * be send to client with new created sid, wid and current user_level, or 
     * an error, if login failed.
     *
     * @public
     *
     * @param    $args['user'] (string) user name
     * @param    $args['pass'] (string) password (attention: unencypted!!!!)
     * @param    $args['project'] (string) name of project to log in to
     */ 
    function login($args){
        global $conf, $project;

        $sid = $project->user->login($args['user'], $args['pass'], $args['project'], $_SERVER["REMOTE_ADDR"]);
        if ($sid) {
            $wid = $project->user->register_window($sid, $_SERVER["REMOTE_ADDR"], 0, 'main');
        }
        if ($sid && $wid) {
            $func = new ttRpcFunc('logged_in', array('sid' => $sid, 'wid' => $wid, 'user_level' => $project->user->get_level_by_sid($sid), 'error' => false));
        } else {
            $func = new ttRpcFunc('logged_in', array('error' => true));
        }

        return $func;
    }
    // }}}
    // {{{ register_window()
    /**
     * registeres a new window to user. a message will be send back to
     * client with new wid or an error, if registration fails.
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['type'] (string) type of window to be registered
     */ 
    function register_window($args) {
        global $conf, $project;
        
        $wid = $project->user->register_window($args['sid'], $_SERVER["REMOTE_ADDR"], 0, $args['type']);

        if ($wid) {
            $func = new ttRpcFunc('registered_window', array('wid' => $wid, 'user_level' => $project->user->get_level_by_sid($args['sid']), 'error' => false));
        } else {
            $func = new ttRpcFunc('registered_window', array('error' => true));
        }
        return $func;
    }
    // }}}
    // {{{ keepAlive()
    function keepAlive($args) {
        global $project;

        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            $project->user->update_login($args['sid']);
        }

        return new ttRpcFunc("", array());
    }
    // }}}
    // {{{ get_config()
    /**
     * gets global configuration data and interface texts from db
     *
     * @public
     *
     * @return     $set_config (xmlfuncobj) configuration
     */ 
    function get_config() {
        global $conf;
        global $project;
        global $msgHandler;    
        
        $conf_array = array();
        
        $conf_array['app_name'] = $conf->app_name;
        $conf_array['app_version'] = $conf->app_version;
        
        $conf_array['thumb_width'] = $conf->thumb_width;
        $conf_array['thumb_height'] = $conf->thumb_height;
        $conf_array['thumb_load_num'] = $conf->thumb_load_num;
        
        $conf_array['interface_lib'] = $conf->interface_lib;
        
        $conf_array['interface_text'] = "";
        $lang = $conf->getTexts($conf->interface_language);
        foreach ($lang as $key => $val) {
            $conf_array['interface_text'] .= "<text name=\"$key\" value=\"" . utf8_encode($val) . "\" />";
        }
        
        $conf_array['interface_scheme'] = '';
        $colors = $conf->getScheme($conf->interface_scheme);
        foreach ($colors as $key => $val) {
            $conf_array['interface_scheme'] .= "<color name=\"$key\" value=\"" . htmlspecialchars($val) . "\" />";
        }
        
        $conf_array['projects'] = $project->get_avail_projects();
        
        foreach($conf->ns as $ns_key => $ns) {
            $conf_array['namespaces'] .= "<namespace name=\"$ns_key\" prefix=\"{$ns['ns']}\" uri=\"{$ns['uri']}\"/>";
        }
        
        $conf_array['url_page_scheme_intern'] = $conf->url_page_scheme_intern;
        $conf_array['url_lib_scheme_intern'] = $conf->url_lib_scheme_intern;
        
        $conf_array['global_entities'] = '';
        foreach ($conf->global_entities as $val) {
            $conf_array['global_entities'] .= "<entity name=\"$val\"/>";
        }
        
        $conf_array['output_file_types'] = '';
        foreach ($conf->output_file_types as $key => $val) {
            $conf_array['output_file_types'] .= "<output_file_type name=\"$key\" extension=\"" . $val["extension"] . "\"/>";
        }

        $conf_array['output_encodings'] = '';
        foreach ($conf->output_encodings as $val) {
            $conf_array['output_encodings'] .= "<output_encoding name=\"$val\" />";
        }
        
        $conf_array['output_methods'] = '';
        foreach ($conf->output_methods as $val) {
            $conf_array['output_methods'] .= "<output_method name=\"$val\" />";
        }
        
        $conf_array['users'] = $project->user->get_userlist();

        return new ttRpcFunc('set_config', $conf_array);
    }
    // }}}
    // {{{ get_project()
    /**
     * gets project settings from db
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     *
     * @return    $set_project_data (xmlfuncobj) project settings
     */ 
    function get_project($args) {
        global $conf, $project;
        
        $data = array();
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            if ($project_name && $xml_def = $project->get_settings($project_name)) {
                $data['name'] = $project_name;
                $data['settings'] = $xml_def->dump_node($xml_def->document_element());
                $data['users'] = $project->user->get_userlist();
            } else {
                $data['error'] = true;
            }
        } else {
            $data['error'] = true;
        }
        
        return new ttRpcFunc('set_project_data', $data);
    }
    // }}}
    // {{{ get_tree()
    /**
     * gets all data of different project trees
     * 
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['type'] (string) type of xml
     */
    function get_tree($args) {
        global $conf, $project, $log;
        
        $data = array();
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            $callbackFunc = "update_tree_{$args['type']}";    
            // {{{ get settings
            if ($args['type'] == 'settings') {
                $xml_def = $project->get_settings($project_name);
                if ($xml_def !== false) {
                    $data['data'] = $xml_def->dump_node($xml_def->document_element());
                } else {
                    $data['error'] = true;
                }
            // }}}
            // {{{ get colors
            } elseif ($args['type'] == 'colors') {
                $xml_def = $project->get_colors($project_name);
                if ($xml_def !== false) {
                    $data['data'] = $xml_def->dump_node($xml_def->document_element());
                } else {
                    $data['error'] = true;
                }
            // }}}
            // {{{ get tpl_templates
            } elseif ($args['type'] == 'tpl_templates') {
                $xml_def = $project->get_tpl_template_struct($project_name);
                if ($xml_def !== false) {
                    $data['data'] = $xml_def->dump_node($xml_def->document_element());
                } else {
                    $data['error'] = true;
                }
            // }}}
            // {{{ get tpl_newnodes
            } elseif ($args['type'] == 'tpl_newnodes') {
                $xml_def = $project->get_tpl_newnodes($project_name);
                if ($xml_def !== false) {
                    $data['data'] = $xml_def->dump_node($xml_def->document_element());
                } else {
                    $data['error'] = true;
                }
            // }}}
            // {{{ get pages
            } elseif ($args['type'] == 'pages') {
                $xml_def = $project->get_page_struct($project_name);
                if ($xml_def !== false) {
                    $data['data'] = $xml_def->dump_node($xml_def->document_element());
                } else {
                    $data['error'] = true;
                }
            // }}}
            // {{{ get page_data
            } elseif ($args['type'] == 'page_data') {
                $xml_def = $project->get_page_data($project_name, $args['id']);
                if ($xml_def !== false) {
                    $data['data'] = $xml_def->dump_node($xml_def->document_element());
                } else {
                    $data['error'] = true;
                }
            // }}}
            // {{{ get files
            } elseif ($args['type'] == 'files') {
                $xml_def = $project->get_lib_tree($project_name);
                if ($xml_def !== false) {
                    $data['data'] = $xml_def->dump_node($xml_def->document_element());
                } else {
                    $data['error'] = true;
                }
            // }}}
            // {{{ unknown
            } else {
                $data['data'] = '<error />';
                $log->add_entry("get for -{$args['type']}- is not yet defined.");
            }
            // }}}
        } else {
            $data['error'] = true;
        }
        
        return new ttRpcFunc($callbackFunc, $data);
    }
    // }}}
    // {{{ get_prop()
    /**
     * gets all data of different project props
     * 
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['type'] (string) type of xml
     */
    function get_prop($args) {
        global $conf, $project, $log;
        
        $data = array();
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            $callbackFunc = "update_prop_{$args['type']}";    
            // {{{ get tpl_templates
            if ($args['type'] == 'tpl_templates') {
                $xml_def = $project->get_tpl_template_data($project_name, $args['id']);
                if ($xml_def !== false) {
                    $data['data'] = $xml_def->dump_node($xml_def->document_element());
                } else {
                    $data['error'] = true;
                }
            // }}}
            // {{{ get files
            } elseif ($args['type'] == 'files') {
                $xml_def = $project->get_lib_dir_content($project_name, $args['id']);
                if ($xml_def !== false) {
                    $data['data'] = $xml_def->dump_node($xml_def->document_element());
                } else {
                    $data['error'] = true;
                }
            // }}}
            // {{{ unknown
            } else {
                $data['data'] = '<error />';
                $log->add_entry("get for -{$args['type']}- is not yet defined.");
                alert("get for -{$args['type']}- is not yet defined.");
            }
            // }}}
        } else {
            $data['error'] = true;
        }
        
        return new ttRpcFunc($callbackFunc, $data);
    }
    // }}}
    // {{{ get_imageProp()
    /**
     * gets info about a file and the image size, if file is an image
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['filepath'] (string) path to file
     * @param    $args['filename'] (string) name of file
     *
     * @return    $set_imageProp (xmlfuncobj) image informations
     */ 
    function get_imageProp($args) {
        global $conf, $project;
        
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip']) && $args['filename'] != '') {
            $imagePath = $project->get_project_path($project_name) . '/lib' . $args['filepath'] . $args['filename'];
            if (file_exists($imagePath)) {    
                $data = mediainfo::get_file_info($imagePath);
                $data['name'] = $args['filename'];
                $data['path'] = $args['filepath'];

                return new ttRpcFunc('set_imageProp', $data);
            }
        }
    }
    // }}}
    // {{{ save_node()
    /**
     * saves xml back to database after editing in client
     * 
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['data'] (string) xml data to save including db:id's
     * @param    $args['type'] (string) type of xml to save (for updating).
     */ 
    function save_node($args) {
        global $conf, $project, $log;
        
        $data = array();
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            $xml_doc = project::domxml_open_mem($args['data']);
            $xml_node = $xml_doc->document_element();
            $id = $project->get_node_id($xml_node);
            $updated = false;
            if ($id != null) {
                $page_id = $project->save_element($project_name, $id, $xml_node);
                $updated_ids = array($id, $page_id);
            }
            tell_clients_to_update($project_name, $args['sid'], $args['type'], $updated_ids);
        }
    }
    // }}}
    // {{{ add_node()
    /**
     * adds new node to xmldb
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['type'] (string) type of node will be added (for updating)
     * @param    $args['node_type'] (string) type of element, that will be added.
     *             for some treeTypes it may also be the xmldata, that will be added.
     * @param    $args['target_id'] (int) id to which the new node will be added
     * @param    $args['newname'] (string) new name of element, that will be added
     * @param    $args['xmldata'] (string) data to insert into new element
     *
     * @return    $retval (xmlfuncobj) returns function object to set new 
     *            active node to just added element. but the behaviour depends on type.
     *
     * @todo    add_node for tree type 'colors'
     */ 
    function add_node($args) {
        global $conf, $project, $log;
        global $xml_db;
        
        $data = array();
        $updated = false;
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            // {{{ pages
            if ($args['type'] == 'pages') {
                if ($args['node_type'] == 'page') {
                    $new_id = $project->add_page($project_name, $args['target_id'], $args['new_name'], $args['xmldata']);
                } elseif ($args['node_type'] == 'folder') {
                    $new_id = $project->add_page_folder($project_name, $args['target_id'], $args['new_name']);
                } elseif ($args['node_type'] == 'separator') {
                    $new_id = $project->add_page_separator($project_name, $args['target_id']);
                }
                $updated = array($new_id, $args['target_id']);
                $retval = new ttRpcFunc("set_activeId_{$args['type']}", array('id' => $new_id));
            // }}}
            // {{{ page_data
            } else if ($args['type'] == 'page_data') {
                $new_id = $project->add_page_data_element($project_name, $args['target_id'], $args['new_name'], $args['node_type']);
                $updated = array($new_id, $args['target_id']);
                $retval = new ttRpcFunc("set_activeId_{$args['type']}", array('id' => $new_id));
            // }}}
            // {{{ files
            } else if ($args['type'] == 'files') {
                $projectPath = $project->get_project_path($project->user->get_project_by_sid($args['sid']));
                $fs = fs::factory('local');
                $fs->mk_dir($projectPath . '/lib' . $args['target_id'] . $args['new_name']);
                
                tell_clients_to_update($project_name, $args['sid'], $args['type']);
                
                return new ttRpcFunc("set_activeId_{$args['type']}", array('id' => ($args['target_id'] . $args['new_name'])));
            // }}}
            // {{{ colors
            } else if ($args['type'] == 'colors') {
                $doc_def = '';
                
                if ($doc_def != '') {
                    $doc_obj = project::domxml_open_mem($doc_def);
                    $node = $doc_obj->document_element();
                    $node->set_attribute('name', $args['new_name']);
                    
                    $new_id = $xml_db->save_node(&$node, $args['target_id']);                
                    
                    tell_clients_to_update($project, $args['sid'], $args['type']);

                    return new ttRpcFunc("set_activeId_{$args['type']}", array('id' => $new_id));
                }
            // }}}
            // {{{ tpl_templates
            } elseif ($args['type'] == 'tpl_templates') {
                if ($args['node_type'] == 'template') {
                    $new_id = $project->add_tpl_template($project_name, $args['target_id'], $args['new_name']);
                } elseif ($args['node_type'] == 'folder') {
                    $new_id = $project->add_tpl_template_folder($project_name, $args['target_id'], $args['new_name']);
                }
                $updated = array($new_id, $args['target_id']);
                $retval = new ttRpcFunc("set_activeId_{$args['type']}", array('id' => $new_id));
            // }}}
            // {{{ tpl_newnodes
            } elseif ($args['type'] == 'tpl_newnodes') {
                if ($args['node_type'] == 'new_node') {
                    $new_id = $project->add_tpl_newnode($project_name, $args['target_id'], $args['new_name']);
                }
                $updated = array($new_id, $args['target_id']);
                $retval = new ttRpcFunc("set_activeId_{$args['type']}", array('id' => $new_id));
            // }}}
            // {{{ type undefined
            } else {
                alert("add node of {$args['type']} not yet defined");
            }
            // }}}
            tell_clients_to_update($project_name, $args['sid'], $args['type'], $updated);
            return $retval;
        }
    }
    // }}}
    // {{{ delete_node()
    /**
     * deletes a xmlnode from database
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['id'] (int) id of node to delete
     * @param    $args['type'] (string) type of node, that will be deleted (needed for updating).
     */ 
    function delete_node($args) {
        global $conf, $project;
        global $xml_db;
        
        $data = array();
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            // {{{ files
            if ($args['type'] == 'files') {
                $projectPath = $project->get_project_path($project_name);
                $file_access = fs::factory('local');
                if (is_dir($projectPath . '/lib' . $args['id'])) {
                    $pathparts = pathinfo($args['id']);
                    $file_access->mk_dir($projectPath . '/trash' . $pathparts['dirname']);
                    $file_access->f_rename($projectPath . '/lib' . $args['id'], $projectPath . '/trash' . $args['id']);
                    tell_clients_to_update($project_name, $args['sid'], $args['type']);
                } else if (is_file($projectPath . '/lib' . $args['id'])) {
                    $pathparts = pathinfo($args['id']);
                    $file_access->mk_dir($projectPath . '/trash' . $pathparts['dirname']);
                    if (file_exists($projectPath . '/trash' . $args['id'])) {
                        $file_access->rm($projectPath . '/trash' . $args['id']);
                    }
                    $file_access->f_rename($projectPath . '/lib' . $args['id'], $projectPath . '/trash' . $args['id']);
                    tell_clients_to_update($project_name, $args['sid'], 'fileProps', $pathparts['dirname'] . '/');
                }
            // }}}
            // {{{ all other types
            } else {
                $changed_ids = $project->delete_element($project_name, $args['id']);
            }
            // }}}
            tell_clients_to_update($project_name, $args['sid'], $args['type'], $changed_ids);
        }
    }
    // }}}
    // {{{ rename_node()
    /**
     * renames a node in database (sets name attribute to a new value)
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['id'] (int) id of node to be renamed
     * @param    $args['new_name'] (string) newname to be set
     * @param    $args['type'] (string) type of node (needed for updating)
     */ 
    function rename_node($args) {
        global $conf, $project;
        global $xml_db;
        
        $data = array();
        $updated = false;
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            // {{{ files
            if ($args['type'] == 'files') {
                $projectPath = $project->get_project_path($project_name);
                
                $oldpath_array = explode('/', $args['id']);
                array_pop($oldpath_array);
                $newpath_array = $oldpath_array;
                $newpath_array[count($newpath_array) - 1] = $args['new_name'];
                rename($projectPath . '/lib' . implode('/', $oldpath_array), $projectPath . '/lib' . implode('/', $newpath_array));
            // }}}
            // {{{ all other types    
            } else {
                $project->rename_element($project_name, $args['id'], $args['new_name']);
                $updated = $args['id'];
            }
            // }}}
            tell_clients_to_update($project_name, $args['sid'], $args['type'], array($args['id'], $page_id));
        }
        if ($args['type'] == 'content' || $args['type'] == 'contentObj') {
            return new ttRpcFunc('preview_update', array('error' => 0));
        }
    }
    // }}}
    // {{{ set_page_colorscheme()
    /**
     * sets the colorscheme of a page
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['id'] (int) id of page
     * @param    $args['colorscheme'] (string) colorscheme to set to
     */ 
    function set_page_colorscheme($args) {
        global $conf;
        global $xml_db;
        global $project;
        
        $data = array();
        if (($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) && $args['type'] == 'page_data') {
            if (($data_id = $project->_set_element_lastchange_UTC($args['id'])) != null) {
                tpl_engine::delete_from_transform_cache($project_name, $data_id, 'preview');
            }
            list($meta_id) = $xml_db->get_child_ids_by_name($data_id, "pg", "meta");
            $xml_db->set_attribute($meta_id, '', 'colorscheme', $args['colorscheme']);
            
            tell_clients_to_update($project_name, $args['sid'], 'page_data', array($args['id'], $data_id));
            tell_clients_to_update($project_name, $args['sid'], 'pages', array($args['id']));
        }
        return new ttRpcFunc('preview_update', array('error' => 0));
    }
    // }}}
    // {{{ set_page_navigation()
    /**
     * sets navigation categories
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['id'] (int) id of page
     * @param    $args['navigations'] (string) xml string of navigation settings
     */ 
    function set_page_navigations($args) {
        global $conf;
        global $xml_db;
        global $project;
        
        $data = array();
        if (($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) && $args['type'] == 'page_data') {
            $temp_XML = domxml_open_mem($args['navigations']);
            $temp_node = $temp_XML->document_element();
            $temp_attrs = $temp_node->attributes();

            foreach ($temp_attrs as $temp_attr) {
                $xml_db->set_attribute($args['id'], '', $temp_attr->name(), $temp_attr->value());
            }
            
            $nodetype = $xml_db->get_node_name_by_id($args['id']);
            $data_id = $project->_set_element_lastchange_UTC($args['id']);
            if ($nodetype == "{$conf->ns['page']['ns']}:page" || $nodetype == "{$conf->ns['page']['ns']}:folder") {
                tpl_engine::clear_transform_cache($project_name, 'preview');
            }
            tell_clients_to_update($project_name, $args['sid'], 'page_data', array($args['id'], $data_id));
            tell_clients_to_update($project_name, $args['sid'], 'pages', array($args['id']));
        }
        return new ttRpcFunc('preview_update', array('error' => 0));
    }
    // }}}
    // {{{ set_page_file_options()
    /**
     * sets different page options like multilang or filetype
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['id'] (int) id of page
     * @param    $args['multilang'] (string) treu if multilang, false otherwise
     * @param    $args['file_name'] (string) ???? needed anymore?
     * @param    $args['file_type'] (string) type of page, that will be generated
     *
     * @todo    check, if file_name property is still required 
     */ 
    function set_page_file_options($args) {
        global $conf;
        global $xml_db;
        global $project;
        
        $data = array();
        if (($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) && $args['type'] == 'page_data') {
            $xml_db->set_attribute($args['id'], '', 'file_type', $args['file_type']);
            if ($xml_db->get_attribute($args['id'], '', 'multilang') != $args['multilang']) {
                $xml_db->set_attribute($args['id'], '', 'multilang', $args['multilang']);
            }

            if (($data_id = $project->_set_element_lastchange_UTC($args['id'])) != null) {
                tpl_engine::delete_from_transform_cache($project_name, $args['id'], 'preview');
            }
            tell_clients_to_update($project_name, $args['sid'], 'page_data', array($args['id'], $data_id));
            tell_clients_to_update($project_name, $args['sid'], 'pages', array($args['id']));
        }
        return new ttRpcFunc('preview_update', array('error' => 0));
    }
    // }}}
    // {{{ set_template_node_type()
    /**
     * sets type (template-set) of a template
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['id'] (int) id of template
     * @param    $args['new_type'] (string) type to set template-type to
     */ 
    function set_template_node_type($args) {
        global $xml_db;
        global $project;
        
        $data = array();
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip']) && $args['type'] == 'tpl_templates') {
            $xml_db->set_attribute($args['id'], '', 'type', $args['new_type']);
            
            tell_clients_to_update($project_name, $args['sid'], $args['type'], array($args['id']));
        }
    }
    // }}}
    // {{{ set_template_node_active()
    /**
     * sets wether a template node is active or not
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['id'] (int) id of template
     * @param    $args['new_active'] (string) true if template should be active,
     *             false otherwise.
     */ 
    function set_template_node_active($args) {
        global $xml_db;
        global $project;
        
        $data = array();
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip']) && $args['type'] == 'tpl_templates') {
            $xml_db->set_attribute($args['id'], '', 'active', $args['new_active']);
            
            tell_clients_to_update($project_name, $args['sid'], $args['type'], array($args['id']));
        }
    }
    // }}}
    // {{{ duplicate_node()
    /**
     * duplicates a xml-node
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['id'] (int) id of node
     * @param    $args['new_name'] (string) new name of node (sets name attribute)
     * @param    $args['type'] (string) type of node (needed for update).
     */ 
    function duplicate_node($args) {
        global $conf, $project;
        global $xml_db;
        global $log;
        
        $updated_ids = array();
        $data = array();
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            // {{{ files
            if ($args['type'] == 'files') {

            // }}}
            // {{{ all other element
            } else {
                $new_id = $project->duplicate_element($project_name, $args['id'], $args['new_name']);
                $updated_ids = array($new_id, $args['id']);
                $retVal = new ttRpcFunc("set_activeId_{$args['type']}", array('id' => $new_id));
            }
            // }}}
            tell_clients_to_update($project_name, $args['sid'], $args['type'], $updated_ids);
            return $retVal;
        }
    }
    // }}}
    // {{{ move_node_in()
    /**
     * moves a xmlnode into another node. node will appear at the end of
     * the children-list of the target-node
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['id'] (int) id of node
     * @param    $args['target_id'] (int) id of target node
     */ 
    function move_node_in($args) {
        global $conf, $project;
        global $xml_db;
        
        $data = array();
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            if ($args['type'] == 'files') {
                $projectPath = $project->get_project_path($project_name);
                $temppath = explode('/', $projectPath . '/lib' . $args['id']);
                $temppath = $temppath[count($temppath) - 2];
                $file_access = fs::factory('local');
                $file_access->f_rename($projectPath . '/lib' . $args['id'], $projectPath . '/lib' . $args['target_id'] . $temppath . '/');
            } else {
                $project->move_element_in($project_name, $args['id'], $args['target_id']);
            }
                
            tell_clients_to_update($project_name, $args['sid'], $args['type'], array($args['id'], $args['target_id'], $page_id));
        }
    }
    // }}}
    // {{{ move_node_before()
    /**
     * moves a node directly before another node.
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['id'] (int) id of node
     * @param    $args['target_id'] (int) id of target node
     */ 
    function move_node_before($args) {
        global $conf, $project;
        global $xml_db;
        
        $data = array();
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            $project->move_element_before($project_name, $args['id'], $args['target_id']);
            
            tell_clients_to_update($project_name, $args['sid'], $args['type'], array($args['id'], $args['target_id']));
        }
    }
    // }}}
    // {{{ move_node_after()
    /**
     * moves a node directly after another node.
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['id'] (int) id of node
     * @param    $args['target_id'] (int) id of target node
     */ 
    function move_node_after($args) {
        global $conf, $project;
        global $xml_db;
        
        $data = array();
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            $project->move_element_after($project_name, $args['id'], $args['target_id']);
            
            tell_clients_to_update($project_name, $args['sid'], $args['type'], array($args['id'], $args['target_id']));
        }
    }
    // }}}
    // {{{ copy_node_in()
    /**
     * copies a node into another node. the node appears at the 
     * end of the children-list of the target-node.
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['id'] (int) id of node
     * @param    $args['target_id'] (int) id of target node
     */ 
    function copy_node_in($args) {
        global $conf, $project;
        global $xml_db, $log;
        
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            if ($args['type'] == 'files') {
                $projectPath = $project->get_project_path($project_name);
                $temppath = explode('/', $projectPath . '/lib' . $args['id']);
                $temppath = $temppath[count($temppath) - 2];
                $file_access = fs::factory('local');
                $file_access->f_copy($projectPath . '/lib' . $args['id'], $projectPath . '/lib' . $args['target_id'] . $temppath . '/');
            } else {
                $new_id = $project->copy_element_in($project_name, $args['id'], $args['target_id'], $args['new_name']);
                $retVal = new ttRpcFunc("set_activeId_{$args['type']}", array('id' => $new_id));
            }
            
            tell_clients_to_update($project_name, $args['sid'], $args['type'], array($args['id'], $args['target_id'], $new_id));
        }
        return $retVal;
    }
    // }}}
    // {{{ copy_node_before()
    /**
     * copies a node directly before another node.
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['id'] (int) id of node
     * @param    $args['target_id'] (int) id of target node
     */ 
    function copy_node_before($args) {
        global $conf, $project;
        global $xml_db;
        
        $data = array();
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            $new_id = $project->copy_element_before($project_name, $args['id'], $args['target_id'], $args['new_name']);
            $retVal = new ttRpcFunc("set_activeId_{$args['type']}", array('id' => $new_id));
            tell_clients_to_update($project_name, $args['sid'], $args['type'], array($args['id'], $args['target_id'], $new_id));
        }
        return $retVal;
    }
    // }}}
    // {{{ copy_node_after()
    /**
     * copies a node directly after another node.
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['id'] (int) id of node
     * @param    $args['target_id'] (int) id of target node
     */ 
    function copy_node_after($args) {
        global $conf, $project;
        global $xml_db;
        
        $data = array();
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            $new_id = $project->copy_element_after($project_name, $args['id'], $args['target_id'], $args['new_name']);
            $retVal = new ttRpcFunc("set_activeId_{$args['type']}", array('id' => $new_id));
            tell_clients_to_update($project_name, $args['sid'], $args['type'], array($args['id'], $args['target_id'], $new_id));
        }
        return $retVal;
    }
    // }}}
    // {{{ release_template()
    /**
     * releases the template of specified template-set, so it
     * will be used for all users for previewing and publishing.
     * 
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['type'] (string) 
     * @param    $args['template_type'] (string) name of template set
     */ 
    function release_templates($args) {
        global $xml_db, $project;
        
        $data = array();
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            $XSLTProc = tpl_engine::factory('xslt');
            $XSLTProc->cache_template($project_name, $args['template_type']);
            $XSLTProc->clear_transform_cache($project_name, 'preview');

            tell_clients_to_update($project_name, $args['sid'], $args['type']);
        }
    }
    // }}}
    // {{{ backup_project()
    /**
     * starts a new background task to backup the specified project
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['project'] (string) name of project to backup.
     *            if not set, the project, that the user is logged in,
     *            will be backed up.
     * @param    $args['type'] (string) type of backup, that will be made.
     *            'data' saves the project data in an backup-xml-file.
     *            'lib' saves the file library in a packed tar file.
     *            and 'all' performs to both types of backup.
     * @param    $args['comment'] (string) comment, which will be saved 
     *            with backup.
     */ 
    function backup_project($args) {
        global $conf, $project;
        
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            if ($args['project'] != '') {
                $project_name = $args['project'];
            }
            if ($args['type'] != 'data' && $args['type'] != 'lib' && $args['type'] != 'all') {
                $type = 'all';
            } else {
                $type = $args['type'];
            }
            
            $path_backup = $conf->path_server_root . $conf->path_backup . '/' . str_replace(' ', '_', strtolower($project_name)) . '/';
            $file_backup = 'backup_' . gmdate('YmdHis');
            
            //CREATE
            $task = new bgTasks_task($conf->db_table_tasks, $conf->db_table_tasks_threads);
            $start_date = mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'));
            $task->create('backup project', 'project [' . $project_name . ']', $project->user->get_userid_by_sid($args['sid']), $start_date);
            
            //ADD functions
            if ($type == 'all' || $type == 'data') {
                $funcs = array(
                    new ttRpcFunc('backup_db_init', array(
                        'project' => $project_name, 
                        'file_path' => $path_backup, 
                        'file_name' => $file_backup . '.xml', 
                        'comment' => $args['comment'], 
                        'server_name' => $_SERVER['SERVER_NAME'],
                    )),
                    new ttRpcFunc('backup_db_add_data_node', array('type' => 'settings')),
                    new ttRpcFunc('backup_db_add_data_node', array('type' => 'pages')),
                    new ttRpcFunc('backup_db_add_data_node', array('type' => 'pages_struct')),
                    new ttRpcFunc('backup_db_add_data_node', array('type' => 'colorschemes')),
                    new ttRpcFunc('backup_db_add_data_node', array('type' => 'tpl_templates')),
                    new ttRpcFunc('backup_db_add_data_node', array('type' => 'tpl_templates_struct')),
                    new ttRpcFunc('backup_db_add_data_node', array('type' => 'tpl_newnodes')),
                    new ttRpcFunc('backup_db_end', array(
                        'file_path' => $path_backup, 
                        'file_name' => $file_backup . '.xml',
                    )),
                );
                $task->add_thread($funcs);
            }
            if ($type == 'all' || $type == 'lib') {    
                $funcs = array(
                    new ttRpcFunc('backup_lib_init', array(
                        'project' => $project_name, 
                        'file_path' => $path_backup, 
                        'file_name' => $file_backup,
                    )),
                );
                
                $olddir = getcwd();
                chdir($project->get_project_path($project_name) . '/lib/');
                $this->_backup_project_lib_add_dir($funcs, '');
                chdir($olddir);
                
                $funcs[] = new ttRpcFunc('backup_lib_end', array());
                
                $task->add_thread($funcs);
            }
        }
    }

    /**
     * adds recursively directories to the backup-thread-list
     *
     * @private
     *
     * @param    $funcs (array) array function objects of the current backup
     * @param    $path (string) current path of directory that should be backed up.
     */
    function _backup_project_lib_add_dir(&$funcs, $path) {
        $funcs[] = new ttRpcFunc('backup_lib_add_dir', array('file_path' => $path));
        
        $fs_access = fs::factory('local');
        $dirarray = $fs_access->list_dir($path);

        foreach ($dirarray['dirs'] as $dir) {
            $this->_backup_project_lib_add_dir($funcs, $path . $dir .  '/');
        }
    }
    // }}}
    // {{{ get_backup_files()
    /**
     * gets files in backup directory of given project for restoring
     *
     * @public
     *
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['project'] (string) name of project to backup.
     * 
     * @return    $set_backupFiles (xmlfuncobj) available backups
     */ 
    function get_backup_files($args) {
        global $conf, $project;
        
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            if ($args['project'] != '') {
                $project_name = $args['project'];
            }
            
            $backupsLib = array();
            $backupsDb = array();
            
            $file_path = $conf->path_server_root . $conf->path_backup . '/' . str_replace(' ', '_', strtolower($project_name)) . '/';

            $fs_access = fs::factory('local');
            $dirarray = $fs_access->list_dir($file_path);

            foreach ($dirarray['files'] as $file) {
                if (substr($file, 0, 7) == 'backup_') {
                    if (substr($file, -4) == '.xml') {
                        $backupsDb[] = $file;
                    } else if (substr($file, -4) == '.tgz' || substr($file, -4) == '.bz2') {
                        $backupsLib[] = $file;
                    }
                }
            }

            rsort($backupsDb);
            rsort($backupsLib);
            
            $listDBStr = "<{$conf->ns['backup']['ns']}:backupListDB>";
            foreach ($backupsDb as $file) {
                $listDBStr .= "<{$conf->ns['backup']['ns']}:backupFile ";
                $listDBStr .= " name=\"" . htmlentities($file) . "\"";
                if (strlen($file) == 25) {
                    $listDBStr .= " date=\"" . htmlentities(date($conf->date_format_UTC, $this->_getTimestampFromFilename($file, 7))) . "\"";
                }
                if (substr($file, 7, 3) == "dev") {
                    $listDBStr .= " devBackup=\"true\"";
                }
                $listDBStr .= ">";
                $listDBStr .= $this->_readInfoFromDBBackupFile($file_path . $file);
                $listDBStr .= "</{$conf->ns['backup']['ns']}:backupFile>";
            }
            $listDBStr .= "</{$conf->ns['backup']['ns']}:backupListDB>";
            
            $listLibStr .= "<{$conf->ns['backup']['ns']}:backupListLib>";
            foreach ($backupsLib as $file) {
                $listLibStr .= "<{$conf->ns['backup']['ns']}:backupFile ";
                $listLibStr .= " name=\"" . htmlentities($file) . "\"";
                if (strlen($file) == 25) {
                    $listLibStr .= " date=\"" . htmlentities($conf->dateUTC($conf->date_format_UTC, $this->_getTimestampFromFilename($file, 7))) . "\"";
                }
                $listLibStr .= ">";
                $listLibStr .= "</{$conf->ns['backup']['ns']}:backupFile>";
            }
            $listLibStr .= "</{$conf->ns['backup']['ns']}:backupListLib>";
        }

        return new ttRpcFunc('set_backup_files', array('listDB' => $listDBStr, 'listLib' => $listLibStr));
    }

    /**
     * gets timestamp of backup out of a filename. the filename has 
     * the following format: 'backup_YYYYMMDDhhmmss.ext'
     *
     * @private
     *
     * @param    $filename (string) name of backup file
     * @param    $dateoffset (int) offset where date informations begin.
     *            the default is 7 for 'backup_'-prefix.
     *
     * @return    $date (int) unix timestamp of date
     */
    function _getTimestampFromFilename($filename, $dateoffset = 7) {
        return @mktime(substr($filename, 8 + $dateoffset, 2), substr($filename, 10 + $dateoffset, 2), substr($filename, 12 + $dateoffset, 2), substr($filename, 4 + $dateoffset, 2), substr($filename, 6 + $dateoffset, 2), substr($filename, 0 + $dateoffset, 4));
    }

    /**
     * reads backup informations (comment etc.) from xml backup file
     *
     * @private
     *
     * @param    $filename (string) filename of backup file
     *
     * @return    $infonode (string) infostring from file
     *
     * @todo    add support for library backups
     */
    function _readInfoFromDBBackupFile($filename) {
        global $conf;
        
        if (file_exists($filename)) {
            $fp = fopen($filename, 'r');
            $found = false;
            $maxlines = 40;
            $i = 0;
            while (!feof($fp) && !$found && $i < $maxlines) {
                $line = fgets($fp);
                if (preg_match("/<{$conf->ns['backup']['ns']}:info (.*)\/>/", $line, $infonode)) {
                    $found = true;
                }
                $i++;
            }
        }
        return $infonode[0];
    }
    // }}}
    // {{{ restore_project()
    /**
     * creates background task for restoring project data or library
     * from backup-files and backup-archives.
     *
     * @public
     *
     * @param    $args['sid'] (string) sesssion id
     * @param    $args['wid'] (string) session window id
     * @param    $args['project'] (string) name of project to backup.
     *            if not set, the project, that the user is logged in,
     *            will be backed up.
     * @param    $args['file'] (string) name of file to restore from
     * @param    $args['type'] (string) tells, what kind of data will be restored.
     *            'data' restores data into the xml database.
     *            'lib' restores the files into the file-library.
     *            'all' is not yet available.
     * @param    $args['subtype'] (string) is  a suboption, different for
     *            each restore type. for 'data' i tells what part of data will be 
     *            restored (pages, colorschemes, templates and/or settings).
     *            for 'lib' it tells wether the library should cleared first.
     *
     * @todo    add option for restoring the library for overwriting files or not
     */ 
    function restore_project($args) {
        global $conf, $project;
        
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            if ($args['project'] != '') {
                $project_name = $args['project'];
            }
            $options = explode(',', $args['options']);
            
            //CREATE
            $task = new bgTasks_task($conf->db_table_tasks, $conf->db_table_tasks_threads);
            $start_date = mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'));
            $task->create('restore project', 'project [' . $project_name . ']', $project->user->get_userid_by_sid($args['sid']), $start_date);
            
            //ADD functions
            if ($args['type'] == 'all' || $args['type'] == 'data') {
                $funcs = array();
                foreach ($options as $option) {
                    $funcs[] = new ttRpcFunc('restore_db_from_file', array('project' => $project_name, 'file' => $args['file'], 'type' => 'data', 'subtype' => $option));
                }
                $task->add_thread($funcs);
                
                $funcs = array();
                $funcs[] = new ttRpcFunc('db_optimize', array());
                $task->add_thread($funcs);
                
                $funcs = array();
                foreach ($options as $option) {
                    $funcs[] = new ttRpcFunc('restore_db_sendupdate', array('project' => $project_name, 'subtype' => $option));
                }
                $task->add_thread($funcs);
            }
            if ($args['type'] == 'all' || $args['type'] == 'lib') {
                $funcs = array();
                
                $file_path = $conf->path_server_root . $conf->path_backup . '/' . str_replace(' ', '_', strtolower($project_name)) . '/' . $args['file'];
                $target_path = $project->get_project_path($project_name) . '/lib/';
                
                $archivObj = new Archive_Tar($file_path);
                if (($archiveFileList = $archivObj->listContent()) != 0) {
                    $funcs[] = new ttRpcFunc('restore_lib_init', array(
                        'target_path' => $target_path, 
                        'filename' => $file_path,
                        'clear' => in_array('clear', $options) ? 'true' : 'false',
                    ));
                    
                    $old_dir = '';
                    $actual_files = '';
                    for ($i = 0; $i < sizeof($archiveFileList); $i++) {
                        if ($i % 120 == 0) {
                            if ($actual_files != '') {
                                $actual_files .= '</filelist>';
                                $funcs[] = new ttRpcFunc('restore_lib_extract_dir', array(
                                    'target_path' => $target_path, 
                                    'filelist' => $actual_files,
                                ));
                            }
                            $actual_files = "<filelist>";
                        } 
                        $actual_files .= "<file name=\"" . htmlentities($archiveFileList[$i]['filename']) . "\" />";
                    }
                    $actual_files .= '</filelist>';
                    $funcs[] = new ttRpcFunc('restore_lib_extract_dir', array(
                        'target_path' => $target_path, 
                        'filelist' => $actual_files,
                    ));
                    $funcs[] = new ttRpcFunc('restore_lib_end', array('project' => $project_name));
                }
                $task->add_thread($funcs);
            }
        }
    }
    // }}}
    // {{{ _dummy_wait
    /**
     * creates a dummy background task. only for testing purposes.
     *
     * @private
     *
     * @param    $name (string) name of task
     * @param    $depends (string) depends option, which denies concurrent tasks
     *            to be executed at the same time.
     */ 
    function _dummy_wait($name, $depends) {
        global $confi, $project;
        global $xml_db;
        
        $task = new bgTasks_task($conf->db_table_tasks, $conf->db_table_tasks_threads);
        $start_date = mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'));
        $task->create($name, $depends, $project->user->get_userid_by_sid($args['sid']));
        
        for ($i = 0; $i < 10; $i++) {
            $task->add_thread(new ttRpcFunc('wait', array('duration' => 1)));
        }
        for ($i = 0; $i < 20; $i++) {
            $task->add_thread(new ttRpcFunc('wait', array('duration' => 3)));
        }
    }
    // }}}
    // {{{ publish_project()
    /**
     * creates a new background task for publishing a project to a
     * local or a remote directory.
     *
     * @public
     * 
     * @param    $args['sid'] (string) session id
     * @param    $args['wid'] (string) session window id
     * @param    $args['project'] (string) name of project to backup.
     *            if not set, the project, that the user is logged in,
     *            will be published.
     * @param    $args['publish_id'] (int) id of the publishing settings
     *            to execute.
     */ 
    function publish_project($args) {
        global $conf, $project;
        global $xml_db, $log;
        
        if ($project_name = $project->user->is_valid_user($args['sid'], $args['wid'], $args['ip'])) {
            if ($args['project'] != '') {
                $project_name = $args['project'];
            }
            
            //parse settings
            $tempdoc = $xml_db->get_doc_by_id($args['publish_id']);
            $tempnode = $tempdoc->document_element();
            //
            //get languages
            $output_languages = array();
            $xml_proc = tpl_engine::factory('xslt');
            $xml_temp = $xml_proc->get_languages($project_name);
            $xpath_temp = project::xpath_new_context($xml_temp);
            $xfetch = xpath_eval($xpath_temp, "/{$conf->ns['project']['ns']}:languages/{$conf->ns['project']['ns']}:language/@shortname");
            foreach ($xfetch->nodeset as $temp_node) {
                $output_languages[] = $temp_node->get_content();
            }
            
            //create
            $task = new bgTasks_task($conf->db_table_tasks, $conf->db_table_tasks_threads);
            $start_date = mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'));
            $task->create('publish project', 'project [' . $project_name . ']', $project->user->get_userid_by_sid($args['sid']), $start_date, new ttRpcFunc('publish_init', array(
                'project' => $project_name, 
                'project_id' => $xml_db->get_doc_id_by_name($project),
                'publish_id' => $args['publish_id'],
                'cache_path' => $project->get_project_path($project_name) . '/publish/',
                'template_set' => $tempnode->get_attribute('template_set'),
                'output_folder' => $tempnode->get_attribute('output_folder'),
                'baseurl' => $tempnode->get_attribute('baseurl'),
                'output_user' => $tempnode->get_attribute('output_user'),
                'output_pass' => $tempnode->get_attribute('output_pass'),
            )));
            $baseurl = $tempnode->get_attribute('baseurl');
            
            $doc_id = $xml_db->get_doc_id_by_name($project_name);
            
            //caching
            $funcs = array(
                new ttRpcFunc('publish_init_test', array()),
                new ttRpcFunc('publish_cache_init', array()),
                new ttRpcFunc('publish_cache_xslt_templates', array('publish_id' => $args['publish_id'])),
                new ttRpcFunc('publish_cache_colorschemes', array()),
                new ttRpcFunc('publish_cache_languages', array()),
                new ttRpcFunc('publish_cache_navigation', array()),
                new ttRpcFunc('publish_cache_settings', array()),
            );
            
            $xslt_proc = tpl_engine::factory('xslt');
            $xml_nav = $xslt_proc->get_navigation($project_name);
            $xpath_nav = project::xpath_new_context($xml_nav);
            
            //get pages
            $page_ids = array();
            $xfetch = xpath_eval($xpath_nav, "//{$conf->ns['page']['ns']}:page");
            foreach ($xfetch->nodeset as $temp_node) {
                $page_ids[] = $xml_db->get_node_id($temp_node);
            }
            foreach ($page_ids as $page_id) {
                $funcs[] = new ttRpcFunc('publish_cache_page', array('page_id' => $page_id));
            }

            //get folders
            $folder_ids = array();
            $xfetch = xpath_eval($xpath_nav, "//{$conf->ns['page']['ns']}:folder");
            foreach ($xfetch->nodeset as $temp_node) {
                $folder_ids[] = $xml_db->get_node_id($temp_node);
            }
            foreach ($folder_ids as $folder_id) {
                $funcs[] = new ttRpcFunc('publish_cache_page', array('page_id' => $folder_id));
            }
            

            $funcs[] = new ttRpcFunc('publish_cache_end', array());
            foreach ($output_languages as $output_language) {
                $funcs[] = new ttRpcFunc('publish_process_remove_old', array('lang' => $output_language));
            }
            
            $task->add_thread($funcs);
            
            //process
            $funcs = array();
            foreach ($page_ids as $page_id) {
                foreach ($output_languages as $output_language) {
                    $funcs[] = new ttRpcFunc('publish_process_page', array(
                        'page_id' => $page_id, 
                        'lang' => $output_language,
                        'publish_id' => $args['publish_id']
                    ));
                }
            }

            $funcs = array_chunk($funcs, 60);
            foreach ($funcs as $func) {
                $task->add_thread($func);
            }

            //publish library
            $funcs = array();

            $pb = new publish($project_name, $args['publish_id']);
            $pb->reset_all_file_exists();
            $files = $pb->get_changed_lib_files();
            foreach ($files as $file) {
                $funcs[] = new ttRpcFunc('publish_lib_file', array(
                    'path' => $file->path, 
                    'filename' => $file->filename, 
                    'sha1' => $file->sha1, 
                    'publish_id' => $args['publish_id']
                ));
            }
            
            $funcs = array_chunk($funcs, 80);
            foreach ($funcs as $func) {
                $task->add_thread($func);
            }
            
            //publish pages            
            $funcs = array();
            foreach ($page_ids as $page_id) {
                foreach ($output_languages as $output_language) {
                    $funcs[] = new ttRpcFunc('publish_page_file', array(
                        'page_id' => $page_id, 
                        'lang' => $output_language,
                        'publish_id' => $args['publish_id']
                    ));
                }
            }

            $funcs = array_chunk($funcs, 60);
            foreach ($funcs as $func) {
                $task->add_thread($func);
            }

            $funcs = array();
            $funcs[] = new ttRpcFunc('publish_index_page', array('lang' => $output_languages[0]));
            $funcs[] = new ttRpcFunc('publish_sitemap', array(
                'publish_id' => $args['publish_id'],
                'baseurl' => $baseurl,
            ));
            $funcs[] = new ttRpcFunc('publish_end', array(
                'publish_id' => $args['publish_id']
            ));
            $task->add_thread($funcs);
        }
    }

    /**
     * adds threads to a publishing task to publish all files in the
     * file library.
     * 
     * @private
     *
     * @param    $funcs (array) array of function of task to create
     * @param    $path (string) path of directory to add
     */
    function _publish_project_lib_add_dir(&$funcs, $path) {
        $funcs[] = new ttRpcFunc('publish_lib_dir', array('file_path' => $path));

        $fs_access = fs::factory('local');
        $dirarray = $fs_access->list_dir($path != '' ? "./$path" : '.');
        
        foreach ($dirarray['dirs'] as $dir) {
            $this->_publish_project_lib_add_dir($funcs, $path . $dir .  '/');
        }
    }
    // }}}
}

/*
 * MAIN
 */ 
headerNoCache();
headerType('text/xml');
set_time_limit(0);
ignore_user_abort(true);

$msgHandler = new ttRpcMsgHandler(new rpc_phpConnect_functions());

//call
$funcs = $msgHandler->parse_msg(file_get_contents("php://input"));
$value = array();
foreach ($funcs as $func) {
    $func->add_args(array('ip' => $_SERVER['REMOTE_ADDR']));
    $tempval = $func->call();
    if (is_a($tempval, 'ttRpcFunc')) {
        $value[] = $tempval;
    }
}
if (count($pocket_updates) > 0) {
    send_updates();
}
$value = array_merge($value, $project->user->get_updates($project->user->sid));

if (count($value) == 0) {
    $value[] = new ttRpcFunc('nothing', array('error' => 0));
}

$msg = ($msgHandler->create_msg($value));
//@todo add gzip compression
echo($msg);
flush();

if (!$conf->pocket_use) {
    //init objects
    $task_control = new bgTasks_control($conf->db_table_tasks, $conf->db_table_tasks_threads);
    $pocket_server = "";
    register_shutdown_function(array($task_control, "handle_tasks"), array($pocket_server));
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
