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
 * @todo add test if doc_id is not an integer and not a valid document for public methods
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
    protected $doc_ids = array();

    protected $table_docs;
    protected $table_xml;

    private $doctypeHandlers = array();
    // }}}

    /* public */
    // {{{ constructor()
    public function __construct($tableprefix, $pdo, $cache, $dont_strip_white = array()) {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_NATURAL);

        $this->cache = $cache;

        $this->db_ns = new xmlns("db", "http://cms.depagecms.net/ns/database");
        $this->dont_strip_white = $dont_strip_white;

        $this->setTables($tableprefix);
    }
    // }}}
    // {{{ setTables
    protected function setTables($tableprefix) {
        $this->table_docs = $tableprefix . "_xmldocs";
        $this->table_xml = $tableprefix . "_xmltree";
    }
    // }}}

    // {{{ getDocList()
    /**
     * gets available documents in database
     *
     * @return    $docs (array) the key is the name of the document,
     *            the value is the document db-id.
     */
    public function getDocList($name = "") {
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
            "SELECT 
                docs.name, 
                docs.name AS name, 
                docs.id AS id, 
                docs.rootid AS rootid, 
                docs.type AS type
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
    // {{{ docExists()
    /**
     * gets the doc-id of a xml-document by name or id and checks if the
     * document exists
     *
     * @param     $doc_id_or_name (mixed) id or name of the document
     * @return    (int) id of the document or false when document does not exist
     */
    public function docExists($doc_id_or_name) {
        if (!isset($this->doc_ids[$doc_id_or_name])) {
            if ((int) $doc_id_or_name > 0) {
                // is already a doc-id
                $query = $this->pdo->prepare(
                    "SELECT docs.name AS docname
                    FROM {$this->table_docs} AS docs
                    WHERE docs.id = :doc_id"
                );
                $query->execute(array(
                    'doc_id' => (int) $doc_id_or_name,
                ));
                $result = $query->fetchObject();

                if ($result === false) {
                    // document does not exist
                    // @todo thow exception?
                    return false;
                }

                $name = $result->docname;
                $id = $doc_id_or_name;
            } else {
                // statically cache doc-id
                $doc_list = $this->getDocList($doc_id_or_name);

                if (!isset($doc_list[$doc_id_or_name])) {
                    // document does not exist
                    return false;
                }

                $name = $doc_id_or_name;
                $id = (int) $doc_list[$name]->id;
            }

            $this->doc_ids[$name] = $id;
            $this->doc_ids[$id] = $id;
        }

        return $this->doc_ids[$doc_id_or_name];
    }
    // }}}
    // {{{ getDocInfo()
    /**
     * gets info about a document by doc_id
     *
     * @return    $docs (array) the key is the name of the document,
     *            the value is the document db-id.
     */
    public function getDocInfo($doc_id_or_name) {
        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
            $query = $this->pdo->prepare(
                "SELECT 
                    docs.id AS id, 
                    docs.name AS name, 
                    docs.rootid AS rootid, 
                    docs.type AS type, 
                    docs.ns AS namespaces
                FROM {$this->table_docs} AS docs
                WHERE docs.id = :doc_id
                LIMIT 1"
            );
            $query->execute(array(
                'doc_id' => $doc_id,
            ));

            return $query->fetchObject();
        } else {
            return false;
        }
    }
    // }}}
    // {{{ getDoc()
    public function getDoc($doc_id_or_name, $add_id_attribute = true) {
        $this->beginTransaction();

        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
            $doc = $this->getDocInfo($doc_id);
            $xml = $this->getSubdocByNodeId($doc->id, $doc->rootid, $add_id_attribute);
        } else {
            return false;
        }

        $this->endTransaction();

        return $xml;
    }
    // }}}
    // {{{ getSubdocByNodeId()
    /**
     * gets an xml-document-object from specific db-id
     *
     * @param    $id (int) db-id of node to get
     * @param    $add_id_attribute (bool) true, if you want to add the db-id attributes
     *            to xml-definition, false to remove them.
     * @param    $level (int) number of recursive getChildnodesByParentId calls. how deep to traverse the tree.
     */
    public function getSubdocByNodeId($doc_id_or_name, $id, $add_id_attribute = true, $level = PHP_INT_MAX) {
        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
            $identifier = "{$this->table_docs}/d{$doc_id}/{$id}.xml";

            $xml_str = $this->cache->get($identifier);
            if ($xml_str !== false) {
                // read from cache
                $xml_doc = new \DOMDocument();
                $xml_doc->loadXML($xml_str);
            } else {
                // read from database
                $this->beginTransaction();

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
                $this->namespaces = $this->extractNamespaces($this->namespace_string);
                $this->namespaces[] = $this->db_ns;

                $query = $this->pdo->prepare(
                    "SELECT 
                        xml.id AS id, 
                        xml.name AS name, 
                        xml.type AS type, 
                        xml.value AS value
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
                    $xml_str .= $this->getChildnodesByParentId($doc_id, $row->id, $level);

                    $xml_str .= "</{$row->name}>";

                } else {
                    throw new xmldbException("This node is no ELEMENT_NODE or node does not exist");
                }

                $xml_doc = new \DOMDocument();
                $xml_doc->loadXML($xml_str);

                $this->endTransaction();

                // add xml to xml-cache
                if (is_object($xml_doc) && $xml_doc->documentElement != null) {
                    $this->cache->set($identifier, $xml_doc->saveXML());
                }
            }
        } else {
            $xml_doc = false;
        }

        if (is_a($xml_doc, "DOMDocument") && $xml_doc->documentElement != null) {
            if (!$add_id_attribute) {
                $this->removeIdAttr($xml_doc);
            }
            return $xml_doc;
        } else {
            return false;    
        }
    }
    // }}}
    // {{{ saveDoc()
    public function saveDoc($doc_id_or_name, $xml) {
        if (!is_object($xml) || !(get_class($xml) == 'DOMDocument') || is_null($xml->documentElement)) {
            throw new xmldbException("This document is not a valid XML-Document");
        }

        $this->beginTransaction();

        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
            $doc_info = $this->getDocInfo($doc_id);

            $query = $this->pdo->prepare(
                "DELETE FROM {$this->table_xml}
                WHERE id_doc = :doc_id"
            );
            $query->execute(array(
                'doc_id' => $doc_id,
            ));

            $this->clearCache($doc_id);
        } else {
            if (!is_string($doc_id_or_name)) {
                throw new xmldbException("You have to give a valid name to save a new document.");
            }
            $query = $this->pdo->prepare(
                "INSERT {$this->table_docs} SET 
                    name = :name"
            );
            $query->execute(array(
                'name' => $doc_id_or_name,
            ));
            $doc_info = new \stdClass();
            $doc_info->id = $this->pdo->lastInsertId();
            $doc_info->name = $doc_id_or_name;
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

        $doc_info->rootid = $this->saveNode($doc_info->id, $xml);
        $query = $this->pdo->prepare(
            "UPDATE {$this->table_docs}
            SET 
                rootid = :rootid, 
                ns = :ns,
                entities=''
            WHERE id = :doc_id"
        );
        $query->execute(array(
            'rootid' => $doc_info->rootid,
            'ns' => $namespaces,
            'doc_id' => $doc_info->id,
        ));


        $this->endTransaction();

        return $doc_info->id;
    }
    // }}}
    // {{{ removeDoc()
    public function removeDoc($doc_id_or_name) {
        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
            $query = $this->pdo->prepare(
                "DELETE
                FROM {$this->table_docs}
                WHERE id = :doc_id"
            );
            $query->execute(array(
                'doc_id' => $doc_id,
            ));
            $this->clearCache($doc_id);

            return true;
        } else {
            return false;
        }
    }
    // }}}

    // {{{ getSubDocByXpath()
    /**
     * gets document by xpath. if xpath directs to more than
     * one node, only the first node will be returned.
     *
     * @param    $doc_id (int) id of document
     * @param    $xpath (string) xpath to target node
     * @param    $add_id_attribute (bool) whether to add db:id attribute or not
     *
     * @return    $doc (domxmlobject)
     */
    public function getSubDocByXpath($doc_id_or_name, $xpath, $add_id_attribute = true) {
        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
            $ids = $this->getNodeIdsByXpath($doc_id, $xpath);
            if (count($ids) > 0) {
                return $this->getSubdocByNodeId($doc_id, $ids[0], $add_id_attribute);
            }
        }

        return false;
    }
    // }}}
    // {{{ unlinkNode()
    public function unlinkNode($doc_id_or_name, $node_id) {
        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false && $this->getDoctypeHandler($doc_id)->isAllowedUnlink($node_id)) {
            return $this->unlinkNodeById($doc_id, $node_id);
        } else {
            return false;
        }
    }
    // }}}
    // {{{ addNode()
    public function addNode($doc_id_or_name, $node, $target_id, $target_pos) {
        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false && $this->getDoctypeHandler($doc_id)->isAllowedAdd($node, $target_id)) {
            return $this->saveNode($doc_id, $node, $target_id, $target_pos, true);
        } else {
            return false;
        }
    }
    // }}}
    // {{{ replaceNode()
    /**
     * replaces a node in database
     *
     * @param    $node (domxmlnode) node to save
     * @param    $id_to_replace (int) db-id of node to be replaced
     * @param    $doc_id (int) document db-id
     *
     * @return    $changed_ids (array) list of db-ids that has been changed
     */
    public function replaceNode($doc_id_or_name, $node, $id_to_replace) {
        $this->beginTransaction();

        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
            $target_id = $this->getParentIdByNodeId($doc_id, $id_to_replace);
            $target_pos = $this->getPosByNodeId($doc_id, $id_to_replace);
            
            $this->unlinkNodeById($doc_id, $id_to_replace, array(), true);

            $changed_ids = array();
            $changed_ids[] = $this->saveNode($doc_id, $node, $target_id, $target_pos, true);
            $changed_ids[] = $target_id;
        } else {
            $changed_ids = false;
        }
            
        $this->endTransaction();
        
        return $changed_ids;
    }
    // }}}
    
    // {{{ moveNodeIn
    /**
     * moves node to another node (append child)
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     */
    public function moveNodeIn($doc_id_or_name, $node_id, $target_id) {
        $this->beginTransaction();

        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
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
            
            $success = $this->moveNode($doc_id, $node_id, $target_id, $result->newpos);
        } else {
            $success = false;
        }

        $this->endTransaction();

        return $success;
    }
    // }}}
    // {{{ moveNodeBefore()
    /**
     * moves node before another node (insert before)
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     */
    public function moveNodeBefore($doc_id_or_name, $node_id, $target_id) {
        $this->beginTransaction();

        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
            $target_parent_id = $this->getParentIdByNodeId($doc_id, $target_id);
            $target_pos = $this->getPosByNodeId($doc_id, $target_id);
            
            $success = $this->moveNode($doc_id, $node_id, $target_parent_id, $target_pos);
        } else {
            $success = false;
        }

        $this->endTransaction();

        return $success;
    }
    // }}}
    // {{{ moveNodeAfter()
    /**
     * moves node after another node
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     */
    public function moveNodeAfter($doc_id_or_name, $node_id, $target_id) {
        $this->beginTransaction();

        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
            $target_parent_id = $this->getParentIdByNodeId($doc_id, $target_id);
            $target_pos = $this->getPosByNodeId($doc_id, $target_id) + 1;
            
            $success = $this->moveNode($doc_id, $node_id, $target_parent_id, $target_pos);
        } else {
            $success = false;
        }

        $this->endTransaction();

        return $success;
    }
    // }}}
    // {{{ moveNode()
    /**
     * moves node in database
     *
     * @public
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     * @param    $target_pos (int) position to move to
     */
    public function moveNode($doc_id_or_name, $node_id, $target_id, $target_pos) {
        $this->beginTransaction();

        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false && $this->getDoctypeHandler($doc_id)->isAllowedMove($node_id, $target_id)) {
            $node_parent_id = $this->getParentIdByNodeId($doc_id, $node_id);
            $node_pos = $this->getPosByNodeId($doc_id, $node_id);
            
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
                
                $this->clearCache($doc_id);
            }
            $success = true;
        } else {
            $success = false;
        }
        
        $this->endTransaction();

        return $success;
    }
    // }}}

    // {{{ copyNodeIn()
    /**
     * copy node to another node
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     */
    public function copyNodeIn($doc_id_or_name, $node_id, $target_id) {
        $this->beginTransaction();

        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
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

            $success = $this->copyNode($doc_id, $node_id, $target_id, $result->newpos);

            $this->endTransaction();
        } else {
            $success = false;
        }

        return $success;
    }
    // }}}
    // {{{  copyNodeBefore()
    /**
     * copy node before another node
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     */
    public function copyNodeBefore($doc_id_or_name, $node_id, $target_id) {
        $this->beginTransaction();

        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
            $target_parent_id = $this->getParentIdByNodeId($doc_id, $target_id);
            $target_pos = $this->getPosByNodeId($doc_id, $target_id);
            
            $success = $this->copyNode($doc_id, $node_id, $target_parent_id, $target_pos);
        } else {
            $success = false;
        }

        $this->endTransaction();

        return $success;
    }
    // }}}
    // {{{ copyNodeAfter()
    /**
     * copy node after another node
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     */
    public function copyNodeAfter($doc_id_or_name, $node_id, $target_id) {
        $this->beginTransaction();

        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
            $target_parent_id = $this->getParentIdByNodeId($doc_id, $target_id);
            $target_pos = $this->getPosByNodeId($doc_id, $target_id) + 1;
            
            $success = $this->copyNode($doc_id, $node_id, $target_parent_id, $target_pos);
        } else {
            $success = false;
        }

        $this->endTransaction();

        return $success;
    }
    // }}}
    // {{{ copyNode()
    /**
     * copy node in database
     *
     * @public
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     * @param    $target_pos (int) pos to copy to
     */
    public function copyNode($doc_id_or_name, $node_id, $target_id, $target_pos) {
        $doc_id = $this->docExists($doc_id_or_name);
        
        if ($doc_id !== false && $this->getDoctypeHandler($doc_id)->isAllowedMove($node_id, $target_id)) {
            $xml_doc = $this->getSubdocByNodeId($doc_id, $node_id, false);
            $root_node = $xml_doc;
            
            $this->clearCache($doc_id);
            
            return $this->saveNode($doc_id, $root_node, $target_id, $target_pos, false);
        } else {
            return false;
        }
    }
    // }}}
    
    // {{{ build_node()
    public function build_node($doc_id, $name, $attributes) {
        //@todo dont build node directly but get from templates according to document type
        $doc_info = $this->getNamespacesAndEntities($doc_id);
        $xml = "<$name {$doc_info->namespaces}";
        foreach ($attributes as $attr => $value) {
            $xml .= " $attr=\"$value\"";
        }
        $xml .= "/>";

        $doc = new \DOMDocument;
        $doc->loadXML($xml);

        return $doc->documentElement;
    }
    // }}}
    
    // {{{ setAttribute()
    /**
     * sets attribute of node
     *
     * @param    $node_id (int) db-id of node to set attribute
     * @param    $attr_ns (string) namespace prefix of attribute
     * @param    $attr_name (string) name of attribute
     * @param    $attr_value (string) new value of attribute
     */
    public function setAttribute($doc_id_or_name, $node_id, $attr_name, $attr_value) {
        $success = false;

        $this->beginTransaction();
        
        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
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
                $this->clearCache($doc_id);

                $success = true;
            }
        }
        
        $this->endTransaction();

        return $success;
    }
    // }}}
    // {{{ getAttribute()
    /**
     * gets attribute of node
     *
     * @param    $node_id (int) db-id of node
     * @param    $attr_ns (string) namespace prefix of attribute
     * @param    $attr_name (string) name of attribute
     *
     * @return    $val (string) value
     */
    public function getAttribute($doc_id_or_name, $node_id, $attr_name) {
        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
            $attributes = $this->getAttributes($doc_id, $node_id);

            if (isset($attributes[$attr_name])) {
                return $attributes[$attr_name];
            }
        }

        return false;
    }
    // }}}
    // {{{ getAttributes()
    /**
     * gets all attributes of a node by id
     *
     * @param    $node_id (int) db-id of node
     *
     * @return    $attributes (array) array of attributes
     */
    public function getAttributes($doc_id_or_name, $node_id) {
        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
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
        } else {
            return false;
        }
    }
    // }}}
    
    // {{{ getParentIdByNodeId
    /**
     * gets parent db-id by one of its child_nodes-id
     *
     * @param    $id (int) node db-id
     *
     * @return    $parent_id (id) db-id of parent node, false, if
     *            node doesn't exist.
     */
    public function getParentIdByNodeId($doc_id_or_name, $id) {
        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
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
            }
        }
        return false;
    }
    // }}}
    // {{{ getNodeNameByNodeId
    /**
     * gets node_name by node db-id
     *
     * @param    $id (int) node db-id
     *
     * @return    $node_name (string) name of node, false, if
     *            node doesn't exist.
     */
    public function getNodeNameByNodeId($doc_id_or_name, $id) {
        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
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
            }
        }
        return false;
    }
    // }}}

    // {{{ getPermissions()
    public function getPermissions($doc_id_or_name) {
        // @todo get this from document type
        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
            // @todo get from doc type
            $permissions = new permissions();

            return $permissions;
        } else {
            return false;
        }
    }
    // }}}

    /* private */
    // {{{ getDoctypeHandler()
    public function getDoctypeHandler($doc_id) {
        if (!isset($this->doctypeHandlers[$doc_id])) {
            $className = $this->getDocInfo($doc_id)->type;

            if (empty($className)) {
                $handler = new xmldoctypes\base($this, $doc_id);
            } else {
                $className = "\\" . $className;
                $handler = new $className($this, $doc_id);
            }

            $this->doctypeHandlers[$doc_id] = $handler;
        }

        return $this->doctypeHandlers[$doc_id];
    }
    // }}}
    
    // {{{ beginTransaction()
    private function beginTransaction() {
        if ($this->transaction == 0) {
            $this->pdo->beginTransaction();
        }
        $this->transaction++;
    }
    // }}}
    // {{{ endTransaction()
    private function endTransaction() {
        $this->transaction--;
        if ($this->transaction == 0) {
            $this->pdo->commit();
        }
    }
    // }}}

    // {{{ getFreeNodeIds()
    /**
     * gets unused db-node-ids for saving nodes
     *
     * @param    $needed (int) mininum number of ids, that are requested
     */
    private function getFreeNodeIds($needed = 1) {
        $num = 0;
        
        $this->free_element_ids = array();
        $query = $this->pdo->prepare(
            "SELECT row AS id FROM
                (SELECT 
                    @row := @row + 1 as row, xml.id 
                FROM 
                    {$this->table_xml} xml, 
                    (SELECT @row := 0) r 
                WHERE @row <> id 
                ORDER BY xml.id) AS seq
            WHERE NOT EXISTS (
                SELECT  1
                FROM {$this->table_xml} xml
                WHERE xml.id = row
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
    
    // {{{ getPosByNodeId
    /**
     * gets node position in its parents childlist by node db-id.
     *
     * @param    $id (int) node db-id
     *
     * @return    $pos (int) position in node parents childlist
     */
    private function getPosByNodeId($doc_id, $id) {
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
    // {{{ getNodeIdsByName()
    /**
     * gets node-ids by name from specific document
     *
     * @param    $doc_id (int) document db-id
     * @param    $node_ns (string) namespace-prefix
     * @param    $node_name (string) nodename
     *
     * @return    $node_ids (array) db-ids of nodes
     */
    private function getNodeIdsByName($doc_id, $node_ns = '', $node_name = '', $attr_cond = null) {
        $node_ids = array();

        list($name_query, $name_param) = $this->getNameQuery($node_ns, $node_name);
        list($attr_query, $attr_param) = $this->getAttrQuery($attr_cond);
        
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
    // {{{ getChildIdsByName()
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
    private function getChildIdsByName($doc_id, $parent_id, $node_ns = '', $node_name = '', $attr_cond = null, $only_element_nodes = false) {
        $node_ids = array();
        
        list($name_query, $name_param) = $this->getNameQuery($node_ns, $node_name);
        list($attr_query, $attr_param) = $this->getAttrQuery($attr_cond);

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
    // {{{ getNameQuery()
    /**
     * gets part of sql query for selecting nodes by their name
     *
     * @param    $node_ns (string) namespace prefix of node
     * @param    $node_name (string) name of node
     *
     * @return    $name_query (string)
     */
    private function getNameQuery($node_ns, $node_name) {
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
    // {{{ getAttrQuery()
    /**
     * gets part of sql query for selecting node by their attribute
     *
     * @param    $attr_cond (array) every element must have following 
     *            subelements: name, value and operator. 
     *
     * @return    $attr_query (string)
     */
    private function getAttrQuery($attr_cond) {
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
    // {{{ getNodeIdsByXpath()
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
    private function getNodeIdsByXpath($doc_id, $xpath) {
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
                            $fetched_ids = array_merge($fetched_ids, $this->getChildIdsByName($doc_id, $actual_id, $ns, $name, null, true));
                        }
                    // }}}
                    // {{{ fetch by name and position:
                    } else if (preg_match("/^([0-9]+)$/", $condition)) {
                        /*
                         * "... /ns:name[n] ..."
                         */
                        foreach ($actual_ids as $actual_id) {
                            $temp_ids = $this->getChildIdsByName($doc_id, $actual_id, $ns, $name, null, true);
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
                            $fetched_ids = array_merge($fetched_ids, $this->getChildIdsByName($doc_id, $actual_id, $ns, $name, $cond_array, true));
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
                        $fetched_ids = $this->getNodeIdsByName($doc_id, $ns, $name);    
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
                            $fetched_ids = $this->getNodeIdsByName($doc_id, $ns, $name, $cond_array);
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
     *        1. getChildIdsByName()
     *        2. getNodeIdsByName()
     * 
     * @private
     *
     * @param    $condition (string) attribute conditions
     * @param    $strings (array) of literal strings used in condition
     *
     * @return    $attr (array) array of attr-conditions
     */
    private function get_condition_attributes($condition, $strings) {
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
    private function remove_literal_strings($text, &$strings) {
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
    // {{{ getChildnodesByParentId()
    /**
     * gets child nodes of a node as string
     *
     * @param   $doc_id (int) document id
     * @param   $parent_id (int) id of parent-node
     * @param   $level (int) number of recursive calls. how deep to traverse the tree.
     * @return  $xml_doc (string) xml node definition of node
     */
    protected function getChildnodesByParentId($doc_id, $parent_id, $level = PHP_INT_MAX) {
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
                if ($level > 0) {
                    $xml_doc .= $this->getChildnodesByParentId($doc_id, $row->id, $level - 1);
                }

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
    // {{{ extractNamespaces()
    public function extractNamespaces($str) {
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
    // {{{ getNamespacesAndEntities()
    private function getNamespacesAndEntities($doc_id) {
        $query = $this->pdo->prepare(
            "SELECT docs.entities AS entities, docs.ns AS namespaces
            FROM {$this->table_docs} AS docs
            WHERE docs.id = :doc_id"
        );
        $query->execute(array(
            'doc_id' => $doc_id,
        ));
        return $query->fetchObject();
    }
    // }}}

    // {{{ saveNode()
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
    public function saveNode($doc_id_or_name, $node, $target_id = null, $target_pos = -1, $stripwhitespace = true) {
        $this->beginTransaction();

        $doc_id = $this->docExists($doc_id_or_name);
        
        //get all nodes in array
        $node_array = array();
        $this->getNodeArrayForSaving($node_array, $node);

        if ($node_array[0]['id'] != null && $target_id === null) {
            //set target_id/pos/doc
            $target_id = $this->getParentIdByNodeId($doc_id, $node_array[0]['id']);
            $target_pos = $this->getPosByNodeId($doc_id, $node_array[0]['id']);

            if ($target_id === false) {
                $target_id = null;
            }

            //unlink old node
            $this->unlinkNodeById($doc_id, $node_array[0]['id']);
            $this->clearCache($doc_id);
        } else if ($target_id === null) {
            $target_id = null;
            $target_pos = 0;
        } else if ($target_id !== null) {
            $parent_id = $this->getParentIdByNodeId($doc_id, $target_id);
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
            $this->clearCache($doc_id);
            
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

        $this->getFreeNodeIds(count($node_array));
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
                //echo($parent_node->getAttribute('ref') . " -> ");
                $this->set_attribute_ns($parent_node, "{$this->db_ns->ns}ref", $changed_ref_ids[$xfetch->nodeset[$i]->node_value()]);
                //echo($parent_node->getAttribute('ref') . "<br />");
            }
        }
        */
        
        //save root node
        $node_array[0]['id'] = $this->saveNodeToDb($doc_id, $node_array[0]['node'], $node_array[0]['id'], $target_id, $target_pos, true);
        
        //save element nodes
        for ($i = 1; $i < count($node_array); $i++) {
            if ($node_array[$i]['node']->nodeType == XML_ELEMENT_NODE) {
                $node_array[$i]['id'] = $this->saveNodeToDb($doc_id,$node_array[$i]['node'], $node_array[$i]['id'], $node_array[$node_array[$i]['parent_index']]['id'], $node_array[$i]['pos']);
            }
        }

        //save other nodes
        for ($i = 1; $i < count($node_array); $i++) {
            if ($node_array[$i]['node']->nodeType != XML_ELEMENT_NODE) {
                $node_array[$i]['id'] = $this->saveNodeToDb($doc_id, $node_array[$i]['node'], $node_array[$i]['id'], $node_array[$node_array[$i]['parent_index']]['id'], $node_array[$i]['pos']);
            }
        }
        
        $this->endTransaction();
        
        return $node_array[0]['id'];
    }
    // }}}
    // {{{ saveNodeToDb()
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
    private function saveNodeToDb($doc_id, $node, $id, $target_id, $target_pos, $increase_pos = false) {
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
                if ($attrib->prefix . ':' . $attrib->localName != $this->db_ns->ns . ':' . $this->id_attribute && $attrib->localName != $this->db_ns->ns . ':' . $this->id_attribute) {
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
            } else if ($this->getNodeId($node) == null) {
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
    // {{{ getNodeArrayForSaving()
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
    protected function getNodeArrayForSaving(&$node_array, $node, $parent_index = null, $pos = 0, $stripwhitespace = true) {
        $type = $node->nodeType;
        //is DOCUMENT_NODE
        if ($type == XML_DOCUMENT_NODE) {
            $root_node = $node->documentElement;
            $this->getNodeArrayForSaving($node_array, $root_node, $parent_index, $pos, $stripwhitespace);
        //is ELEMENT_NODE
        } elseif ($type == XML_ELEMENT_NODE) {
            $id = $this->getNodeId($node);
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
                    $this->getNodeArrayForSaving($node_array, $tmp_node, $parent_index, $i, $stripwhitespace);
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

    // {{{ unlinkNodeById()
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
    public function unlinkNodeById($doc_id, $id)  {
        $query = $this->pdo->prepare(
            "DELETE FROM {$this->table_xml}
            WHERE id_doc = :doc_id AND id = :id"
        );
        $query->execute(array(
            'doc_id' => $doc_id,
            'id' => $id,
        ));

        $this->clearCache($doc_id);

        return array();
    }
    // }}}

    // {{{ removeIdAttr
    /**
     * remove all db-id attributes recursive from nodes
     *
     * @private
     *
     * @param    $node (domxmlnode) node to remove attribute from
     */
    protected function removeIdAttr($node) {
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
    // {{{ clearCache()
    /**
     * clears the node-cache
     *
     * @public
     */
    protected function clearCache($doc_id = null) {
        if (is_null($doc_id)) {
            $this->cache->delete("{$this->table_docs}/");
        } else {
            $this->cache->delete("{$this->table_docs}/d{$doc_id}/");
        }
    }
    // }}}
    // {{{ getNodeId()
    /**
     * gets node db-id from db-id attribute
     *
     * @public
     *
     * @param    $node (domxmlnode) node to get id from
     *
     * @return    $db_id (int)
     */
    public function getNodeId($node) {
        $db_id = null;
        if ($node->nodeType == XML_ELEMENT_NODE) {
            $db_id = $node->getAttributeNS($this->db_ns->uri, $this->id_attribute);
        }
        
        return $db_id;
    }        
    // }}}
    // {{{ getNodeDataId()
    /**
     * gets node db-dataid from db-dataid attribute
     *
     * @public
     *
     * @param    $node (domxmlnode) node to get id from
     *
     * @return    $db_id (int)
     */
    public function getNodeDataId($node) {
        $db_id = null;
        if ($node->nodeType == XML_ELEMENT_NODE) {
            $db_id = $node->getAttributeNS($this->db_ns->uri, $this->id_data_attribute);
        }
        
        return $db_id;
    }        
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
