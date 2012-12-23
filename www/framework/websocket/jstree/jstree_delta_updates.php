<?php

namespace depage\websocket\jstree;

class jstree_delta_updates {
    // clients will update themselves about every 3 seconds maximum. retain enough updates to update partially.
    // if we estimate 10 updates per second, then retain at least 30 updates. some buffer on top and we should be good.
    const MAX_UPDATES_BEFORE_RELOAD = 50;

    function __construct($table_prefix, $pdo, $xmldb, $doc_id, $seq_nr = -1) {
        $this->table_name = $table_prefix . "_xmldeltaupdates";
        $this->pdo = $pdo;
        $this->xmldb = $xmldb;
        $this->doc_id = (int)$doc_id;

        $this->seq_nr = (int)$seq_nr;
        if ($this->seq_nr == -1)
            $this->seq_nr = $this->currentChangeNumber();
    }

    public function currentChangeNumber() {
        $query = $this->pdo->prepare("SELECT MAX(id) AS id FROM " . $this->table_name . " WHERE doc_id = ?");
        if ($query->execute(array($this->doc_id)))
            if ($row = $query->fetch())
                return (int)$row["id"];

        return -1;
    }

    public function recordChange($parent_id) {
        $query = $this->pdo->prepare("INSERT INTO " . $this->table_name . " (node_id, doc_id) VALUES (?, ?)");
        $query->execute(array((int)$parent_id, $this->doc_id));
    }

    public function discardOldChanges() {
        $min_id_query = $this->pdo->prepare("SELECT id FROM " . $this->table_name . " WHERE doc_id = ? ORDER BY id DESC LIMIT " . (self::MAX_UPDATES_BEFORE_RELOAD - 1) . ", 1");
        $min_id_query->execute(array($this->doc_id));
        $row = $min_id_query->fetch();

        $delete_query = $this->pdo->prepare("DELETE FROM " . $this->table_name . " WHERE id < ? AND doc_id = ?");
        $delete_query->execute(array((int)$row["id"], $this->doc_id));
    }

    private function changedParentIds() {
        $parent_ids = array();

        $query = $this->pdo->prepare("SELECT id, node_id FROM " . $this->table_name . " WHERE id > ? AND doc_id = ? ORDER BY id ASC");
        if ($query->execute(array($this->seq_nr, $this->doc_id))) {
            while ($row = $query->fetch()) {
                $node_id = (int)$row["node_id"];
                if (!in_array($node_id, $parent_ids))
                    $parent_ids[] = $node_id;

                // set seq_nr to seq_nr of processed change
                $this->seq_nr = $row["id"];
            }
        }

        return $parent_ids;
    }

    // returns an associative array of parent node id keys and children node values, that where involved in a recent change
    public function changedNodes() {
        // do a partial update with only immediate children by default
        $level_of_children = 0;
        $initial_seq_nr = $this->seq_nr;
        $parent_ids = $this->changedParentIds();

        // very unlikely case that more delta updates happened than will be retained in db. reload whole document
        if ($this->seq_nr - $initial_seq_nr > self::MAX_UPDATES_BEFORE_RELOAD) {
            $level_of_children = PHP_INT_MAX;
            $doc_info = $this->xmldb->get_doc_info($this->doc_id);
            $parent_ids = array($doc_info->rootid);
        }

        $changed_nodes = array();
        foreach ($parent_ids as $parent_id) {
            $changed_nodes[$parent_id] = $this->xmldb->get_subdoc_by_elementId($this->doc_id, $parent_id, true, $level_of_children);
        }

        return $changed_nodes;
    }

    public function encodedDeltaUpdate() {
        $changed_notes = $this->changedNodes();
        if (empty($changed_notes))
            return "";

        $result = array(
            'nodes' => \depage\cms\jstree_xml_to_html::toHTML($changed_notes),
            'seq_nr' => $this->seq_nr,
        );

        return json_encode($result);
    }
}

?>
