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
        } else {
            throw new \Depage\Cms\Exceptions\Project("unknown document");
        }

        if (!$this->project) {
            throw new \Depage\Cms\Exceptions\Project("not allowed");
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
    public function tree($selected = false)
    {
        $treeUrl = "project/{$this->projectName}/tree/{$this->docName}/";
        $wsUrl = new \Depage\Html\Link("jstree", "wss", "");
        $languages = array_keys($this->project->getLanguages());

        return new Html("jstree.tpl", [
            'projectName' => $this->projectName,
            'docName' => $this->docName,
            'rootId' => $this->docInfo->rootid,
            'rootNodeType' => $this->doc->getNodeNameById($this->docInfo->rootid),
            'docId' => $this->docInfo->id,
            'wsUrl' => $wsUrl,
            'treeUrl' => $treeUrl,
            'rootId' => $this->docInfo->rootid,
            'seqNr' => $this->getCurrentSeqNr($this->docInfo->id),
            'settings' => $this->treeSettings(),
            'selected' => $selected,
            'previewLang' => $languages[0],
        ], $this->htmlOptions);
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

        $target_id = filter_input(INPUT_POST, 'target_id', FILTER_SANITIZE_NUMBER_INT);
        $position = filter_input(INPUT_POST, 'position', FILTER_SANITIZE_NUMBER_INT);
        $type = isset($_POST['node']) ? filter_var($_POST['node']['_type'], FILTER_SANITIZE_STRING) : null;
        $extra = isset($_POST['extra']) ? filter_var($_POST['extra'], FILTER_UNSAFE_RAW) : null;

        $id = $this->doc->addNodeByName($type, $target_id, $position);
        $status = $id !== false;
        if ($status) {
            $this->recordChange($this->docId, [$target_id]);
        }

        return new \Depage\Json\Json(["status" => $status, "id" => $id]);
    }
    // }}}
    // {{{ createNodeIn
    /**
     * @param $node child node data
     * @param $position position for new child in parent
     */
    public function createNodeIn()
    {
        $status = false;

        $target_id = filter_input(INPUT_POST, 'target_id', FILTER_SANITIZE_NUMBER_INT);
        $type = isset($_POST['node']) ? filter_var($_POST['node'], FILTER_SANITIZE_STRING) : null;
        $extra = isset($_POST['extra']) ? filter_var($_POST['extra'], FILTER_UNSAFE_RAW) : null;

        $id = $this->doc->addNodeByName($type, $target_id, $position, $this->parseDataNodes($extra));
        $status = $id !== false;
        if ($status) {
            $this->recordChange($this->docId, [$target_id]);
        }

        return new \Depage\Json\Json(["status" => $status, "id" => $id]);
    }
    // }}}
    // {{{ createNodeBefore
    /**
     * @param $node child node data
     * @param $position position for new child in parent
     */
    public function createNodeBefore()
    {
        $status = false;

        $target_id = filter_input(INPUT_POST, 'target_id', FILTER_SANITIZE_NUMBER_INT);
        $type = isset($_POST['node']) ? filter_var($_POST['node'], FILTER_SANITIZE_STRING) : null;
        $extra = isset($_POST['extra']) ? filter_var($_POST['extra'], FILTER_UNSAFE_RAW) : null;

        $target_pos = $this->doc->getPosById($target_id);
        $parent_id = $this->doc->getParentIdById($target_id);

        $id = $this->doc->addNodeByName($type, $parent_id, $target_pos, $this->parseDataNodes($extra));
        $status = $id !== false;
        if ($status) {
            $this->recordChange($this->docId, [$parent_id]);
        }

        return new \Depage\Json\Json(["status" => $status, "id" => $id]);
    }
    // }}}
    // {{{ createNodeAfter
    /**
     * @param $node child node data
     * @param $position position for new child in parent
     */
    public function createNodeAfter()
    {
        $status = false;

        $target_id = filter_input(INPUT_POST, 'target_id', FILTER_SANITIZE_NUMBER_INT);
        $type = isset($_POST['node']) ? filter_var($_POST['node'], FILTER_SANITIZE_STRING) : null;
        $extra = isset($_POST['extra']) ? filter_var($_POST['extra'], FILTER_UNSAFE_RAW) : null;

        $target_pos = $this->doc->getPosById($target_id) + 1;
        $parent_id = $this->doc->getParentIdById($target_id);

        $id = $this->doc->addNodeByName($type, $parent_id, $target_pos, $this->parseDataNodes($extra));
        $status = $id !== false;
        if ($status) {
            $this->recordChange($this->docId, [$parent_id]);
        }

        return new \Depage\Json\Json(["status" => $status, "id" => $id]);
    }
    // }}}
    // {{{ parseDataNodes()
    /**
     * @brief parseDataNodes
     *
     * @param mixed $str
     * @return void
     **/
    protected function parseDataNodes($str)
    {
        $dataNodes = [];
        $str = trim($str, " \r\n\t");

        $doc = new \DOMDocument();
        $parsed = $doc->loadXML("<root xmlns:pg=\"http://cms.depagecms.net/ns/page\" xmlns:sec=\"http://cms.depagecms.net/ns/section\" xmlns:edit=\"http://cms.depagecms.net/ns/edit\">$str</root>");

        if ($parsed) {
            foreach ($doc->documentElement->childNodes as $node) {
                $dataNodes[] = $node;
            }
        }

        return ['dataNodes' => $dataNodes];
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
        $this->recordChange($this->docId, [$parent_id]);
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
        $name = filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW);

        $this->doc->setAttribute($id, "name", $name);
        $parent_id = $this->doc->getParentIdById($id);
        $this->recordChange($this->docId, [$parent_id]);
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
            $this->recordChange($this->docId, [$old_parent_id, $target_id]);
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
            $this->recordChange($this->docId, [$old_parent_id, $target_id]);
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
        $parent_id = $this->doc->getParentIdById($target_id);
        $status = $this->doc->moveNodeBefore($id, $target_id);

        if ($status) {
            $this->recordChange($this->docId, [$old_parent_id, $parent_id]);
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
        $parent_id = $this->doc->getParentIdById($target_id);
        $status = $this->doc->moveNodeAfter($id, $target_id);

        if ($status) {
            $this->recordChange($this->docId, [$old_parent_id, $parent_id]);
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

        $old_parent_id = $this->doc->getParentIdById($id);
        $id = $this->doc->copyNode($id, $target_id, $position);
        $status = $id !== false;

        if ($status) {
            $this->recordChange($this->docId, [$old_parent_id, $target_id]);
        }

        return new \Depage\Json\Json(array("status" => $status, "id" => $id));
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
        $id = $this->doc->copyNodeIn($id, $target_id);
        $status = $id !== false;

        if ($status) {
            $this->recordChange($this->docId, [$old_parent_id, $target_id]);
        }

        return new \Depage\Json\Json(array("status" => $status, "id" => $id));
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
        $parent_id = $this->doc->getParentIdById($target_id);
        $id = $this->doc->copyNodeBefore($id, $target_id);
        $status = $id !== false;

        if ($status) {
            $this->recordChange($this->docId, [$old_parent_id, $parent_id]);
        }

        return new \Depage\Json\Json(array("status" => $status, "id" => $id));
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
        $parent_id = $this->doc->getParentIdById($target_id);
        $id = $this->doc->copyNodeAfter($id, $target_id);
        $status = $id !== false;

        if ($status) {
            $this->recordChange($this->docId, [$old_parent_id, $parent_id]);
        }

        return new \Depage\Json\Json(array("status" => $status, "id" => $id));
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

        // @todo move node to trash for "pages" document?
        $parent_id = $this->doc->getParentIdById($id);
        $ids = $this->doc->deleteNode($id);
        $status = count($ids) > 0;
        if ($status) {
            $this->recordChange($this->docId, [$parent_id]);
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
            $this->recordChange($this->docId, [$id, $parent_id]);
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
    // {{{ releaseDocument
    /**
     * releases Document
     *
     * @return \json
     */
    public function releaseDocument()
    {
        if ($this->authUser->canPublishProject()) {
            $status = $this->project->releaseDocument($this->docName, $this->authUser->id);
        } else {
            $status = $this->project->requestDocumentRelease($this->docName, $this->authUser->id);
        }

        return new \Depage\Json\Json(array("status" => $status));
    }
    // }}}

    // {{{ treeSettings
    /**
     * Type Settings
     *
     * // TODO: set icons?
     *
     * @return \json
     */
    protected function treeSettings()
    {
        $permissions = $this->doc->getPermissions();
        return [
            "maxDepth" => -2,
            "maxChildren" => -2,
            "validParents" => $permissions->validParents,
            "availableNodes" => $permissions->availableNodes,
            "userCanPublish" => $this->authUser->canPublishProject(),
        ];
    }
    // }}}

    // {{{ saveVersion()
    // $.post('http://localhost/depage-cms/project/depage/tree/pages/save-version', {'published' : false}, function(response) { console.log(response); } );
    /**
     * saveVersion
     *
     * Save a version of the given document id
     *
     * @return \json
     */
    public function saveVersion()
    {
        $published = filter_input(INPUT_POST, 'published', FILTER_SANITIZE_STRING);

        $history = $this->doc->getHistory();
        $timestamp = $history->save($this->authUser->id, $published);

        return new \Depage\Json\Json(array("status" => !! $timestamp, "time" => $timestamp));
    }
    // }}}
    // {{{ getVersions()
    // $.get('http://localhost/depage-cms/project/depage/tree/pages/get-versions', function(response) { console.log(response); } );
    /**
     * getVersions
     *
     * Save a version of the given document id
     *
     * @return \json
     */
    public function getVersions()
    {
        $versions = array();

        $history = $this->doc->getHistory();
        $versions = $history->getVersions();

        return new \Depage\Json\Json(array("versions" => $versions));
    }
    // }}}
    // {{{ deleteVersion()
    // $.post('http://localhost/depage-cms/project/depage/tree/pages/delete-version', {'timestamp' : 1174930995}, function(response) { console.log(response); } );
    /**
     * deleteVersion
     *
     * Delete a saved version of the given document by timestamp.
     *
     * @return \json
     */
    public function deleteVersion()
    {
        $status = false;
        $timestamp = filter_input(INPUT_POST, 'timestamp', FILTER_SANITIZE_NUMBER_INT);

        $history = $this->doc->getHistory();
        $status = $history->delete($timestamp);

        return new \Depage\Json\Json(array("status" => $status, "timestamp" => $timestamp));
    }
    // }}}
    // {{{ restoreVersion()
    // $.post('http://localhost/depage-cms/project/depage/tree/pages/restore-version', {'timestamp' : 1364490757}, function(response) { console.log(response); } );
    /**
     * restoreVersion
     *
     * Restore a saved version of the given document by timestamp.
     *
     * @return \json
     */
    public function restoreVersion()
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
        $parent_ids = array_unique($parent_ids);
        foreach ($parent_ids as $parent_id) {
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

    // {{{ getCurrentSeqNr
    /**
     * Get Current Sequence Number
     *
     * @param $doc_id
     * @return int
     */
    protected function getCurrentSeqNr($doc_id)
    {
        return $this->deltaUpdates->currentChangeNumber();
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
