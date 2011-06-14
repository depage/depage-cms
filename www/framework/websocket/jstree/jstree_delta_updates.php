<?php

namespace depage\websocket\jstree;

class jstree_delta_updates {
    function __construct($table_prefix, $db, $xmldb, $doc_id, $seq_nr = -1) {
        $this->table_name = $table_prefix . "_delta_updates";
        $this->db = $db;
        $this->xmldb = $xmldb;
        $this->doc_id = (int)$doc_id;

        $this->seq_nr = (int)$seq_nr;
        if ($this->seq_nr == -1)
            $this->seq_nr = $this->currentChangeNumber();
    }

    public function currentChangeNumber() {
        $query = $this->db->prepare("SELECT MAX(id) AS id FROM " . $this->table_name . " WHERE doc_id = ?");
        if ($query->execute(array($this->doc_id)))
            if ($row = $query->fetch())
                return (int)$row["id"];

        return -1;
    }

    public function recordChange($parent_id) {
        $query = $this->db->prepare("INSERT INTO " . $this->table_name . " (node_id, doc_id) VALUES (?, ?)");
        $query->execute(array((int)$parent_id, $this->doc_id));
    }

    public function discardOldChanges() {
        $query = $this->db->prepare("DELETE FROM " . $this->table_name . " WHERE id <= ? AND doc_id = ?");
        $query->execute(array($this->seq_nr, $this->doc_id));
    }

    private function changedParentIds() {
        // TODO: transaction for all db requests?
        $parent_ids = array();

        $query = $this->db->prepare("SELECT id, node_id FROM " . $this->table_name . " WHERE id > ? AND doc_id = ? ORDER BY id ASC");
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
    // only get immediate children and replace partially in client
    public function changedNodes() {
        $parent_ids = $this->changedParentIds();

        $changed_nodes = array();
        foreach ($parent_ids as $parent_id) {
            $changed_nodes[$parent_id] = $this->xmldb->get_subdoc_by_elementId($this->doc_id, $parent_id, true, 0);
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
