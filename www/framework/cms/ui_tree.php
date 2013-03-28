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
 */

namespace depage\cms;

use \html;

class ui_tree extends ui_base {
    // {{{ _init
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
    public function __destruct() {
        if (isset($_REQUEST["doc_id"])) {
            $delta_updates = new \depage\websocket\jstree\jstree_delta_updates($this->prefix, $this->pdo, $this->xmldb, $_REQUEST["doc_id"], 0);
            $delta_updates->discardOldChanges();
        }
    }
    // }}}

    // {{{ index
    public function index() {
        return $this->tree($this->docName);
    }
    // }}}
    // {{{ error
    public function error($error, $env) {
        parent::error($error, $env);
        //@todo return error in json format to catch from javascript
    }
    // }}}
    // {{{ tree()
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

    // TODO: set icons?
    // {{{ types_settings
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

    // {{{ recordChange
    protected function recordChange($doc_id, $parent_ids) {
        $delta_updates = new \depage\websocket\jstree\jstree_delta_updates($this->prefix, $this->pdo, $this->xmldb, $doc_id);

        $unique_parent_ids = array_unique($parent_ids);
        foreach ($unique_parent_ids as $parent_id) {
            $delta_updates->recordChange($parent_id);
        }
    }
    // }}}

    // {{{ get_html_nodes
    protected function get_html_nodes($doc_name) {
        $doc = $this->xmldb->getDocXml($doc_name);
        $html = \depage\cms\jstree_xml_to_html::toHTML(array($doc));

        return current($html);
    }
    // }}}
    
    // {{{ get_current_seq_nr
    protected function get_current_seq_nr($doc_id) {
       $delta_updates = new \depage\websocket\jstree\jstree_delta_updates($this->prefix, $this->pdo, $this->xmldb, $doc_id);
       return $delta_updates->currentChangeNumber();
    }
    // }}}
    // {{{ valid_children_or_none
    static private function valid_children_or_none(&$valid_children, $element) {
        if (empty($valid_children[$element])) {
            return "none";
        } else {
            return $valid_children[$element];
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
