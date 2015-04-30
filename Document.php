<?php
/**
 * @file    modules/xmldb/xmldb.php
 *
 * cms xmldb module
 *
 *
 * copyright (c) 2002-2011 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 *
 */
namespace Depage\XmlDb;

class Document
{
    // {{{ variables
    private $pdo;
    private $cache;

    protected $db_ns;

    private $table_prefix;
    private $table_docs;
    private $table_xml;
    private $table_nodetypes;

    private $transaction = 0;

    private $xmldb;
    private $doc_id;

    private $id_attribute = "id";
    private $id_data_attribute = "dataid";

    private $dont_strip_white = array(); // TODO set when document is loaded - add to doctypes base
    private $free_element_ids = array();

    private $doctypeHandlers = array();
    // }}}
    // {{{ constructor
    /**
     * Construct
     *
     * @param $xmldb
     * @param $doc_id
     */
    public function __construct($xmldb, $doc_id)
    {
        $this->pdo = $xmldb->pdo;
        $this->cache = $xmldb->cache;

        $this->db_ns = new xmlns("db", "http://cms.depagecms.net/ns/database");

        $this->xmldb = $xmldb;

        $this->table_prefix = $xmldb->table_prefix;
        $this->table_docs = $xmldb->table_docs;
        $this->table_xml = $xmldb->table_xml;
        $this->table_nodetypes = $xmldb->table_nodetypes;

        $this->doc_id = $doc_id;
    }
    // }}}

    // {{{ getDocId
    /**
     * Get Doc ID
     *
     * @return int
     */
    public function getDocId()
    {
        return $this->doc_id;
    }
    // }}}

    // {{{ getHistory
    /**
     * getHistory
     *
     * @return history
     */
    public function getHistory()
    {
        return new history($this->pdo, $this->table_prefix, $this);
    }
    // }}}

    // {{{ getXml
    /**
     * getXml
     *
     * @return string
     */
    public function getXml($add_id_attribute = true)
    {
        $root_id = $this->getDocInfo()->rootid;
        return $this->getSubdocByNodeId($root_id, $add_id_attribute);
    }
    // }}}

    // {{{ getDoctypeHandler
    /**
     *
     * @param $doc_id
     * @return mixed
     */
    public function getDoctypeHandler()
    {
        if (!isset($this->doctypeHandlers[$this->doc_id])) {
            $className = $this->getDocInfo()->type;

            if (empty($className)) {
                $handler = new XmlDocTypes\Base($this->xmldb, $this);
            } else {
                $className = "\\" . $className;
                $handler = new $className($this->xmldb, $this);
            }

            $this->doctypeHandlers[$this->doc_id] = $handler;
        }

        return $this->doctypeHandlers[$this->doc_id];
    }
    // }}}

    // {{{ getDocInfo
    /**
     * gets info about a document by doc_id
     *
     * @return $doc (array)
     */
    public function getDocInfo()
    {
        $query = $this->pdo->prepare(
            "SELECT
                docs.id AS id,
                docs.name AS name,
                docs.rootid AS rootid,
                docs.type AS type,
                docs.ns AS namespaces,
                docs.lastchange AS lastchange,
                docs.lastchange_uid AS lastchangeUid
            FROM {$this->table_docs} AS docs
            WHERE docs.id = :doc_id
            LIMIT 1"
        );
        $query->execute(array(
            'doc_id' => $this->doc_id,
        ));

        $info = $query->fetchObject();

        if ($info) {
            $info->lastchange = new \DateTime($info->lastchange);
        }

        return $info;
    }
    // }}}

    // {{{ cleanDoc
    /**
     * clean all nodes inside of document
     *
     * @return $doc (array)
     */
    public function cleanDoc()
    {
        $info = $this->getDocInfo();

        $query = $this->pdo->prepare(
            "REPLACE {$this->table_docs}
            SET
                id = :id,
                name = :name,
                rootid = :rootid,
                type = :type,
                ns = :ns"
        );

        if ($info) {
            $query->execute(array(
                'id' => $info->id,
                'name' => $info->name,
                'rootid' => $info->rootid,
                'type' => $info->type,
                'ns' => $info->namespaces,
            ));

            $this->clearCache();
        }

        return $info;
    }
    // }}}

