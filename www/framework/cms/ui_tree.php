<?php
/**
 * @file    framework/cms/ui_tree.php
 *
 * depage cms jstree module
 *
 *
 * copyright (c) 2011 Lion Vollnhals [lion.vollnhals@googlemail.com]
 *
 * @author    Lion Vollnhals [lion.vollnhals@googlemail.com]
 * @author    Ben Wallis
 */

namespace depage\cms;

use \html;

class ui_tree extends ui_base {
    // {{{ _init
    /**
     * Init
     *
     * @param array $importVariables
     */
    public function _init(array $importVariables = array()) {
        parent::_init($importVariables);

        if (!empty($this->urlSubArgs[0])) {
            $this->projectName = $this->urlSubArgs[0];
        }
        if (!empty($this->urlSubArgs[1])) {
            $this->docName = $this->urlSubArgs[1];
        }
        $this->prefix = $this->pdo->prefix . "_proj_" . $this->projectName;
        $this->xmldb = new \depage\xmldb\xmldb ($this->prefix, $this->pdo, \depage\cache\cache::factory("xmldb"));
    }
    // }}}
    
    // {{{ destructor
    /**
     * Destructor
     *
     */
    public function __destruct() {
        if (isset($_REQUEST["doc_id"])) {
            $delta_updates = new \depage\websocket\jstree\jstree_delta_updates($this->prefix, $this->pdo, $this->xmldb, $_REQUEST["doc_id"], 0);
            $delta_updates->discardOldChanges();
        }
    }
    // }}}

    // {{{ index
    /**
     * Index
     *
     * @return bool|\html|null
     */
    public function index() {
        return $this->tree($this->docName);
    }
    // }}}

    // {{{ error
    /**
     * Error
     *
     * @param $error
     * @param $env
     * @return null|void
     */
    public function error($error, $env) {
        parent::error($error, $env);
        //@todo return error in json format to catch from javascript
    }
    // }}}

    // {{{ tree()
    /**
     * Tree
     *
     * @param $docName
     * @return bool|\html
     */
    public function tree($docName) {
        $actionUrl = "project/{$this->projectName}/tree/{$docName}/";

        if($doc = $this->xmldb->getDoc($docName)) {

            $doc_info = $doc->getDocInfo();

            $h = new html("jstree.tpl", array(
                'actionUrl' => $actionUrl,
                'doc_id' => $doc_info->id,
                'root_id' => $doc_info->rootid,
                'seq_nr' => $this->get_current_seq_nr($doc_info->id),
                'nodes' => $this->get_html_nodes($docName),
            ), $this->html_options);

            return $h;
        }

        return false;

    }
    // }}}

    // {{{ create_node
    /**
     * @param $doc_id document id
     * @param $node child node data
     * @param $position position for new child in parent
     */
    public function create_node() {
        $status = false;
        $this->log->log($_REQUEST);

        $doc_id = filter_input(INPUT_POST, 'doc_id', FILTER_SANITIZE_NUMBER_INT);
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $target_id = filter_input(INPUT_POST, 'target_id', FILTER_SANITIZE_NUMBER_INT);
        $position = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $type = isset($_POST['node']) ? filter_var($_POST['node']['_type'], FILTER_SANITIZE_STRING) : null;

        if ($doc = $this->xmldb->getDoc($doc_id)) {
            $id = $doc->addNodeByName($type, $target_id, $position);
            $status = $id !== false;
            if ($status) {
                $this->recordChange($doc_id, array($target_id));
            }
        }
        return new \json(array("status" => $status, "id" => $id));
    }
    // }}}

    // {{{ rename_node
    /**
     * Rename Node
     *
     * @return \json
     */
    public function rename_node() {
        $status = false;
        $doc_id = filter_input(INPUT_POST, 'doc_id', FILTER_SANITIZE_NUMBER_INT);
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);

        if ($doc = $this->xmldb->getDoc($doc_id)) {
            $doc->setAttribute($id, "name", $name);
            $parent_id = $doc->getParentIdById($id);
            $this->recordChange($doc_id, array($parent_id));
            $status = true;
        }

