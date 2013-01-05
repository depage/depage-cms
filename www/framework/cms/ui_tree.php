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

        $doc_info = $this->xmldb->getDocInfo($docName);

        $h = new html("jstree.tpl", array(
            'actionUrl' => $actionUrl,
            'doc_id' => $doc_info->id,
            'root_id' => $doc_info->rootid, 
            'seq_nr' => $this->get_current_seq_nr($doc_info->id),
            'nodes' => $this->get_html_nodes($docName),
        ), $this->html_options); 

        return $h;
    }
    // }}}

    // {{{ create_node
    /**
     * @param $doc_id document id
     * @param $node child node data
     * @param $position position for new child in parent
     */
    public function create_node() {
        $this->log->log($_REQUEST);
        $id = $this->xmldb->addNodeByName($_REQUEST["doc_id"], $_REQUEST["node"]["_type"], $_REQUEST["target_id"], $_REQUEST["position"]);   
        $status = $id !== false;
        if ($status) {
            $this->recordChange($_REQUEST["doc_id"], array($_REQUEST["target_id"]));
        }

        return new \json(array("status" => $status, "id" => $id));
    }
    // }}}
    // {{{ rename_node
    public function rename_node() {
        $this->xmldb->setAttribute($_REQUEST["doc_id"], $_REQUEST["id"], "name", $_REQUEST["name"]);
        $parent_id = $this->xmldb->getParentIdById($_REQUEST["doc_id"], $_REQUEST["id"]);
        $this->recordChange($_REQUEST["doc_id"], array($parent_id));

        return new \json(array("status" => 1));
    }
    // }}}
    // {{{ move_node
    public function move_node() {
        $old_parent_id = $this->xmldb->getParentIdById($_REQUEST["doc_id"], $_REQUEST["id"]);
        $status = $this->xmldb->moveNode($_REQUEST["doc_id"], $_REQUEST["id"], $_REQUEST["target_id"], $_REQUEST["position"]);
        if ($status) {
            $this->recordChange($_REQUEST["doc_id"], array($old_parent_id, $_REQUEST["target_id"]));
        }

        return new \json(array("status" => $status));
    }
    // }}}
    // {{{ copy_node
    public function copy_node() {
        $status = $this->xmldb->copyNode($_REQUEST["doc_id"], $_REQUEST["id"], $_REQUEST["target_id"], $_REQUEST["position"]);
        if ($status) {
            $this->recordChange($_REQUEST["doc_id"], array($_REQUEST["target_id"], $status));
        }

        return new \json(array("status" => $status));
    }
    // }}}
    // {{{ remove_node
    public function remove_node() {
        $parent_id = $this->xmldb->getParentIdById($_REQUEST["doc_id"], $_REQUEST["id"]);
        $ids = $this->xmldb->unlinkNode($_REQUEST["doc_id"], $_REQUEST["id"]);
        $status = $ids !== false;
        if ($status) {
            $this->recordChange($_REQUEST["doc_id"], array($parent_id));
        }

        return new \json(array("status" => $status));
    }
    // }}}

    // TODO: set icons?
    // {{{ types_settings
    public function types_settings() {
        $doc_info = $this->xmldb->getDocInfo($this->docName);
        $doc_id = $doc_info->id;
        $root_element_name = $this->xmldb->getNodeNameById($doc_id, $doc_info->rootid);

        $permissions = $this->xmldb->getPermissions($doc_id);
        $this->log->log($permissions);
        $settings = array(
            "typesfromurl" => array(
                "max_depth" => -2,
                "max_children" => -2,
                "valid_parents" => $permissions->validParents,
                "available_nodes" => $permissions->availableNodes
            ),
        );

        return new \json($settings);
    }
    // }}}

    // TODO: disable
    // {{{ add_permissions
    public function add_permissions($doc_id, $element, $parent) {
        $permissions = $this->xmldb->getPermissions($doc_id);
        $permissions->allow_element_in($element, $parent);

        $this->xmldb->set_permissions($doc_id, $permissions);
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
        $doc = $this->xmldb->getDoc($doc_name);
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
