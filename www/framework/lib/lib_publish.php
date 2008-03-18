<?php
/**
 * @file    lib_publish.php
 *
 * Publish Library
 *
 * Intelligent publishing Library to publish only changed and new files
 *
 *
 * copyright (c) 2007-2008 Frank Hellenkamp [jonas@depagecms.net]
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

        $this->fs_local = fs::factory('local');
    }
    /* }}} */
    /* {{{ get_changed_lib_files */
    function get_changed_lib_files() {
        global $conf, $project;

        $ppath = $project->get_project_path($this->project_name);
        chdir($ppath . '/lib/');
        $this->_get_changed_lib_files_dir();
        chdir($conf->path_server_root . $conf->path_base . '/framework');
        
        foreach ($this->files as $file) {
            $file->sha1 = sha1_file($ppath . $file->get_fullname());
            echo($file->sha1 . " " . $file->get_fullname() . "\n");
            $this->add_file_to_db($file->path, $file->filename, $file->sha1);
        }
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
    function add_file_to_db($path, $filename, $sha1) {
        global $conf;

        $result = db_query(
            "INSERT INTO $conf->db_table_publish_files 
            SET 
                pid={$this->publish_id}, 
                path='" . mysql_escape_string($path) . "',
                filename='" . mysql_escape_string($filename) . "',
                sha1='$sha1'
            "
        );
    }
    /* }}} */
}

class publish_file {
    var $path = "";
    var $filename = "";
    var $page_id = null;
    var $sha1 = "";

    /* {{{ constructor */
    function publish_file($path, $filename, $page_id = null) {
        $this->path = $path;
        $this->filename = $filename;
        $this->page_id = $page_id;
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
