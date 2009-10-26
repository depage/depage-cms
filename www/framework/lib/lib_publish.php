<?php
/**
 * @file    lib_publish.php
 *
 * Publish Library
 *
 * Intelligent publishing Library to publish only changed and new files
 *
 *
 * copyright (c) 2007-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

require_once('lib_project.php');

class publish {
    var $fs_local;
    var $project_name = "";
    var $publish_id;
    var $files = array();

    /* {{{ constructor */
    function publish($project_name, $publish_id) {
        $this->project_name = $project_name;
        $this->publish_id = $publish_id;

        $this->_set_project($project_name);

        $this->fs_local = fs::factory('local');
    }
    /* }}} */
    /* {{{ get_changed_lib_files */
    function get_changed_lib_files() {
        global $conf, $project;

        $changed_files = array();

        $ppath = $project->get_project_path($this->project_name);

        chdir($ppath . '/lib/');
        $this->_get_changed_lib_files_dir();
        chdir($conf->path_server_root . $conf->path_base . 'framework');
        
        foreach ($this->files as $file) {
            $file->sha1 = sha1_file($ppath . $file->get_fullname());
            if ($this->file_changed($file)) {
                $changed_files[] = $file;
            } else {
                $this->set_file_exists($file);
            }
        }

        return $changed_files;
    }
    /* }}} */
    /* {{{ _get_changed_lib_files_dir */
    function _get_changed_lib_files_dir($path = "") {
        $dirarray = $this->fs_local->list_dir($path != '' ? "./$path" : '.');
        
        foreach ($dirarray['files'] as $file) {
            $this->files[] = new publish_file("/lib/" . $path, $file);
        }
        foreach ($dirarray['dirs'] as $dir) {
            $this->_get_changed_lib_files_dir($path . $dir .  '/');
        }
    }
    /* }}} */
    /* {{{ add_file_to_db */
    function add_file_to_db($file) {
        global $conf;

        // remove old entry
        $result = db_query(
            "DELETE FROM $this->db_table_publish_files 
            WHERE 
                pid={$this->publish_id} AND
                path='" . mysql_escape_string($file->path) . "' AND
                filename='" . mysql_escape_string($file->filename) . "'
            "
        );

        // add new entry
        $result = db_query(
            "INSERT INTO $this->db_table_publish_files 
            SET 
                pid={$this->publish_id}, 
                path='" . mysql_escape_string($file->path) . "',
                filename='" . mysql_escape_string($file->filename) . "',
                sha1='{$file->sha1}',
                lastmod=NOW(),
                exist='1'
            "
        );
    }
    /* }}} */
    /* {{{ set_file_exists */
    function set_file_exists($file) {
        global $conf;

        // update entry
        $result = db_query(
            "UPDATE $this->db_table_publish_files 
            SET 
                exist='1'
            WHERE
                pid={$this->publish_id} AND
                path='" . mysql_escape_string($file->path) . "' AND
                filename='" . mysql_escape_string($file->filename) . "'
            "
        );
    }
    /* }}} */
    /* {{{ reset_all_file_exists */
    function reset_all_file_exists() {
        global $conf;

        // update entry
        $result = db_query(
            "UPDATE $this->db_table_publish_files 
            SET 
                exist='0'
            WHERE
                pid={$this->publish_id}
            "
        );
    }
    /* }}} */
    /* {{{ get_deleted_files */
    function get_deleted_files() {
        global $conf;

        $files = array();

        // update entry
        $result = db_query(
            "SELECT
                path,
                filename
            FROM $this->db_table_publish_files 
            WHERE
                pid={$this->publish_id} AND
                exist='0'
            "
        );

        if ($result) {
            $num = mysql_num_rows($result);
            for ($i = 0; $i < $num; $i++) {
                $row = mysql_fetch_assoc($result);
                $files[] = new publish_file($row['path'], $row['filename']);
            }
        }

        return $files;
    }
    /* }}} */
    /* {{{ clear_deleted_files */
    function clear_deleted_files() {
        global $conf;

        $files = array();

        // update entry
        $result = db_query(
            "DELETE
            FROM $this->db_table_publish_files 
            WHERE
                pid={$this->publish_id} AND
                exist='0'
            "
        );

        return $files;
    }
    /* }}} */
    /* {{{ file_changed */
    function file_changed($file) {
        global $conf;

        $result = db_query(
            "SELECT sha1 FROM $this->db_table_publish_files 
            WHERE 
                pid={$this->publish_id} AND
                path='" . mysql_escape_string($file->path) . "' AND
                filename='" . mysql_escape_string($file->filename) . "'
            "
        );
    
        if ($result && mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            if ($row['sha1'] == $file->sha1) {
                return false;
            } 
        } 
        return true;
    }
    /* }}} */
    /* {{{ get_lastmod */
    function get_lastmod($file) {
        global $conf;

        // get lastmod entry
        $result = db_query(
            "SELECT
                lastmod
            FROM $this->db_table_publish_files 
            WHERE
                pid={$this->publish_id} AND
                path='" . mysql_escape_string($file->path) . "' AND
                filename='" . mysql_escape_string($file->filename) . "'
            "
        );

        if ($result && mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            return strtotime($row['lastmod']);
        }

        return false;
    }
    /* }}} */
    
    // {{{ _set_project()
    /**
     * sets actual project, depending on user
     *
     * @private
     */
    function _set_project($project_name) {
        global $conf, $log;
        
        $project = str_replace(' ', '_', strtolower($project_name));
        $this->db_table_publish_files = "{$conf->db_prefix}_{$project}_publish_files";
    }
    // }}}
}

class publish_file {
    var $path = "";
    var $filename = "";
    var $sha1 = "";

    /* {{{ constructor */
    function publish_file($path, $filename) {
        $this->path = $path;
        if (substr($this->path, -1, 1) != "/") {
            $this->path .= "/";
        }
        $this->filename = $filename;
    }
    /* }}} */
    /* {{{ get_fullname */
    function get_fullname() {
        return $this->path . $this->filename;
    }
    /* }}} */
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
