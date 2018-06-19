<?php
/**
 * @file    framework/Cms/Ui/Tree.php
 *
 * depage cms jstree module
 *
 *
 * copyright (c) 2011 Lion Vollnhals [lion.vollnhals@googlemail.com]
 *
 * @author    Lion Vollnhals [lion.vollnhals@googlemail.com]
 * @author    Ben Wallis
 *
 * @todo remove doc_id references -> implicit in url (docName)
 */

namespace Depage\Cms\Ui;

use \Depage\Html\Html;

class Tree extends Base {
    // {{{ _init
    /**
     * Init
     *
     * @param array $importVariables
     */
    public function _init(array $importVariables = [])
    {
        parent::_init($importVariables);

        // @todo add auth!!!

        if (!empty($this->urlSubArgs[0])) {
            $this->projectName = $this->urlSubArgs[0];

            $this->project = $this->getProject($this->projectName);
        }
        if (!empty($this->urlSubArgs[1])) {
            //@todo throw error if urlSubArgs is not set or document does not exist
            $this->prefix = $this->pdo->prefix . "_proj_" . $this->projectName;
            $this->xmldb = $this->project->getXmlDb();

            $this->docName = $this->urlSubArgs[1];
            $this->doc = $this->xmldb->getDoc($this->docName);
            $this->docInfo = $this->doc->getDocInfo();
            $this->docId = $this->docInfo->id;
        }
        $this->deltaUpdates = new \Depage\WebSocket\JsTree\DeltaUpdates($this->prefix, $this->pdo, $this->xmldb, $this->docId, $this->projectName, 0);
    }
    // }}}

    // {{{ destructor
    /**
     * Destructor
     *
     */
    public function __destruct()
    {
        $this->deltaUpdates->discardOldChanges();
    }
    // }}}

