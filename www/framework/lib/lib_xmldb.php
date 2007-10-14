<?php
/**
 * @file    lib_xmldb.php
 *
 * XML Database Library
 *
 * This file defines a xml database layer on top of a MySQL
 * database. Mostly the nodes are accessed by a unique node id
 * called db:id.
 *
 *
 * copyright (c) 2002-2007 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 *
 * $Id: lib_xmldb.php,v 1.55 2004/11/12 19:45:31 jonas Exp $
 */

// {{{ define and includes
if (!function_exists('die_error')) require_once('lib_global.php');

require_once('lib_project.php');
// }}}

/**
 * CLASS XML Database
 *
 * @todo    Abstract this XML database layer to work with
 *            different XML solutions.\n
 *            1. On top of MySQL (now)\n
 *            2. Directly through the local file system
 *               without the need for an external database\n
 *            3. On top of normal XML databases accessed by 
 *               XPath and XQuery etc.\n
 *
 * @todo    Port to PHP 5 XML extensions
 */
class xml_db {
    // {{{ variables
    /**
     * database locking status
     *
     * @private
     */
    var $_is_locked = 0;
    var $_lock_mode = '';
    // }}}

    // {{{ constructor
    /**
     * constructor, sets needed parameters
     *
     * @public
     *
     * @param    $element_table (string) name of db-table, where xml-nodes
     *            were saved.
     * @param    $cache_table (string) name of db-table, where documents
     *            will be cached for faster access.
     * @param    $dbxml_ns (string) prefix of the namespace for db-node-ids
     * @param    $dbxml_ns_uri (string) uri of the namespace for db-node-ids
     * @param    $global_ns (array) global namespace definition, that all
     *            all saved documents will use.
     * @param    $dont_strip_white (array) nodenames, in which all whitespace
     *            will be preserved during saving. in all other nodes, all
     *            whitespace will be stripped.
     */
    function xml_db($element_table, $cache_table, $dbxml_ns, $dbxml_ns_uri, $global_ns = array(), $dont_strip_white = array()) {
        $this->set_tables($element_table, $cache_table);
        $this->global_ns = $global_ns;
        $this->dont_strip_white = $dont_strip_white;
        
        //adds internal namespace for ids;
        $this->dbxml_ns_uri = $dbxml_ns_uri;
        $this->dbxml_ns = $dbxml_ns;
        $this->global_ns[$dbxml_ns] = $dbxml_ns_uri;
        $this->id_attribute = $dbxml_ns . ':id';
        $this->id_ref_attribute = $dbxml_ns . ':ref';
        
        $this->free_element_ids = array();
        $this->free_attribute_ids = array();
    }
    // }}}
    // {{{ set_tables
    function set_tables($element_table, $cache_table) {
        global $log;

        //$log->add_entry("tables: $element_table, $cache_table");
        $this->element_table = $element_table;
        $this->cache_table = $cache_table;
    }
    // }}}
    // {{{ lock_write()
    /**
     * locks all tables for writing
     *
     * @public
     */
    function lock_write() {
        global $conf;
        
        $this->_is_locked++;
        if ($this->_is_locked == 1 || $this->_lock_mode == 'r') {
            db_query(
                "LOCK TABLES 
                $this->element_table WRITE, 
                $this->cache_table WRITE" 
            );
            $this->_lock_mode = 'w';
        }
    }
    // }}}
    // {{{ lock_read()
    /**
     * locks all tables for reading
     *
     * @public
     */
    function lock_read() {
        global $conf;
        
        $this->_is_locked++;
        if ($this->_is_locked == 1) {
            db_query(
                "LOCK TABLES 
                $this->element_table READ,
                $this->cache_table READ" 
            );
            $this->_lock_mode = 'r';
        }
    }
    // }}}
    // {{{ unlock()
    /**
     * unlocks all tables
     *
     * @param    $force (bool) on true forces to unlock
     *
     * @public
     */
    function unlock($force = false) {
        global $conf;
        
        $this->_is_locked--;
        if ($this->_is_locked == 0 || $force) {
            db_query(
                "UNLOCK TABLES"
            );
            $this->_is_locked = 0;
            $this->_lock_mode = '';
            $this->free_element_ids = array();
        }
    }
    // }}}
    // {{{ get_free_ids()
    /**
     * gets unused db-node-ids for saving nodes
     *
     * @public
     *
     * @param    $needed (int) mininum number of ids, that are requested
     */
    function get_free_ids($needed = 1) {
        global $conf;
        
        $this->free_element_ids = array();
        $result = db_query(
            "SELECT id 
            FROM $this->element_table 
            WHERE type='DELETED' 
            ORDER BY id"
        );
        if ($result && ($num = mysql_num_rows($result)) > 0) {
            for ($i = 0; $i < $num; $i++) {
                $row = mysql_fetch_assoc($result);
                $this->free_element_ids[] = $row['id'];
            }
        }
        if ($num < $needed) {
            $result = db_query(
                "SELECT MAX(id) + 1 AS id_max
                FROM $this->element_table"
            );
            $row = mysql_fetch_array($result);
            for ($i = 0; $i < $needed - $num; $i++) {
                $this->free_element_ids[] = $row['id_max'] + $i;
            }
        }
        mysql_free_result($result);
    }
    // }}}
    // {{{ get_docs()
    /**
     * gets available documents in database
     *
     * @public
     *
     * @return    $docs (array) the key is the name of the document,
     *            the value is the document db-id.
     */
    function get_docs() {
        $docs = array();
        $result = db_query(
            "SELECT id, name
            FROM $this->element_table
            WHERE type='DOCUMENT_NODE'
            ORDER BY name ASC"
        );
        if ($result) {
            $num = mysql_num_rows($result);
            for ($i = 0; $i < $num; $i++) {
                $row = mysql_fetch_assoc($result);
                $docs[$row['name']] = $row['id'];
            }
        }
        return $docs;
    }
    // }}}
    // {{{ get_doc_id_by_name()
    /**
     * gets the document db-id by name of document
     *
     * @public
     *
     * @param    $name (string) name of document
     *
     * @return    $docid (int) id of document, false, if document
     *            dosn't exist.
     */
    function get_doc_id_by_name($name) {
        $result = db_query(
            "SELECT id 
            FROM $this->element_table 
            WHERE name='$name' and type='DOCUMENT_NODE'"
        );
        if ($result && ($num = mysql_num_rows($result)) == 1) {
            $row = mysql_fetch_assoc($result);
            $retVal = $row['id'];
        } else {
            $retVal = false;    
        }
        mysql_free_result($result);
        
        return $retVal;
    }
    // }}}
    // {{{ get_doc_id_by_id()
    /**
     * gets the document db-id by some of its node-ids
     *
     * @public
     *
     * @param    $id (int) node db-id
     *
     * @return    $docid (int) id of document, false, if node
     *            doesn't exist.
     */
    function get_doc_id_by_id($id) {
        $result = db_query(
            "SELECT id_doc 
            FROM $this->element_table 
            WHERE id='$id'"
        );
        if ($result && ($num = mysql_num_rows($result)) == 1) {
            $row = mysql_fetch_assoc($result);
            $retVal = $row['id_doc'];
        } else {
            $retVal = false;    
        }
        mysql_free_result($result);
        
        return $retVal;
    }
    // }}}
    // {{{ get_parent_id_by_id
    /**
     * gets parent db-id by one of its child_nodes-id
     *
     * @public
     *
     * @param    $id (int) node db-id
     *
     * @return    $parent_id (id) db-id of parent node, false, if
     *            node doesn't exist.
     */
    function get_parent_id_by_id($id) {
        $result = db_query(
            "SELECT id_parent 
            FROM $this->element_table 
            WHERE id='$id'"
        );
        if ($result && ($num = mysql_num_rows($result)) == 1) {
            $row = mysql_fetch_assoc($result);
            $retVal = $row['id_parent'];
        } else {
            $retVal = false;    
        }
        mysql_free_result($result);
        
        return $retVal;
    }
    // }}}
    // {{{ get_node_name_by_id
    /**
     * gets node_name by node db-id
     *
     * @public
     *
     * @param    $id (int) node db-id
     *
     * @return    $node_name (string) name of node, false, if
     *            node doesn't exist.
     */
    function get_node_name_by_id($id) {
        $result = db_query(
            "SELECT name 
            FROM $this->element_table 
            WHERE id='$id'"
        );
        if ($result && ($num = mysql_num_rows($result)) == 1) {
            $row = mysql_fetch_assoc($result);
            $retVal = $row['name'];
        } else {
            $retVal = false;    
        }
        mysql_free_result($result);
        
        return $retVal;
    }
    // }}}
    // {{{ get_pos_by_id
    /**
     * gets node position in its parents childlist by node db-id.
     *
     * @public
     *
     * @param    $id (int) node db-id
     *
     * @return    $pos (int) position in node parents childlist
     */
    function get_pos_by_id($id) {
        $result = db_query(
            "SELECT pos 
            FROM $this->element_table 
            WHERE id='$id'"
        );
        if ($result && ($num = mysql_num_rows($result)) == 1) {
            $row = mysql_fetch_assoc($result);
            $retVal = $row['pos'];
        } else {
            $retVal = null;    
        }
        mysql_free_result($result);
        
        return $retVal;
    }
    // }}}
    // {{{ get_node_ids_by_name()
    /**
     * gets node-ids by name from specific document
     *
     * @public
     *
     * @param    $doc_id (int) document db-id
     * @param    $node_ns (string) namespace-prefix
     * @param    $node_name (string) nodename
     *
     * @return    $node_ids (array) db-ids of nodes
     */
    function get_node_ids_by_name($doc_id, $node_ns = '', $node_name = '', $attr_cond = null) {
        $node_ids = array();

        $name_query = $this->_get_name_query($node_ns, $node_name);
        $attr_query = $this->_get_attr_query($attr_cond);
        
        $result = db_query(
            "SELECT id 
            FROM $this->element_table 
            WHERE id_doc='$doc_id' and type='ELEMENT_NODE' $name_query $attr_query"
        );
        if ($result && ($num = mysql_num_rows($result)) > 0) {
            for ($i = 0; $i < $num; $i++) {
                $row = mysql_fetch_assoc($result);
                $node_ids[] = $row['id'];
            }
        }
        mysql_free_result($result);
        
        return $node_ids;
    }

