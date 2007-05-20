<?php
/**
 * @file    lib_project_acss_mysql2.php
 *
 * Project Access Library (MySQL)
 *
 * This file provides access to projects over the MySQL interface.
 * This file should only be included by lib_project.php.
 * It needs the lib_xmldb library for database access.
 *
 *
 * copyright (c) 2002-2007 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 *
 * $Id: lib_project_acss_mysql.php,v 1.16 2004/11/12 19:45:31 jonas Exp $
 */

// {{{ define and require
if (!function_exists('die_error')) require_once('lib_global.php');

require_once('lib_xmldb.php');
require_once('lib_auth.php');
// }}}

/**
 * project class which defines procedures to handle project data
 * it has to be created with an interface. until now, there is 
 * only an mysql interface.
 *
 * @todo    perhaps reorganize project, so that not all project data 
 *            is saved in one big xml_document, but in different documents.
 *            the result should be faster saving and deleting\n
 *            \n
 *            project.project_name -> settings of project\n
 *            project.pages -> page structure and page_data of project\n
 *            project.globals -> templates and colors\n
 */
class project_acss_mysql2 extends project {
    // {{{ class variables
    /**
     * cache-array for project-ids
     *
     * @private
     */
    var $_project_ids = array();

    /**
     * cache-array for element-types
     *
     * @private
     */
    var $_element_type = array();
    var $_element_type_data_id = array();
    // }}}

