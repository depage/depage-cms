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
        $this->cache = $cache;

        $this->db_ns = new xmlns("db", "http://cms.depagecms.net/ns/database");
        $this->dont_strip_white = $dont_strip_white;

        $this->set_tables($tableprefix);
    }
    // }}}
    // {{{ set_tables
    public function set_tables($tableprefix) {
        $this->table_docs = $tableprefix . "_xmltree";
        $this->table_xml = $tableprefix . "_xmldocs";
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
        } else {
            $namequery = "WHERE name LIKE \"$name\"";
        }
        $result = db()->query(
            "SELECT docs.id AS id, docs.name AS name, docs.rootid AS rootid
            FROM {$this->table_docs} AS docs
            $namequery
            ORDER BY docs.name ASC"
        );

        $num = db()->num_rows();
        for ($i = 0; $i < $num; $i++) {
            $docs[$result[$i]->name] = new StdClass();
            $docs[$result[$i]->name]->id = $result[$i]->id;
            $docs[$result[$i]->name]->name = $result[$i]->name;
            $docs[$result[$i]->name]->rootid = $result[$i]->rootid;
        }
        return $docs;
    }
    // }}}
    // {{{ get_doc_info()
    /**
     * gets available documents in database
     *
     * @public
     *
     * @return    $docs (array) the key is the name of the document,
     *            the value is the document db-id.
     */
    public function get_doc_info($doc_id) {
        $result = db()->query(
            "SELECT docs.id AS id, docs.name AS name, docs.rootid AS rootid
            FROM {$this->table_docs} AS docs
            WHERE docs.id='$doc_id'
            ORDER BY docs.name ASC"
        );
        if (db()->num_rows() == 1) {
            $value = new StdClass();
            $value->id = $result[0]->id;
            $value->name = $result[0]->name;
            $value->rootid = $result[0]->rootid;

            return $value;
        } else {
            return false;
        }
    }
    // }}}
    // {{{ doc_exists()
    public function doc_exists($name) {
        $result = db()->query(
            "SELECT COUNT(docs.id) AS num
            FROM {$this->table_docs} AS docs
            WHERE docs.name='" . db()->escape($name) . "'"
        );

        return $result[0]->num > 0 ? true : false;
    }
    // }}}
    // {{{ get_doc()
    public function get_doc($name) {
        $this->begin_transaction();

        if ($this->doc_exists($name)) {
            $docs = $this->get_doc_list($name);
            $xml = $this->get_subdoc_by_elementId($docs[$name]->id, $docs[$name]->rootid);
        } else {
            return false;
        }

        $this->end_transaction();

        return $xml;
    }
    // }}}
    // {{{ save_doc()
    public function save_doc($name, $xml) {
        if (!($xml instanceof DOMDocument) || is_null($xml->documentElement)) {
            throw new depage_exception("This document is not a valid XML-Document");
        }

        $this->begin_transaction(DB_TRANSMIT_WRITE);

        $docs = $this->get_doc_list($name);
        if (isset($docs[$name])) {
            db()->query(
                "UPDATE {$this->table_xml}
                SET id_parent=NULL, id_doc=NULL, pos=NULL, name=NULL, value='', type='DELETED'
                WHERE id_doc='{$docs[$name]->id}'"
            );
            $this->clear_cache($docs[$name]->id);
        } else {
            db()->query(
                "INSERT {$this->table_docs} SET 
                    name='" . db()->escape($name) . "'"
            );
            $docs[$name] = new StdClass();
            $docs[$name]->id = db()->insert_id();
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
        db()->query(
            "UPDATE {$this->table_docs}
            SET rootid={$docs[$name]->rootid}, ns='" . db()->escape($namespaces) . "', entities=''
            WHERE id={$docs[$name]->id}"
        );

        $this->end_transaction();

        return $docs[$name]->id;
    }
    // }}}
    // {{{ remove_doc()
    public function remove_doc($name) {
        $docs = $this->get_doc_list($name);

        if (isset($docs[$name])) {
            $this->begin_transaction(DB_TRANSMIT_WRITE);

            db()->query(
                "UPDATE {$this->table_xml}
                SET id_parent=NULL, id_doc=NULL, pos=NULL, name=NULL, value='', type='DELETED'
                WHERE id_doc='{$docs[$name]->id}'"
            );
            $this->clear_cache($docs[$name]->id);
            db()->query(
                "DELETE
                FROM {$this->table_docs}
                WHERE name='" . db()->escape($name) . "'"
            );

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
        if (count($ids > 0)) {
            return $this->get_subdoc_by_elementId($doc_id, $ids[0], $add_id_attribute);
        } else {
            return false;
        }
    }
    // }}}
    // {{{ unlink_node_by_elementId()
    public function unlink_node($doc_id, $node_id) {
        $val = $this->unlink_node_by_elementId($doc_id, $node_id);
        $this->clear_deleted_nodes();

        return $val;
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
        $this->begin_transaction(DB_TRANSMIT_WRITE);
        
        $target_id = $this->get_parentId_by_elementId($doc_id, $id_to_replace);
        $target_pos = $this->get_pos_by_elementId($doc_id, $id_to_replace);
        
        $changed_ids = $this->unlink_node_by_elementId($doc_id, $id_to_replace, array(), true);
        $changed_ids[] = $this->save_node($doc_id, $node, $target_id, $target_pos, true);
        $changed_ids[] = $target_id;
        $this->clear_deleted_nodes();
            
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
        $this->begin_transaction(DB_TRANSMIT_WRITE);

        $result = db()->query(
            "SELECT IFNULL(MAX(xml.pos), -1) + 1 AS newpos 
            FROM {$this->table_xml} AS xml 
            WHERE xml.id_parent='$target_id' AND xml.id_doc=$doc_id"
        );
        
        $this->move_node($doc_id, $node_id, $target_id, $result[0]->newpos);

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
        $this->begin_transaction(DB_TRANSMIT_WRITE);

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
        $this->begin_transaction(DB_TRANSMIT_WRITE);

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
        $this->begin_transaction(DB_TRANSMIT_WRITE);

        $result = db()->query(
            "SELECT IFNULL(MAX(xml.pos), -1) + 1 AS newpos 
            FROM {$this->table_xml} AS xml 
            WHERE xml.id_parent='$target_id' AND xml.id_doc=$doc_id"
        );
        $val = $this->copy_node($doc_id, $node_id, $target_id, $result[0]->newpos);

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
        $this->begin_transaction(DB_TRANSMIT_WRITE);

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
        $this->begin_transaction(DB_TRANSMIT_WRITE);

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
    public function set_attribute($doc_id, $node_id, $attr_ns, $attr_name, $attr_value) {
        $this->begin_transaction(DB_TRANSMIT_WRITE);
        
        $changed = false;
        if ($attr_ns != NULL && $attr_ns != '') {
            $query = $attr_ns . ':' . $attr_name;    
        } else {
            $query = $attr_name;
        }
        
        $attr_str = '';
        $result = db()->query(
            "SELECT xml.value 
            FROM {$this->table_xml} AS xml 
            WHERE xml.id='$node_id' AND xml.id_doc=$doc_id"
        );
        if (($num = db()->num_rows()) == 1) {
            $attributes = preg_split("/(=\"|\"$|\" )/", $result[0]->value);
            for ($i = 0; $i < count($attributes) - 1; $i += 2) {
                if ($attributes[$i] == $query) {
                    $attributes[$i + 1] = htmlspecialchars($attr_value);
                    $changed = true;
                }
                $attr_str .= $attributes[$i] . "=\"" . $attributes[$i + 1] . "\" ";
            }
            if (!$changed) {
                $attr_str .= $query . "=\"" . htmlspecialchars($attr_value) . "\" ";
            }
            db()->query(
                "UPDATE {$this->table_xml} AS xml 
                SET xml.value='" . db()->escape($attr_str) . "' 
                WHERE xml.id='$node_id' AND xml.id_doc=$doc_id"
            );
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
    public function get_attribute($doc_id, $node_id, $attr_ns, $attr_name) {
        $val = null;
        if ($attr_ns != NULL && $attr_ns != '') {
            $query = $attr_ns . ':' . $attr_name;
        } else {
            $query = $attr_name;    
        }
        $attributes = $this->get_attributes($doc_id, $node_id);

        if (isset($attributes[$query])) {
            return $attributes[$query];
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

        $result = db()->query(
            "SELECT xml.value 
            FROM {$this->table_xml} AS xml 
            WHERE xml.id='$node_id' AND xml.type='ELEMENT_NODE' AND xml.id_doc=$doc_id"
        );
        if (($num = db()->num_rows()) == 1) {
            $matches = preg_split("/(=\"|\"$|\" )/", $result[0]->value);
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
    private function begin_transaction($transtype = DB_TRANSMIT_READ) {
        db()->begin_transaction(array(
            $this->table_docs,
            $this->table_xml,
            'docs',
            'xml',
        ), $transtype);
    }
    // }}}
    // {{{ end_transaction()
    private function end_transaction() {
        db()->end_transaction();
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
        $result = db()->query(
            "SELECT xml.id AS id
            FROM {$this->table_xml} AS xml
            WHERE xml.type='DELETED' 
            ORDER BY xml.id"
        );

        $num = db()->num_rows();
        foreach ($result as $id) {
            $this->free_element_ids[] = $id->id;
        }
        
        if ($num < $needed) {
            $result = db()->query(
                "SELECT IFNULL(MAX(xml.id), 0) + 1 AS id_max
                FROM {$this->table_xml} AS xml"
            );
            for ($i = 0; $i < $needed - $num; $i++) {
                $this->free_element_ids[] = $result[0]->id_max + $i;
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
        $result = db()->query(
            "SELECT xml.id_doc AS id_doc
            FROM {$this->table_xml} AS xml
            WHERE xml.id='$id'"
        );
        if (db()->num_rows() == 1) {
            return $result[0]->id_doc;
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
        $result = db()->query(
            "SELECT xml.id_parent AS id_parent
            FROM {$this->table_xml} AS xml
            WHERE xml.id=$id AND xml.id_doc=$doc_id"
        );
        if (db()->num_rows() == 1) {
            return $result[0]->id_parent;
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
        $result = db()->query(
            "SELECT xml.name AS name
            FROM {$this->table_xml} AS xml
            WHERE xml.id=$id AND xml.id_doc=$doc_id"
        );
        if (db()->num_rows() == 1) {
            return $result[0]->name;
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
        $result = db()->query(
            "SELECT xml.pos AS pos
            FROM {$this->table_xml} AS xml
            WHERE xml.id=$id AND xml.id_doc=$doc_id"
        );
        if (db()->num_rows() == 1) {
            return $result[0]->pos;
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

        $name_query = $this->get_name_query($node_ns, $node_name);
        $attr_query = $this->get_attr_query($attr_cond);
        
        $result = db()->query(
            "SELECT xml.id AS id
            FROM {$this->table_xml} AS xml
            WHERE xml.id_doc='$doc_id' and xml.type='ELEMENT_NODE' $name_query $attr_query"
        );
        if (($num = db()->num_rows()) > 0) {
            for ($i = 0; $i < $num; $i++) {
                $node_ids[] = $result[$i]->id;
            }
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
        
        $name_query = $this->get_name_query($node_ns, $node_name);
        $attr_query = $this->get_attr_query($attr_cond);
        
        if ($only_element_nodes) {
            $result = db()->query(
                "SELECT xml.id AS id
                FROM {$this->table_xml} AS xml
                WHERE xml.id_doc=$doc_id AND xml.id_parent" . ($parent_id === NULL ? " IS NULL" : "=$parent_id") . " AND (xml.type='ELEMENT_NODE' $name_query $attr_query) 
                ORDER BY pos"
            );
        } else {
            $result = db()->query(
                "SELECT xml.id AS id
                FROM {$this->table_xml} AS xml
                WHERE xml.id_doc=$doc_id AND xml.id_parent" . ($parent_id === NULL ? " IS NULL" : "=$parent_id") . " AND ((xml.type='ELEMENT_NODE' $name_query $attr_query) or (xml.type!='ELEMENT_NODE')) 
                ORDER BY pos"
            );
        }
        if (($num = db()->num_rows()) > 0) {
            for ($i = 0; $i < $num; $i++) {
                $node_ids[] = $result[$i]->id;
            }
            return $node_ids;
        } else {
            return false;
        }
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
        } else if ($node_ns == '*') {
            $name_query = " and xml.name LIKE '%$node_name'";
        } else if ($node_ns != '' && $node_name == '*') {
            $name_query = " and xml.name LIKE '$node_ns:%'";
        } else if ($node_ns != '') {
            $name_query = " and xml.name='$node_ns:$node_name'";
        } else {
            $name_query = " and xml.name='$node_name'";    
        }

        return $name_query;
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
        if (!is_array($attr_cond)) {
            $attr_query = '';
        } else {
            $attr_query = 'and (';
            foreach($attr_cond as $temp_cond) {
                if ($temp_cond['value'] == null) {
                    $attr_query .= " {$temp_cond['operator']} xml,value LIKE '%{$temp_cond['name']}=%'";
                } else {
                    $attr_query .= " {$temp_cond['operator']} xml.value LIKE '%{$temp_cond['name']}=\"" . db()->escape($temp_cond['value']) . "\"%'";
                }
            }
            $attr_query .= ')';
        }

        return $attr_query;
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
        $pName = "(?:([^\/\[\]]*):)?([^\/\[\]]+)";
        $pCondition = "(?:\[(.*?)\])?";

        preg_match_all("/(\/+)$pName$pCondition/", $xpath, $xpath_elements, PREG_SET_ORDER);

        $result = db()->query(
            "SELECT docs.rootid AS rootid
            FROM {$this->table_docs} AS docs
            WHERE docs.id=$doc_id"
        );
        $rootid = $result[0]->rootid;
        $actual_ids = array(NULL);

        foreach ($xpath_elements as $level => $element) {
            $fetched_ids = array();
            $element[] = '';
            list(,$divider, $ns, $name, $condition) = $element;
            if ($divider == '/') {
                // {{{ fetch only by name:
                if ($condition == '') {    
                    /*
                     * "... /ns:name ..."
                     */
                    foreach ($actual_ids as $actual_id) {
                        $fetched_ids = array_merge($fetched_ids, $this->get_childIds_by_name($doc_id, $actual_id, $ns, $name));
                    }
                // }}}
                // {{{ fetch by name and position:
                } else if (preg_match("/^([0-9]+)$/", $condition)) {
                    /*
                     * "... /ns:name[n] ..."
                     */
                    foreach ($actual_ids as $actual_id) {
                        $temp_ids = $this->get_childIds_by_name($doc_id, $actual_id, $ns, $name);
                        $fetched_ids[] = $temp_ids[((int) $condition) - 1];
                    }
                // }}}
                // {{{fetch by simple attributes:
                } else if (preg_match("/[\w\d@=: _-]*/", $temp_condition = $this->remove_literal_strings($condition, $strings = array()))) {
                    /*
                     * "... /ns:name[@attr1] ..."
                     * "... /ns:name[@attr1 = 'string1'] ..."
                     * "... /ns:name[@attr1 = 'string1' and/or @attr2 = 'string2'] ..."
                     */
                    $cond_array = $this->get_condition_attributes($temp_condition, $strings);
                    foreach ($actual_ids as $actual_id) {
                        $fetched_ids = array_merge($fetched_ids, $this->get_childIds_by_name($doc_id, $actual_id, $ns, $name, $cond_array));
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
                    $fetched_ids = $this->get_elementIds_by_name($doc_id, $actual_ids[0], $ns, $name);    
                // }}}
                // {{{ fetch by simple attributes:
                } else if (preg_match("/[\w\d@=: _-]*/", $temp_condition = $this->remove_literal_strings($condition, $strings = array()))) {
                    /*
                     * "//ns:name[@attr1] ..."
                     * "//ns:name[@attr1 = 'string1'] ..."
                     * "//ns:name[@attr1 = 'string1' and/or @attr2 = 'string2'] ..."
                     */
                    $cond_array = $this->get_condition_attributes($temp_condition, $strings);
                    foreach ($actual_ids as $actual_id) {
                        $fetched_ids = $this->get_elementIds_by_name($doc_id, $actual_ids[0], $ns, $name, $cond_array);
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
        $pAttr = "@(\w[\w\d:]*)";
        $pOperator = "(=)";
        $pBool = "(and|or)";
        $pString = "\\$(\d*)";
        preg_match_all("/$pAttr\s*(?:$pOperator\s*$pString)?\s*$pBool?/", $condition, $conditions);
        $cond_array = array();
        for ($i = 0; $i < count($conditions[0]); $i++) {
            $cond_array[] = array(
                'name' => $conditions[1][$i],
                'value' => $conditions[2][$i] == '' ? null : $strings[$conditions[3][$i]],
                'operator' => $conditions[4][$i - 1],
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
    public function remove_literal_strings($text, $strings) {
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
    private function get_subdoc_by_elementId($doc_id, $id, $add_id_attribute = true) {
        global $conf;

        $fs = new fs_local();
        $identifier = "{$this->table_docs}/d{$doc_id}/{$id}.xml";

        $xml_str = $this->cache->get($identifier);
        if ($xml_str !== false) {
            // read from cache
            $xml_doc = new DOMDocument();
            $xml_doc->loadXML($xml_str);
        } else {
            // read from database
            $this->begin_transaction(DB_TRANSMIT_READ);

            $result = db()->query(
                "SELECT docs.entities AS entities, docs.ns AS namespaces
                FROM {$this->table_docs} AS docs
                WHERE docs.id=$doc_id"
            );
            $this->entities = $result[0]->entities;
            $this->namespace_string = $result[0]->namespaces;
            $this->namespaces = $this->extract_namespaces($this->namespace_string);
            $this->namespaces[] = $this->db_ns;

            $xml_doc  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
            $xml_doc .= "<!DOCTYPE depage [ $this->entities ]>";
            
            $result = db()->query(
                "SELECT xml.id AS id, xml.name AS name, xml.type AS type, xml.value AS value
                FROM {$this->table_xml} AS xml
                WHERE xml.id=$id AND xml.id_doc=$doc_id"
            );
            if (($num = db()->num_rows()) == 1) {
                //if node is ELEMENT_NODE
                if ($result[0]->type == 'ELEMENT_NODE') {
                    $xml_str = $this->get_node_by_id($doc_id, $id, true, $result[0]);
                    $xml_doc = new DOMDocument();
                    $xml_doc->loadXML($xml_str);
                }
            }

            db()->end_transaction();

            // add xml to xml-cache
            if (is_object($xml_doc) && $xml_doc->documentElement != null) {
                $this->cache->put($xml_doc->saveXML());
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
    // {{{ get_node_by_id()
    /**
     * gets node definition by its node id
     *
     * @private
     *
     * @param    $xml_doc (string) xml definition to append elements
     * @param    $id (int) db-id of node to add
     * @param    $is_root (bool) if true, global namespace-definitions will
     *            be added to node. false, otherwise.
     * @param    $row (array) result of last select
     *
     * @return    $xml_doc (string) xml node definition of node
     */
    public function get_node_by_id($doc_id, $id, $is_root = false, $row) {
        /*
         * @todo rewrite to return actual node value by "return"
         */
        //get ELMEMENT_NODE
        if ($row->type == 'ELEMENT_NODE') {
            $xml_doc = "";

            //create node
            $name = $row->name;
            $node_data = "<$name";
            
            if ($is_root) {
                $node_data .= " xmlns:{$this->db_ns->ns}=\"{$this->db_ns->uri}\"";
                $node_data .= " {$this->namespace_string}";
            }
            
            //add attributes to node
            $node_data .= " {$row->value}";
            
            //add id_attribute to node
            $node_data .= " {$this->db_ns->ns}:{$this->id_attribute}=\"$id\">";
            
            $xml_doc .= $node_data;
            //add child_nodes
            $result = db()->query(
                "SELECT xml.id AS id, xml.name AS name, xml.type AS type, xml.value AS value
                FROM {$this->table_xml} AS xml
                WHERE xml.id_parent='$id' AND xml.id_doc='$doc_id'
                ORDER BY xml.pos"
            );
            if (($num = db()->num_rows()) > 0) {
                for ($i = 0; $i < $num; $i++) {
                    $xml_doc .= $this->get_node_by_id($doc_id, $result[$i]->id, false, $result[$i]);
                }
            }
            $xml_doc .= "</$name>";

            return $xml_doc;
        //get TEXT_NODES
        } else if ($row->type == 'TEXT_NODE') {
            return htmlspecialchars($row->value);
        //get CDATA_SECTION
        } else if ($row->type == 'CDATA_SECTION_NODE') {
            //$node = $xml_doc->create_cdata_section($row->value);
        //get COMMENT_NODE
        } else if ($row->type == 'COMMENT_NODE') {
            return "<!--{$row->value}-->";
        //get PROCESSING_INSTRUCTION
        } else if ($row->type == 'PI_NODE') {
            return "<?{$row->name} {$row->value} ?>";
        //get ENTITY_REF Node
        } else if ($row->type == 'ENTITY_REF_NODE') {
            //$node = $xml_doc->create_entity_reference($row->value);
        }
    }
    // }}}
    // {{{ extract_namespaces()
    public function extract_namespaces($str) {
        $namespaces = array();

        $pName = "([a-zA-Z0-9]*)";
        $pAttr = "([^\"]*)";
        preg_match_all("/xmlns:$pName=\"$pAttr\"/", $str, $ns_elements, PREG_SET_ORDER);
        foreach ($ns_elements AS $ns_element) {
            $namespaces[] = new ns($ns_element[1], $ns_element[2]);
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
        
        $this->begin_transaction(DB_TRANSMIT_WRITE);
        
        //get all nodes in array
        $node_array = array();
        $this->get_nodearray_for_saving($node_array, $node);

        if ($node_array[0]['id'] != null && $target_id === null) {
            //set target_id/pos/doc
            //$target_doc = $this->get_docId_by_elementId($node_array[0]['id']);
            $target_id = $this->get_parentId_by_elementId($doc_id, $node_array[0]['id']);
            $target_pos = $this->get_pos_by_elementId($doc_id, $node_array[0]['id']);

            //unlink old node
            $this->unlink_node_by_elementId($doc_id, $node_array[0]['id'], array(), true);
            $this->clear_cache($doc_id);
        } else if ($target_id === null) {
            $target_id = 'NULL';
            $target_pos = 0;
        } else if ($target_id !== null) {
            //$target_doc = $this->get_docId_by_elementId($target_id);
            $parent_id = $this->get_parentId_by_elementId($doc_id, $target_id);
            //unlink child nodes, if target is document
            if ($parent_id === false) {
                $result = db()->query(
                    "DELETE
                    FROM {$this->table_xml}
                    WHERE id_doc='$doc_id'"
                );
            }
            $this->clear_cache($doc_id);
            
            //set target_id/pos/doc
            $result = db()->query(
                "SELECT IFNULL(MAX(xml.pos), -1) + 1 AS pos 
                FROM {$this->table_xml} AS xml
                WHERE xml.id_parent='$target_id' AND id_doc='$doc_id'"
            );
            if (($num = db()->num_rows()) == 1) {
                if ($target_pos > $result[0]->pos || $target_pos == -1) {
                    $target_pos = $result[0]->pos;
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
        $this->clear_deleted_nodes();
        
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
            if ($increase_pos) {
                db()->query(
                    "UPDATE {$this->table_xml}
                    SET pos=pos+1 
                    WHERE id_parent=$target_id AND pos >=$target_pos AND id_doc=$doc_id"
                );
            }
            db()->query(
                "REPLACE {$this->table_xml}
                SET id=$id_query, id_parent=$target_id, id_doc=$doc_id, pos=$target_pos, name='$name_query', value='" . db()->escape($attr_str) . "', type='ELEMENT_NODE'"
            );
            if ($id === null) {
                $id = db()->insert_id();
                $node->setAttributeNS($this->db_ns->uri, $this->db_ns->ns . ':' . $this->id_attribute, $id);
            } else if ($this->get_node_elementId($node) == null) {
                $node->setAttributeNS($this->db_ns->uri, $this->db_ns->ns . ':' . $this->id_attribute, $id);
            }
        } else {
            if ($node->nodeType == XML_TEXT_NODE) {
                $node_type = 'TEXT_NODE';
                $node_data = $node->textContent;
            } else if ($node->node_type == XML_COMMENT_NODE) {
                $node_type = 'COMMENT_NODE';
                $node_data = $node->textContent;
            } else if ($node->node_type == XML_ENTITY_REF_NODE) {
                $node_type = 'ENTITY_REF_NODE';
                $node_data = $node->nodeName;
            }
            
            db()->query(
                "REPLACE {$this->table_xml}
                SET id=$id_query, id_parent=$target_id, id_doc=$doc_id, pos=$target_pos, name=NULL, value='" . db()->escape($node_data) . "', type='$node_type'"
            );
            if ($id === null) {
                $id = db()->insert_id();
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
    public function unlink_node_by_elementId($doc_id, $id, $ids_to_keep = array(), $reorder_pos = true, $row = NULL)  {
        $this->begin_transaction(DB_TRANSMIT_WRITE);
        
        $deleted_ids = array();
        if (is_null($row)) {
            $result = db()->query(
                "SELECT xml.id_parent AS id_parent, xml.pos AS pos
                FROM {$this->table_xml} AS xml
                WHERE xml.id='$id' AND xml.id_doc='$doc_id'"
            );
            $row = $result[0];
            $addParentNode = $result[0]->id_parent;
        } else {
            $addParentNode = NULL;
        }
        if (!is_null($row)) {
            //reorder node positions
            if ($reorder_pos && ($row->id_parent != null)) {
                db()->query(
                    "UPDATE {$this->table_xml}
                    SET pos = pos - 1 
                    WHERE id_parent={$row->id_parent} AND pos > {$row->pos} AND id_doc='$doc_id'"
                );
            }
            //unlink child-nodes
            $result = db()->query(
                "SELECT xml.id AS id, xml.id_parent AS id_parent, xml.pos AS pos
                FROM {$this->table_xml} AS xml
                WHERE xml.id_parent='$id' AND xml.id_doc='$doc_id'"
            );
            if (($num = db()->num_rows()) > 0) {
                for ($i = 0; $i < $num; $i++) {
                    $deleted_ids = array_merge($deleted_ids, $this->unlink_node_by_elementId($doc_id, $result[$i]->id, $ids_to_keep, false, $result[$i]));
                }    
            }
            $deleted_ids[] = $id;
            
            if ($reorder_pos) {
                db()->query(
                    "UPDATE {$this->table_xml}
                    SET type='deleted'
                    WHERE id_doc='$doc_id' AND id IN (" . implode(',', $deleted_ids)    . ")"
                );
                $this->clear_cache($doc_id);
            }
        }
        $this->end_transaction();

        if (!is_null($addParentNode)) {
            $deleted_ids[] = $addParentNode;
        }
        
        return $deleted_ids;
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

        $this->begin_transaction(DB_TRANSMIT_WRITE);
        
        $node_parent_id = $this->get_parentId_by_elementId($doc_id, $node_id);
        $node_pos = $this->get_pos_by_elementId($doc_id, $node_id);
        
        if ($target_id == $node_parent_id && $target_pos > $node_pos) {
            $target_pos--;
        }
        
        if ($target_id != $node_parent_id || $target_pos != $node_pos) {
            db()->query(
                "UPDATE {$this->table_xml}
                SET id_doc=NULL, id_parent=NULL, pos=NULL 
                WHERE id='$node_id' AND id_doc=$doc_id"
            );
            db()->query(
                "UPDATE {$this->table_xml}
                SET pos=pos-1 
                WHERE id_parent='$node_parent_id' AND pos>$node_pos AND id_doc=$doc_id"
            );

            db()->query(
                "UPDATE {$this->table_xml}
                SET pos=pos+1 
                WHERE id_parent='$target_id' AND pos>=$target_pos AND id_doc=$doc_id"
            );
            db()->query(
                "UPDATE {$this->table_xml}
                SET id_doc='$doc_id', id_parent='$target_id', pos='$target_pos' 
                WHERE id='$node_id'"
            );
            
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
        $root_node = $xml_doc->document_element();
        
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
            $xpath = new DOMXPath($xml_doc);
            $xp_result = $xpath->query(".//*[@{$this->db_ns->ns}:{$this->id_attribute}]", $node);
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
    // {{{ clear_deleted_nodes()
    /**
     * clears data of deleted nodes
     *
     * @public
     */
    public function clear_deleted_nodes() {
        db()->query(
            "UPDATE {$this->table_xml}
            SET id_parent=NULL, id_doc=NULL, pos=NULL, name=NULL, value=''
            WHERE type='DELETED' AND id_doc IS NOT NULL"
        );
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

    /* do these function really belong here? */

    /* are these functions really needed? */
    // {{{ optimize_database()
    /**
     * optimizes xml_db tables
     *
     * @public
     */
    public function optimize_database() {
        db()->query(
            "OPTIMIZE TABLE 
            {$this->table_xml}"
        );
    }
    // }}}
    // {{{ get_elementIds_to_keep()
    /**
     * gets the db-ids, that are used in the given document. this will be
     * used to keep the same ids during deleting and saving.
     *
     * @public
     *
     * @param    $node (domxmlnode) node to get db-id from
     *
     * @return    $ids (array) list of db-ids, that are use in the given
     *            node and its children.
     */
    public function get_elementIds_to_keep($node) {
        $ids = array();

        $xpath_node = xpath_new_context_ns($node, array($this->db_ns));
        $xfetch = xpath_eval($xpath_node, "//*");
        for ($i = 0; $i < count($xfetch->nodeset); $i++) {
            $attribs = $xfetch->nodeset[$i]->attributes();
            for ($j = 0; $j < count($attribs); $j++) {
                if (($attribs[$j]->prefix() . ':' . $attribs[$j]->name()) == $this->id_attribute || $attribs[$j]->name() == $this->id_attribute) {
                    $ids[] = $attribs[$j]->value();
                }
            }
        }
        
        return $ids;
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