    // {{{ getSubdocByNodeId
    /**
     * gets an xml-document-object from specific db-id
     *
     * @param    $id (int) db-id of node to get
     * @param    $add_id_attribute (bool) true, if you want to add the db-id attributes
     *            to xml-definition, false to remove them.
     * @param    $level (int) number of recursive getChildnodesByParentId calls. how deep to traverse the tree.
     */
    public function getSubdocByNodeId($id, $add_id_attribute = true, $level = PHP_INT_MAX)
    {
        $identifier = "{$this->table_docs}_d{$this->doc_id}/{$id}.xml";

        $xml_doc = new \Depage\Xml\Document();

        $xml_str = $this->cache->get($identifier);

        if ($xml_str) {
            // read from cache
            $xml_doc->loadXML($xml_str);
        } else {
            // read from database
            $this->xmldb->beginTransaction();

            $query = $this->pdo->prepare(
                "SELECT
                    docs.entities AS entities,
                    docs.ns AS namespaces,
                    docs.lastchange AS lastchange,
                    docs.lastchange_uid AS lastchangeUid
                FROM {$this->table_docs} AS docs
                WHERE docs.id = :doc_id"
            );
            $query->execute(array(
                'doc_id' => $this->doc_id,
            ));
            $result = $query->fetchObject();

            $this->entities = $result->entities;
            $this->namespace_string = $result->namespaces;
            $this->namespaces = $this->extractNamespaces($this->namespace_string);
            $this->namespaces[] = $this->db_ns;
            $this->lastchange = $result->lastchange;
            $this->lastchangeUid = $result->lastchangeUid;

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
                'doc_id' => $this->doc_id,
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
                $node_data .= " {$this->db_ns->ns}:{$this->id_attribute}=\"$row->id\"";

                //add lastchange-data
                $node_data .= " {$this->db_ns->ns}:lastchange=\"{$this->lastchange}\"";
                $node_data .= " {$this->db_ns->ns}:lastchangeUid=\"{$this->lastchangeUid}\"";

                $node_data .= ">";

                $xml_str .= $node_data;

                //add child_nodes
                $xml_str .= $this->getChildnodesByParentId($row->id, $level);

                $xml_str .= "</{$row->name}>";
            } else {
                $this->xmldb->endTransaction();

                throw new XmlDbException("This node is no ELEMENT_NODE or node does not exist");
            }

            $success = $xml_doc->loadXML($xml_str);
            $dth = $this->getDoctypeHandler();

            $this->xmldb->endTransaction();

            $changed = $dth->testDocument($xml_doc);
            if ($changed) {
                $this->saveNode($xml_doc);
            }

            // add xml to xml-cache
            // TODO bug in cache caused by saving when level is 0
            if (is_object($xml_doc) && $xml_doc->documentElement != null && $level === PHP_INT_MAX) {
                $this->cache->set($identifier, $xml_doc->saveXML());
            }
        }

        if (is_a($xml_doc, "DOMDocument") && $xml_doc->documentElement != null && !$add_id_attribute) {
            $this->removeIdAttr($xml_doc);
        }