    // {{{ constructor
    /**
     * constructor, defines xml-database access object
     */
    function project_acss_mysql2($param) {
        global $conf;

        $this->xmldb = new xml_db(
            $conf->db_table_xml_elements,                     //element table
            $conf->db_table_xml_cache,                         //cache table
            $conf->ns['database']['ns'],                     //db_xml namespace
            $conf->ns['database']['uri'],                     //db_xml namespace uri
            $conf->ns,                                        //global namespaces
            array(                                            //preserve whitespace for these nodes
                "{$conf->ns['edit']['ns']}:template",        //template publish 
                "{$conf->ns['edit']['ns']}:newnode",        //template newnode
                "{$conf->ns['edit']['ns']}:plain_source",    //plain source
                "p",                                        //text paragraphs
            )
        );
        
        /**
         * add user obj to project library
         */
        $this->user = new ttUser();
        /**
         * delete this global reference after all xmldb access rewritten
         * through project classes
         */
        $GLOBALS['xml_db'] = &$this->xmldb;
    }
    // }}}
    // {{{ get_avail_projects()
    /**
     * gets available projects from database.
     *
     * @public
     *
     * @return    $projects (array) available projects
     */
    function get_avail_projects() {
        global $conf;
                
        $xml_proj = '';
        
        $docs = $this->xmldb->get_docs();
        foreach ($docs as $name => $id) {
            list($temp_id) = $this->xmldb->get_node_ids_by_xpath($id, "/{$conf->ns['project']['ns']}:project/{$conf->ns['project']['ns']}:settings/{$conf->ns['project']['ns']}:type");
            $xml_proj .= "<project name=\"{$name}\" preview=\"" . $this->xmldb->get_attribute($temp_id, "", "preview") . "\" />";
        }
        
        return $xml_proj;
    }
    // }}}
    // {{{ get_projectId()
    /**
     * gets project-id by name or id or child-id. project-ids where cached for faster access.
     *
     * @public
     *
     * @param    $project_name (string) name of project
     *
     * @return    $id (int) id of project
     */
    function get_projectId($project_name) {
        global $log;

        if (preg_match("/^([0-9]+)$/", (string) $project_name)) {
            $log->add_entry("!!!! ATTENTION get_projectID by number instead of name");
            return $this->xmldb->get_doc_id_by_id($project_name);
        } else {
            if (!isset($this->_project_ids[$project_name])) {
                $this->_project_ids[$project_name] = $this->xmldb->get_doc_id_by_name($project_name);
            }
            return $this->_project_ids[$project_name];
        }
    }
    // }}}
    // {{{ get_settings()
    /**
     * gets project settings from db
     *
     * @public
     *
     * @param    $project_name (string) name of project
     *
     * @return    $settings (domxmlobject) poject settings as xml
     */
    function &get_settings($project_name) {
        global $conf;

        $doc_id = $this->get_projectId($project_name);
        $settings = $this->xmldb->get_doc_by_xpath($doc_id, "//{$conf->ns['project']['ns']}:settings");

        return $settings;
    }
    // }}}
    // {{{ get_languages()
    /**
     * gets languages of project
     *
     * @public
     *
     * @param    $project_name (string) name of project
     *
     * @return    $languages (array) array of available languages
     */
    function get_languages($project_name) {
        global $conf;

        $languages = array();
        $doc_id = $this->get_projectId($project_name);
        $ids_lang = $this->xmldb->get_node_ids_by_xpath($doc_id, "//{$conf->ns['project']['ns']}:languages/{$conf->ns['project']['ns']}:language");
        foreach($ids_lang as $temp_id) {
            $attributes = $this->xmldb->get_attributes($temp_id);
            $languages[$attributes['shortname']] = $attributes['name'];
        }
        return $languages;
    }
    // }}}
    // {{{ get_languages_xml()
    /**
     * gets languages of project
     *
     * @public
     *
     * @param    $project_name (string) name of project
     *
     * @return    $languages (array) array of available languages
     */
    function &get_languages_xml($project_name) {
        global $conf;

        $doc_id = $this->get_projectId($project_name);
        return $this->xmldb->get_doc_by_xpath($doc_id, "//{$conf->ns['project']['ns']}:languages", false);
    }
    // }}}
    // {{{ get_page_struct()
    /**
     * gets page hirarchy from db
     *
     * @public
     *
     * @param    $project_name (string) project name
     */
    function &get_page_struct($project_name) {
        global $conf;

        $doc_id = $this->get_projectId($project_name);
        $xml_def = $this->xmldb->get_doc_by_xpath($doc_id, "//{$conf->ns['project']['ns']}:pages_struct");

        return $xml_def;
    }
    // }}}
    // {{{ get_page_data()
    /**
     * gets page from db
     *
     * @public
     *
     * @param    $project_name (string) name of project
     * @param    $id (int) id of page
     *
     * @todo    move the language tester, so that it is called only, if some 
     *            language settings are changing.
     * @todo    test, if id belongs to project with $project_name
     */
    function &get_page_data($project_name, $id) {
        global $conf, $log;
        
        $xml_def = $this->xmldb->get_doc_by_id($id);
        
        return $xml_def;
    }
    // }}}
    // {{{ get_page_data_id_by_page_id()
    /**
     * gets ref-id to page from db
     *
     * @public
     *
     * @param    $project_name (string) name of project
     * @param    $id (int) id of page
     *
     * @todo    test, if id belongs to project with $project_name
     */
    function get_page_data_id_by_page_id($project_name, $id) {
        global $conf;
        
        $data_id = $this->xmldb->get_attribute($id, $conf->ns['database']['ns'], 'ref');
        
        return $data_id;
    }
    // }}}
    // {{{ get_page_id_by_page_data_id()
    /**
     * gets ref-id to page from db
     *
     * @public
     *
     * @param    $project_name (string) name of project
     * @param    $id (int) id of page
     *
     * @todo    test, if id belongs to project with $project_name
     */
    function get_page_id_by_page_data_id($project_name, $id) {
        global $conf;
        
        $tempid = $id;
        while (($name = $this->xmldb->get_node_name_by_id($tempid)) != "pg:page_data") {
            $tempid = $this->xmldb->get_parent_id_by_id($tempid);
        }
        
        list($data_id) = $this->xmldb->get_node_ids_by_xpath($this->get_projectId($project_name), "//{$conf->ns['page']['ns']}:page[@{$conf->ns['database']['ns']}:ref = '$tempid']");
        
        return $data_id;
    }
    // }}}
    // {{{ get_page_attributes()
    /**
     * gets page attributes from db
     *
     * @public
     *
     * @param    $project_name (string) name of project
     * @param    $id (int) id of page
     *
     * @todo    test, if id belongs to project with $project_name\n
     *            test, if node is page node
     */
    function get_page_attributes($project_name, $id) {
        global $conf;
        
        $attrs = $this->xmldb->get_attributes($id);
        
        return $attrs;
    }
    // }}}
    // {{{ get_lib_tree()
    /**
     * gets filetree of project library
     *
     * @public
     *
     * @param    $project_name (string) project name
     */
    function get_lib_tree($project_name) {
        global $conf;

        $lib_path = $this->get_project_path($project_name) . "/lib";
        $fs_access = fs::factory('local');

            $dir_str = "<{$conf->ns['project']['ns']}:dir name=\"" . $project_name . "\">";
        $dir_str .= $this->_get_lib_tree_dir($fs_access, $lib_path);
        $dir_str .= "</{$conf->ns['project']['ns']}:dir>";

        return $this->domxml_open_mem($dir_str);
    }
    /**
     * gets directories recursively and adds their subdirs to xml string
     *
     * @private
     *
     * @param    $fs_access (object) filesystem access object for local fs access
     * @param    $dir (string) directory
     *
     * @return    $dirXML (string) directory structure as xml
     */
    function _get_lib_tree_dir(&$fs_access, $path) {
        global $conf;

        $dirarray = array();
        $dirXML = '';
        
        $dirarray = $fs_access->list_dir($path);
        
        foreach ($dirarray['dirs'] as $dir) {
            $dirXML .= "<{$conf->ns['project']['ns']}:dir name=\"$dir\">";
            $dirXML .= $this->_get_lib_tree_dir($fs_access, "$path/$dir");
            $dirXML .= "</{$conf->ns['project']['ns']}:dir>"; 
        }
        
        return $dirXML;
    }
    // }}}
    // {{{ get_lib_dir_content()
    /**
     * gets files of specific directory
     * 
     * @public
     *
     * @param    $project_name (string) name of project
     * @param    $path (string) relative path to directory in library
     */
    function get_lib_dir_content($project_name, $path) {
        global $conf;

        $dir = $this->get_project_path($project_name) . '/lib' . $path;
        $fs_access = fs::factory('local');
        $dirarray = $fs_access->list_dir($dir);

        $dir_str = "<{$conf->ns['project']['ns']}:files><{$conf->ns['project']['ns']}:filelist dir=\"$path\">";
        foreach ($dirarray['files'] as $file) {
            $fileinfo = pathinfo($file);
            $imageinfo = @getimagesize($dir . '/' . $file);
            $dir_str .= "<file name=\"$file\"";
            $dir_str .= " extension=\"{$fileinfo['extension']}\"";
            if ($imageinfo[2] > 0) {
                $dir_str .= " width=\"{$imageinfo[0]}\"";
                $dir_str .= " height=\"{$imageinfo[1]}\"";
            }
            $dir_str .= " size=\"" . $fs_access->f_size_format("$dir/$file") . "\"";
            $dir_str .= " date=\"" . $conf->dateUTC($conf->date_format_UTC, filemtime("$dir/$file")) . "\"";

            $dir_str .= " />";
        }
        $dir_str .= "</{$conf->ns['project']['ns']}:filelist></{$conf->ns['project']['ns']}:files>";

        return $this->domxml_open_mem($dir_str);
    }
    // }}}
    // {{{ get_colors()
    /**
     * get colors from database
     *
     * @public
     *
     * @param    $project_name (string) project name
     */
    function &get_colors($project_name) {
        global $conf;

        $doc_id = $this->get_projectId($project_name);
        $xml_def = $this->xmldb->get_doc_by_xpath($doc_id, "//{$conf->ns['project']['ns']}:colorschemes");

        return $xml_def;
    }
    // }}}
    // {{{ get_tpl_template_struct()
    /**
     * get tpl_template_structure from db
     *
     * @public
     *
     * @param    $project_name (string) project name
     */
    function &get_tpl_template_struct($project_name) {
        global $conf;

        $doc_id = $this->get_projectId($project_name);
        $xml_def = $this->xmldb->get_doc_by_xpath($doc_id, "//{$conf->ns['project']['ns']}:tpl_templates_struct");

        return $xml_def;
    }
    // }}}
    // {{{ get_tpl_template_data()
    /**
     * get tpl_template_data from db
     *
     * @public
     *
     * @param    $project_name (string) project name
     * @param    $id (int) db-id of template data
     *
     * @todo    add check, if template belongs to project
     */
    function &get_tpl_template_data($project_name, $id) {
        global $conf;

        $doc_id = $this->get_projectId($project_name);
        $xml_def = $this->xmldb->get_doc_by_id($id);

        return $xml_def;
    }
    // }}}
    // {{{ get_tpl_template_contents()
    /**
     * gets contents of tpl_template_data elements, that
     * are active and of specific type.
     * 
     * @public
     *
     * @param    $project_name (string) name of project
     * @param    $type (string) name of template-set
     *
     * @return    $tplset (array) array of strings with template contents
     */
    function get_tpl_template_contents($project_name, $type) {
        global $conf;

        $tpl_set = array();
        $doc_id = $this->get_projectId($project_name);
        $tpl_ids = $this->xmldb->get_node_ids_by_xpath($doc_id, "//{$conf->ns['project']['ns']}:tpl_templates/{$conf->ns['page']['ns']}:template_data[@active='true' and @type='$type']/{$conf->ns['edit']['ns']}:template");
        foreach ($tpl_ids as $id) {
            $temp_doc = $this->xmldb->get_doc_by_id($id);
            $temp_node = $temp_doc->document_element();
            $tpl_set[] = $temp_node->get_content();
        }
        return $tpl_set;
    }
    // }}}
    // {{{ get_tpl_settings_xml()
    /**
     * gets template_set settings from db
     *
     * @public
     *
     * @param    $project_name (string) name of project
     *
     * @return    $settings (domxmlobject) poject settings as xml
     */
    function &get_tpl_settings_xml($project_name, $type) {
        global $conf;

        $doc_id = $this->get_projectId($project_name);
        $settings = $this->xmldb->get_doc_by_xpath($doc_id, "//{$conf->ns['project']['ns']}:template_set[@name = '$type']");

        return $settings;
    }
    // }}}
    // {{{ get_tpl_newnodes()
    /**
     * get tpl_newnodes from db
     *
     * @public
     *
     * @param    $project_name (string) project name
     */
    function &get_tpl_newnodes($project_name) {
        global $conf;

        $doc_id = $this->get_projectId($project_name);
        $xml_def = $this->xmldb->get_doc_by_xpath($doc_id, "//{$conf->ns['project']['ns']}:tpl_newnodes");

        return $xml_def;
    }
    // }}}
    // {{{ get_type()
    /**
     * gets type or subtype of element, identified by db:id
     *
     * @param    $id (int) id of element to test
     *
     * @return    $type (string) type of element or parent element
     *            or false, if element does not exist\n
     *            $data_id (int) is the id of the element that identifies
     *            the node. sometimes its the id of the node itself, or 
     *            one of its parent its. if no node exists with current id, 
     *            it contains false.
     */
    function get_type($id, &$data_id) {
        global $conf, $log;

        if (isset($_element_type[$id])) {
            $data_id = $_element_type_data_id[$id];
            return $_element_type[$id];
        }
        if ($id == NULL) {
            $data_id = false;
            return false;
        }
        $name = $this->xmldb->get_node_name_by_id($id);
        $data_id = $id;
        if (!$name) {
            $data_id = false;
            $retVal = false;
        } else if ($name == "{$conf->ns['page']['ns']}:page" || $name == "{$conf->ns['project']['ns']}:pages_struct") {
            $retVal = 'pages';
            $data_id = $this->get_page_data_id_by_page_id('', $id);
        } else if ($name == "{$conf->ns['page']['ns']}:page_data" || $name == "{$conf->ns['page']['ns']}:folder_data") {
            $retVal = 'page_data';
        } else if ($name == "{$conf->ns['project']['ns']}:colorscheme" || $name == "{$conf->ns['project']['ns']}:colorschemes") {
            $retVal = 'colors';
        } else if ($name == "{$conf->ns['project']['ns']}:settings") {
            $retVal = 'settings';
        } else if ($name == "{$conf->ns['page']['ns']}:template" || $name == "{$conf->ns['project']['ns']}:tpl_templates_struct") {
            $retVal = 'tpl_templates';
        } else if ($name == "{$conf->ns['page']['ns']}:template_data") {
            $retVal = 'tpl_template_data';
        } else if ($name == "{$conf->ns['page']['ns']}:newnode" || $name == "{$conf->ns['project']['ns']}:tpl_newnodes") {
            $retVal = 'tpl_newnodes';
            $data_id = $this->get_page_data_id_by_page_id('', $id);
        } else if ($name == "{$conf->ns['project']['ns']}:project") {
            $retVal = 'unknown';
        } else {
            $data_id = $this->xmldb->get_parent_id_by_id($id);
            $retVal = $this->get_type($data_id, $data_id);
        }
        $_element_type[$id] = $retVal;
        $_element_type_data_id[$id] = $data_id;

        return $retVal;
    }
    // }}}
    // {{{ save_element()
    /**
     * saves changed data to database
     *
     * @public
     *
     * @param    $project_name (string) name of project
     * @param    $id (int) id of element to save
     * @param    $data (xml_object) xml data to save to database
     */
    function save_element($project_name, $id, &$data) {
        global $log;

        if (in_array($type = $this->get_type($id, $data_id), array('page_data', 'tpl_template_data', 'tpl_newnodes', 'colors', 'settings'))) {
            $this->xmldb->save_node($data);
            switch ($type) {
                case 'page_data':
                    $this->_set_element_lastchange_UTC($id);
                    tpl_engine::delete_from_transform_cache($project_name, $data_id, 'preview');
                    break;
                case 'colors':
                    tpl_engine::clear_transform_cache($project_name, 'preview');
                    break;
                case 'tpl_template_data':
                    $this->_set_element_lastchange_UTC($id);
                    break;
                case 'tpl_newnodes':
                    $this->_set_element_lastchange_UTC($id);
                    break;
                case 'settings':
                    break;
            }
        } else if ($type) {
            $log->add_entry("$type is not supported for saving");
        } else {
            $log->add_entry("no id is given for saving");
        }
        return $page_id;
    }
    // }}}
    // {{{ add_page()
    /**
     * adds new page to document tree
     *
     * @param    $project_name (string) name of project
     * @param    $target_id (int) id of page to rename
     * @param    $newname (string) new name
     * @param    $page_data (string) data to insert in page directly
     */
    function add_page($project_name, $target_id, $newname, $page_data = '') {
        global $conf, $log;

        $doc_page = @file_get_contents('xml/pages_new_page.xml');
        $doc_page_data = @file_get_contents('xml/pages_new_page_data.xml');
        if ($doc_page != '' && $doc_page_data != '' && in_array($type = $this->get_type($target_id, $data_id), array('pages'))) {
            //set standard document type
            $output_types = array_keys($conf->output_file_types);
            $doc_page = str_replace('%insert_default_type%', htmlspecialchars($output_types[0]), $doc_page);
            
            //set standard colorscheme
            $xml_colors = $this->get_colors($project_name);
            $xpath_colors = $this->xpath_new_context($xml_colors);
            $xfetch = xpath_eval($xpath_colors, "/{$conf->ns['project']['ns']}:colorschemes/{$conf->ns['project']['ns']}:colorscheme[@{$conf->ns['database']['ns']}:name!=\"tree_name_color_global\"]");
            if (count($xfetch->nodeset) > 0) {
                $doc_page_data = str_replace('%insert_default_colorscheme%', htmlspecialchars($xfetch->nodeset[0]->get_attribute('name')), $doc_page_data);
            } else {
                $doc_page_data = str_replace('%insert_default_colorscheme%', '', $doc_page_data);
            }
            
            //insert node_data
            $doc_page_data = str_replace('%insert_data%', $page_data, $doc_page_data);

            //set name
            $doc_page = str_replace('%insert_default_name%', htmlspecialchars($newname), $doc_page);
            if ($xml_page_data = $this->domxml_open_mem($doc_page_data)) {
                $languages = $this->get_languages($project_name);
                $this->_test_pageObj_languages($xml_page_data, 'true', $languages);
                list($data_id) = $this->xmldb->get_node_ids_by_xpath($this->get_projectId($project_name), "//{$conf->ns['project']['ns']}:pages");
                $new_id = $this->xmldb->save_node(&$xml_page_data, $data_id);                
                $doc_page = str_replace('%insert_data_id%', $new_id, $doc_page);
                $xml_page = $this->domxml_open_mem($doc_page);
                if ($xml_page) {
                    $new_id = $this->xmldb->save_node(&$xml_page, $target_id);

                    return $new_id;
                } else {
                    $log->add_entry('no valid xml data to insert', 'debug');
                }
            } else {
                $log->add_entry('no valid xml data to insert', 'debug');
            }
        }
    }
    // }}}
    // {{{ add_page_folder()
    /**
     * adds new folder to document tree
     *
     * @param    $project_name (string) name of project
     * @param    $target_id (int) id of page to rename
     * @param    $newname (string) new name
     */
    function add_page_folder($project_name, $target_id, $newname) {
        global $conf, $log;

        $doc_page = @file_get_contents('xml/pages_new_folder.xml');
        $doc_page_data = @file_get_contents('xml/pages_new_folder_data.xml');
        if ($doc_page != '' && $doc_page_data != '' && in_array($type = $this->get_type($target_id, $data_id), array('pages'))) {
            //set name
            $doc_page = str_replace('%insert_default_name%', htmlspecialchars($newname), $doc_page);
            if ($xml_page_data = $this->domxml_open_mem($doc_page_data)) {
                $data_ids = $this->xmldb->get_node_ids_by_xpath($this->get_projectId($project_name), "//{$conf->ns['project']['ns']}:pages");
                $new_id = $this->xmldb->save_node(&$xml_page_data, $data_ids[0]);                
                $doc_page = str_replace('%insert_data_id%', $new_id, $doc_page);
                $xml_page = $this->domxml_open_mem($doc_page);
                if ($xml_page) {
                    $new_id = $this->xmldb->save_node(&$xml_page, $target_id);

                    return $new_id;
                } else {
                    $log->add_entry('no valid xml data to insert', 'debug');
                }
            } else {
                $log->add_entry('no valid xml data to insert', 'debug');
            }
        }
    }
    // }}}
    // {{{ add_page_data_element()
    /**
     * adds new page to document tree
     *
     * @param    $project_name (string) name of project
     * @param    $target_id (int) id of page to rename
     * @param    $newname (string) new name
     * @param    $page_data (string) data to insert in page directly
     */
    function add_page_data_element($project_name, $target_id, $newname, $page_data = '') {
        global $conf, $log;
        
        if (($xml_element = project::domxml_open_mem($page_data)) && in_array($type = $this->get_type($target_id, $data_id), array('page_data'))) {
            $root_node = $xml_element->document_element();
            $page_id = $this->get_page_id_by_page_data_id($project_name, $target_id);

            $languages = $this->get_languages($project_name);
            $this->_test_pageObj_languages($xml_element, $this->xmldb->get_attribute($page_id, '', 'multilang'), $languages);

            $new_id = $this->xmldb->save_node(&$root_node, $target_id);                
            if (($page_data_id = $this->_set_element_lastchange_UTC($target_id)) != null) {
                tpl_engine::delete_from_transform_cache($project_name, $page_data_id, 'preview');
            }

            return $new_id;
        }
    }
    // }}}
    // {{{ add_tpl_template()
    /**
     * adds new tpl_template to document tree
     *
     * @param    $project_name (string) name of project
     * @param    $target_id (int) id of page to rename
     * @param    $newname (string) new name
     */
    function add_tpl_template($project_name, $target_id, $newname) {
        global $conf, $log;

        $doc_tpl = @file_get_contents('xml/tpl_templates_new_template.xml');
        $doc_tpl_data = @file_get_contents('xml/tpl_templates_new_template_data.xml');
        if ($doc_tpl != '' && $doc_tpl_data != '' && in_array($type = $this->get_type($target_id, $data_id), array('tpl_templates'))) {
            //set name
            $doc_tpl = str_replace('%insert_default_name%', htmlspecialchars($newname), $doc_tpl);
            if ($xml_tpl_data = $this->domxml_open_mem($doc_tpl_data)) {
                $data_ids = $this->xmldb->get_node_ids_by_xpath($this->get_projectId($project_name), "//{$conf->ns['project']['ns']}:tpl_templates");
                $new_id = $this->xmldb->save_node(&$xml_tpl_data, $data_ids[0]);                
                $doc_tpl = str_replace('%insert_data_id%', $new_id, $doc_tpl);
                if ($xml_tpl = $this->domxml_open_mem($doc_tpl)) {
                    $new_id = $this->xmldb->save_node(&$xml_tpl, $target_id);

                    return $new_id;
                } else {
                    $log->add_entry('no valid xml data to insert', 'debug');
                }
            } else {
                $log->add_entry('no valid xml data to insert', 'debug');
            }
        }
    }
    // }}}
    // {{{ add_tpl_template_folder()
    /**
     * adds new folder to template tree
     *
     * @param    $project_name (string) name of project
     * @param    $target_id (int) id of page to rename
     * @param    $newname (string) new name
     */
    function add_tpl_template_folder($project_name, $target_id, $newname) {
        global $conf, $log;

        $doc_tpl = @file_get_contents('xml/tpl_templates_new_folder.xml');
        if ($doc_tpl != '' && in_array($type = $this->get_type($target_id, $data_id), array('tpl_templates'))) {
            //set name
            $doc_tpl = str_replace('%insert_default_name%', htmlspecialchars($newname), $doc_tpl);
            if ($xml_tpl = project::domxml_open_mem($doc_tpl)) {
                $new_id = $this->xmldb->save_node(&$xml_tpl, $target_id);

                return $new_id;
            } else {
                $log->add_entry('no valid xml data to insert', 'debug');
            }
        }
    }
    // }}}
    // {{{ add_tpl_newnode()
    /**
     * adds new newnode to newnode list
     *
     * @param    $project_name (string) name of project
     * @param    $target_id (int) id of page to rename
     * @param    $newname (string) new name
     */
    function add_tpl_newnode($project_name, $target_id, $newname) {
        global $conf, $log;

        $doc_tpl = @file_get_contents('xml/tpl_newnodes_new_newnode.xml');
        if ($doc_tpl != '' && in_array($type = $this->get_type($target_id, $data_id), array('tpl_newnodes'))) {
            //set name
            $doc_tpl = str_replace('%insert_default_name%', htmlspecialchars($newname), $doc_tpl);
            if ($xml_tpl = project::domxml_open_mem($doc_tpl)) {
                $new_id = $this->xmldb->save_node(&$xml_tpl, $target_id);

                return $new_id;
            } else {
                $log->add_entry('no valid xml data to insert', 'debug');
            }
        }
    }
    // }}}
    // {{{ rename_element()
    /**
     * renames element in database
     *
     * @param    $project_name (string) name of project
     * @param    $id (int) id of page to rename
     * @param    $newname (string) new name
     */
    function rename_element($project_name, $id, $newname) {
        global $log;

        if (in_array($type = $this->get_type($id, $data_id), array('pages', 'page_data', 'tpl_templates', 'tpl_newnodes', 'colors', 'settings'))) {
            $this->xmldb->set_attribute($id, '', 'name', $newname);
            switch ($type) {
                case 'pages':
                    tpl_engine::clear_transform_cache($project_name, 'preview');
                    break;
                case 'page_data':
                    $this->_set_element_lastchange_UTC($id);
                    tpl_engine::delete_from_transform_cache($project_name, $data_id, 'preview');
                    break;
                case 'colors':
                    tpl_engine::clear_transform_cache($project_name, 'preview');
                    break;
            }
        } else if ($type) {
            $log->add_entry("$type is not supported for renaming");
        } else {
            $log->add_entry("no id is given for renaming");
        }
    }
    // }}}
    // {{{ delete_element()
    /**
     * deletes element
     *
     * public
     *
     * @param    $project_name (string) name of project
     * @param    $id (int) id of elemnt to delete
     *
     * @todo    define delete for settings
     */
    function delete_element($project_name, $id) {
        global $conf, $log;

        $data_ids = array();
        $changed_ids = array();
        if (in_array($type = $this->get_type($id, $data_id), array('pages', 'page_data', 'colors', 'tpl_templates', 'tpl_newnodes', 'colors', 'settings'))) {
            switch ($type) {
                case 'pages':
                    //get data_ids
                    $xml_pages = $this->get_page_struct($project_name);
                    $ctx_pages = project::xpath_new_context($xml_pages);
                    $xfetch = xpath_eval($ctx_pages, "//*[@db:id = '$id']/@db:ref");
                    foreach ($xfetch->nodeset as $attr) {
                        $data_ids[] = $attr->value();
                    }
                    $xfetch = xpath_eval($ctx_pages, "//*[@db:id = '$id']//*/@db:ref");
                    if (is_array($xfetch->nodeset)) {
                        foreach ($xfetch->nodeset as $attr) {
                            $data_ids[] = $attr->value();
                        }
                    }

                    //delete data_nodes
                    foreach ($data_ids as $data_id) {
                        $changed_ids = array_merge($changed_ids, $this->xmldb->unlink_node_by_id($data_id));
                    }

                    //delete page_nodes
                    $changed_ids = array_merge($changed_ids, $this->xmldb->unlink_node_by_id($id));
                    tpl_engine::clear_transform_cache($project_name, 'preview');
                    $this->xmldb->clear_deleted_nodes();
                    break;
                case 'page_data':
                    $this->_set_element_lastchange_UTC($id);
                    $changed_ids = $this->xmldb->unlink_node_by_id($id);
                    $this->xmldb->clear_deleted_nodes();
                    $changed_ids[] = $data_id;
                    if ($data_id != null) {
                        tpl_engine::delete_from_transform_cache($project_name, $data_id, 'preview');
                    }
                    break;
                case 'colors':
                    $changed_ids = $this->xmldb->unlink_node_by_id($id);
                    tpl_engine::clear_transform_cache($project_name, 'preview');
                    $this->xmldb->clear_deleted_nodes();
                    break;
                case 'settings':
                    $changed_ids = $this->xmldb->unlink_node_by_id($id);
                    $this->xmldb->clear_deleted_nodes();
                    break;
                case 'tpl_templates':
                    //get data_ids
                    $xml_pages = $this->get_tpl_template_struct($project_name);
                    $ctx_pages = project::xpath_new_context($xml_pages);
                    $xfetch = xpath_eval($ctx_pages, "//*[@db:id = '$id']/@db:ref");
                    foreach ($xfetch->nodeset as $attr) {
                        $data_ids[] = $attr->value();
                    }
                    $xfetch = xpath_eval($ctx_pages, "//*[@db:id = '$id']//*/@db:ref");
                    if (is_array($xfetch->nodeset)) {
                        foreach ($xfetch->nodeset as $attr) {
                            $data_ids[] = $attr->value();
                        }
                    }

                    //delete data_nodes
                    foreach ($data_ids as $data_id) {
                        $changed_ids = array_merge($changed_ids, $this->xmldb->unlink_node_by_id($data_id));
                    }

                    //delete tpl_nodes
                    $changed_ids = array_merge($changed_ids, $this->xmldb->unlink_node_by_id($id));
                    $this->xmldb->clear_deleted_nodes();
                    break;
                case 'tpl_newnodes':
                    $changed_ids = $this->xmldb->unlink_node_by_id($id);
                    $this->xmldb->clear_deleted_nodes();
                    break;
            }
        }

        return $changed_ids;
    }
    // }}}
    // {{{ duplicate_element()
    /**
     * duplicates element
     *
     * public
     *
     * @param    $project_name (string) name of project
     * @param    $id (int) id of elemnt to delete
     * @param    $new_name (string) new name after duplication
     *
     * @todo    define dulicate for settings
     */
    function duplicate_element($project_name, $id, $new_name = NULL) {
        global $conf, $log;

        $log->add_entry("attemting to duplicate '$id' in '$project_name' with new name '$new_name'");
        if (in_array($type = $this->get_type($id, $data_id), array('pages', 'page_data', 'colors', 'tpl_templates', 'tpl_newnodes', 'colors', 'settings'))) {
            switch ($type) {
                case 'pages':
                    //get needed data
                    $target_id = $this->xmldb->get_parent_id_by_id($id);
                    $target_pos = $this->xmldb->get_pos_by_id($id) + 1;
                    $page_data_id = $this->get_page_data_id_by_page_id($project_name, $id);
                    list($proj_pgs_id) = $this->xmldb->get_node_ids_by_xpath($this->get_projectId($project_name), "//{$conf->ns['project']['ns']}:pages");

                    //duplicate page_data
                    $new_data_id = $this->xmldb->copy_node_in($page_data_id, $proj_pgs_id);
                    $this->_set_element_lastchange_UTC($new_data_id);

                    //add page node
                    $pg_attr = $this->xmldb->get_attributes($id);
                    if ($new_name != null) {
                        $pg_attr['name'] = $new_name;
                    }
                    $pg_attr["{$conf->ns['database']['ns']}:ref"] = $new_data_id;
                    $page_xml_str = "<{$conf->ns['page']['ns']}:page ";
                    foreach($pg_attr as $name => $value) {
                        $page_xml_str .= "$name=\"" . htmlspecialchars($value) . "\" ";
                    }
                    $page_xml_str .= "/>";
                    $page_xml = project::domxml_open_mem($page_xml_str);
                    $new_id = $this->xmldb->save_node($page_xml, $target_id, $target_pos);
                    break;
                case 'page_data':
                    $target_id = $this->xmldb->get_parent_id_by_id($id);
                    $target_pos = $this->xmldb->get_pos_by_id($id) + 1;
                    $xmldoc = $this->xmldb->get_doc_by_id($id);
                    $root_node = $xmldoc->document_element();
                    if ($new_name != null) {
                        $root_node->set_attribute('name', $new_name);
                    }

                    $new_id = $this->xmldb->save_node($root_node, $target_id, $target_pos);
                    if (($page_id = $this->_set_element_lastchange_UTC($id)) != null) {
                        tpl_engine::delete_from_transform_cache($project_name, $page_id, 'preview');
                    }
                    break;
                case 'colors':
                    $target_id = $this->xmldb->get_parent_id_by_id($id);
                    $target_pos = $this->xmldb->get_pos_by_id($id) + 1;
                    $xmldoc = $this->xmldb->get_doc_by_id($id);
                    $root_node = $xmldoc->document_element();
                    if ($new_name != null) {
                        $root_node->set_attribute('name', $new_name);
                    }

                    $new_id = $this->xmldb->save_node($root_node, $target_id, $target_pos);
                    break;
                case 'settings':
                    $target_id = $this->xmldb->get_parent_id_by_id($id);
                    $target_pos = $this->xmldb->get_pos_by_id($id) + 1;
                    $xmldoc = $this->xmldb->get_doc_by_id($id);
                    $root_node = $xmldoc->document_element();
                    if ($new_name != null) {
                        $root_node->set_attribute('name', $new_name);
                    }

                    $new_id = $this->xmldb->save_node($root_node, $target_id, $target_pos);
                    break;
                case 'tpl_templates':
                    //get needed data
                    $target_id = $this->xmldb->get_parent_id_by_id($id);
                    $target_pos = $this->xmldb->get_pos_by_id($id) + 1;
                    $page_data_id = $this->get_page_data_id_by_page_id($project_name, $id);
                    list($proj_pgs_id) = $this->xmldb->get_node_ids_by_xpath($this->get_projectId($project_name), "//{$conf->ns['project']['ns']}:tpl_templates");

                    //duplicate tpl_data
                    if ($page_data_id != NULL) {
                        $new_data_id = $this->xmldb->copy_node_in($page_data_id, $proj_pgs_id);
                        $this->_set_element_lastchange_UTC($new_data_id);
                    }

                    //add template node
                    $pg_attr = $this->xmldb->get_attributes($id);
                    if ($new_name != null) {
                        $pg_attr['name'] = $new_name;
                    }
                    if ($page_data_id != NULL) {
                        $pg_attr["{$conf->ns['database']['ns']}:ref"] = $new_data_id;
                        $page_xml_str = "<{$conf->ns['page']['ns']}:template ";
                    } else {
                        $page_xml_str = "<{$conf->ns['page']['ns']}:folder ";
                    }
                    foreach($pg_attr as $name => $value) {
                        $page_xml_str .= "$name=\"" . htmlspecialchars($value) . "\" ";
                    }
                    $page_xml_str .= "/>";
                    $page_xml = project::domxml_open_mem($page_xml_str);
                    $new_id = $this->xmldb->save_node($page_xml, $target_id, $target_pos);
                    break;
                case 'tpl_newnodes':
                    $target_id = $this->xmldb->get_parent_id_by_id($id);
                    $target_pos = $this->xmldb->get_pos_by_id($id) + 1;
                    $xmldoc = $this->xmldb->get_doc_by_id($id);
                    $root_node = $xmldoc->document_element();
                    if ($new_name != null) {
                        $root_node->set_attribute('name', $new_name);
                    }

                    $new_id = $this->xmldb->save_node($root_node, $target_id, $target_pos);
                    break;
            }
        }

        $log->add_entry("duplicating '$type' and '$new_id' is the new id.");
        return $new_id;
    }
    // }}}
    // {{{ move_element_in()
    /**
     * move one node into another
     *
     * @param    $project_name (string) name of project
     * @param    $id (int) id of element
     * @param    $target_id (int) id of target element
     */
    function move_element_in($project_name, $id, $target_id) {
        global $conf, $log;

        $type = $this->get_type($id, $data_id);
        $target_type = $this->get_type($target_id, $target_data_id);
        if ($type && $type == $target_type) {
            $this->xmldb->move_node_in($id, $target_id);
            $this->_set_element_lastchange_UTC($id);
            switch ($type) {
                case 'pages':
                    tpl_engine::clear_transform_cache($project_name, 'preview');
                    break;
                case 'page_data':
                    tpl_engine::delete_from_transform_cache($project_name, $data_id, 'preview');
                    break;
            }
        }
    }
    // }}}
    // {{{ move_element_before()
    /**
     * move one node before another
     *
     * @param    $project_name (string) name of project
     * @param    $id (int) id of element
     * @param    $target_id (int) id of target element
     */
    function move_element_before($project_name, $id, $target_id) {
        global $conf, $log;

        $type = $this->get_type($id, $data_id);
        $target_type = $this->get_type($target_id, $target_data_id);
        if ($type && $type == $target_type) {
            $this->xmldb->move_node_before($id, $target_id);
            $this->_set_element_lastchange_UTC($id);
            switch ($type) {
                case 'pages':
                    tpl_engine::clear_transform_cache($project_name, 'preview');
                    break;
                case 'page_data':
                    tpl_engine::delete_from_transform_cache($project_name, $data_id, 'preview');
                    break;
            }
        }
    }
    // }}}
    // {{{ move_element_after()
    /**
     * move one node after another
     *
     * @param    $project_name (string) name of project
     * @param    $id (int) id of element
     * @param    $target_id (int) id of target element
     */
    function move_element_after($project_name, $id, $target_id) {
        global $conf, $log;

        $type = $this->get_type($id, $data_id);
        $target_type = $this->get_type($target_id, $target_data_id);
        if ($type && $type == $target_type) {
            $this->xmldb->move_node_after($id, $target_id);
            $this->_set_element_lastchange_UTC($id);
            switch ($type) {
                case 'pages':
                    tpl_engine::clear_transform_cache($project_name, 'preview');
                    break;
                case 'page_data':
                    tpl_engine::delete_from_transform_cache($project_name, $data_id, 'preview');
                    break;
            }
        }
    }
    // }}}
    // {{{ copy_element_in()
    /**
     * copy one node into another
     *
     * @param    $project_name (string) name of project
     * @param    $id (int) id of element
     * @param    $target_id (int) id of target element
     * @param    $new_name (string) new name of copy
     */
    function copy_element_in($project_name, $id, $target_id, $new_name = NULL) {
        global $conf, $log;

        $type = $this->get_type($id, $data_id);
        $target_type = $this->get_type($target_id, $target_data_id);
        if ($type && $type == $target_type) {
            switch ($type) {
                case 'pages':
                    //get needed data
                    list($proj_pgs_id) = $this->xmldb->get_node_ids_by_xpath($this->get_projectId($project_name), "//{$conf->ns['project']['ns']}:pages");
                    $new_id = $this->xmldb->copy_node_in($id, $target_id);
                    $nav = $this->get_page_struct($project_name);

                    $page_nodes = array();

                    $nav_ctx = project::xpath_new_context($nav);
                    $pages = xpath_eval($nav_ctx, "//*[@{$this->xmldb->id_attribute} = '$new_id']");
                    for ($i = 0; $i < count($pages->nodeset); $i++) {
                        $page_nodes[] = $pages->nodeset[$i];
                    }
                    $pages = xpath_eval($nav_ctx, "//*[@{$this->xmldb->id_attribute} = '$new_id']//*");
                    for ($i = 0; $i < count($pages->nodeset); $i++) {
                        $page_nodes[] = $pages->nodeset[$i];
                    }

                    //duplicate pages
                    for ($i = 0; $i < count($page_nodes); $i++) {
                        $data_id = $page_nodes[$i]->get_attribute('id');
                        $ref_node = $page_nodes[$i]->get_attribute_node('ref');
                        $new_data_id = $this->xmldb->copy_node_in($page_nodes[$i]->get_attribute('ref'), $proj_pgs_id);
                        $this->xmldb->set_attribute_ns($page_nodes[$i], $conf->ns['database']['uri'], $conf->ns['database']['ns'], 'ref', $new_data_id);
                        $this->_set_element_lastchange_UTC($new_data_id);
                    }
                    $cp_nodes = xpath_eval($nav_ctx, "//*[@{$this->xmldb->id_attribute} = '$new_id']");
                    $cp_nodes->nodeset[0]->set_attribute('name', $new_name);
                    $this->xmldb->save_node($cp_nodes->nodeset[0]);

                    tpl_engine::clear_transform_cache($project_name, 'preview');
                    break;
                case 'page_data':
                case 'colors':
                case 'settings':
                case 'tpl_newnodes':
                    $new_id = $this->xmldb->copy_node_in($id, $target_id);
                    if ($new_name != NULL) {
                        $this->xmldb->set_attribute($new_id, '', 'name', $new_name);
                    }
                    $this->_set_element_lastchange_UTC($new_id);
                    if ($type == 'page_data') {    
                        tpl_engine::delete_from_transform_cache($project_name, $data_id, 'preview');
                    }
                    break;
            }
        }
        return $new_id;
    }
    // }}}
    // {{{ copy_element_before()
    /**
     * copy one node before another
     *
     * @param    $project_name (string) name of project
     * @param    $id (int) id of element
     * @param    $target_id (int) id of target element
     * @param    $new_name (string) new name of copy
     */
    function copy_element_before($project_name, $id, $target_id, $new_name = NULL) {
        global $conf, $log;

        $type = $this->get_type($id, $data_id);
        $target_type = $this->get_type($target_id, $target_data_id);
        if ($type && $type == $target_type) {
            switch ($type) {
                case 'pages':
                    //get needed data
                    list($proj_pgs_id) = $this->xmldb->get_node_ids_by_xpath($this->get_projectId($project_name), "//{$conf->ns['project']['ns']}:pages");
                    $new_id = $this->xmldb->copy_node_before($id, $target_id);
                    $nav = $this->get_page_struct($project_name);

                    $page_nodes = array();

                    $nav_ctx = project::xpath_new_context($nav);
                    $pages = xpath_eval($nav_ctx, "//*[@{$this->xmldb->id_attribute} = '$new_id']");
                    for ($i = 0; $i < count($pages->nodeset); $i++) {
                        $page_nodes[] = $pages->nodeset[$i];
                    }
                    $pages = xpath_eval($nav_ctx, "//*[@{$this->xmldb->id_attribute} = '$new_id']//*");
                    for ($i = 0; $i < count($pages->nodeset); $i++) {
                        $page_nodes[] = $pages->nodeset[$i];
                    }

                    //duplicate pages
                    for ($i = 0; $i < count($page_nodes); $i++) {
                        $data_id = $page_nodes[$i]->get_attribute('id');
                        $ref_node = $page_nodes[$i]->get_attribute_node('ref');
                        $new_data_id = $this->xmldb->copy_node_in($page_nodes[$i]->get_attribute('ref'), $proj_pgs_id);
                        $this->xmldb->set_attribute_ns($page_nodes[$i], $conf->ns['database']['uri'], $conf->ns['database']['ns'], 'ref', $new_data_id);
                        $this->_set_element_lastchange_UTC($new_data_id);
                    }
                    $cp_nodes = xpath_eval($nav_ctx, "//*[@{$this->xmldb->id_attribute} = '$new_id']");
                    $cp_nodes->nodeset[0]->set_attribute('name', $new_name);
                    $this->xmldb->save_node($cp_nodes->nodeset[0]);

                    tpl_engine::clear_transform_cache($project_name, 'preview');

                    break;
                case 'page_data':
                case 'colors':
                case 'settings':
                case 'tpl_newnodes':
                    $new_id = $this->xmldb->copy_node_before($id, $target_id);
                    if ($new_name != NULL) {
                        $this->xmldb->set_attribute($new_id, '', 'name', $new_name);
                    }
                    $this->_set_element_lastchange_UTC($new_id);
                    if ($type == 'page_data') {    
                        tpl_engine::delete_from_transform_cache($project_name, $data_id, 'preview');
                    }
                    break;
            }
        }
        return $new_id;
    }
    // }}}
    // {{{ copy_element_after()
    /**
     * copy one node after  another
     *
     * @param    $project_name (string) name of project
     * @param    $id (int) id of element
     * @param    $target_id (int) id of target element
     * @param    $new_name (string) new name of copy
     */
    function copy_element_after($project_name, $id, $target_id, $new_name = NULL) {
        global $conf, $log;

        $type = $this->get_type($id, $data_id);
        $target_type = $this->get_type($target_id, $target_data_id);
        if ($type && $type == $target_type) {
            switch ($type) {
                case 'pages':
                    //get needed data
                    list($proj_pgs_id) = $this->xmldb->get_node_ids_by_xpath($this->get_projectId($project_name), "//{$conf->ns['project']['ns']}:pages");
                    $new_id = $this->xmldb->copy_node_after($id, $target_id);
                    $nav = $this->get_page_struct($project_name);

                    $page_nodes = array();

                    $nav_ctx = project::xpath_new_context($nav);
                    $pages = xpath_eval($nav_ctx, "//*[@{$this->xmldb->id_attribute} = '$new_id']");
                    for ($i = 0; $i < count($pages->nodeset); $i++) {
                        $page_nodes[] = $pages->nodeset[$i];
                    }
                    $pages = xpath_eval($nav_ctx, "//*[@{$this->xmldb->id_attribute} = '$new_id']//*");
                    for ($i = 0; $i < count($pages->nodeset); $i++) {
                        $page_nodes[] = $pages->nodeset[$i];
                    }

                    //duplicate pages
                    for ($i = 0; $i < count($page_nodes); $i++) {
                        $data_id = $page_nodes[$i]->get_attribute('id');
                        $ref_node = $page_nodes[$i]->get_attribute_node('ref');
                        $new_data_id = $this->xmldb->copy_node_in($page_nodes[$i]->get_attribute('ref'), $proj_pgs_id);
                        $this->xmldb->set_attribute_ns($page_nodes[$i], $conf->ns['database']['uri'], $conf->ns['database']['ns'], 'ref', $new_data_id);
                        $this->_set_element_lastchange_UTC($new_data_id);
                    }
                    $cp_nodes = xpath_eval($nav_ctx, "//*[@{$this->xmldb->id_attribute} = '$new_id']");
                    $cp_nodes->nodeset[0]->set_attribute('name', $new_name);
                    $this->xmldb->save_node($cp_nodes->nodeset[0]);

                    tpl_engine::clear_transform_cache($project_name, 'preview');

                    break;
                case 'page_data':
                case 'colors':
                case 'settings':
                case 'tpl_newnodes':
                    $new_id = $this->xmldb->copy_node_after($id, $target_id);
                    if ($new_name != NULL) {
                        $this->xmldb->set_attribute($new_id, '', 'name', $new_name);
                    }
                    $this->_set_element_lastchange_UTC($new_id);
                    if ($type == 'page_data') {    
                        tpl_engine::delete_from_transform_cache($project_name, $data_id, 'preview');
                    }
                    break;
            }
        }
        return $new_id;
    }
    // }}}

    // {{{ _set_element_lastchange_UTC()
    /**
     * sets last change date of page
     *
     * @private
     *
     * @param    $id (int) id of changed element
     *
     * @return    $page_id (int) id of changed page
     */
    function _set_element_lastchange_UTC($id) {
        global $conf, $log;
        
        if ($type = $this->get_type($id, $data_id)) {
            switch ($type) {
                case 'page_data':
                    list($meta_id) = $this->xmldb->get_child_ids_by_name($data_id, $conf->ns['page']['ns'], 'meta');
                    if ($meta_id) {
                        $this->xmldb->set_attribute($meta_id, '', 'lastchange_UTC', $conf->dateUTC($conf->date_format_UTC));
                        $this->xmldb->set_attribute($meta_id, '', 'lastchange_uid', $this->user->uid);
                    }
                    break;
                case 'tpl_template_data':
                case 'tpl_newnodes':
                    $this->xmldb->set_attribute($data_id, '', 'lastchange_UTC', $conf->dateUTC($conf->date_format_UTC));
                    $this->xmldb->set_attribute($meta_id, '', 'lastchange_uid', $this->user->uid);
                    break;
            }
            return $data_id;
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