    /**
     * gets ids of children of node by their nodename
     *
     * @public
     *
     * @param    $parent_id (int) db-id of parent node
     * @param    $node_ns (string) namespace prefix of node
     * @param    $node_name (string) nodename of node
     * @param    $only_element_nodes (bool) returns only Element-nodes if true
     *            and all childnodes, if false
     *
     * @return    $node_ids (array) list of node db-ids
     */
    function get_child_ids_by_name($parent_id, $node_ns = '', $node_name = '', $attr_cond = null, $only_element_nodes = false) {
        $node_ids = array();
        
        $name_query = $this->_get_name_query($node_ns, $node_name);
        $attr_query = $this->_get_attr_query($attr_cond);
        
        if ($only_element_nodes) {
            $result = db_query(
                "SELECT id 
                FROM $this->element_table 
                WHERE id_parent='$parent_id' and (type='ELEMENT_NODE' $name_query $attr_query) 
                ORDER BY pos"
            );
        } else {
            $result = db_query(
                "SELECT id 
                FROM $this->element_table 
                WHERE id_parent='$parent_id' and ((type='ELEMENT_NODE' $name_query $attr_query) or (type!='ELEMENT_NODE')) 
                ORDER BY pos"
            );
        }
        if ($result && ($num = mysql_num_rows($result)) > 0) {
            for ($i = 0; $i < $num; $i++) {
                $row = mysql_fetch_assoc($result);
                $node_ids[] = $row['id'];
            }
            mysql_free_result($result);
        }
        
        return $node_ids;
    }

