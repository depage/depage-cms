<?php
/**
 * @file    lib_tpl.php
 *
 * Template Framework Library
 *
 * This file contains the root library to transform
 * XML data with different template engines into other
 * data output.
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

// {{{ define and includes
if (!function_exists('die_error')) require_once('lib_global.php');

require_once('lib_xmldb.php');
require_once('lib_project.php');
// }}}

/**
 * parent class for all other template classes
 */
class tpl_engine {
    // {{{ factory()
    /**
     * provides an interface for generating tpl_engine:: objects
     * of various types
     *
     * @public
     *
     * @param    $driver (string) name of template engine to create.
     *             now only xslt is supported.
     * @param    $param (array) array of parameters, which are passed 
     *            to new tpl_engine object
     */
    function &factory($driver, $param = array()) {
        $driver = strtolower($driver);
        $class = "tpl_engine_{$driver}";
        require_once("lib_tpl_{$driver}.php");

        return new $class($param);
    }
    // }}}
    // {{{ add_to_transform_cache()
    /**
     * adds transformed data to transformation cache
     *
     * @public
     *
     * @param    $project_name (string) project name
     * @param    $type (string) template set of transformation
     * @param    $id (string) id of page being transformed
     * @param    $lang (string) language of transformation
     * @param    $access (string) type of access (preview or browsing)
     * @param    $transformed (string) transformed data, that is added to cache
     * @param    $ids_used (array) array of ids used for generating this page
     */
    function add_to_transform_cache($project_name, $type, $id, $lang, $access, $transformed, $ids_used) {
        global $conf, $project;
        
        $this->_set_project($project_name);

        $id_project = $project->get_projectId($project_name);
        $value = mysql_real_escape_string($transformed['value']);
        $content_type = mysql_real_escape_string($transformed['content_type']);
        $content_encoding = mysql_real_escape_string($transformed['content_encoding']);
        $ids_used = implode(',', $ids_used);
        
        db_query(
            "REPLACE {$this->db_table_transform_cache}
            SET id_project='$id_project', id_page='$id', type='$type', lang='$lang', access='$access', ids_used='$ids_used,', value='$value', content_type='$content_type', content_encoding='$content_encoding'"
        );
    }
    // }}}
    // {{{ get_from_transform_cache()
    /**
     * gets transformed data from cache
     *
     * @public
     *
     * @param    $project_name (string) project name
     * @param    $type (string) template set of transformation
     * @param    $id (sring) id of page, that is requested
     * @param    $lang (string) language of tansformation
     * @param    $access (string) type of access (preview or browsing)
     *
     * @return    $transformed (string) transformed data from cache
     */
    function get_from_transform_cache($project_name, $type, $id, $lang, $access) {
        global $conf, $project;
        
        $transformed = array();
        $id_project = $project->get_projectId($project_name);

        $this->_set_project($project_name);
        
        $result = db_query(
            "SELECT value, content_type, content_encoding 
            FROM {$this->db_table_transform_cache}
            WHERE id_project='$id_project' and id_page='$id' and type='$type' and lang='$lang' and access='$access'"
        );
        if ($result && mysql_num_rows($result) == 1) {
            $row = mysql_fetch_assoc($result);
            //mysql_free_result($result);
            
            return $row;
        } else {
            return false;
        }
        
    }
    // }}}
    // {{{ delete_from_transform_cache()
    /**
     * deletes page from transform cache
     *
     * @public
     *
     * @param    $project_name (string) project name
     * @param    $id (string) id of page to be deleted
     * @param    $access (string) type of access (preview or browsing)
     */
    function delete_from_transform_cache($project_name, $id, $access) {
        global $conf, $project;
        
        $id_project = $project->get_projectId($project_name);
        
        $this->_set_project($project_name);
        
        $result = db_query(
            "DELETE  
            FROM {$this->db_table_transform_cache}
            WHERE id_project='$id_project' AND access='$access' AND (ids_used LIKE '%$id,%' OR id_page='$id')"
        );
    }
    // }}}
    // {{{ clear_transform_cache()
    /**
     * clears transformation cache
     *
     * @public
     *
     * @param    $project_name (string) project name
     * @param    $access (string) type of acccess (preview or browsing)
     */
    function clear_transform_cache($project_name, $access) {
        global $conf, $project;
        
        $id_project = $project->get_projectId($project_name);

        $this->_set_project($project_name);
        
        $result = db_query(
            "DELETE  
            FROM {$this->db_table_transform_cache}
            WHERE id_project='$id_project' AND access='$access'"
        );
    }
    // }}}
    
    // {{{ _set_project()
    /**
     * sets actual project, depending on user
     *
     * @private
     */
    function _set_project($project_name) {
        global $conf, $log;
        global $project;

        $project->_set_project($project_name);
        
        $project_name = str_replace(' ', '_', strtolower($project_name));
        $this->db_table_transform_cache = "{$conf->db_prefix}_{$project_name}_transform_cache";
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