    // {{{ index
    /**
     * Index
     *
     * @return bool|\html|null
     */
    public function index()
    {
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
    public function error($error, $env)
    {
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
    public function tree()
    {
        $treeUrl = "project/{$this->projectName}/tree/{$this->docName}/";
        $wsUrl = "ws://127.0.0.1:8000/jstree?docId={$this->docInfo->id}&projectName={$this->projectName}";
        //$wsUrl = "ws://127.0.0.1:8000/jstree";

        $h = new Html("jstree.tpl", [
            'projectName' => $this->projectName,
            'docName' => $this->docName,
            'docId' => $this->docInfo->id,
            'wsUrl' => $wsUrl,
            'treeUrl' => $treeUrl,
            'rootId' => $this->docInfo->rootid,
            'seqNr' => $this->get_current_seq_nr($this->docInfo->id),
            //'nodes' => $this->nodes($docName),
        ], $this->htmlOptions);

        return $h;
    }
    // }}}

    // {{{ createNode
    /**
     * @param $node child node data
     * @param $position position for new child in parent
     */
    public function createNode()
    {
        $status = false;

        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $target_id = filter_input(INPUT_POST, 'target_id', FILTER_SANITIZE_NUMBER_INT);
        $position = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $type = isset($_POST['node']) ? filter_var($_POST['node']['_type'], FILTER_SANITIZE_STRING) : null;

        $id = $this->doc->addNodeByName($type, $target_id, $position);
        $status = $id !== false;
        if ($status) {
            $this->recordChange($this->docId, [$target_id]);
        }

        return new \Depage\Json\Json(["status" => $status, "id" => $id]);
    }
    // }}}

    // {{{ setAttribute
    /**
     * Rename Node
     *
     * @return \json
     */
    public function setAttribute()
    {
        $status = false;
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $value = filter_input(INPUT_POST, 'value', FILTER_SANITIZE_STRING);

        $this->doc->setAttribute($id, $name, $value);
        $parent_id = $this->doc->getParentIdById($id);
        $this->recordChange($this->docId, array($parent_id));
        $status = true;

        return new \Depage\Json\Json([
            "status" => $status,
            "docId" => $this->doc->getDocId(),
            "id" => $id,
            "name" => $name,
            "value" => $value,
        ]);
        //return new \Depage\Json\Json(array("status" => $status));
    }
    // }}}

    // {{{ renameNode
    /**
     * Rename Node
     *
     * @return \json
     */
    public function renameNode()
    {
        $status = false;
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);

        $this->doc->setAttribute($id, "name", $name);
        $parent_id = $this->doc->getParentIdById($id);
        $this->recordChange($this->docId, array($parent_id));
        $status = true;

        return new \Depage\Json\Json(array("status" => $status));
    }
    // }}}

    // {{{ moveNode
    /**
     * Move Node
     *
     * @return \json
     */
    public function moveNode()
    {
        $status = false;
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $target_id = filter_input(INPUT_POST, 'target_id', FILTER_SANITIZE_NUMBER_INT);
        $position = filter_input(INPUT_POST, 'position', FILTER_SANITIZE_NUMBER_INT);

        $old_parent_id = $this->doc->getParentIdById($id);
        $status = $this->doc->moveNode($id, $target_id, $position);

        if ($status) {
            $this->recordChange($this->docId, array($old_parent_id, $target_id));
        }

        return new \Depage\Json\Json(array("status" => $status));
    }
    // }}}
    // {{{ moveNodeIn
    /**
     * Move Node
     *
     * @return \json
     */
    public function moveNodeIn()
    {
        $status = false;
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $target_id = filter_input(INPUT_POST, 'target_id', FILTER_SANITIZE_NUMBER_INT);

        $old_parent_id = $this->doc->getParentIdById($id);
        $status = $this->doc->moveNodeIn($id, $target_id);

        if ($status) {
            $this->recordChange($this->docId, array($old_parent_id, $target_id));
        }

        return new \Depage\Json\Json(array("status" => $status));
    }
    // }}}
    // {{{ moveNodeBefore
    /**
     * Move Node
     *
     * @return \json
     */
    public function moveNodeBefore()
    {
        $status = false;
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $target_id = filter_input(INPUT_POST, 'target_id', FILTER_SANITIZE_NUMBER_INT);

        $old_parent_id = $this->doc->getParentIdById($id);
        $parent_id = $this->doc->moveNodeBefore($id, $target_id);

        if ($parent_id) {
            $this->recordChange($this->docId, array($old_parent_id, $parent_id));
        }

        return new \Depage\Json\Json(array("status" => $status));
    }
    // }}}
    // {{{ moveNodeAfter
    /**
     * Move Node
     *
     * @return \json
     */
    public function moveNodeAfter()
    {
        $status = false;
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $target_id = filter_input(INPUT_POST, 'target_id', FILTER_SANITIZE_NUMBER_INT);

        $old_parent_id = $this->doc->getParentIdById($id);
        $parent_id = $this->doc->moveNodeAfter($id, $target_id);

        if ($parent_id) {
            $this->recordChange($this->docId, array($old_parent_id, $parent_id));
        }

        return new \Depage\Json\Json(array("status" => $status));
    }
    // }}}

    // {{{ copyNode
    /**
     * Copy Node
     *
     * @return \json
     */
    public function copyNode()
    {
        $status = false;
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $target_id = filter_input(INPUT_POST, 'target_id', FILTER_SANITIZE_NUMBER_INT);
        $position = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

        $status = !! $this->doc->copyNode($id, $target_id, $position);

        if ($status) {
            $this->recordChange($this->docId, array($target_id, $status));
        }

        return new \Depage\Json\Json(array("status" => $status, "id" => $status));
    }
    // }}}
    // {{{ copyNodeIn
    /**
     * copy Node
     *
     * @return \json
     */
    public function copyNodeIn()
    {
        $status = false;
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $target_id = filter_input(INPUT_POST, 'target_id', FILTER_SANITIZE_NUMBER_INT);

        $old_parent_id = $this->doc->getParentIdById($id);
        $status = $this->doc->copyNodeIn($id, $target_id);

        if ($status) {
            $this->recordChange($this->docId, array($old_parent_id, $target_id));
        }

        return new \Depage\Json\Json(array("status" => $status));
    }
    // }}}
    // {{{ copyNodeBefore
    /**
     * copy Node
     *
     * @return \json
     */
    public function copyNodeBefore()
    {
        $status = false;
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $target_id = filter_input(INPUT_POST, 'target_id', FILTER_SANITIZE_NUMBER_INT);

        $old_parent_id = $this->doc->getParentIdById($id);
        $parent_id = $this->doc->copyNodeBefore($id, $target_id);

        if ($status) {
            $this->recordChange($this->docId, array($old_parent_id, $parent_id));
        }

        return new \Depage\Json\Json(array("status" => $status));
    }
    // }}}
    // {{{ copyNodeAfter
    /**
     * copy Node
     *
     * @return \json
     */
    public function copyNodeAfter()
    {
        $status = false;
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $target_id = filter_input(INPUT_POST, 'target_id', FILTER_SANITIZE_NUMBER_INT);

        $old_parent_id = $this->doc->getParentIdById($id);
        $parent_id = $this->doc->copyNodeAfter($id, $target_id);

        if ($status) {
            $this->recordChange($this->docId, array($old_parent_id, $parent_id));
        }

        return new \Depage\Json\Json(array("status" => $status));
    }
    // }}}

    // {{{ deleteNode
    /**
     * Remove Node
     *
     * @return \json
     */
    public function deleteNode()
    {
        $status = false;
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

        $parent_id = $this->doc->getParentIdById($id);
        $ids = $this->doc->deleteNode($id);
        $status = count($ids) > 0;
        if ($status) {
            $this->recordChange($this->docId, array($parent_id));
        }

        return new \Depage\Json\Json(array("status" => $status));
    }
    // }}}

    // {{{ duplicateNode
    /**
     * Duplicate Node
     *
     * @return \json
     */
    public function duplicateNode()
    {
        $status = false;
        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

        $id = $this->doc->duplicateNode($id);

        if ($status) {
            $parent_id = $this->doc->getParentIdById($id);
            $this->recordChange($this->docId, array($id, $parent_id));
        }

        return new \Depage\Json\Json(array("status" => $status, "id" => $id));
    }
    // }}}

    // {{{ deleteDocument
    /**
     * Remove Node
     *
     * @return \json
     */
    public function deleteDocument()
    {
        $status = false;

        $name = isset($_POST['docName']) ? filter_var($_POST['docName'], FILTER_SANITIZE_STRING) : null;
        if ($this->docName == $name) {
            $status = $this->xmldb->removeDoc($this->docName);
        }

        return new \Depage\Json\Json(array("status" => $status));
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
    public function types_settings()
    {
        $settings = array();

        $permissions = $this->doc->getPermissions();
        $this->log->log($permissions);
        $settings = array(
            "typesfromurl" => array(
                "max_depth" => -2,
                "max_children" => -2,
                "valid_parents" => $permissions->validParents,
                "available_nodes" => $permissions->availableNodes
            ),
        );

        return new \Depage\Json\Json($settings);
    }
    // }}}

    // {{{ save_version()
    // $.post('http://localhost/depage-cms/project/depage/tree/pages/save-version', {'published' : false}, function(response) { console.log(response); } );
    /**
     * save_version
     *
     * Save a version of the given document id
     *
     * @return \json
     */
    public function save_version()
    {
        $published = filter_input(INPUT_POST, 'published', FILTER_SANITIZE_STRING);

        $history = $this->doc->getHistory();
        $timestamp = $history->save($this->auth_user->id, $published);

        return new \Depage\Json\Json(array("status" => !! $timestamp, "time" => $timestamp));
    }
    // }}}

    // {{{ get_versions()
    // $.get('http://localhost/depage-cms/project/depage/tree/pages/get-versions', function(response) { console.log(response); } );
    /**
     * save_version
     *
     * Save a version of the given document id
     *
     * @return \json
     */
    public function get_versions()
    {
        $versions = array();

        $history = $this->doc->getHistory();
        $versions = $history->getVersions();

        return new \Depage\Json\Json(array("versions" => $versions));
    }
    // }}}

    // {{{ delete_version()
    // $.post('http://localhost/depage-cms/project/depage/tree/pages/delete-version', {'timestamp' : 1174930995}, function(response) { console.log(response); } );
    /**
     * delete_version
     *
     * Delete a saved version of the given document by timestamp.
     *
     * @return \json
     */
    public function delete_version()
    {
        $status = false;
        $timestamp = filter_input(INPUT_POST, 'timestamp', FILTER_SANITIZE_NUMBER_INT);

        $history = $this->doc->getHistory();
        $status = $history->delete($timestamp);

        return new \Depage\Json\Json(array("status" => $status, "timestamp" => $timestamp));
    }
    // }}}

    // {{{ restore_version()
    // $.post('http://localhost/depage-cms/project/depage/tree/pages/restore-version', {'timestamp' : 1364490757}, function(response) { console.log(response); } );
    /**
     * restore_version
     *
     * Restore a saved version of the given document by timestamp.
     *
     * @return \json
     */
    public function restore_version()
    {
        $xml = false;
        $timestamp = filter_input(INPUT_POST, 'timestamp', FILTER_SANITIZE_NUMBER_INT);

        $history = $this->doc->getHistory();
        $xml_doc = $history->restore($timestamp);
        $xml = $xml_doc->saveXml();

        return new \Depage\Json\Json(array("status" => !! $xml, "timestamp" => $timestamp, "xml" => $xml));
    }
    // }}}

    // {{{ recordChange
    /**
     * Record Change
     *
     * @param $doc_id
     * @param $parent_ids
     */
    protected function recordChange($doc_id, $parent_ids)
    {
        $unique_parent_ids = array_unique($parent_ids);
        foreach ($unique_parent_ids as $parent_id) {
            $this->deltaUpdates->recordChange($parent_id);
        }
    }
    // }}}

    // {{{ deltaUpdates()
    /**
     * @brief deltaUpdates
     *
     * @param mixed
     * @return void
     **/
    public function deltaUpdates()
    {
        $deltaUpdates = new \Depage\WebSocket\JsTree\DeltaUpdates($this->prefix, $this->pdo, $this->xmldb, $this->docId, $this->projectName, $_REQUEST["seq_nr"]);

        return $deltaUpdates->encodedDeltaUpdate();
    }
    // }}}

    // {{{ getHtmlNodes
    /**
     * Get HTML Nodes
     *
     * @param $doc_name
     * @return mixed
     */
    public function nodes($nodeId = "")
    {
        if (empty($nodeId)) {
            $doc = $this->doc->getXml($this->docName);
        } else {
            $doc = $this->doc->getSubdocByNodeId($nodeId);
        }
        $html = \Depage\Cms\JsTreeXmlToHtml::toHTML(array($doc), $this->projectName);

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
    protected function get_current_seq_nr($doc_id)
    {
        return $this->deltaUpdates->currentChangeNumber();
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