        return new \json(array("status" => $status));
    }
    // }}}

    // {{{ move_node
    /**
     * Move Node
     *
     * @return \json
     */
    public function move_node() {
        $status = false;
        $doc_id = filter_input(INPUT_POST, 'doc_id', FILTER_SANITIZE_NUMBER_INT);
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $target_id = filter_input(INPUT_POST, 'target_id', FILTER_SANITIZE_NUMBER_INT);
        $position = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

        if ($doc = $this->xmldb->getDoc($doc_id)) {
            $old_parent_id = $doc->getParentIdById($doc_id, $id);
            $status = $this->xmldb->moveNode($id, $target_id, $position);
            if ($status) {
                $this->recordChange($doc_id, array($old_parent_id, $target_id));
            }
        }
        return new \json(array("status" => $status));
    }
    // }}}

    // {{{ copy_node
    /**
     * Copy Node
     *
     * @return \json
     */
    public function copy_node() {
        $status = false;
        $doc_id = filter_input(INPUT_POST, 'doc_id', FILTER_SANITIZE_NUMBER_INT);
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $target_id = filter_input(INPUT_POST, 'target_id', FILTER_SANITIZE_NUMBER_INT);
        $position = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

        if ($doc = $this->xmldb->getDoc($doc_id)) {
            $status = !! $doc->copyNode($id, $target_id, $position);

            if ($status) {
                $this->recordChange($doc_id, array($target_id, $status));
            }
        }
        return new \json(array("status" => $status, "id" => $status));
    }
    // }}}

    // {{{ remove_node
    /**
     * Remove Node
     *
     * @return \json
     */
    public function remove_node() {
        $status = false;
        $doc_id = filter_input(INPUT_POST, 'doc_id', FILTER_SANITIZE_NUMBER_INT);
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

        if ($doc = $this->xmldb->getDoc($doc_id)) {
            $parent_id = $doc->getParentIdById($id);
            $ids = $doc->unlinkNode($id);
            $status = $ids !== false;
            if ($status) {
                $this->recordChange($doc_id, array($parent_id));
            }
        }
        return new \json(array("status" => $status));
    }
    // }}}

    // {{{ duplicate_node
    /**
     * Duplicate Node
     *
     * @return \json
     */
    public function duplicate_node() {
        $status = false;
        $doc_id = filter_input(INPUT_GET, 'doc_id', FILTER_SANITIZE_NUMBER_INT);
        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

        if ($doc = $this->xmldb->getDoc($doc_id)) {
            $id = $doc->duplicateNode($id);

            if ($status) {
                $parent_id = $doc->getParentIdById($id);
                $this->recordChange($doc_id, array($id, $parent_id));
            }
        }
        return new \json(array("status" => $status, "id" => $id));
    }
    // }}}

    // {{{ types_settings
    /**
     * Type Settings
     *
     * // TODO: set icons?
     *
     * @return \json
     */
    public function types_settings() {
        $settings = array();
        $doc_id = filter_input(INPUT_GET, 'doc_id', FILTER_SANITIZE_NUMBER_INT);
        if ($doc = $this->xmldb->getDoc($doc_id)) {
            $permissions = $doc->getPermissions();
            $this->log->log($permissions);
            $settings = array(
                "typesfromurl" => array(
                    "max_depth" => -2,
                    "max_children" => -2,
                    "valid_parents" => $permissions->validParents,
                    "available_nodes" => $permissions->availableNodes
                ),
            );
        }

        return new \json($settings);
    }
    // }}}

    // TODO: disable
    // {{{ add_permissions
    /**
     * Add Permissions
     *
     * @param $doc_id
     * @param $element
     * @param $parent
     */
    public function add_permissions($doc_id, $element, $parent) {
        $doc_id = filter_input(INPUT_GET, 'doc_id', FILTER_SANITIZE_NUMBER_INT);
        if ($doc = $this->xmldb->getDoc($doc_id)) {
            $permissions = $doc->getPermissions();
            $permissions->allow_element_in($element, $parent);

            $doc->set_permissions($permissions);
        }
        echo $permissions;
    }
    // }}}

    // {{{ save_version()
    // $.post('http://localhost/depage-cms/project/depage/tree/pages/save-version', {'doc_id' : 1, 'published' : false}, function(response) { console.log(response); } );
    /**
     * save_version
     *
     * Save a version of the given document id
     *
     * @return \json
     */
    public function save_version() {
        $doc_id = filter_input(INPUT_POST, 'doc_id', FILTER_SANITIZE_NUMBER_INT);
        $published = filter_input(INPUT_POST, 'published', FILTER_SANITIZE_STRING);

        if ($doc = $this->xmldb->getDoc($doc_id)) {
            $history = $doc->getHistory();
            $timestamp = $history->save($this->auth_user->id, $published);
        }
        return new \json(array("status" => !! $timestamp, "time" => $timestamp));
    }
    // }}}

    // {{{ get_versions()
    // $.get('http://localhost/depage-cms/project/depage/tree/pages/get-versions', {'doc_id' : 1}, function(response) { console.log(response); } );
    /**
     * save_version
     *
     * Save a version of the given document id
     *
     * @return \json
     */
    public function get_versions() {
        $doc_id = filter_input(INPUT_GET, 'doc_id', FILTER_SANITIZE_NUMBER_INT);

        $versions = array();

        if ($doc = $this->xmldb->getDoc($doc_id)) {
            $history = $doc->getHistory();
            $versions = $history->getVersions();
        }

        return new \json(array("versions" => $versions));
    }
    // }}}

    // {{{ delete_version()
    // $.post('http://localhost/depage-cms/project/depage/tree/pages/delete-version', {'doc_id' : 1, 'timestamp' : 1174930995}, function(response) { console.log(response); } );
    /**
     * delete_version
     *
     * Delete a saved version of the given document by timestamp.
     *
     * @return \json
     */
    public function delete_version() {
        $status = false;
        $doc_id = filter_input(INPUT_POST, 'doc_id', FILTER_SANITIZE_NUMBER_INT);
        $timestamp = filter_input(INPUT_POST, 'timestamp', FILTER_SANITIZE_NUMBER_INT);

        if ($doc = $this->xmldb->getDoc($doc_id)) {
            $history = $doc->getHistory();
            $status = $history->delete($timestamp);
        }

        return new \json(array("status" => $status, "timestamp" => $timestamp));
    }
    // }}}

    // {{{ restore_version()
    // $.post('http://localhost/depage-cms/project/depage/tree/pages/restore-version', {'doc_id' : 1, 'timestamp' : 1364490757}, function(response) { console.log(response); } );
    /**
     * restore_version
     *
     * Restore a saved version of the given document by timestamp.
     *
     * @return \json
     */
    public function restore_version() {
        $xml = false;
        $doc_id = filter_input(INPUT_POST, 'doc_id', FILTER_SANITIZE_NUMBER_INT);
        $timestamp = filter_input(INPUT_POST, 'timestamp', FILTER_SANITIZE_NUMBER_INT);

        if ($doc = $this->xmldb->getDoc($doc_id)) {
            $history = $doc->getHistory();
            $xml_doc = $history->restore($timestamp);
            $xml = $xml_doc->saveXml();
        }

        return new \json(array("status" => !! $xml, "timestamp" => $timestamp, "xml" => $xml));
    }
    // }}}

    // {{{ recordChange
    /**
     * Record Change
     *
     * @param $doc_id
     * @param $parent_ids
     */
    protected function recordChange($doc_id, $parent_ids) {
        $delta_updates = new \depage\websocket\jstree\jstree_delta_updates($this->prefix, $this->pdo, $this->xmldb, $doc_id);

        $unique_parent_ids = array_unique($parent_ids);
        foreach ($unique_parent_ids as $parent_id) {
            $delta_updates->recordChange($parent_id);
        }
    }
    // }}}

    // {{{ get_html_nodes
    /**
     * Get HTML Nodes
     *
     * @param $doc_name
     * @return mixed
     */
    protected function get_html_nodes($doc_name) {
        $doc = $this->xmldb->getDocXml($doc_name);
        $html = \depage\cms\jstree_xml_to_html::toHTML(array($doc));

        return current($html);
    }
    // }}}
    
    // {{{ get_current_seq_nr
    /**
     * Get Current Sequence Number
     *
     * @param $doc_id
     * @return int
     */
    protected function get_current_seq_nr($doc_id) {
       $delta_updates = new \depage\websocket\jstree\jstree_delta_updates($this->prefix, $this->pdo, $this->xmldb, $doc_id);
       return $delta_updates->currentChangeNumber();
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