    /**
     * gets part of sql query for selecting nodes by their name
     *
     * @private
     *
     * @param    $node_ns (string) namespace prefix of node
     * @param    $node_name (string) name of node
     *
     * @return    $name_query (string)
     */
    function _get_name_query($node_ns, $node_name) {
        if ($node_ns == '' && ($node_name == '' || $node_name == '*')) {
            $name_query = '';
        } else if ($node_ns == '*') {
            $name_query = " and name LIKE '%$node_name'";
        } else if ($node_ns != '' && $node_name == '*') {
            $name_query = " and name LIKE '$node_ns:%'";
        } else if ($node_ns != '') {
            $name_query = " and name='$node_ns:$node_name'";
        } else {
            $name_query = " and name='$node_name'";    
        }

        return $name_query;
    }

    /**
     * gets part of sql query for selecting node by their attribute
     *
     * @private
     *
     * @param    $attr_cond (array) every element must have following 
     *            subelements: name, value and operator. 
     *
     * @return    $attr_query (string)
     */
    function _get_attr_query($attr_cond) {
        if (!is_array($attr_cond)) {
            $attr_query = '';
        } else {
            $attr_query = 'and (';
            foreach($attr_cond as $temp_cond) {
                if ($temp_cond['value'] == null) {
                    $attr_query .= " {$temp_cond['operator']} value LIKE '%{$temp_cond['name']}=%'";
                } else {
                    $attr_query .= " {$temp_cond['operator']} value LIKE '%{$temp_cond['name']}=\"" . mysql_escape_string($temp_cond['value']) . "\"%'";
                }
            }
            $attr_query .= ')';
        }

        return $attr_query;
    }
    // }}}
    // {{{ get_node_ids_by_xpath()
    /**
     * gets node_ids by xpath
     *
     * @attention
     *            this supports only a small subset of xpath-queries.
     *            so recheck source before using.
     *
     * @public
     *
     * @param    $doc_id (int) id of document
     * @param    $xpath (string) xpath to target node
     *
     * @return    $nodeids (array) array of found node ids
     *
     * @todo    implement full xpath specifications
     */
    function get_node_ids_by_xpath($doc_id, $xpath) {
        global $log;

        $pName = "(?:([^\/\[\]]*):)?([^\/\[\]]+)";
        $pCondition = "(?:\[(.*?)\])?";

        preg_match_all("/(\/+)$pName$pCondition/", $xpath, $xpath_elements, PREG_SET_ORDER);
        $actual_ids = array($doc_id);

        foreach ($xpath_elements as $level => $element) {
            $fetched_ids = array();

            list(,$divider, $ns, $name, $condition) = $element;
            if ($divider == '/') {
                // {{{ fetch only by name:
                if ($condition == '') {    
                    /*
                     * "... /ns:name ..."
                     */
                    foreach ($actual_ids as $actual_id) {
                        $fetched_ids = array_merge($fetched_ids, $this->get_child_ids_by_name($actual_id, $ns, $name));
                    }
                // }}}
                // {{{ fetch by name and position:
                } else if (preg_match("/^([0-9]+)$/", $condition)) {
                    /*
                     * "... /ns:name[n] ..."
                     */
                    foreach ($actual_ids as $actual_id) {
                        $temp_ids = $this->get_child_ids_by_name($actual_id, $ns, $name);
                        $fetched_ids[] = $temp_ids[((int) $condition) - 1];
                    }
                // }}}
                // {{{fetch by simple attributes:
                } else if (preg_match("/[\w\d@=: _-]*/", $temp_condition = $this->_remove_literal_strings($condition, $strings = array()))) {
                    /*
                     * "... /ns:name[@attr1] ..."
                     * "... /ns:name[@attr1 = 'string1'] ..."
                     * "... /ns:name[@attr1 = 'string1' and/or @attr2 = 'string2'] ..."
                     */
                    $cond_array = $this->_get_condition_attributes($temp_condition, $strings);
                    foreach ($actual_ids as $actual_id) {
                        $fetched_ids = array_merge($fetched_ids, $this->get_child_ids_by_name($actual_id, $ns, $name, $cond_array));
                    }
                // }}}
                } else {
                    $log->add_entry("get_xpath \"$xpath\" for this syntax not yet defined.");
                }
            } elseif ($divider == '//' && $level == 0) {
                // {{{ fetch only by name recursive:
                if ($condition == '') {
                    /*
                     * "//ns:name ..."
                     */
                    $fetched_ids = $this->get_node_ids_by_name($actual_ids[0], $ns, $name);    
                // }}}
                // {{{ fetch by simple attributes:
                } else if (preg_match("/[\w\d@=: _-]*/", $temp_condition = $this->_remove_literal_strings($condition, $strings = array()))) {
                    /*
                     * "//ns:name[@attr1] ..."
                     * "//ns:name[@attr1 = 'string1'] ..."
                     * "//ns:name[@attr1 = 'string1' and/or @attr2 = 'string2'] ..."
                     */
                    $cond_array = $this->_get_condition_attributes($temp_condition, $strings);
                    foreach ($actual_ids as $actual_id) {
                        $fetched_ids = $this->get_node_ids_by_name($actual_ids[0], $ns, $name, $cond_array);
                    }
                // }}}
                } else {
                    $log->add_entry("get_xpath \"$xpath\" for this syntax not yet defined.");
                }
            } else {
                $log->add_entry("get_xpath \"$xpath\" for this syntax not yet defined.");
            }
            
            $actual_ids = $fetched_ids;
        }
        return $fetched_ids;
    }

