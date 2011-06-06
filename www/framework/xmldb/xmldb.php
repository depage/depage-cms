<?php
/**
 * @file    modules/xmldb/xmldb.php
 *
 * cms xmldb module
 *
 *
 * copyright (c) 2002-2011 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 *
 * @todo validate tree solution with left/right columns (nested sets)
 */

namespace depage\xmldb; 

class xmldb {
    // {{{ variables
    private $entities;
    private $namespace_string;

    protected $pdo;
    protected $cache;

    protected $transaction = 0;

    protected $id_attribute = "id";
    protected $id_data_attribute = "dataid";
    protected $id_ref_attribute = "ref";

    protected $db_ns;

    protected $dont_strip_white = array();
    protected $free_element_ids = array();

    protected $table_docs;
    protected $table_xml;
    // }}}

    /* public */
    // {{{ constructor()
    public function __construct($tableprefix, $pdo, $cache, $dont_strip_white = array()) {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_NATURAL);

        $this->cache = $cache;

        $this->db_ns = new xmlns("db", "http://cms.depagecms.net/ns/database");
        $this->dont_strip_white = $dont_strip_white;

        $this->set_tables($tableprefix);
    }
    // }}}
    // {{{ set_tables
    protected function set_tables($tableprefix) {
        $this->table_docs = $tableprefix . "_xmldocs";
        $this->table_xml = $tableprefix . "_xmltree";
    }
    // }}}

    // {{{ get_doc_list()
    /**
     * gets available documents in database
     *
     * @public
     *
     * @return    $docs (array) the key is the name of the document,
     *            the value is the document db-id.
     */
    public function get_doc_list($name = "") {
        $docs = array();
        if ($name == "") {
            $namequery = "";
            $query_param = array();
        } else {
            $namequery = "WHERE name LIKE :projectname";
            $query_param = array(
                'projectname' => $name
            );
        }
        $query = $this->pdo->prepare(
            "SELECT docs.name, docs.name AS name, docs.id AS id, docs.rootid AS rootid
            FROM {$this->table_docs} AS docs
            $namequery
            ORDER BY docs.name ASC"
        );
        $query->execute($query_param);

        while ($doc = $query->fetchObject()) {
            $docs[$doc->name] = $doc;
        }

        return $docs;
    }
    // }}}
    // {{{ get_doc_info()
    /**
     * gets info about a document by doc_id
     *
     * @public
     *
     * @return    $docs (array) the key is the name of the document,
     *            the value is the document db-id.
     */
    public function get_doc_info($doc_id) {
        $query = $this->pdo->prepare(
            "SELECT docs.id AS id, docs.name AS name, docs.rootid AS rootid
            FROM {$this->table_docs} AS docs
            WHERE docs.id = :doc_id
            LIMIT 1"
        );
        $query->execute(array(
            'doc_id' => $doc_id,
        ));

        return $query->fetchObject();
    }
    // }}}
    // {{{ doc_exists()
    public function doc_exists($name) {
        $query = $this->pdo->prepare(
            "SELECT COUNT(docs.id) AS num
            FROM {$this->table_docs} AS docs
            WHERE docs.name = :doc_name"
        );
        $query->execute(array(
            'doc_name' => $name,
        ));
        $result = $query->fetchObject();

        return $result->num > 0 ? true : false;
    }
    // }}}
    // {{{ get_doc()
    public function get_doc($name, $add_id_attribute = true) {
        $this->begin_transaction();

        if ($this->doc_exists($name)) {
            $docs = $this->get_doc_list($name);
            $xml = $this->get_subdoc_by_elementId($docs[$name]->id, $docs[$name]->rootid, $add_id_attribute);
        } else {
            return false;
        }

        $this->end_transaction();

        return $xml;
    }
    // }}}
    // {{{ save_doc()
    public function save_doc($name, $xml) {
        if (!is_object($xml) || !(get_class($xml) == 'DOMDocument') || is_null($xml->documentElement)) {
            throw new xmldbException("This document is not a valid XML-Document");
        }

        $this->begin_transaction();

        $docs = $this->get_doc_list($name);
        if (isset($docs[$name])) {
            $query = $this->pdo->prepare(
                "DELETE FROM {$this->table_xml}
                WHERE id_doc = :doc_id"
            );
            $query->execute(array(
                'doc_id' => $docs[$name]->id,
            ));

            $this->clear_cache($docs[$name]->id);
        } else {
            $query = $this->pdo->prepare(
                "INSERT {$this->table_docs} SET 
                    name = :name"
            );
            $query->execute(array(
                'name' => $name,
            ));
            $docs[$name] = new \stdClass();
            $docs[$name]->id = $this->pdo->lastInsertId();
            $docs[$name]->name = $name;
        }

        $xml_text = $xml->saveXML();

        /*
         * @todo    get namespaces from document
         *            at this moment it is only per preg_match
         *            not by the domxml interface, because
         *            namespace definitions are not available
         */
        preg_match_all("/ xmlns:([^=]*)=\"([^\"]*)\"/", $xml_text, $matches, PREG_SET_ORDER);
        $namespaces = "";
        for ($i = 0; $i < count($matches); $i++) {
            if ($matches[$i][1] != $this->db_ns->ns) {
                $namespaces .= $matches[$i][0];
            }
        }

        /*
         * @todo    get document and entities
         *            or set html_entities as standard as long
         *            as php does not inherit the entites() function
         */

        $docs[$name]->rootid = $this->save_node($docs[$name]->id, $xml);
        $query = $this->pdo->prepare(
            "UPDATE {$this->table_docs}
            SET 
                rootid = :rootid, 
                ns = :ns,
                entities=''
            WHERE id = :doc_id"
        );
        $query->execute(array(
            'rootid' => $docs[$name]->rootid,
            'ns' => $namespaces,
            'doc_id' => $docs[$name]->id,
        ));


        $this->end_transaction();

        return $docs[$name]->id;
    }
    // }}}
    // {{{ remove_doc()
    public function remove_doc($name) {
        $docs = $this->get_doc_list($name);

        if (isset($docs[$name])) {
            $this->begin_transaction();

            $query = $this->pdo->prepare(
                "DELETE FROM {$this->table_xml}
                WHERE id_doc = :doc_id"
            );
            $query->execute(array(
                'doc_id' => $docs[$name]->id,
            ));

            $this->clear_cache($docs[$name]->id);
            $query = $this->pdo->prepare(
                "DELETE
                FROM {$this->table_docs}
                WHERE name = :name"
            );
            $query->execute(array(
                'name' => $name,
            ));

            $this->end_transaction();

            return true;
        } else {
            return false;
        }
    }
    // }}}

    // {{{ get_subdoc_by_xpath()
    /**
     * gets document by xpath. if xpath directs to more than
     * one node, only the first node will be returned.
     *
     * @public
     *
     * @param    $doc_id (int) id of document
     * @param    $xpath (string) xpath to target node
     * @param    $add_id_attribute (bool) whether to add db:id attribute or not
     *
     * @return    $doc (domxmlobject)
     */
    public function get_subdoc_by_xpath($doc_id, $xpath, $add_id_attribute = true) {
        $ids = $this->get_elementIds_by_xpath($doc_id, $xpath);
        if (count($ids) > 0) {
            return $this->get_subdoc_by_elementId($doc_id, $ids[0], $add_id_attribute);
        } else {
            return false;
        }
    }
    // }}}
    // {{{ unlink_node()
    public function unlink_node($doc_id, $node_id) {
        return $this->unlink_node_by_elementId($doc_id, $node_id);
    }
    // }}}
    // {{{ add_node()
    public function add_node($doc_id, $node, $target_id, $target_pos) {
        return $this->save_node($doc_id, $node, $target_id, $target_pos, true);
    }
    // }}}
    // {{{ replace_node()
    /**
     * replaces a node in database
     *
     * @public
     *
     * @param    $node (domxmlnode) node to save
     * @param    $id_to_replace (int) db-id of node to be replaced
     * @param    $doc_id (int) document db-id
     *
     * @return    $changed_ids (array) list of db-ids that has been changed
     */
    public function replace_node($doc_id, $node, $id_to_replace) {
        $this->begin_transaction();
        
        $target_id = $this->get_parentId_by_elementId($doc_id, $id_to_replace);
        $target_pos = $this->get_pos_by_elementId($doc_id, $id_to_replace);
        
        $this->unlink_node_by_elementId($doc_id, $id_to_replace, array(), true);

        $changed_ids = array();
        $changed_ids[] = $this->save_node($doc_id, $node, $target_id, $target_pos, true);
        $changed_ids[] = $target_id;
            
        $this->end_transaction();
        
        return $changed_ids;
    }
    // }}}
    // {{{ move_node_in
    /**
     * moves node to another node (append child)
     *
     * @public
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     */
    public function move_node_in($doc_id, $node_id, $target_id) {
        $this->begin_transaction();

        $query = $this->pdo->prepare(
            "SELECT IFNULL(MAX(xml.pos), -1) + 1 AS newpos 
            FROM {$this->table_xml} AS xml 
            WHERE xml.id_parent = :target_id AND xml.id_doc = :doc_id"
        );
        $query->execute(array(
            'target_id' => $target_id,
            'doc_id' => $doc_id,
        ));
        $result = $query->fetchObject();
        
        $this->move_node($doc_id, $node_id, $target_id, $result->newpos);

        $this->end_transaction();
    }
    // }}}
    // {{{ move_node_before()
    /**
     * moves node before another node (insert before)
     *
     * @public
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     */
    public function move_node_before($doc_id, $node_id, $target_id) {
        $this->begin_transaction();

        $target_parent_id = $this->get_parentId_by_elementId($doc_id, $target_id);
        $target_pos = $this->get_pos_by_elementId($doc_id, $target_id);
        
        $this->move_node($doc_id, $node_id, $target_parent_id, $target_pos);

        $this->end_transaction();
    }
    // }}}
    // {{{ move_node_after()
    /**
     * moves node after another node
     *
     * @public
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     */
    public function move_node_after($doc_id, $node_id, $target_id) {
        $this->begin_transaction();

        $target_parent_id = $this->get_parentId_by_elementId($doc_id, $target_id);
        $target_pos = $this->get_pos_by_elementId($doc_id, $target_id) + 1;
        
        $this->move_node($doc_id, $node_id, $target_parent_id, $target_pos);

        $this->end_transaction();
    }
    // }}}
    // {{{ copy_node_in()
    /**
     * copy node to another node
     *
     * @public
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     */
    public function copy_node_in($doc_id, $node_id, $target_id) {
        $this->begin_transaction();

        $query = $this->pdo->prepare(
            "SELECT IFNULL(MAX(xml.pos), -1) + 1 AS newpos 
            FROM {$this->table_xml} AS xml 
            WHERE xml.id_parent = :target_id AND xml.id_doc = :doc_id"
        );
        $query->execute(array(
            'target_id' => $target_id,
            'doc_id' => $doc_id,
        ));
        $result = $query->fetchObject();

        $val = $this->copy_node($doc_id, $node_id, $target_id, $result->newpos);

        $this->end_transaction();

        return $val;
    }
    // }}}
    // {{{  copy_node_before()
    /**
     * copy node before another node
     *
     * @public
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     */
    public function copy_node_before($doc_id, $node_id, $target_id) {
        $this->begin_transaction();

        $target_parent_id = $this->get_parentId_by_elementId($doc_id, $target_id);
        $target_pos = $this->get_pos_by_elementId($doc_id, $target_id);
        
        $val = $this->copy_node($doc_id, $node_id, $target_parent_id, $target_pos);

        $this->end_transaction();

        return $val;
    }
    // }}}
    // {{{ copy_node_after()
    /**
     * copy node after another node
     *
     * @public
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     */
    public function copy_node_after($doc_id, $node_id, $target_id) {
        $this->begin_transaction();

        $target_parent_id = $this->get_parentId_by_elementId($doc_id, $target_id);
        $target_pos = $this->get_pos_by_elementId($doc_id, $target_id) + 1;
        
        $val = $this->copy_node($doc_id, $node_id, $target_parent_id, $target_pos);

        $this->end_transaction();

        return $val;
    }
    // }}}
    
    // {{{ set_attribute()
    /**
     * sets attribute of node
     *
     * @public
     *
     * @param    $node_id (int) db-id of node to set attribute
     * @param    $attr_ns (string) namespace prefix of attribute
     * @param    $attr_name (string) name of attribute
     * @param    $attr_value (string) new value of attribute
     */
    public function set_attribute($doc_id, $node_id, $attr_name, $attr_value) {
        $this->begin_transaction();
        
        $changed = false;
        
        $attr_str = '';
        $query = $this->pdo->prepare(
            "SELECT xml.value 
            FROM {$this->table_xml} AS xml 
            WHERE xml.id = :node_id AND xml.id_doc = :doc_id"
        );
        $query->execute(array(
            'node_id' => $node_id,
            'doc_id' => $doc_id,
        ));
        $result = $query->fetchObject();
        if ($result) {
            $attributes = preg_split("/(=\"|\"$|\" )/", $result->value);
            for ($i = 0; $i < count($attributes) - 1; $i += 2) {
                if ($attributes[$i] == $attr_name) {
                    $attributes[$i + 1] = htmlspecialchars($attr_value);
                    $changed = true;
                }
                $attr_str .= $attributes[$i] . "=\"" . $attributes[$i + 1] . "\" ";
            }
            if (!$changed) {
                $attr_str .= $attr_name . "=\"" . htmlspecialchars($attr_value) . "\" ";
            }
            $query = $this->pdo->prepare(
                "UPDATE {$this->table_xml} AS xml 
                SET xml.value = :attr_str 
                WHERE xml.id = :node_id AND xml.id_doc = :doc_id"
            );
            $query->execute(array(
                'node_id' => $node_id,
                'attr_str' => $attr_str,
                'doc_id' => $doc_id,
            ));
            $this->clear_cache($doc_id);
        }
        
        $this->end_transaction();
    }
    // }}}
    // {{{ get_attribute()
    /**
     * gets attribute of node
     *
     * @public
     *
     * @param    $node_id (int) db-id of node
     * @param    $attr_ns (string) namespace prefix of attribute
     * @param    $attr_name (string) name of attribute
     *
     * @return    $val (string) value
     */
    public function get_attribute($doc_id, $node_id, $attr_name) {
        $val = null;
        $attributes = $this->get_attributes($doc_id, $node_id);

        if (isset($attributes[$attr_name])) {
            return $attributes[$attr_name];
        } else {
            return false;
        }
    }
    // }}}
    // {{{ get_attributes()
    /**
     * gets all attributes of a node by id
     *
     * @public
     *
     * @param    $node_id (int) db-id of node
     *
     * @return    $attributes (array) array of attributes
     */
    public function get_attributes($doc_id, $node_id) {
        $attributes = array();

        $query = $this->pdo->prepare(
            "SELECT xml.value 
            FROM {$this->table_xml} AS xml 
            WHERE xml.id = :node_id AND xml.type='ELEMENT_NODE' AND xml.id_doc = :doc_id"
        );
        $query->execute(array(
            'node_id' => $node_id,
            'doc_id' => $doc_id,
        ));
        $result = $query->fetchObject();

        if ($result) {
            $matches = preg_split("/(=\"|\"$|\" )/", $result->value);
            $matches = array_chunk($matches, 2);
            foreach($matches as $match) {
                if ($match[0] != '') {
                    $attributes[$match[0]] = $match[1];
                }
            }
        }
        
        return $attributes;
    }
    // }}}

    /* private */
    // {{{ begin_transaction()
    private function begin_transaction() {
        if ($this->transaction == 0) {
            $this->pdo->beginTransaction();
        }
        $this->transaction++;
    }
    // }}}
    // {{{ end_transaction()
    private function end_transaction() {
        $this->transaction--;
        if ($this->transaction == 0) {
            $this->pdo->commit();
        }
    }
    // }}}

    // {{{ get_free_elementIds()
    /**
     * gets unused db-node-ids for saving nodes
     *
     * @param    $needed (int) mininum number of ids, that are requested
     */
    private function get_free_elementIds($needed = 1) {
        global $conf;

        $num = 0;
        
        $this->free_element_ids = array();
        $query = $this->pdo->prepare(
            "SELECT row AS id FROM
                (SELECT @row := @row + 1 as row, t.id FROM {$this->table_xml} t, (SELECT @row := 0) r WHERE @row <> id ORDER BY t.id) AS seq
            WHERE NOT EXISTS (
                SELECT  1
                FROM {$this->table_xml} t
                WHERE t.id = row
            );"
        );
        

        $query->execute();

        $results = $query->fetchAll(\PDO::FETCH_OBJ);
        foreach ($results as $id) {
            $this->free_element_ids[] = $id->id;
        }
        
        if ($num < $needed) {
            $query = $this->pdo->prepare(
                "SELECT IFNULL(MAX(xml.id), 0) + 1 AS id_max
                FROM {$this->table_xml} AS xml"
            );
            $query->execute();
            $result = $query->fetchObject();

            for ($i = 0; $i < $needed - $num; $i++) {
                $this->free_element_ids[] = $result->id_max + $i;
            }
        }
    }
    // }}}
    // {{{ get_docId_by_elementId()
    /**
     * gets the document db-id by some of its node-ids
     *
     * @param    $id (int) node db-id
     *
     * @return    $docid (int) id of document, false, if node
     *            doesn't exist.
     */
    private function get_docId_by_elementId($id) {
        $query = $this->pdo->prepare(
            "SELECT xml.id_doc AS id_doc
            FROM {$this->table_xml} AS xml
            WHERE xml.id = :id"
        );
        $query->execute(array(
            'id' => $id,
        ));
        $result = $query->fetchObject();
        if ($result) {
            return $result->id_doc;
        } else {
            return false;
        }
    }
    // }}}
    // {{{ get_parentId_by_elementId
    /**
     * gets parent db-id by one of its child_nodes-id
     *
     * @param    $id (int) node db-id
     *
     * @return    $parent_id (id) db-id of parent node, false, if
     *            node doesn't exist.
     */
    private function get_parentId_by_elementId($doc_id, $id) {
        $query = $this->pdo->prepare(
            "SELECT xml.id_parent AS id_parent
            FROM {$this->table_xml} AS xml
            WHERE xml.id= :id AND xml.id_doc = :doc_id"
        );
        $query->execute(array(
            'id' => $id,
            'doc_id' => $doc_id,
        ));
        $result = $query->fetchObject();
        if ($result) {
            return $result->id_parent;
        } else {
            return false;
        }
    }
    // }}}
    // {{{ get_nodeName_by_elementId
    /**
     * gets node_name by node db-id
     *
     * @param    $id (int) node db-id
     *
     * @return    $node_name (string) name of node, false, if
     *            node doesn't exist.
     */
    private function get_nodeName_by_elementId($doc_id, $id) {
        $query = $this->pdo->prepare(
            "SELECT xml.name AS name
            FROM {$this->table_xml} AS xml
            WHERE xml.id = :id AND xml.id_doc = :doc_id"
        );
        $query->execute(array(
            'id' => $id,
            'doc_id' => $doc_id,
        ));
        $result = $query->fetchObject();
        if ($result) {
            return $result->name;
        } else {
            return false;
        }
    }
    // }}}
    // {{{ get_pos_by_elementId
    /**
     * gets node position in its parents childlist by node db-id.
     *
     * @param    $id (int) node db-id
     *
     * @return    $pos (int) position in node parents childlist
     */
    private function get_pos_by_elementId($doc_id, $id) {
        $query = $this->pdo->prepare(
            "SELECT xml.pos AS pos
            FROM {$this->table_xml} AS xml
            WHERE xml.id = :id AND xml.id_doc = :doc_id"
        );
        $query->execute(array(
            'id' => $id,
            'doc_id' => $doc_id,
        ));
        $result = $query->fetchObject();

        if ($result) {
            return $result->pos;
        } else {
            return NULL;
        }
    }
    // }}}
    // {{{ get_elementIds_by_name()
    /**
     * gets node-ids by name from specific document
     *
     * @param    $doc_id (int) document db-id
     * @param    $node_ns (string) namespace-prefix
     * @param    $node_name (string) nodename
     *
     * @return    $node_ids (array) db-ids of nodes
     */
    private function get_elementIds_by_name($doc_id, $node_ns = '', $node_name = '', $attr_cond = null) {
        $node_ids = array();

        list($name_query, $name_param) = $this->get_name_query($node_ns, $node_name);
        list($attr_query, $attr_param) = $this->get_attr_query($attr_cond);
        
        $query = $this->pdo->prepare(
            "SELECT xml.id AS id
            FROM {$this->table_xml} AS xml
            WHERE xml.id_doc = :doc_id and xml.type='ELEMENT_NODE' $name_query $attr_query"
        );
        $query->execute(array_merge(
            $name_param,
            $attr_param,
            array(
                'doc_id' => $doc_id,
            )
        ));
        while ($result = $query->fetchObject()) {
            $node_ids[] = $result->id;
        }
        return $node_ids;
    }
    // }}}
    // {{{ get_childIds_by_name()
    /**
     * gets ids of children of node by their nodename
     *
     * @param    $parent_id (int) db-id of parent node
     * @param    $node_ns (string) namespace prefix of node
     * @param    $node_name (string) nodename of node
     * @param    $only_element_nodes (bool) returns only Element-nodes if true
     *            and all childnodes, if false
     *
     * @return    $node_ids (array) list of node db-ids
     */
    private function get_childIds_by_name($doc_id, $parent_id, $node_ns = '', $node_name = '', $attr_cond = null, $only_element_nodes = false) {
        $node_ids = array();
        
        list($name_query, $name_param) = $this->get_name_query($node_ns, $node_name);
        list($attr_query, $attr_param) = $this->get_attr_query($attr_cond);

        if (is_null($parent_id) || $parent_id === false) {
            $parent_query = "xml.id_parent IS NULL";
            $parent_param = array();
        } else {
            $parent_query = "xml.id_parent = :parent_id";
            $parent_param = array(
                'parent_id' => $parent_id,
            );
        }
        
        if ($only_element_nodes) {
            $query = $this->pdo->prepare(
                "SELECT xml.id AS id
                FROM {$this->table_xml} AS xml
                WHERE xml.id_doc = :doc_id AND $parent_query AND (xml.type='ELEMENT_NODE' $name_query $attr_query) 
                ORDER BY pos"
            );
            $query->execute(array_merge(
                $name_param,
                $attr_param,
                $parent_param,
                array(
                    'doc_id' => $doc_id,
                )
            ));
        } else {
            $query = $this->pdo->prepare(
                "SELECT xml.id AS id
                FROM {$this->table_xml} AS xml
                WHERE xml.id_doc = :doc_id AND $parent_query AND ((xml.type='ELEMENT_NODE' $name_query $attr_query) OR (xml.type!='ELEMENT_NODE')) 
                ORDER BY pos"
            );
            $query->execute(array_merge(
                $name_param,
                $attr_param,
                $parent_param,
                array(
                    'doc_id' => $doc_id,
                )
            ));
        }
        while ($result = $query->fetchObject()) {
            $node_ids[] = $result->id;
        }
        return $node_ids;
    }
    // }}}
    // {{{ get_name_query()
    /**
     * gets part of sql query for selecting nodes by their name
     *
     * @param    $node_ns (string) namespace prefix of node
     * @param    $node_name (string) name of node
     *
     * @return    $name_query (string)
     */
    private function get_name_query($node_ns, $node_name) {
        if ($node_ns == '' && ($node_name == '' || $node_name == '*')) {
            $name_query = '';
            $name_param = array();
        } else if ($node_ns == '*') {
            $name_query = " and xml.name LIKE :node_name";
            $name_param = array(
                'node_name' => "%$node_name",
            );
        } else if ($node_ns != '' && $node_name == '*') {
            $name_query = " and xml.name LIKE :node_name";
            $name_param = array(
                'node_name' => "$node_ns:%",
            );
        } else if ($node_ns != '') {
            $name_query = " and xml.name = :node_name";
            $name_param = array(
                'node_name' => "$node_ns:$node_name",
            );
        } else {
            $name_query = " and xml.name = :node_name";    
            $name_param = array(
                'node_name' => $node_name,
            );
        }

        return array($name_query, $name_param);
    }
    // }}}
    // {{{ get_attr_query()
    /**
     * gets part of sql query for selecting node by their attribute
     *
     * @param    $attr_cond (array) every element must have following 
     *            subelements: name, value and operator. 
     *
     * @return    $attr_query (string)
     */
    private function get_attr_query($attr_cond) {
        $attr_query = '';
        $attr_param = array();

        if (is_array($attr_cond) && count($attr_cond) > 0) {
            $attr_query = 'and (';
            foreach($attr_cond as $i => $temp_cond) {
                if ($temp_cond['value'] == null) {
                    $attr_query .= " {$temp_cond['operator']} xml.value LIKE :attr{$i}_cond";
                    $attr_param["attr{$i}_cond"] = "%{$temp_cond['name']}=%";
                } else {
                    $attr_query .= " {$temp_cond['operator']} xml.value LIKE :attr{$i}_cond";
                    $attr_param["attr{$i}_cond"] = "%{$temp_cond['name']}=\"" . htmlspecialchars($temp_cond['value']) . "\"%";
                }
            }
            $attr_query .= ')';
        }

        return array($attr_query, $attr_param);
    }
    // }}}
    // {{{ get_elementIds_by_xpath()
    /**
     * gets node_ids by xpath
     *
     * @attention
     *            this supports only a small subset of xpath-queries.
     *            so recheck source before using.
     *
     * @param    $doc_id (int) id of document
     * @param    $xpath (string) xpath to target node
     *
     * @return    $nodeids (array) array of found node ids
     *
     * @todo    implement full xpath specifications
     */
    private function get_elementIds_by_xpath($doc_id, $xpath) {
        $identifier = "{$this->table_docs}/d{$doc_id}/xpath_" . sha1($xpath);

        $fetched_ids = $this->cache->get($identifier);

        if ($fetched_ids === false) {
            $pName = "(?:([^\/\[\]]*):)?([^\/\[\]]+)";
            $pCondition = "(?:\[(.*?)\])?";

            preg_match_all("/(\/+)$pName$pCondition/", $xpath, $xpath_elements, PREG_SET_ORDER);

            $query = $this->pdo->prepare(
                "SELECT docs.rootid AS rootid
                FROM {$this->table_docs} AS docs
                WHERE docs.id = :doc_id"
            );
            $query->execute(array(
                'doc_id' => $doc_id,
            ));
            $result = $query->fetchObject();
            $rootid = $result->rootid;
            $actual_ids = array(NULL);

            foreach ($xpath_elements as $level => $element) {
                $fetched_ids = array();
                $element[] = '';
                list(,$divider, $ns, $name, $condition) = $element;
                $strings = array();

                if ($divider == '/') {
                    // {{{ fetch only by name:
                    if ($condition == '') {    
                        /*
                         * "... /ns:name ..."
                         */
                        foreach ($actual_ids as $actual_id) {
                            $fetched_ids = array_merge($fetched_ids, $this->get_childIds_by_name($doc_id, $actual_id, $ns, $name, null, true));
                        }
                    // }}}
                    // {{{ fetch by name and position:
                    } else if (preg_match("/^([0-9]+)$/", $condition)) {
                        /*
                         * "... /ns:name[n] ..."
                         */
                        foreach ($actual_ids as $actual_id) {
                            $temp_ids = $this->get_childIds_by_name($doc_id, $actual_id, $ns, $name, null, true);
                            $fetched_ids[] = $temp_ids[((int) $condition) - 1];
                        }
                    // }}}
                    // {{{fetch by simple attributes:
                    } else if (preg_match("/[\w\d@=: _-]*/", $temp_condition = $this->remove_literal_strings($condition, $strings))) {
                        /*
                         * "... /ns:name[@attr1] ..."
                         * "... /ns:name[@attr1 = 'string1'] ..."
                         * "... /ns:name[@attr1 = 'string1' and/or @attr2 = 'string2'] ..."
                         */
                        $cond_array = $this->get_condition_attributes($temp_condition, $strings);
                        foreach ($actual_ids as $actual_id) {
                            $fetched_ids = array_merge($fetched_ids, $this->get_childIds_by_name($doc_id, $actual_id, $ns, $name, $cond_array, true));
                        }
                    // }}}
                    } else {
                        //$log->add_entry("get_xpath \"$xpath\" for this syntax not yet defined.");
                    }
                } elseif ($divider == '//' && $level == 0) {
                    // {{{ fetch only by name recursive:
                    if ($condition == '') {
                        /*
                         * "//ns:name ..."
                         */
                        $fetched_ids = $this->get_elementIds_by_name($doc_id, $ns, $name);    
                    // }}}
                    // {{{ fetch by simple attributes:
                    } else if (preg_match("/[\w\d@=: _-]*/", $temp_condition = $this->remove_literal_strings($condition, $strings))) {
                        /*
                         * "//ns:name[@attr1] ..."
                         * "//ns:name[@attr1 = 'string1'] ..."
                         * "//ns:name[@attr1 = 'string1' and/or @attr2 = 'string2'] ..."
                         */
                        $cond_array = $this->get_condition_attributes($temp_condition, $strings);
                        foreach ($actual_ids as $actual_id) {
                            $fetched_ids = $this->get_elementIds_by_name($doc_id, $ns, $name, $cond_array);
                        }
                    // }}}
                    } else {
                        //$log->add_entry("get_xpath \"$xpath\" for this syntax not yet defined.");
                    }
                } else {
                    //$log->add_entry("get_xpath \"$xpath\" for this syntax not yet defined.");
                }
                
                $actual_ids = $fetched_ids;
            }

            $this->cache->set($identifier, $fetched_ids);
        }
        return $fetched_ids;
    }

    /**
     * gets attributes array from xpath-condition\n
     * (... [@this = 'some' and @that = 'some other'])\n
     * can be used with:\n
     *        1. get_childIds_by_name()
     *        2. get_elementIds_by_name()
     * 
     * @private
     *
     * @param    $condition (string) attribute conditions
     * @param    $strings (array) of literal strings used in condition
     *
     * @return    $attr (array) array of attr-conditions
     */
    public function get_condition_attributes($condition, $strings) {
        $cond_array = array();

        $pAttr = "@(\w[\w\d:]*)";
        $pOperator = "(=)";
        $pBool = "(and|or|AND|OR)";
        $pString = "\\$(\d*)";
        preg_match_all("/$pAttr\s*(?:$pOperator\s*$pString)?\s*$pBool?/", $condition, $conditions);

        for ($i = 0; $i < count($conditions[0]); $i++) {
            $cond_array[] = array(
                'name' => $conditions[1][$i],
                'value' => $conditions[2][$i] == '' ? null : $strings[$conditions[3][$i]],
                'operator' => $i > 0 ? $conditions[4][$i - 1] : "",
            );
        }

        return $cond_array;
    }

    /**
     * replaces strings surrounded by " or ' with pointer to array
     *
     * @private
     *
     * @param    $text (string) text to process
     * @param    $strings (array) array of removed strings
     *
     * @return    $text (string)
     */
    public function remove_literal_strings($text, &$strings) {
        $n = 0;
        $newText = '';
        $strings = array();

        $p = "/([^\"']*)|(?:\"([^\"]*)\"|'([^']*)')/";
        preg_match_all($p, $text, $parts);

        for ($i = 0; $i < count($parts[0]); $i++) {
            if ($parts[1][$i] == '' && ($parts[2][$i] != '' || $parts[3][$i] != '')) {
                $strings[$n] = $parts[2][$i] . $parts[3][$i];
                $newText .= "\$$n";
                $n++;
            } else {
                $newText .= $parts[1][$i];
            }
        }
        return $newText;
    }
    // }}}
    // {{{ get_subdoc_by_elementId()
    /**
     * gets an xml-document-object from specific db-id
     *
     * @param    $id (int) db-id of node to get
     * @param    $add_id_attribute (bool) true, if you want to add the db-id attributes
     *            to xml-definition, false to remove them.
     */
    public function get_subdoc_by_elementId($doc_id, $id, $add_id_attribute = true) {
        global $conf;

        $identifier = "{$this->table_docs}/d{$doc_id}/{$id}.xml";

        $xml_str = $this->cache->get($identifier);
        if ($xml_str !== false) {
            // read from cache
            $xml_doc = new \DOMDocument();
            $xml_doc->loadXML($xml_str);
        } else {
            // read from database
            $this->begin_transaction();

            $query = $this->pdo->prepare(
                "SELECT docs.entities AS entities, docs.ns AS namespaces
                FROM {$this->table_docs} AS docs
                WHERE docs.id = :doc_id"
            );
            $query->execute(array(
                'doc_id' => $doc_id,
            ));
            $result = $query->fetchObject();

            $this->entities = $result->entities;
            $this->namespace_string = $result->namespaces;
            $this->namespaces = $this->extract_namespaces($this->namespace_string);
            $this->namespaces[] = $this->db_ns;

            $query = $this->pdo->prepare(
                "SELECT xml.id AS id, xml.name AS name, xml.type AS type, xml.value AS value
                FROM {$this->table_xml} AS xml
                WHERE xml.id = :id AND xml.id_doc = :doc_id"
            );

            $xml_str = "";

            $query->execute(array(
                'doc_id' => $doc_id,
                'id' => $id,
            ));
            $row = $query->fetchObject();

            //get ROOT-NODE
            if ($row->type == 'ELEMENT_NODE') {
                //create node
                $node_data = "<{$row->name}";
                
                $node_data .= " xmlns:{$this->db_ns->ns}=\"{$this->db_ns->uri}\"";
                $node_data .= " {$this->namespace_string}";
                
                //add attributes to node
                $node_data .= " {$row->value}";
                
                //add id_attribute to node
                $node_data .= " {$this->db_ns->ns}:{$this->id_attribute}=\"$row->id\">";
                
                $xml_str .= $node_data;
                
                //add child_nodes
                $xml_str .= $this->get_childnodes_by_parentid($doc_id, $row->id);

                $xml_str .= "</{$row->name}>";

            } else {
                throw new depage_exception("This node is no ELEMENT_NODE or node does not exist");
            }

            $xml_doc = new \DOMDocument();
            $xml_doc->loadXML($xml_str);

            $this->end_transaction();

            // add xml to xml-cache
            if (is_object($xml_doc) && $xml_doc->documentElement != null) {
                $this->cache->set($identifier, $xml_doc->saveXML());
            }
        }
        if (is_a($xml_doc, "DOMDocument") && $xml_doc->documentElement != null) {
            if (!$add_id_attribute) {
                $this->remove_idAttributes($xml_doc);
            }
            return $xml_doc;
        } else {
            return false;    
        }
    }
    // }}}
    // {{{ get_childnodes_by_parentid()
    /**
     * gets child nodes of a node as string
     *
     * @param   $doc_id (int) document id
     * @param   $parent_id (int) id of parent-node
     *
     * @return  $xml_doc (string) xml node definition of node
     */
    protected function get_childnodes_by_parentid($doc_id, $parent_id) {
        static $query = null;

        // prepare query
        if (is_null($query)) {
            $query = $this->pdo->prepare(
                "SELECT xml.id AS id, xml.name AS name, xml.type AS type, xml.value AS value
                FROM {$this->table_xml} AS xml
                WHERE xml.id_parent = :parent_id AND xml.id_doc = :doc_id
                ORDER BY xml.pos"
            );
        }

        $xml_doc = "";
        
        $query->execute(array(
            'doc_id' => $doc_id,
            'parent_id' => $parent_id,
        ));
        $results = $query->fetchAll(\PDO::FETCH_OBJ);
        foreach ($results as $row) {
            //get ELMEMENT_NODE
            if ($row->type == 'ELEMENT_NODE') {
                //create node
                $node_data = "<{$row->name}";
                
                //add attributes to node
                $node_data .= " {$row->value}";
                
                //add id_attribute to node
                $node_data .= " {$this->db_ns->ns}:{$this->id_attribute}=\"$row->id\">";
                
                $xml_doc .= $node_data;
                
                //add child_nodes
                $xml_doc .= $this->get_childnodes_by_parentid($doc_id, $row->id);

                $xml_doc .= "</{$row->name}>";
            //get TEXT_NODES
            } else if ($row->type == 'TEXT_NODE') {
                $xml_doc .= htmlspecialchars($row->value);
            //get CDATA_SECTION
            } else if ($row->type == 'CDATA_SECTION_NODE') {
                // @todo CDATA not implemented yet
            //get COMMENT_NODE
            } else if ($row->type == 'COMMENT_NODE') {
                $xml_doc .= "<!--{$row->value}-->";
            //get PROCESSING_INSTRUCTION
            } else if ($row->type == 'PI_NODE') {
                $xml_doc .= "<?{$row->name} {$row->value}?>";
            //get ENTITY_REF Node
            } else if ($row->type == 'ENTITY_REF_NODE') {
                // @todo ENTITY_REF_NODE not implemented yet
            }
        }

        return $xml_doc;
    }
    // }}}
    // {{{ extract_namespaces()
    public function extract_namespaces($str) {
        $namespaces = array();

        $pName = "([a-zA-Z0-9]*)";
        $pAttr = "([^\"]*)";
        preg_match_all("/xmlns:$pName=\"$pAttr\"/", $str, $ns_elements, PREG_SET_ORDER);
        foreach ($ns_elements AS $ns_element) {
            $namespaces[] = new xmlns($ns_element[1], $ns_element[2]);
        }
        return $namespaces;
    }
    // }}}

    // {{{ save_node()
    /**
     * saves a xml document or part of an document to database
     *
     * @public
     *
     * @param    $node (domxmlnode) node to save
     * @param    $target_id (int) node db-id to save to
     * @param    $target_pos (int) position to save at
     * @param    $stripwhitespace (bool) wether to strip whitespace
     *            from textnodes or not.
     */
    public function save_node($doc_id, $node, $target_id = null, $target_pos = -1, $stripwhitespace = true) {
        global $conf, $log;
        
        $this->begin_transaction();
        
        //get all nodes in array
        $node_array = array();
        $this->get_nodearray_for_saving($node_array, $node);

        if ($node_array[0]['id'] != null && $target_id === null) {
            //set target_id/pos/doc
            //$target_doc = $this->get_docId_by_elementId($node_array[0]['id']);
            $target_id = $this->get_parentId_by_elementId($doc_id, $node_array[0]['id']);
            $target_pos = $this->get_pos_by_elementId($doc_id, $node_array[0]['id']);

            if ($target_id === false) {
                $target_id = null;
            }

            //unlink old node
            $this->unlink_node_by_elementId($doc_id, $node_array[0]['id']);
            $this->clear_cache($doc_id);
        } else if ($target_id === null) {
            $target_id = null;
            $target_pos = 0;
        } else if ($target_id !== null) {
            //$target_doc = $this->get_docId_by_elementId($target_id);
            $parent_id = $this->get_parentId_by_elementId($doc_id, $target_id);
            //unlink child nodes, if target is document
            if ($parent_id === false) {
                $query = $this->pdo->prepare(
                    "DELETE
                    FROM {$this->table_xml}
                    WHERE id_doc = :doc_id"
                );
                $query->execute(array(
                    'doc_id' => $doc_id,
                ));
            }
            $this->clear_cache($doc_id);
            
            //set target_id/pos/doc
            $query = $this->pdo->prepare(
                "SELECT IFNULL(MAX(xml.pos), -1) + 1 AS pos 
                FROM {$this->table_xml} AS xml
                WHERE xml.id_parent = :target_id AND id_doc = :doc_id"
            );
            $query->execute(array(
                'target_id' => $target_id,
                'doc_id' => $doc_id,
            ));
            $result = $query->fetchObject();
            if ($result) {
                if ($target_pos > $result->pos || $target_pos == -1) {
                    $target_pos = $result->pos;
                }
            } else {
                $target_pos = 0;
            }
            
            /*
            //resort
            db()->query(
                "UPDATE {$this->table_xml}
                SET pos=pos+1 
                WHERE id_parent=$target_id AND pos>=$target_pos AND id_doc=$doc_id"
            );
            */
        }

        $this->get_free_elementIds(count($node_array));
        for ($i = 0; $i < count($node_array); $i++) {
            if ($node_array[$i]['id'] !== null) {
                $index = array_search($node_array[$i]['id'], $this->free_element_ids);
                if ($index !== false) {
                    array_splice($this->free_element_ids, $index, 1);
                } else {
                    $node_array[$i]['id'] = null;
                }
            }
        }
        
        for ($i = 0; $i < count($node_array); $i++) {
            if ($node_array[$i]['id'] === null) {
                $node_array[$i]['id'] = array_shift($this->free_element_ids);
            }
        }
        
        /* correct changed references */
        /*
        $changed_ref_ids = array();
        for ($i = 1; $i < count($node_array); $i++) {
            if ($node_array[$i]['node']->node_type() == XML_ELEMENT_NODE && $node_array[$i]['id'] != $node_array[$i]['id_old']) {
                $changed_ref_ids[$node_array[$i]['id_old']] = $node_array[$i]['id'];
            }
        }
        $xpath_node = xpath_new_context_ns($node, array($this->db_ns));
        $xfetch = xpath_eval($xpath_node, ".//@{$this->id_ref_attribute}", $node);
        for ($i = 0; $i < count($xfetch->nodeset); $i++) {
            if(isset($changed_ref_ids[$xfetch->nodeset[$i]->node_value()])) {
                $parent_node = $xfetch->nodeset[$i]->parent_node();
                //echo($parent_node->get_attribute('ref') . " -> ");
                $this->set_attribute_ns($parent_node, "{$this->db_ns->ns}ref", $changed_ref_ids[$xfetch->nodeset[$i]->node_value()]);
                //echo($parent_node->get_attribute('ref') . "<br />");
            }
        }
        */
        
        //save root node
        $node_array[0]['id'] = $this->save_node_to_db($doc_id, $node_array[0]['node'], $node_array[0]['id'], $target_id, $target_pos, true);
        
        //save element nodes
        for ($i = 1; $i < count($node_array); $i++) {
            if ($node_array[$i]['node']->nodeType == XML_ELEMENT_NODE) {
                $node_array[$i]['id'] = $this->save_node_to_db($doc_id,$node_array[$i]['node'], $node_array[$i]['id'], $node_array[$node_array[$i]['parent_index']]['id'], $node_array[$i]['pos']);
            }
        }

        //save other nodes
        for ($i = 1; $i < count($node_array); $i++) {
            if ($node_array[$i]['node']->nodeType != XML_ELEMENT_NODE) {
                $node_array[$i]['id'] = $this->save_node_to_db($doc_id, $node_array[$i]['node'], $node_array[$i]['id'], $node_array[$node_array[$i]['parent_index']]['id'], $node_array[$i]['pos']);
            }
        }
        
        $this->end_transaction();
        
        return $node_array[0]['id'];
    }
    // }}}
    // {{{ save_node_to_db()
    /**
     * saves a node to database
     *
     * @private
     *
     * @param    $node (domxmlnode) node to save
     * @param    $id (int) db-id to save node in
     * @param    $target_id (int) db-id of parent node
     * @param    $target_pos (int) position to save node at
     * @param    $target_doc (int) doc-id of target document
     * @param    $increase_pos (bool) wether to change positions in 
     *            target nodes childlist
     *
     * @return    $id (int) db-id under which node has been saved
     */
    private function save_node_to_db($doc_id, $node, $id, $target_id, $target_pos, $increase_pos = false) {
        static $insert_query = null;
        if (is_null($insert_query)) {
            $insert_query = $this->pdo->prepare(
                "REPLACE {$this->table_xml}
                SET 
                    id = :id_query, 
                    id_parent = :target_id, 
                    id_doc = :doc_id, 
                    pos = :target_pos, 
                    name = :name, 
                    value = :value, 
                    type = :type"
            );
        }

        if ($id === null) {
            $id_query = 'NULL';
        } else {
            $id_query = $id;
        }
        if ($node->nodeType == XML_ELEMENT_NODE) {
            if ($node->prefix != '') {
                $name_query = $node->prefix . ':' . $node->localName;
            } else {
                $name_query = $node->localName;
            }
            $attribs = $node->attributes;
            $attr_str = '';
            foreach ($node->attributes as $attrib) {
                if ($attrib->prefix . ':' . $attrib->localName != $this->db_ns->ns . ':' . $this->id_attribute) {
                    $attrib_ns = ($attrib->prefix == '') ? '' : $attrib->prefix . ':';
                    $attrib_name = $attrib->localName;
                    $attrib_value = $attrib->value;
                    
                    $attr_str .= $attrib_ns . $attrib_name . "=\"" . htmlspecialchars($attrib_value) . "\" ";
                }
            }
            if ($target_id !== null && $increase_pos) {
                $query = $this->pdo->prepare(
                    "UPDATE {$this->table_xml}
                    SET pos = pos + 1 
                    WHERE id_parent = :target_id AND pos >= :target_pos AND id_doc = :doc_id"
                );
                $query->execute(array(
                    'target_id' => $target_id,
                    'target_pos' => $target_pos,
                    'doc_id' => $doc_id,
                ));
            }
            $insert_query->execute(array(
                'id_query' => $id_query,
                'target_id' => $target_id,
                'target_pos' => $target_pos,
                'name' => $name_query,
                'value' => $attr_str,
                'type' => 'ELEMENT_NODE',
                'doc_id' => $doc_id,
            ));

            if ($id === null) {
                $id = $query->lastInsertId();
                $node->setAttributeNS($this->db_ns->uri, $this->db_ns->ns . ':' . $this->id_attribute, $id);
            } else if ($this->get_node_elementId($node) == null) {
                $node->setAttributeNS($this->db_ns->uri, $this->db_ns->ns . ':' . $this->id_attribute, $id);
            }
        } else {
            if ($node->nodeType == XML_TEXT_NODE) {
                $node_type = 'TEXT_NODE';
                $node_data = $node->textContent;
                $node_name = null;
            } else if ($node->nodeType == XML_COMMENT_NODE) {
                $node_type = 'COMMENT_NODE';
                $node_data = $node->textContent;
                $node_name = null;
            } else if ($node->nodeType == XML_PI_NODE) {
                $node_type = 'PI_NODE';
                $node_data = $node->textContent;
                $node_name = $node->target;
            } else if ($node->nodeType == XML_ENTITY_REF_NODE) {
                $node_type = 'ENTITY_REF_NODE';
                $node_data = $node->nodeName;
                $node_name = null;
            }
            
            $insert_query->execute(array(
                'id_query' => $id_query,
                'target_id' => $target_id,
                'target_pos' => $target_pos,
                'name' => $node_name,
                'value' => $node_data,
                'type' => $node_type,
                'doc_id' => $doc_id,
            ));
            if ($id === null) {
                $id = $query->lastInsertId();
            }
        }
        return $id;
    }
    // }}}
    // {{{ get_nodearray_for_saving()
    /**
     * gets all nodes of a document in one array
     *
     * @private
     *
     * @param    $node_array (array) list of nodes to add current node to
     * @param    $node (domxmlnode) current node
     * @param    $parent_index (int) index of parent node in created node list
     * @param    $pos (int) position of current node
     * @param    $stripwhitespace (bool) wether to strip whitespace from textnodes
     *            while saving
     */
    public function get_nodearray_for_saving(&$node_array, $node, $parent_index = null, $pos = 0, $stripwhitespace = true) {
        $type = $node->nodeType;
        //is DOCUMENT_NODE
        if ($type == XML_DOCUMENT_NODE) {
            $root_node = $node->documentElement;
            $this->get_nodearray_for_saving($node_array, $root_node, $parent_index, $pos, $stripwhitespace);
        //is ELEMENT_NODE
        } elseif ($type == XML_ELEMENT_NODE) {
            $id = $this->get_node_elementId($node);
            $node_array[] = array(
                'id' => $id, 
                'id_old' => $id, 
                'parent_index' => $parent_index,
                'pos' => $pos,
                'node' => $node,
            );
            $parent_index = count($node_array) - 1;
            
            $node_name = (($node->prefix != '') ? $node->prefix . ':' : '') . $node->localName;
            if (!$stripwhitespace || in_array($node_name, $this->dont_strip_white)) {
                $stripwhitespace = false;
            }
            $tmp_node = $node->firstChild;
            $i = 0;
            while ($tmp_node != null) {
                if ($tmp_node->nodeType != XML_TEXT_NODE || (!$stripwhitespace || trim($tmp_node->textContent) != '')) {
                    $this->get_nodearray_for_saving($node_array, $tmp_node, $parent_index, $i, $stripwhitespace);
                    $i++;
                }
                $tmp_node = $tmp_node->nextSibling;    
            }
        //is *_NODE
        } else {
            $node_array[] = array(
                'id' => null, 
                'id_old' => null, 
                'parent_index' => $parent_index, 
                'pos' => $pos, 
                'node' => $node,
            );
        }
    }    
    // }}}

    // {{{ unlink_node_by_elementId()
    /**
     * unlinks and deletes a specific node from database
     *
     * @public
     *
     * @param    $id (int) db-id of node to delete
     * @param    $ids_to_keep (array) array of currently deleted 
     *            element nodes.
     * @param    $reorder_pos (bool) if true, the position of the nodes
     *            before and after the deleted node are changed.
     * @param    $row (array) result of last deleted row
     *
     * @return    $deleted_ids (array) list of db-ids of deleted nodes
     */
    public function unlink_node_by_elementId($doc_id, $id)  {
        $query = $this->pdo->prepare(
            "DELETE FROM {$this->table_xml}
            WHERE id_doc = :doc_id AND id = :id"
        );
        $query->execute(array(
            'doc_id' => $doc_id,
            'id' => $id,
        ));

        $this->clear_cache($doc_id);

        return array();
    }
    // }}}
    // {{{ move_node()
    /**
     * moves node in database
     *
     * @public
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     * @param    $target_pos (int) position to move to
     */
    public function move_node($doc_id, $node_id, $target_id, $target_pos) {
        //echo("doc_id: $doc_id\nnode_id: $node_id\ntarget_id: $target_id\ntarget_pos: $target_pos\n");
        //return false;

        $this->begin_transaction();
        
        $node_parent_id = $this->get_parentId_by_elementId($doc_id, $node_id);
        $node_pos = $this->get_pos_by_elementId($doc_id, $node_id);
        
        if ($target_id == $node_parent_id && $target_pos > $node_pos) {
            $target_pos--;
        }
        
        if ($target_id != $node_parent_id || $target_pos != $node_pos) {
            // remove node from parent
            $query = $this->pdo->prepare(
                "UPDATE {$this->table_xml}
                SET id_doc=NULL, id_parent=NULL, pos=NULL 
                WHERE id = :node_id AND id_doc = :doc_id"
            );
            $query->execute(array(
                'node_id' => $node_id,
                'doc_id' => $doc_id,
            ));
            // update position on source position
            $query = $this->pdo->prepare(
                "UPDATE {$this->table_xml}
                SET pos=pos-1 
                WHERE id_parent = :node_parent_id AND pos > :node_pos AND id_doc = :doc_id"
            );
            $query->execute(array(
                'node_parent_id' => $node_parent_id,
                'node_pos' => $node_pos,
                'doc_id' => $doc_id,
            ));

            // update positions on target position
            $query = $this->pdo->prepare(
                "UPDATE {$this->table_xml}
                SET pos=pos+1 
                WHERE id_parent = :target_id AND pos >= :target_pos AND id_doc = :doc_id"
            );
            $query->execute(array(
                'target_id' => $target_id,
                'target_pos' => $target_pos,
                'doc_id' => $doc_id,
            ));
            // reattach node at target position
            $query = $this->pdo->prepare(
                "UPDATE {$this->table_xml}
                SET id_doc = :doc_id, id_parent = :target_id, pos = :target_pos 
                WHERE id = :node_id"
            );
            $query->execute(array(
                'target_id' => $target_id,
                'target_pos' => $target_pos,
                'node_id' => $node_id,
                'doc_id' => $doc_id,
            ));
            
            $this->clear_cache($doc_id);
        }
        
        $this->end_transaction();
    }
    // }}}
    // {{{ copy_node()
    /**
     * copy node in database
     *
     * @public
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     * @param    $target_pos (int) pos to copy to
     */
    public function copy_node($doc_id, $node_id, $target_id, $target_pos) {
        $xml_doc = $this->get_subdoc_by_elementId($doc_id, $node_id, false);
        $root_node = $xml_doc;
        
        $this->clear_cache($doc_id);
        
        return $this->save_node($doc_id, $root_node, $target_id, $target_pos, false);
    }
    // }}}

    // {{{ remove_idAttributes
    /**
     * remove all db-id attributes recursive from nodes
     *
     * @private
     *
     * @param    $node (domxmlnode) node to remove attribute from
     */
    public function remove_idAttributes($node) {
        if ($node->nodeType == XML_ELEMENT_NODE || $node->nodeType == XML_DOCUMENT_NODE) {
            if ($node->nodeType == XML_ELEMENT_NODE) {
                $xml_doc = $node->ownerDocument;
            } else {
                $xml_doc = $node;
                $node = $xml_doc->documentElement;
            }
            $xpath = new \DOMXPath($xml_doc);
            $xp_result = $xpath->query("//*[@{$this->db_ns->ns}:{$this->id_attribute}]", $node);
            foreach ($xp_result as $node) {
                $node->removeAttributeNS($this->db_ns->uri, $this->id_attribute);
            }
        }
    }
    // }}}
    // {{{ clear_cache()
    /**
     * clears the node-cache
     *
     * @public
     */
    public function clear_cache($doc_id = null) {
        if (is_null($doc_id)) {
            $this->cache->delete("{$this->table_docs}/");
        } else {
            $this->cache->delete("{$this->table_docs}/d{$doc_id}/");
        }
    }
    // }}}
    // {{{ get_node_elementId()
    /**
     * gets node db-id from db-id attribute
     *
     * @public
     *
     * @param    $node (domxmlnode) node to get id from
     *
     * @return    $db_id (int)
     */
    public function get_node_elementId($node) {
        global $log;

        $db_id = null;
        if ($node->nodeType == XML_ELEMENT_NODE) {
            $db_id = $node->getAttributeNS($this->db_ns->uri, $this->id_attribute);
        }
        
        return $db_id;
    }        
    // }}}
    // {{{ get_node_dataId()
    /**
     * gets node db-dataid from db-dataid attribute
     *
     * @public
     *
     * @param    $node (domxmlnode) node to get id from
     *
     * @return    $db_id (int)
     */
    public function get_node_dataId($node) {
        global $log;

        $db_id = null;
        if ($node->nodeType == XML_ELEMENT_NODE) {
            $db_id = $node->getAttributeNS($this->db_ns->uri, $this->id_data_attribute);
        }
        
        return $db_id;
    }        
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