        return $xml_doc;
    }
    // }}}
    // {{{ getSubDocByXpath
    /**
     * gets document by xpath. if xpath directs to more than
     * one node, only the first node will be returned.
     *
     * @param    $xpath (string) xpath to target node
     * @param    $add_id_attribute (bool) whether to add db:id attribute or not
     *
     * @return    $doc (domxmlobject)
     */
    public function getSubDocByXpath($xpath, $add_id_attribute = true)
    {
        $ids = $this->getNodeIdsByXpath($xpath);
        if (count($ids) > 0) {
            return $this->getSubdocByNodeId($ids[0], $add_id_attribute);
        }
        return false;
    }
    // }}}

    // {{{ save
    /**
     * @param $xml
     * @return mixed
     * @throws xmldbException
     */
    public function save(\DomDocument $xml)
    {
        $this->xmldb->beginTransaction();

        $doc_info = $this->cleanDoc();
        $xml_text = $xml->saveXML();

        // @TODO get namespaces from document at this moment it is only per preg_match not by the domxml interface, because
        // @TODO namespace definitions are not available
        preg_match_all("/ xmlns:([^=]*)=\"([^\"]*)\"/", $xml_text, $matches, PREG_SET_ORDER);
        $namespaces = "";
        for ($i = 0; $i < count($matches); $i++) {
            if ($matches[$i][1] != $this->db_ns->ns) {
                $namespaces .= $matches[$i][0];
            }
        }

        // @TODO get document and entities or set html_entities as standard as long as php does not inherit the entites() function
        $doc_info->rootid = $this->saveNode($xml);
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

        $this->clearCache();

        $this->xmldb->endTransaction();

        return $doc_info->id;
    }
    // }}}

    // {{{ unlinkNode
    /**
     * @param $node_id
     * @return bool
     */
    public function unlinkNode($node_id)
    {
        if ($this->getDoctypeHandler()->isAllowedUnlink($node_id)) {
            $this->updateLastchange();

            return $this->unlinkNodeById($node_id);
        }
        return false;
    }
    // }}}
    // {{{ unlinkNodeById
    /**
     * unlinks and deletes a specific node from database.
     * re-indexes the positions of the remaining elements.
     *
     * @param     $node_id (int) db-id of node to delete
     *
     * @return    $deleted_ids (array) list of db-ids of deleted nodes
     */
    public function unlinkNodeById($node_id)
    {
        // get parent and position (enables other node positions to be updated after delete)
        $target_id = $this->getParentIdById($node_id);
        $target_pos = $this->getPosById($node_id);

        $dth = $this->getDoctypeHandler();

        if ($dth->onDeleteNode($node_id)) {

            // delete the node
            $query = $this->pdo->prepare(
                "DELETE FROM {$this->table_xml}
                WHERE id_doc = :doc_id AND id = :node_id"
            );
            $query->execute(array(
                'doc_id' => $this->doc_id,
                'node_id' => $node_id,
            ));

            $this->clearCache();

            // update position of remaining nodes
            $query = $this->pdo->prepare(
                "UPDATE {$this->table_xml}
                    SET pos=pos-1
                    WHERE id_parent = :node_parent_id AND pos > :node_pos AND id_doc = :doc_id"
            );
            $query->execute(array(
                'node_parent_id' => $target_id,
                'node_pos' => $target_pos,
                'doc_id' => $this->doc_id,
            ));
        }
        return $target_id;
    }
    // }}}

    // {{{ addNode
    /**
     * @param $node
     * @param $target_id
     * @param $target_pos
     * @return bool
     */
    public function addNode(\DomElement $node, $target_id, $target_pos = -1, $extras = array())
    {
        $dth = $this->getDoctypeHandler();
        if ($dth->isAllowedAdd($node, $target_id)) {
            $dth->onAddNode($node, $target_id, $target_pos, $extras);
            return $this->saveNode($node, $target_id, $target_pos, true);
        }
        return false;
    }
    // }}}
    // {{{ addNodeByName
    /**
     * @param $name
     * @param $target_id
     * @param $target_pos
     * @return bool
     */
    public function addNodeByName($name, $target_id, $target_pos)
    {
        $dth = $this->getDoctypeHandler();
        $target_name = $this->getNodeNameById($target_id);

        $newNode = $dth->getNewNodeFor($name);
        if ($newNode) {
            return $this->addNode($newNode, $target_id, $target_pos);
        }
        return false;
    }
    // }}}

    // {{{ replaceNode
    /**
     * replaces a node in database
     *
     * @param    $node (domxmlnode) node to save
     * @param    $id_to_replace (int) db-id of node to be replaced
     * @param    $this->doc_id (int) document db-id
     *
     * @return    $changed_ids (array) list of db-ids that has been changed
     */
    public function replaceNode($node, $id_to_replace)
    {
        $this->xmldb->beginTransaction();

        $target_id = $this->getParentIdById($id_to_replace);
        $target_pos = $this->getPosById($id_to_replace);

        $this->unlinkNodeById($id_to_replace, array(), true);

        $changed_ids = array();
        $changed_ids[] = $this->saveNode($node, $target_id, $target_pos, true);
        $changed_ids[] = $target_id;

        $this->xmldb->endTransaction();

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
    public function moveNodeIn($node_id, $target_id)
    {
        $this->xmldb->beginTransaction();

        $query = $this->pdo->prepare(
            "SELECT IFNULL(MAX(xml.pos), -1) + 1 AS newpos
            FROM {$this->table_xml} AS xml
            WHERE xml.id_parent = :target_id AND xml.id_doc = :doc_id"
        );
        $query->execute(array(
            'target_id' => $target_id,
            'doc_id' => $this->doc_id,
        ));
        $result = $query->fetchObject();

        $success = $this->moveNode($node_id, $target_id, $result->newpos);

        $this->xmldb->endTransaction();

        return $success;
    }
    // }}}
    // {{{ moveNodeBefore
    /**
     * moves node before another node (insert before)
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     */
    public function moveNodeBefore($node_id, $target_id)
    {
        $this->xmldb->beginTransaction();

        $target_parent_id = $this->getParentIdById($target_id);
        $target_pos = $this->getPosById($target_id);

        $success = $this->moveNode($node_id, $target_parent_id, $target_pos);

        $this->xmldb->endTransaction();

        return $success;
    }
    // }}}
    // {{{ moveNodeAfter
    /**
     * moves node after another node
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     */
    public function moveNodeAfter($node_id, $target_id)
    {
        $this->xmldb->beginTransaction();

        $target_parent_id = $this->getParentIdById($target_id);
        $target_pos = $this->getPosById($target_id) + 1;

        $success = $this->moveNode($node_id, $target_parent_id, $target_pos);

        $this->xmldb->endTransaction();

        return $success;
    }
    // }}}
    // {{{ moveNode
    /**
     * moves node in database
     *
     * // TODO prevent moving a node to its children
     *
     * @public
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     * @param    $target_pos (int) position to move to
     */
    public function moveNode($node_id, $target_id, $target_pos)
    {
        $success = false;

        if ($node_id !== $target_id && $this->getDoctypeHandler()->isAllowedMove($node_id, $target_id)) {
            $this->xmldb->beginTransaction();

            $node_parent_id = $this->getParentIdById($node_id);
            $node_pos = $this->getPosById($node_id);

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
                    'doc_id' => $this->doc_id,
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
                    'doc_id' => $this->doc_id,
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
                    'doc_id' => $this->doc_id,
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
                    'doc_id' => $this->doc_id,
                ));

                $this->updateLastchange();

                $this->clearCache();
            }

            $success = true;

            $this->xmldb->endTransaction();
        }

        return $success;
    }
    // }}}

    // {{{ copyNodeIn
    /**
     * copy node to another node
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     */
    public function copyNodeIn($node_id, $target_id)
    {
        $this->xmldb->beginTransaction();

        $query = $this->pdo->prepare(
            "SELECT IFNULL(MAX(xml.pos), -1) + 1 AS newpos
            FROM {$this->table_xml} AS xml
            WHERE xml.id_parent = :target_id AND xml.id_doc = :doc_id"
        );
        $query->execute(array(
            'target_id' => $target_id,
            'doc_id' => $this->doc_id,
        ));
        $result = $query->fetchObject();

        $success = $this->copyNode($node_id, $target_id, $result->newpos);

        $this->xmldb->endTransaction();

        return $success;
    }
    // }}}
    // {{{  copyNodeBefore
    /**
     * copy node before another node
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     */
    public function copyNodeBefore($node_id, $target_id)
    {
        $this->xmldb->beginTransaction();

        $target_parent_id = $this->getParentIdById($target_id);
        $target_pos = $this->getPosById($target_id);

        $success = $this->copyNode($node_id, $target_parent_id, $target_pos);

        $this->xmldb->endTransaction();

        return $success;
    }
    // }}}
    // {{{ copyNodeAfter
    /**
     * copy node after another node
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     */
    public function copyNodeAfter($node_id, $target_id)
    {
        $this->xmldb->beginTransaction();

        $target_parent_id = $this->getParentIdById($target_id);
        $target_pos = $this->getPosById($target_id) + 1;

        $success = $this->copyNode($node_id, $target_parent_id, $target_pos);

        $this->xmldb->endTransaction();

        return $success;
    }
    // }}}
    // {{{ copyNode
    /**
     * copy node in database
     *
     * @public
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     * @param    $target_pos (int) pos to copy to
     */
    public function copyNode($node_id, $target_id, $target_pos)
    {
        $docHandler = $this->getDoctypeHandler();
        if ($docHandler->isAllowedMove($node_id, $target_id)) {
            $this->xmldb->beginTransaction();

            $xml_doc = $this->getSubdocByNodeId($node_id, false);
            $root_node = $xml_doc;

            $this->clearCache();

            $copy_id = $this->saveNode($root_node, $target_id, $target_pos, true);

            $docHandler->onCopyNode($node_id, $copy_id);

            $this->xmldb->endTransaction();

            return $copy_id;
        }

        return false;
    }
    // }}}

    // {{{ duplicateNode
    /**
     * duplicate node in database, and inserts it in the next position
     *
     * @TODO behaviour should differ according to tree type - don't usually want to copy all sub nodes
     *
     * @param    $node_id (int) db-id of node
     *
     * @return bool (success)
     */
    public function duplicateNode($node_id, $recursive = false)
    {
        // get parent and position for new node
        $target_id = $this->getParentIdById($node_id);
        $target_pos = $this->getPosById($node_id) + 1;
        $docHandler = $this->getDoctypeHandler();

        if ($docHandler->isAllowedMove($node_id, $target_id)) {
            $xml_doc = $this->getSubdocByNodeId($node_id, false);
            $root_node = $xml_doc;

            $this->clearCache();

            $copy_id = $this->saveNode($root_node, $target_id, $target_pos, $recursive);

            $docHandler->onCopyNode($node_id, $copy_id);

            return $copy_id;
        }

        return false;
    }
    // }}}

    // {{{ buildNode
    /**
     *
     * @param $name
     * @param $attributes
     * @return \DOMElement
     */
    public function buildNode($name, $attributes)
    {
        //@todo dont build node directly but get from templates according to document type
        $doc_info = $this->getNamespacesAndEntities($this->doc_id);
        $xml = "<$name {$doc_info->namespaces}";
        foreach ($attributes as $attr => $value) {
            $xml .= " $attr=\"$value\"";
        }
        $xml .= "/>";

        $doc = new \Depage\Xml\Document();
        $doc->loadXML($xml);

        return $doc->documentElement;
    }
    // }}}

    // {{{ setAttribute
    /**
     * sets attribute of node
     *
     * @param    $node_id (int) db-id of node to set attribute
     * @param    $attr_name (string) name of attribute
     * @param    $attr_value (string) new value of attribute
     */
    public function setAttribute($node_id, $attr_name, $attr_value)
    {
        $success = false;

        $this->xmldb->beginTransaction();

        $attributes = $this->getAttributes($node_id);

        if ((isset($attributes[$attr_name]) && $attributes[$attr_name] !== htmlspecialchars($attr_value))
            || !isset($attributes[$attr_name])
        ) {
            $attributes[$attr_name] = $attr_value;

            $success = $this->saveAttributes($node_id, $attributes);
        }

        $this->xmldb->endTransaction();

        return $success;
    }
    // }}}
    // {{{ removeAttribute
    /**
     * removes an attribute of a node
     *
     * @param    $node_id (int) db-id of node to set attribute
     * @param    $attr_name (string) name of attribute
     */
    public function removeAttribute($node_id, $attr_name)
    {
        $success = false;

        $this->xmldb->beginTransaction();

        $attributes = $this->getAttributes($node_id);

        if (isset($attributes[$attr_name])) {
            unset($attributes[$attr_name]);

            $success = $this->saveAttributes($node_id, $attributes);
        }

        $this->xmldb->endTransaction();

        return $success;
    }
    // }}}
    // {{{ saveAttributes
    /**
     * sets attribute of node
     *
     * @param    $node_id (int) db-id of node to set attribute
     * @param    $attributes (array) array of attribute values
     */
    protected function saveAttributes($node_id, $attributes)
    {
        $this->xmldb->beginTransaction();

        $query = $this->pdo->prepare(
            "UPDATE {$this->table_xml} AS xml
            SET xml.value = :attr_str
            WHERE xml.id = :node_id AND xml.id_doc = :doc_id"
        );
        $success = $query->execute(array(
            'node_id' => $node_id,
            'attr_str' => $this->getAttributeString($attributes),
            'doc_id' => $this->doc_id,
        ));

        $this->updateLastchange();

        $this->clearCache();

        $this->xmldb->endTransaction();

        return $success;
    }
    // }}}
    // {{{ getAttribute
    /**
     * gets attribute of node
     *
     * @param    $node_id (int) db-id of node
     * @param    $attr_name (string) name of attribute
     *
     * @return    $val (string) value
     */
    public function getAttribute($node_id, $attr_name)
    {
        $attributes = $this->getAttributes($node_id);

        if (isset($attributes[$attr_name])) {
            return $attributes[$attr_name];
        }

        return false;
    }
    // }}}
    // {{{ getAttributes
    /**
     * gets all attributes of a node by id
     *
     * @param    $node_id (int) db-id of node
     *
     * @return    $attributes (array) array of attributes
     */
    public function getAttributes($node_id)
    {
        $attributes = array();

        $query = $this->pdo->prepare(
            "SELECT xml.value
            FROM {$this->table_xml} AS xml
            WHERE xml.id = :node_id AND xml.type='ELEMENT_NODE' AND xml.id_doc = :doc_id"
        );
        $query->execute(array(
            'node_id' => $node_id,
            'doc_id' => $this->doc_id,
        ));

        if ($result = $query->fetchObject()) {
            $matches = preg_split("/(=\"|\"$|\" )/", $result->value);
            $matches = array_chunk($matches, 2);
            foreach($matches as $match) {
                if ($match[0] != '') {
                    $attributes[$match[0]] = htmlspecialchars_decode($match[1]);
                }
            }
        }

        return $attributes;
    }
    // }}}

    // {{{ getParentIdById
    /**
     * gets parent db-id by one of its child_nodes-id
     *
     * @param    $id (int) node db-id
     *
     * @return    $parent_id (id) db-id of parent node, false, if node doesn't exist.
     */
    public function getParentIdById($id)
    {
        $query = $this->pdo->prepare(
            "SELECT xml.id_parent AS id_parent
            FROM {$this->table_xml} AS xml
            WHERE xml.id= :id AND xml.id_doc = :doc_id"
        );
        $query->execute(array(
            'id' => $id,
            'doc_id' => $this->doc_id,
        ));

        if ($result = $query->fetchObject()) {
            return $result->id_parent;
        }
        return false;
    }
    // }}}
    // {{{ getNodeId
    /**
     * gets node db-id from db-id attribute
     *
     * @param    $node (domxmlnode) node to get id from
     *
     * @return    $db_id (int)
     */
    public function getNodeId($node)
    {
        return $node->nodeType == XML_ELEMENT_NODE
            ? $node->getAttributeNS($this->db_ns->uri, $this->id_attribute)
            : null;
    }
    // }}}
    // {{{ getNodeDataId
    /**
     * gets node db-dataid from db-dataid attribute
     *
     * @public
     *
     * @param    $node (domxmlnode) node to get id from
     *
     * @return    $db_id (int)
     */
    public function getNodeDataId($node)
    {
        return $node->nodeType == XML_ELEMENT_NODE
            ?  $db_id = $node->getAttributeNS($this->db_ns->uri, $this->id_data_attribute)
            : null;
    }
    // }}}
    // {{{ getNodeIdsByXpath
    /**
     * gets node_ids by xpath
     *
     * @attention this supports only a small subset of xpath-queries. so recheck source before using.
     *
     * @param    $this->doc_id (int) id of document
     * @param    $xpath (string) xpath to target node
     *
     * @return    $nodeids (array) array of found node ids
     *
     * @todo    implement full xpath specifications
     */
    public function getNodeIdsByXpath($xpath)
    {
        $identifier = "{$this->table_docs}_d{$this->doc_id}/xpath_" . sha1($xpath);

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
                'doc_id' => $this->doc_id,
            ));
            $result = $query->fetchObject();
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
                            $fetched_ids = array_merge($fetched_ids, $this->getChildIdsByName($actual_id, $ns, $name, null, true));
                        }
                        // }}}
                        // {{{ fetch by name and position:
                    } else if (preg_match("/^([0-9]+)$/", $condition)) {
                        /*
                         * "... /ns:name[n] ..."
                         */
                        foreach ($actual_ids as $actual_id) {
                            $temp_ids = $this->getChildIdsByName($actual_id, $ns, $name, null, true);
                            $fetched_ids[] = $temp_ids[((int) $condition) - 1];
                        }
                        // }}}
                        // {{{ fetch by simple attributes:
                    } else if (preg_match("/[\w\d@=: _-]*/", $temp_condition = $this->remove_literal_strings($condition, $strings))) {
                        /*
                         * "... /ns:name[@attr1] ..."
                         * "... /ns:name[@attr1 = 'string1'] ..."
                         * "... /ns:name[@attr1 = 'string1' and/or @attr2 = 'string2'] ..."
                         */
                        $cond_array = $this->get_condition_attributes($temp_condition, $strings);
                        foreach ($actual_ids as $actual_id) {
                            $fetched_ids = array_merge($fetched_ids, $this->getChildIdsByName($actual_id, $ns, $name, $cond_array, true));
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
                        $fetched_ids = $this->getNodeIdsByName($ns, $name);
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
                            $fetched_ids = $this->getNodeIdsByName($ns, $name, $cond_array);
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
    private function get_condition_attributes($condition, $strings)
    {
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
    private function remove_literal_strings($text, &$strings)
    {
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
    // {{{ getFreeNodeIds
    /**
     * gets unused db-node-ids for saving nodes
     *
     * @param    $needed (int) minimum number of ids, that are requested
     */
    private function getFreeNodeIds($needed = 1)
    {
        // @todo check to replace this with an extra table of deleted ids (trigger on delete)
        /* see here:
            CREATE TRIGGER log_patron_delete AFTER DELETE on patrons
            FOR EACH ROW
            BEGIN
            DELETE FROM patron_info
                WHERE patron_info.pid = old.id;
            END
         */
        $this->free_element_ids = array();
        $lastMax = 0;

        do {
            // @todo for some reason preparing before the loop does not work with native prepared statements
            $query = $this->pdo->prepare(
                "SELECT row AS id FROM
                    (SELECT
                        @row := @row + 1 as row, xml.id
                    FROM
                        {$this->table_xml} as xml,
                        (SELECT @row := :start) r
                    WHERE @row <> id
                    ORDER BY xml.id) AS seq
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM {$this->table_xml} as xml
                    WHERE xml.id = row
                ) LIMIT :maxCount;"

            );

            $query->execute(array(
                'start' => $lastMax,
                'maxCount' => $needed,
            ));

            $results = $query->fetchAll(\PDO::FETCH_OBJ);
            foreach ($results as $id) {
                $this->free_element_ids[] = $id->id;
            }
            if (count($results) > 0) {
                $lastMax = (int) $id->id;
            }
        } while (count($this->free_element_ids) < $needed && count($results) > 0);

        $num = count($this->free_element_ids);

        if ($num < $needed) {
            $query = $this->pdo->prepare(
                "SELECT IFNULL(MAX(xml.id), 0) + 1 AS id_max
                FROM {$this->table_xml} AS xml"
            );
            $query->execute();
            $result = $query->fetchObject();

            $until = $needed - $num;
            for ($i = 0; $i < $until; $i++) {
                $this->free_element_ids[] = $result->id_max + $i;
            }
        }
    }
    // }}}

    // {{{ getNodeNameById
    /**
     * gets node_name by node db-id
     *
     * @param    $id (int) node db-id
     *
     * @return    $node_name (string) name of node, false if node doesn't exist.
     */
    public function getNodeNameById($id)
    {
        $query = $this->pdo->prepare(
            "SELECT xml.name AS name
            FROM {$this->table_xml} AS xml
            WHERE xml.id = :id AND xml.id_doc = :doc_id"
        );
        $query->execute(array(
            'id' => $id,
            'doc_id' => $this->doc_id,
        ));

        if ($result = $query->fetchObject()) {
            return $result->name;
        }
        return false;
    }
    // }}}

    // {{{ extractNamespaces
    public function extractNamespaces($str)
    {
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

    // {{{ getNamespacesAndEntities
    public function getNamespacesAndEntities()
    {
        $query = $this->pdo->prepare(
            "SELECT docs.entities AS entities, docs.ns AS namespaces
            FROM {$this->table_docs} AS docs
            WHERE docs.id = :doc_id"
        );
        $query->execute(array(
            'doc_id' => $this->doc_id,
        ));
        return $query->fetchObject();
    }
    // }}}

    // {{{ saveNode
    /**
     * saves a xml document or part of an document to database
     *
     * @param    $node (domxmlnode) node to save
     * @param    $target_id (int) node db-id to save to
     * @param    $target_pos (int) position to save at
     * @param    $inc_children (bool) also save the related child nodes
     */
    public function saveNode($node, $target_id = null, $target_pos = -1, $inc_children = true)
    {
        $this->xmldb->beginTransaction();

        if ($target_id !== null) {
            /*
             * if target_id is not set, assume we are saving an existing node with a node
             * db:id-attribute set. if target_id is set, assume we want to save a new node
             * so remove all existing node attributes first.
             */
            $this->removeIdAttr($node);
        }

        //get all nodes in array
        $node_array = array();
        $this->getNodeArrayForSaving($node_array, $node);

        if ($node_array[0]['id'] != null && $target_id === null) {
            //set target_id/pos/doc
            $target_id = $this->getParentIdById($node_array[0]['id']);
            $target_pos = $this->getPosById($node_array[0]['id']);

            if ($target_id === false) {
                $target_id = null;
            }

            //unlink old node
            $this->unlinkNodeById($node_array[0]['id']);
            $this->clearCache();
        } else if ($target_id === null) {
            $target_id = null;
            $target_pos = 0;
        } else if ($target_id !== null) {
            $parent_id = $this->getParentIdById($target_id);
            //unlink child nodes, if target is document
            if ($parent_id === false) {
                $this->pdo->exec("SET foreign_key_checks = 0;");
                $query = $this->pdo->prepare(
                    "DELETE
                    FROM {$this->table_xml}
                    WHERE id_doc = :doc_id"
                );
                $query->execute(array(
                    'doc_id' => $this->doc_id,
                ));
                $this->pdo->exec("SET foreign_key_checks = 1;");
            }
            $this->clearCache();

            //set target_id/pos/doc
            $query = $this->pdo->prepare(
                "SELECT IFNULL(MAX(xml.pos), -1) + 1 AS pos
                FROM {$this->table_xml} AS xml
                WHERE xml.id_parent = :target_id AND id_doc = :doc_id"
            );
            $query->execute(array(
                'target_id' => $target_id,
                'doc_id' => $this->doc_id,
            ));
            $result = $query->fetchObject();
            if ($result) {
                if ($target_pos > $result->pos || $target_pos == -1) {
                    $target_pos = $result->pos;
                }
            } else {
                $target_pos = 0;
            }
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

        //save root node
        $node_array[0]['id'] = $this->saveNodeToDb($node_array[0]['node'], $node_array[0]['id'], $target_id, $target_pos, true);

        if($inc_children) {
            //save element nodes
            for ($i = 1; $i < count($node_array); $i++) {
                if ($node_array[$i]['node']->nodeType == XML_ELEMENT_NODE) {
                    $node_array[$i]['id'] = $this->saveNodeToDb($node_array[$i]['node'], $node_array[$i]['id'], $node_array[$node_array[$i]['parent_index']]['id'], $node_array[$i]['pos']);
                }
            }

            //save other nodes
            for ($i = 1; $i < count($node_array); $i++) {
                if ($node_array[$i]['node']->nodeType != XML_ELEMENT_NODE) {
                    $node_array[$i]['id'] = $this->saveNodeToDb($node_array[$i]['node'], $node_array[$i]['id'], $node_array[$node_array[$i]['parent_index']]['id'], $node_array[$i]['pos']);
                }
            }
        }

        $this->updateLastchange();

        $this->xmldb->endTransaction();

        return $node_array[0]['id'];
    }
    // }}}

    // {{{ getPermissions
    /**
     * @return mixed
     */
    public function getPermissions()
    {
        return $this->getDoctypeHandler()->getPermissions();
    }
    // }}}

    // {{{ getPosById
    /**
     * gets node position in its parents childlist by node db-id.
     *
     * @param    $id (int) node id
     *
     * @return    $pos (int) position in node parents childlist
     */
    private function getPosById($id)
    {
        $query = $this->pdo->prepare(
            "SELECT xml.pos AS pos
            FROM {$this->table_xml} AS xml
            WHERE xml.id = :id AND xml.id_doc = :doc_id"
        );
        $query->execute(array(
            'id' => $id,
            'doc_id' => $this->doc_id,
        ));

        if ($result = $query->fetchObject()) {
            return $result->pos;
        }
        return null;
    }
    // }}}

    // {{{ getNodeIdsByName
    /**
     * gets node-ids by name from specific document
     *
     * @param    $node_ns (string) namespace-prefix
     * @param    $node_name (string) nodename
     *
     * @return    $node_ids (array) db-ids of nodes
     */
    private function getNodeIdsByName($node_ns = '', $node_name = '', $attr_cond = null)
    {
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
                'doc_id' => $this->doc_id,
            )
        ));
        while ($result = $query->fetchObject()) {
            $node_ids[] = $result->id;
        }
        return $node_ids;
    }
    // }}}
    // {{{ getChildIdsByName
    /**
     * gets ids of children of node by their nodename
     *
     * @param    $parent_id (int) db-id of parent node
     * @param    $node_ns (string) namespace prefix of node
     * @param    $node_name (string) nodename of node
     * @param    $only_element_nodes (bool) returns only Element-nodes if true and all childnodes, if false
     *
     * @return    $node_ids (array) list of node db-ids
     */
    private function getChildIdsByName($parent_id, $node_ns = '', $node_name = '', $attr_cond = null, $only_element_nodes = false)
    {
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
                    'doc_id' => $this->doc_id,
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
                    'doc_id' => $this->doc_id,
                )
            ));
        }
        while ($result = $query->fetchObject()) {
            $node_ids[] = $result->id;
        }
        return $node_ids;
    }
    // }}}
    // {{{ getChildnodesByParentId
    /**
     * gets child nodes of a node as string
     *
     * @param   $parent_id (int) id of parent-node
     * @param   $level (int) number of recursive calls. how deep to traverse the tree.
     * @return  $xml_doc (string) xml node definition of node
     */
    private function getChildnodesByParentId($parent_id, $level = PHP_INT_MAX)
    {
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
            'doc_id' => $this->doc_id,
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
                    $xml_doc .= $this->getChildnodesByParentId($row->id, $level - 1);
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

    // {{{ getNameQuery
    /**
     * gets part of sql query for selecting nodes by their name
     *
     * @param    $node_ns (string) namespace prefix of node
     * @param    $node_name (string) name of node
     *
     * @return    $name_query (string)
     */
    private function getNameQuery($node_ns, $node_name)
    {
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

    // {{{ getAttrQuery
    /**
     * gets part of sql query for selecting node by their attribute
     *
     * @param    $attr_cond (array) every element must have following
     *            subelements: name, value and operator.
     *
     * @return    $attr_query (string)
     */
    private function getAttrQuery($attr_cond)
    {
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

    // {{{ saveNodeToDb
    /**
     * saves a node to database
     *
     * @param    $node (domxmlnode) node to save
     * @param    $id (int) db-id to save node in
     * @param    $target_id (int) db-id of parent node
     * @param    $target_pos (int) position to save node at
     * @param    $target_doc (int) doc-id of target document
     * @param    $increase_pos (bool) wether to change positions in target nodes childlist
     *
     * @return    $id (int) db-id under which node has been saved
     */
    private function saveNodeToDb($node, $id, $target_id, $target_pos, $increase_pos = false)
    {
        static $insert_query = null;
        if (is_null($insert_query)) {
            $insert_query = $this->pdo->prepare(
                "INSERT {$this->table_xml}
                SET
                    id = :id_query,
                    id_parent = :target_id,
                    id_doc = :doc_id,
                    pos = :target_pos,
                    name = :name,
                    value = :value,
                    type = :type
                ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)"
            );
        }

        if ($id === null || !is_numeric($id)) {
            $id_query = 'NULL';
        } else {
            $id_query = (int) $id;
        }

        if ($node->nodeType == XML_ELEMENT_NODE) {
            if ($node->prefix != '') {
                $name_query = $node->prefix . ':' . $node->localName;
            } else {
                $name_query = $node->localName;
            }
            $attributes = array();
            foreach ($node->attributes as $attrib) {
                $attrib_ns = ($attrib->prefix == '') ? '' : $attrib->prefix . ':';
                $attrib_name = $attrib->localName;

                $attributes[$attrib_ns . $attrib_name] = $attrib->value;
            }
            $attr_str = $this->getAttributeString($attributes);

            if ($target_id !== null && $increase_pos) {
                $query = $this->pdo->prepare(
                    "UPDATE {$this->table_xml}
                    SET pos = pos + 1
                    WHERE id_parent = :target_id AND pos >= :target_pos AND id_doc = :doc_id"
                );
                $query->execute(array(
                    'target_id' => $target_id,
                    'target_pos' => $target_pos,
                    'doc_id' => $this->doc_id,
                ));
            }

            $insert_query->execute(array(
                'id_query' => $id_query,
                'target_id' => $target_id,
                'target_pos' => $target_pos,
                'name' => $name_query,
                'value' => $attr_str,
                'type' => 'ELEMENT_NODE',
                'doc_id' => $this->doc_id,
            ));

            if ($id === null) {
                $id = $query->lastInsertId();
                $node->setAttributeNS($this->db_ns->uri, $this->db_ns->ns . ':' . $this->id_attribute, $id);
            } else {
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
                'doc_id' => $this->doc_id,
            ));

            if ($id === null) {
                $id = $insert_query->lastInsertId();
            }
        }
        return $id;
    }
    // }}}

    // {{{ updateLastchange
    /**
     * updates the lastchange date and uid for the current document
     */
    protected function updateLastchange()
    {
        $query = $this->pdo->prepare(
            "UPDATE {$this->table_docs}
            SET
                lastchange=:timestamp,
                lastchange_uid=:user_id
            WHERE
                id=:doc_id;"
        );

        $timestamp = time();

        if (!empty($this->xmldb->options['userId'])) {
            $user_id = $this->xmldb->options['userId'];
        } else {
            $user_id = null;
        }

        $params = array(
            'doc_id' => $this->getDocId(),
            'timestamp' => date('Y-m-d H:i:s', $timestamp),
            'user_id' => $user_id,
        );
        if ($query->execute($params)) {
            return $timestamp;
        }

        return false;
    }
    // }}}

    // {{{ getNodeArrayForSaving
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
    private function getNodeArrayForSaving(&$node_array, $node, $parent_index = null, $pos = 0, $stripwhitespace = true)
    {
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

    // {{{ removeIdAttr
    /**
     * remove all db-id attributes recursive from nodes
     *
     * @param    $node (domxmlnode) node to remove attribute from
     */
    public function removeIdAttr($node)
    {
        if ($node->nodeType == XML_ELEMENT_NODE || $node->nodeType == XML_DOCUMENT_NODE) {
            list($xml, $node) = \Depage\Xml\Document::getDocAndNode($node);

            $xpath = new \DOMXPath($xml);
            $xp_result = $xpath->query("./descendant-or-self::node()[@{$this->db_ns->ns}:{$this->id_attribute}]", $node);
            foreach ($xp_result as $node) {
                $node->removeAttributeNS($this->db_ns->uri, $this->id_attribute);
            }
        }
    }
    // }}}

    // {{{ getAttributeString
    /**
     * gets attribute string for saving
     *
     * @param    $attributes (array) array of attribute values
     */
    private function getAttributeString($attributes)
    {
        $attr_str = "";
        $autogeneratedAttr = array(
            $this->db_ns->ns . ':' . $this->id_attribute,
            $this->db_ns->ns . ":lastchange",
            $this->db_ns->ns . ":lastchangeUid",
        );
        foreach($attributes as $name => $value) {
            if (!in_array($name, $autogeneratedAttr)) {
                $attr_str .= "$name=\"" . htmlspecialchars($value) . "\" ";
            }
        }

        return $attr_str;
    }
    // }}}

    // {{{ clearCache
    /**
     * clears the node-cache
     *
     */
    private function clearCache()
    {
        if (!is_null($this->doc_id)) {
            $this->cache->delete("{$this->table_docs}_d{$this->doc_id}/");
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