    /**
     * gets attributes array from xpath-condition\n
     * (... [@this = 'some' and @that = 'some other'])\n
     * can be used with:\n
     *        1. get_child_ids_by_name()
     *        2. get_node_ids_by_name()
     * 
     * @private
     *
     * @param    $condition (string) attribute conditions
     * @param    $strings (array) of literal strings used in condition
     *
     * @return    $attr (array) array of attr-conditions
     */
    function _get_condition_attributes($condition, $strings) {
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
    function _remove_literal_strings($text, &$strings) {
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
    // {{{ get_doc_by_xpath()
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
    function &get_doc_by_xpath($doc_id, $xpath, $add_id_attribute = true) {
        $ids = $this->get_node_ids_by_xpath($doc_id, $xpath);
        if (count($ids > 0)) {
            $val = $this->get_doc_by_id($ids[0], null, $add_id_attribute);
        } else {
            $val = false;
        }
        return $val;
    }
    // }}}
    // {{{ get_doc_by_id()
    /**
     * gets an xml-document-object from specific db-id
     *
     * @public
     *
     * @param    $id (int) db-id of node to get
     * @param    $nodefunc (funcobject) still needed after restructuring ???????
     * @param    $add_id_attribute (bool) true, if you want to add the db-id attributes
     *            to xml-definition, false to remove them.
     * @param    $lock (bool) wether to lock database tablessor not
     */
    function &get_doc_by_id($id, $nodefunc = '', $add_id_attribute = true, $lock = true) {
        global $conf;
        
        $result = db_query(
            "SELECT value 
            FROM $this->cache_table 
            WHERE id='$id' and nodefunc='$nodefunc'"
        );
        if ($result && ($num = mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            mysql_free_result($result);
            
            $xml_doc = domxml_open_mem($row['value']);
        } else {
            $xml_doc  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
            $xml_doc .= "<!DOCTYPE ttdoc [";
            for ($i = 0; $i < count($conf->global_entities); $i++) {
                $xml_doc .= "<!ENTITY {$conf->global_entities[$i]} \"&amp;{$conf->global_entities[$i]};\" >";
            }
            $xml_doc .= "]>";
            
            $this->lock_read();
            
            $result = db_query(
                "SELECT id, name, type, value 
                FROM $this->element_table 
                WHERE id='$id'"
            );
            if ($result && ($num = mysql_num_rows($result)) == 1) {
                $row = mysql_fetch_assoc($result);
                mysql_free_result($result);
                
                //if node is DOCUMENT_NODE
                if ($row['type'] == 'DOCUMENT_NODE') {
                    $result = db_query(
                        "SELECT id, name, type, value 
                        FROM $this->element_table 
                        WHERE id_parent='$id'"
                    );
                    if ($result && ($num = mysql_num_rows($result)) == 1) {
                        $row = mysql_fetch_assoc($result);
                        mysql_free_result($result);
                        
                        $this->_get_node_by_id($xml_doc, $row['id'], $nodefunc, true, $row);    
                        $xml_doc = domxml_open_mem($xml_doc);
                    }
                //if node is ELEMENT_NODE
                } else if ($row['type'] == 'ELEMENT_NODE') {
                    $this->_get_node_by_id($xml_doc, $id, $nodefunc, true, $row);
                    $xml_doc = domxml_open_mem($xml_doc);
                }
            }

            $this->unlock();
            if (is_object($xml_doc) && $xml_doc->document_element() != null) {
                db_query(
                    "DELETE 
                    FROM $this->cache_table 
                    WHERE id='$id' AND nodefunc='$nodefunc'"
                );
                db_query(
                    "INSERT $this->cache_table 
                    SET id='$id', nodefunc='$nodefunc', value='" . mysql_escape_string($xml_doc->dump_mem(false)) . "'"
                );
            }
        }
        
        if (is_object($xml_doc) && $xml_doc->document_element() != null) {
            if (!$add_id_attribute) {
                $this->remove_id_attributes($xml_doc);
            }
            return $xml_doc;
        } else {
            return false;    
        }
    }

    /**
     * gets node definition by its node id
     *
     * @private
     *
     * @param    $xml_doc (string) xml definition to append elements
     * @param    $id (int) db-id of node to add
     * @param    $nodefunc (funcobj) still needed after restructuring ???????
     * @param    $is_root (bool) if true, global namespace-definitions will
     *            be added to node. false, otherwise.
     * @param    $row (array) result of last select
     *
     * @return    $xml_doc (string) xml node definition of node
     */
    function &_get_node_by_id(&$xml_doc, $id, $nodefunc = null, $is_root = false, $row = array()) {
        global $conf;
        
        if (count($row) == 0) {
            $result = db_query(
                "SELECT name, type, value 
                FROM $this->element_table 
                WHERE id='$id'"
            );
            $row = mysql_fetch_assoc($result);
            mysql_free_result($result);
        }
        //get ELMEMENT_NODE
        if ($row['type'] == 'ELEMENT_NODE') {
            //create node
            $name = $row['name'];
            $node_data = "<$name";
            
            //if node is root-element add global namespaces
            if ($is_root) {
                foreach ($this->global_ns as $ns) {
                    $node_data .= " xmlns:{$ns['ns']}=\"{$ns['uri']}\"";
                }
            }
            
            //add attributes to node
            $node_data .= " {$row['value']}";
            
            //add id_attribute to node
            $node_data .= " {$this->id_attribute}=\"$id\">";
            
            if ($nodefunc != null) {
                //create node
                $temp_doc = domxml_new_doc('1.0');
                $node_name = explode(':', $row['name']);
                if (count($node_name) == 1) {
                    $node = $temp_doc->create_element($node_name[0]);
                } else {
                    foreach($conf->ns as $ns) {
                        if ($ns['ns'] == $node_name[0]) {
                            $ns_uri = $ns['uri'];
                        }
                    }
                    $node = $temp_doc->create_element_ns($ns_uri, $node_name[1], $node_name[0]);
                }
            }
            if ($is_root || $nodefunc == null || nodeType::$nodefunc($node)) {
                $xml_doc .= $node_data;
                //add child_nodes
                $result = db_query(
                    "SELECT id, name, type, value 
                    FROM $this->element_table 
                    WHERE id_parent='$id' 
                    ORDER BY pos"
                );
                if ($result && ($num = mysql_num_rows($result)) > 0) {
                    for ($i = 0; $i < $num; $i++) {
                        $row = mysql_fetch_assoc($result);
                        $this->_get_node_by_id($xml_doc, $row['id'], $nodefunc, false, $row);
                    }
                }
                mysql_free_result($result);
                $xml_doc .= "</$name>";
            }
        //get TEXT_NODES
        } else if ($row['type'] == 'TEXT_NODE') {
            //$xml_doc .= $row['value'];
            $xml_doc .= htmlspecialchars($row['value']);
        //get CDATA_SECTION
        } else if ($row['type'] == 'CDATA_SECTION_NODE') {
            //$node = $xml_doc->create_cdata_section($row['value']);
        //get COMMENT_NODE
        } else if ($row['type'] == 'COMMENT_NODE') {
            $xml_doc .= "<!--{$row['value']}-->";
        //get PROCESSING_INSTRUCTION
        } else if ($row['type'] == 'PI_NODE') {
            $xml_doc .= "<?{$row['name']} {$row['value']} ?>";
        //get ENTITY_REF Node
        } else if ($row['type'] == 'ENTITY_REF_NODE') {
            //$node = $xml_doc->create_entity_reference($row['value']);
        }
    }
    // }}}
    // {{{ remove_id_attributes
    /**
     * remove all db-id attributes recursive from nodes
     *
     * @private
     *
     * @param    $node (domxmlnode) node to remove attribute from
     */
    function remove_id_attributes(&$node) {
        if ($node->node_type() == XML_ELEMENT_NODE || $node->node_type() == XML_DOCUMENT_NODE) {
            $xpath_node = project::xpath_new_context($node);
            $xfetch = xpath_eval($xpath_node, ".//@{$this->id_attribute}", $node);
            for ($i = 0; $i < count($xfetch->nodeset); $i++) {
                $xfetch->nodeset[$i]->unlink_node();
            }
        }
    }
    // }}}
    // {{{ get_node_ids_to_keep()
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
    function get_node_ids_to_keep($node) {
        $ids = array();

        $xpath_node = project::xpath_new_context($node);
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
    // {{{ clear_cache()
    /**
     * clears the node-cache
     *
     * @public
     *
     * @param    $changed_ids (array) list of id, that has changed
     *            abd so should be deleted from cache
     * @param    $clearall (bool) if true, all chached documents
     *            were deleted.
     */
    function clear_cache($changed_ids = array(), $clearall = false) {
        if ($clearall) {
            db_query(
                "DELETE 
                FROM $this->cache_table 
                WHERE 1=1"
            );
        } else {
            db_query(
                "DELETE
                FROM $this->cache_table
                WHERE id IN (" . implode(',', $changed_ids)    . ")"
            );
            for ($i = 0; $i < count($changed_ids); $i++) {
                db_query(
                    "DELETE 
                    FROM $this->cache_table 
                    WHERE value LIKE '%{$conf->ns['database']['ns']}:id=\"{$changed_ids[$i]}\"%'"
                );
            }
        }
    }
    // }}}
    // {{{ unlink_node_by_id()
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
     * @param    $lock (bool) wether to lock database tables or not
     * @param    $row (array) result of last deleted row
     *
     * @return    $deleted_ids (array) list of db-ids of deleted nodes
     */
    function unlink_node_by_id($id, $ids_to_keep = array(), $reorder_pos = true, $lock = true, $row = array())  {
        $this->lock_write();
        
        $deleted_ids = array();
        if (count($row) == 0) {
            $result = db_query(
                "SELECT id_parent, pos 
                FROM $this->element_table 
                WHERE id='$id'"
            );
            $row = mysql_fetch_assoc($result);
            mysql_free_result($result);
            $addParentNode = $row['id_parent'];
        } else {
            $addParentNode = NULL;
        }
        if (count($row) != 0) {
            //reorder node positions
            if ($reorder_pos && ($row['id_parent'] != null)) {
                db_query(
                    "UPDATE $this->element_table 
                    SET pos = pos - 1 
                    WHERE id_parent={$row['id_parent']} and pos > {$row['pos']}"
                );
            }
            //unlink child-nodes
            $result = db_query(
                "SELECT id, id_parent, pos 
                FROM $this->element_table 
                WHERE id_parent='$id'"
            );
            if ($result && ($num = mysql_num_rows($result)) > 0) {
                for ($i = 0; $i < $num; $i++) {
                    $row = mysql_fetch_assoc($result);
                    $deleted_ids = array_merge($deleted_ids, $this->unlink_node_by_id($row['id'], $ids_to_keep, false, false, $row));
                }    
            }
            mysql_free_result($result);
            $deleted_ids[] = $id;
            
            if ($reorder_pos) {
                db_query(
                    "UPDATE $this->element_table
                    SET type='DELETED'
                    WHERE id IN (" . implode(',', $deleted_ids)    . ")"
                );
                $this->clear_cache($deleted_ids);
            }
        }
        $this->unlock();

        if ($addParentNode != NULL) {
            $deleted_ids[] = $addParentNode;
        }
        
        return $deleted_ids;
    }
    // }}}
    // {{{ clear_deleted_nodes()
    /**
     * clears data of deleted nodes
     *
     * @public
     */
    function clear_deleted_nodes() {
        db_query(
            "UPDATE $this->element_table
            SET id_parent=NULL, id_doc=NULL, pos=NULL, name=NULL, value=''
            WHERE type='DELETED'"
        );
    }
    // }}}
    // {{{ get_node_id()
    /**
     * gets node db-id from db-id attribute
     *
     * @public
     *
     * @param    $node (domxmlnode) node to get id from
     *
     * @return    $db_id (int)
     */
    function get_node_id(&$node) {
        global $log;

        $db_id = null;
        if (method_exists($node, 'node_type') && $node->node_type() == XML_ELEMENT_NODE) {
            $attribs = $node->attributes();
            for ($i = 0; $i < count($attribs); $i++) {
                if (($attribs[$i]->prefix() . ':' . $attribs[$i]->name()) == $this->id_attribute) {
                    $db_id = $attribs[$i]->value();
                } else if ($attribs[$i]->name() == $this->id_attribute) {
                    $db_id = $attribs[$i]->value();
                }
            }
        }
        
        return $db_id;
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
     * @param    $lock (bool) wether to lock database tables or not
     * @param    $stripwhitespace (bool) wether to strip whitespace
     *            from textnodes or not.
     */
    function save_node(&$node, $target_id = null, $target_pos = -1, $lock = true, $stripwhitespace = true, $target_doc = null) {
        global $conf, $log;
        
        $this->lock_write();
        
        //get all nodes in array
        $this->get_nodearray_for_saving($node_array = array(), $node);

        if ($node_array[0]['id'] != null && $target_id === null) {
            //set target_id/pos/doc
            $target_doc = $this->get_doc_id_by_id($node_array[0]['id']);
            $target_id = $this->get_parent_id_by_id($node_array[0]['id']);
            $target_pos = $this->get_pos_by_id($node_array[0]['id']);

            //unlink old node
            $this->unlink_node_by_id($node_array[0]['id'], array(), true, false);
            $this->clear_cache(array($target_id));
        } else if ($target_id !== null) {
            $target_doc = $this->get_doc_id_by_id($target_id);
            //unlink child nodes, if target is document
            if ($target_id == $target_doc) {
                $result = db_query(
                    "SELECT id 
                    FROM $this->element_table 
                    WHERE id_parent='$target_doc'"
                );
                if ($result && ($num = mysql_num_rows($result)) > 0) {
                    $row = mysql_fetch_assoc($result);
                    $this->unlink_node_by_id($row['id'], array(), true, false);
                }
                mysql_free_result($result);
            }
            $this->clear_cache(array($target_id));
            
            //set target_id/pos/doc
            $result = db_query(
                "SELECT IFNULL(MAX(pos), -1) + 1 AS pos 
                FROM $this->element_table 
                WHERE id_parent='$target_id'"
            );
            if ($result && ($num = mysql_num_rows($result)) == 1) {
                $row = mysql_fetch_assoc($result);
                if ($target_pos > $row['pos'] || $target_pos == -1) {
                    $target_pos = $row['pos'];
                }
            } else {
                $target_pos = 0;
            }
            mysql_free_result($result);
            
            //resort
            db_query(
                "UPDATE $this->element_table 
                SET pos=pos+1 
                WHERE id_parent='$target_id' and pos>='$target_pos'"
            );
        }
        
        $this->get_free_ids(count($node_array));
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
        
        //correct changed references
        $changed_ref_ids = array();
        for ($i = 1; $i < count($node_array); $i++) {
            if ($node_array[$i]['node']->node_type() == XML_ELEMENT_NODE && $node_array[$i]['id'] != $node_array[$i]['id_old']) {
                $changed_ref_ids[$node_array[$i]['id_old']] = $node_array[$i]['id'];
            }
        }
        $xpath_node = project::xpath_new_context($node);
        $xfetch = xpath_eval($xpath_node, ".//@{$this->id_ref_attribute}", $node);
        for ($i = 0; $i < count($xfetch->nodeset); $i++) {
            if(isset($changed_ref_ids[$xfetch->nodeset[$i]->node_value()])) {
                $parent_node = $xfetch->nodeset[$i]->parent_node();
                //echo($parent_node->get_attribute('ref') . " -> ");
                $this->set_attribute_ns($parent_node, $this->dbxml_ns_uri, $this->dbxml_ns, 'ref', $changed_ref_ids[$xfetch->nodeset[$i]->node_value()]);
                //echo($parent_node->get_attribute('ref') . "<br />");
            }
        }
        
        //save root node
        $node_array[0]['id'] = $this->save_node_to_db($node_array[0]['node'], $node_array[0]['id'], $target_id, $target_pos, $target_doc, true);
        
        //save element nodes
        for ($i = 1; $i < count($node_array); $i++) {
            if ($node_array[$i]['node']->node_type() == XML_ELEMENT_NODE) {
                $node_array[$i]['id'] = $this->save_node_to_db($node_array[$i]['node'], $node_array[$i]['id'], $node_array[$node_array[$i]['parent_index']]['id'], $node_array[$i]['pos'], $target_doc);
            }
        }

        //save other nodes
        for ($i = 1; $i < count($node_array); $i++) {
            if ($node_array[$i]['node']->node_type() != XML_ELEMENT_NODE) {
                $node_array[$i]['id'] = $this->save_node_to_db($node_array[$i]['node'], $node_array[$i]['id'], $node_array[$node_array[$i]['parent_index']]['id'], $node_array[$i]['pos'], $target_doc);
            }
        }
        $this->clear_deleted_nodes();
        
        $this->unlock();
        
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
    function save_node_to_db(&$node, $id, $target_id, $target_pos, $target_doc, $increase_pos = false) {
        if ($id === null) {
            $id_query = 'NULL';
        } else {
            $id_query = $id;
        }
        if ($node->node_type() == XML_ELEMENT_NODE) {
            if ($node->prefix() != '') {
                $name_query = $node->prefix() . ':' . $node->tagname();
            } else {
                $name_query = $node->tagname();
            }
            $attribs = $node->attributes();
            $attr_str = '';
            for ($i = 0; $i < count($attribs); $i++) {
                if (($attribs[$i]->prefix() . ':' . $attribs[$i]->name()) != $this->id_attribute && $attribs[$i]->name() != $this->id_attribute) {
                    if ($pos = strpos($attribs[$i]->name(), ':')) {
                        $temp_array = explode(':', $attribs[$i]->name());
                        $attrib_ns = $temp_array[0] . ':';
                        $attrib_name = $temp_array[1];
                    } else {
                        $attrib_ns = ($attribs[$i]->prefix() == '') ? '' : $attribs[$i]->prefix() . ':';
                        $attrib_name = $attribs[$i]->name();
                    }
                    $attrib_value = $attribs[$i]->value();
                    
                    $attr_str .= $attrib_ns . $attrib_name . "=\"" . htmlspecialchars($attrib_value) . "\" ";
                }
            }
            if ($increase_pos) {
                db_query(
                    "UPDATE $this->element_table 
                    SET pos=pos+1 
                    WHERE id_parent='$target_id' and pos >='$target_pos'"
                );
            }
            db_query(
                "REPLACE $this->element_table 
                SET id='$id_query', id_parent='$target_id', id_doc='$target_doc', pos='$target_pos', name='$name_query', value='" . mysql_escape_string($attr_str) . "', type='ELEMENT_NODE'"
            );
            if ($id === null) {
                $id = mysql_insert_id();
                $node->set_attribute($this->id_attribute, $id);
            } else if ($this->get_node_id($node) == null) {
                $node->set_attribute($this->id_attribute, $id);
            }
        } else {
            if ($node->node_type() == XML_TEXT_NODE) {
                $node_type = 'TEXT_NODE';
                $node_data = $node->get_content();
            } else if ($node->node_type() == XML_COMMENT_NODE) {
                $node_type = 'COMMENT_NODE';
                $node_data = $node->get_content();
            } else if ($node->node_type() == XML_ENTITY_REF_NODE) {
                $node_type = 'ENTITY_REF_NODE';
                $node_data = $node->node_name();
            }
            
            db_query(
                "REPLACE $this->element_table 
                SET id='$id_query', id_parent='$target_id', id_doc='$target_doc', pos='$target_pos', name=NULL, value='" . mysql_escape_string($node_data) . "', type='$node_type'"
            );
            if ($id === null) {
                $id = mysql_insert_id();
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
    function get_nodearray_for_saving(&$node_array, &$node, $parent_index = null, $pos = 0, $stripwhitespace = true) {
        $type = $node->node_type();
        //is DOCUMENT_NODE
        if ($type == XML_DOCUMENT_NODE) {
            $root_node = $node->document_element();
            $this->get_nodearray_for_saving($node_array, $root_node, $parent_index, $pos, $stripwhitespace);
        //is ELEMENT_NODE
        } elseif ($type == XML_ELEMENT_NODE) {
            $id = $this->get_node_id($node);
            $node_array[] = array(
                'id' => $id, 
                'id_old' => $id, 
                'parent_index' => $parent_index,
                'pos' => $pos,
                'node' => $node,
            );
            $parent_index = count($node_array) - 1;
            
            $node_name = (($node->prefix() != '') ? $node->prefix() . ':' : '') . $node->node_name();
            if (!$stripwhitespace || in_array($node_name, $this->dont_strip_white)) {
                $stripwhitespace = false;
            }
            $tmp_node = $node->first_child();
            $i = 0;
            while ($tmp_node != null) {
                if ($tmp_node->node_type() != XML_TEXT_NODE || (!$stripwhitespace || trim($tmp_node->get_content()) != '')) {
                    $this->get_nodearray_for_saving($node_array, $tmp_node, $parent_index, $i, $stripwhitespace);
                    $i++;
                }
                $tmp_node = $tmp_node->next_sibling();    
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
    // {{{ replace_node()
    /**
     * replaces a node in database
     *
     * @public
     *
     * @param    $node (domxmlnode) node to save
     * @param    $id_to_replace (int) db-id of node to be replaced
     * @param    $doc_id (int) document db-id
     * @param    $lock (bool) wether to lock database tables or not
     *
     * @return    $changed_ids (array) list of db-ids that has been changed
     */
    function replace_node(&$node, $id_to_replace, $doc_id, $lock = true) {
        $this->lock_write();
        
        $target_id = $this->get_parent_id_by_id($id_to_replace);
        $target_pos = $this->get_pos_by_id($id_to_replace);
        
        $changed_ids = $this->unlink_node_by_id($id_to_replace, array(), true, false);
        $changed_ids[] = $this->save_node($node, $target_id, $target_pos, false, true, $doc_id);
        $changed_ids[] = $target_id;
        $this->clear_deleted_nodes();
            
        $this->unlock();
        
        return $changed_ids;
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
    function set_attribute($node_id, $attr_ns, $attr_name, $attr_value) {
        $this->lock_write();
        
        $changed = false;
        if ($attr_ns != NULL && $attr_ns != '') {
            $query = $attr_ns . ':' . $attr_name;    
        } else {
            $query = $attr_name;
        }
        
        $attr_str = '';
        $result = db_query(
            "SELECT value 
            FROM $this->element_table 
            WHERE id='$node_id'"
        );
        if ($result && ($num = mysql_num_rows($result)) == 1) {
            $row = mysql_fetch_assoc($result);
            $attributes = preg_split("/(=\"|\"$|\" )/", $row['value']);
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
            db_query(
                "UPDATE $this->element_table 
                SET value='" . mysql_escape_string($attr_str) . "' 
                WHERE id='$node_id'"
            );
            $this->clear_cache(array($node_id));
        }
        mysql_free_result($result);
        
        $this->unlock();
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
    function get_attribute($node_id, $attr_ns, $attr_name) {
        global $log;

        $val = null;
        if ($attr_ns != NULL && $attr_ns != '') {
            $query = $attr_ns . ':' . $attr_name;
        } else {
            $query = $attr_name;    
        }
        $attributes = $this->get_attributes($node_id);
        
        return $attributes[$query];
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
    function get_attributes($node_id) {
        $attributes = array();

        $result = db_query(
            "SELECT value 
            FROM $this->element_table 
            WHERE id='$node_id' and type='ELEMENT_NODE'"
        );
        if ($result && ($num = mysql_num_rows($result)) == 1) {
            $row = mysql_fetch_assoc($result);
            $matches = preg_split("/(=\"|\"$|\" )/", $row["value"]);
            $matches = array_chunk($matches, 2);
            foreach($matches as $match) {
                if ($match[0] != '') {
                    $attributes[$match[0]] = $match[1];
                }
            }
        }
        mysql_free_result($result);
        
        return $attributes;
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
    function move_node($node_id, $target_id, $target_pos) {
        $this->lock_write();
        
        $node_parent_id = $this->get_parent_id_by_id($node_id);
        $node_pos = $this->get_pos_by_id($node_id);
        $target_doc_id = $this->get_doc_id_by_id($target_id);
        
        if ($target_id == $node_parent_id && $target_pos > $node_pos) {
            $target_pos--;
        }
        
        if ($target_id != $node_parent_id || $target_pos != $node_pos) {
            db_query(
                "UPDATE $this->element_table 
                SET id_doc=NULL, id_parent=NULL, pos=NULL 
                WHERE id='$node_id'"
            );
            db_query(
                "UPDATE $this->element_table 
                SET pos=pos-1 
                WHERE id_parent='$node_parent_id' and pos>$node_pos"
            );

            db_query(
                "UPDATE $this->element_table 
                SET pos=pos+1 
                WHERE id_parent='$target_id' and pos>=$target_pos"
            );
            db_query(
                "UPDATE $this->element_table 
                SET id_doc='$target_doc_id', id_parent='$target_id', pos='$target_pos' 
                WHERE id='$node_id'"
            );
            
            $this->clear_cache(array($node_id, $target_id));
        }
        
        $this->unlock();
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
    function move_node_in($node_id, $target_id) {
        $result = db_query(
            "SELECT IFNULL(MAX(pos), -1) + 1 AS newpos 
            FROM $this->element_table 
            WHERE id_parent='$target_id'"
        );
        $row = mysql_fetch_assoc($result);
        mysql_free_result($result);
        
        $this->move_node($node_id, $target_id, $row['newpos']);
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
    function move_node_before($node_id, $target_id) {
        $target_parent_id = $this->get_parent_id_by_id($target_id);
        $target_pos = $this->get_pos_by_id($target_id);
        
        $this->move_node($node_id, $target_parent_id, $target_pos);
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
    function move_node_after($node_id, $target_id) {
        $target_parent_id = $this->get_parent_id_by_id($target_id);
        $target_pos = $this->get_pos_by_id($target_id) + 1;
        
        $this->move_node($node_id, $target_parent_id, $target_pos);
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
    function copy_node($node_id, $target_id, $target_pos) {
        $xml_doc = $this->get_doc_by_id($node_id, null, false, true);
        $root_node = $xml_doc->document_element();
        
        $this->clear_cache(array($target_id));
        
        return $this->save_node($root_node, $target_id, $target_pos, true, false);
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
    function copy_node_in($node_id, $target_id) {
        $result = db_query(
            "SELECT IFNULL(MAX(pos), -1) + 1 AS newpos 
            FROM $this->element_table 
            WHERE id_parent='$target_id'"
        );
        $row = mysql_fetch_assoc($result);
        mysql_free_result($result);
        
        return $this->copy_node($node_id, $target_id, $row['newpos']);
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
    function copy_node_before($node_id, $target_id) {
        $target_parent_id = $this->get_parent_id_by_id($target_id);
        $target_pos = $this->get_pos_by_id($target_id);
        
        return $this->copy_node($node_id, $target_parent_id, $target_pos);
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
    function copy_node_after($node_id, $target_id) {
        $target_parent_id = $this->get_parent_id_by_id($target_id);
        $target_pos = $this->get_pos_by_id($target_id) + 1;
        
        return $this->copy_node($node_id, $target_parent_id, $target_pos);
    }
    // }}}
    // {{{ optimize_database()
    /**
     * optimizes xml_db tables
     *
     * @public
     */
    function optimize_database() {
        db_query(
            "OPTIMIZE TABLE 
            $this->element_table"
        );
        db_query(
            "OPTIMIZE TABLE 
            $this->cache_table"
        );
    }
    // }}}
    // {{{ set_attribute_ns()
    /**
     * set attribute with namespace
     *
     * @public
     *
     * @param    $node (domxmlnode) node
     * @param    $uri (string) namespace uri
     * @param    $ns (string) namespace prefix
     * @param    $name (string) name
     * @param    $value (string) value to set
     */
    function set_attribute_ns(&$node, $uri = "", $ns = "", $name = "", $value = "") {
        if ($uri != "" && $ns != "") {
            if ($temp_node = $node->get_attribute_node($name)) {
                $temp_node->unlink_node();
            }
            $attr_node = $node->set_attribute($name, $value);
            $attr_node->set_namespace($uri, $ns);
        } else {
            $attr_node = $node->set_attribute($name, $value);
        }
    }    
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
