<?php
/**
 * @file    modules/xmlDb/Document.php
 *
 * cms xmlDb module
 *
 * copyright (c) 2002-2011 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */
namespace Depage\XmlDb;

class Document
{
    // {{{ variables
    protected $pdo;
    protected $cache;
    protected $db_ns;

    protected $table_prefix;
    protected $table_docs;
    protected $table_xml;
    protected $table_nodetypes;

    protected $xmlDb;
    protected $doc_id;

    protected $id_attribute = 'id';
    protected $id_data_attribute = 'dataid';

    protected $free_element_ids = [];
    protected $doctypeHandlers = [];

    private $childNodeQuery = null;
    private $insertNodeQuery = null;
    // }}}
    // {{{ constructor
    /**
     * Construct
     *
     * @param $xmlDb
     * @param $doc_id
     */
    public function __construct($xmlDb, $doc_id)
    {
        $this->pdo = $xmlDb->pdo;
        $this->cache = $xmlDb->cache;

        $this->db_ns = new XmlNs('db', 'http://cms.depagecms.net/ns/database');

        $this->xmlDb = $xmlDb;

        $this->table_prefix = $xmlDb->table_prefix;
        $this->table_docs = $xmlDb->table_docs;
        $this->table_xml = $xmlDb->table_xml;
        $this->table_nodetypes = $xmlDb->table_nodetypes;

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
    // {{{ getXml
    /**
     * getXml
     *
     * @return string
     */
    public function getXml($add_id_attribute = true)
    {
        $root_id = $this->getDocInfo()->rootid;

        if (is_null($root_id)) {
            throw new Exceptions\XmlDbException('Trying to get contents of empty document.');
        } else {
            $xml = $this->getSubdocByNodeId($root_id, $add_id_attribute);
        }

        return $xml;
    }
    // }}}
    // {{{ getDocInfo
    /**
     * gets info about a document by doc_id
     *
     * @return $info (array)
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
        $query->execute([
            'doc_id' => $this->doc_id,
        ]);

        $info = $query->fetchObject();

        if ($info) {
            $info->lastchange = new \DateTime($info->lastchange);
            $info->lastrelease = $this->getHistory()->getLatestVersion()->lastsaved ?? false;
        }

        return $info;
    }
    // }}}
    // {{{ getDoctypeHandler
    /**
     * @param $doc_id
     *
     * @return mixed
     */
    public function getDoctypeHandler()
    {
        if (!isset($this->doctypeHandlers[$this->doc_id])) {
            $className = $this->getDocInfo()->type;

            if (empty($className)) {
                $handler = new XmlDoctypes\Base($this->xmlDb, $this);
            } else {
                $className = '\\' . $className;
                $handler = new $className($this->xmlDb, $this);
            }

            if ($handler instanceOf XmlDoctypes\DoctypeInterface) {
                $this->doctypeHandlers[$this->doc_id] = $handler;
            } else {
                throw new Exceptions\XmlDbException('Doctype handler must implement DoctypeInterface');
            }
        }

        return $this->doctypeHandlers[$this->doc_id];
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
    // {{{ getNamespacesAndEntities
    public function getNamespacesAndEntities()
    {
        $query = $this->pdo->prepare(
            "SELECT docs.entities AS entities, docs.ns AS namespaces
            FROM {$this->table_docs} AS docs
            WHERE docs.id = :doc_id"
        );
        $query->execute([
            'doc_id' => $this->doc_id,
        ]);

        return $query->fetchObject();
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
        return new DocumentHistory($this->pdo, $this->table_prefix, $this);
    }
    // }}}
    // {{{ isReleased()
    /**
     * @brief isReleased
     *
     * @param mixed
     * @return void
     **/
    public function isReleased()
    {
        $info = $this->getDocInfo();

        if (!empty($info->lastrelease) && $info->lastchange->getTimestamp() <= $info->lastrelease->getTimestamp()) {
            return true;
        }

        return false;
    }
    // }}}
    // {{{ isPublished()
    /**
     * @brief isPublished
     *
     * @param mixed
     * @return void
     **/
    public function isPublished()
    {
        $info = $this->getDocInfo();

        return !empty($info->lastrelease);
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
            ? $db_id = $node->getAttributeNS($this->db_ns->uri, $this->id_data_attribute)
            : null;
    }
    // }}}

    // {{{ getNodeAttributeById
    protected function getNodeAttributeById($id, $attribute)
    {
        $query = $this->pdo->prepare(
            "SELECT xml.$attribute AS $attribute
            FROM {$this->table_xml} AS xml
            WHERE xml.id = :id AND xml.id_doc = :doc_id"
        );
        $query->execute([
            'id' => $id,
            'doc_id' => $this->doc_id,
        ]);

        return ($result = $query->fetchObject()) ? $result->$attribute : false;
    }
    // }}}
    // {{{ getNodeNameById
    /**
     * gets node_name by node db-id
     *
     * @param $id (int) node db-id
     *
     * @return $node_name (string) name of node, false if node doesn't exist.
     */
    public function getNodeNameById($id)
    {
        return $this->getNodeAttributeById($id, 'name');
    }
    // }}}
    // {{{ getParentIdById
    /**
     * gets parent db-id by one of its child_nodes-id
     *
     * @param $id (int) node db-id
     *
     * @return $parent_id (id) db-id of parent node, false, if node doesn't exist.
     */
    public function getParentIdById($id)
    {
        return $this->getNodeAttributeById($id, 'id_parent');
    }
    // }}}
    // {{{ getPosById
    /**
     * gets node position in its parents childlist by node db-id.
     *
     * @param $id (int) node id
     *
     * @return $pos (int) position in node parents childlist
     */
    public function getPosById($id)
    {
        return $this->getNodeAttributeById($id, 'pos');
    }
    // }}}

    // {{{ getNodeIdsByXpath
    /**
     * gets node_ids by xpath
     *
     * @attention this supports only a small subset of xpath-queries. so recheck source before using.
     *
     * @param $this->doc_id (int) id of document
     * @param $xpath (string) xpath to target node
     *
     * @return $nodeids (array) array of found node ids
     *
     * @todo implement full xpath specifications
     */
    public function getNodeIdsByXpath($xpath)
    {
        $identifier = "{$this->table_docs}_d{$this->doc_id}/xpath_" . sha1($xpath);
        $fetched_ids = $this->cache->get($identifier);

        if ($fetched_ids === false) {
            $fetched_ids = $this->xmlDb->getNodeIdsByXpath($xpath, $this->doc_id);

            $this->cache->set($identifier, $fetched_ids);
        }
        return $fetched_ids;
    }
    // }}}
    // {{{ getSubdocByNodeId
    /**
     * gets an xml-document-object from specific db-id
     *
     * @param $id (int) db-id of node to get
     * @param $add_id_attribute (bool) true, if you want to add the db-id
     *        attributes to xml-definition, false to remove them.
     */
    public function getSubdocByNodeId($id, $add_id_attribute = true)
    {
        $identifier = "{$this->table_docs}_d{$this->doc_id}/{$id}.xml";
        $xmlDoc = new \Depage\Xml\Document();

        if ($xmlStr = $this->cache->get($identifier)) {
            // read from cache
            $xmlDoc->loadXML($xmlStr);
        } else {
            // read from database
            $this->beginTransactionNonAltering();

            $query = $this->pdo->prepare(
                "SELECT
                    docs.entities AS entities,
                    docs.ns AS namespaces,
                    docs.lastchange AS lastchange,
                    docs.lastchange_uid AS lastchangeUid
                FROM {$this->table_docs} AS docs
                WHERE docs.id = :doc_id"
            );
            $query->execute([
                'doc_id' => $this->doc_id,
            ]);
            $result = $query->fetchObject();

            $this->entities = $result->entities;
            $this->namespace_string = $result->namespaces;
            $this->namespaces = $this->extractNamespaces($this->namespace_string);
            $this->namespaces[$this->db_ns->ns] = $this->db_ns;
            $this->lastchange = $result->lastchange;
            $this->lastchangeUid = $result->lastchangeUid;

            $pad = 4;
            $query = $this->pdo->prepare(
                "WITH RECURSIVE tree (id, id_parent, lvl, sortkey) AS
                    (
                    SELECT id, id_parent, 0, CAST('' AS CHAR(4000))
                        FROM {$this->table_xml} AS xml
                        WHERE id = :id AND id_doc = :doc_id
                    UNION ALL
                    SELECT x.id, x.id_parent, lvl + 1, CONCAT(sortkey, LPAD(IFNULL(x.pos + 1, 0), {$pad}, '0'), ' ')
                        FROM {$this->table_xml} AS x INNER JOIN tree AS t
                        ON x.id_parent = t.id
                    )
                    SELECT lvl, xml.* FROM tree JOIN {$this->table_xml} AS xml ON tree.id = xml.id ORDER BY sortkey;",
                [\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false]);
            $query->execute([
                'doc_id' => $this->doc_id,
                'id' => $id,
            ]);
            $row = $query->fetchObject();

            //get ROOT-NODE
            if ($row && $row->type == 'ELEMENT_NODE') {
                $this->addChildnodesByQuery($query, $row, $xmlDoc);
            } else {
                $this->endTransaction();

                throw new Exceptions\XmlDbException('This node is no ELEMENT_NODE or node does not exist' . " {$this->doc_id}/{$id}");
            }

            $success = true;
            $this->endTransaction();

            $dth = $this->getDoctypeHandler();
            if ($dth->testDocument($xmlDoc)) { // test whether the document was altered
                $this->saveNode($xmlDoc);
            }

            // add xml to xml-cache
            if (is_object($xmlDoc) && $xmlDoc->documentElement != null) {
                $this->cache->set($identifier, $xmlDoc->saveXML());
            }
        }

        if (is_a($xmlDoc, 'DOMDocument') && $xmlDoc->documentElement != null && !$add_id_attribute) {
            $this->removeIdAttr($xmlDoc);
        }

        return $xmlDoc;
    }
    // }}}
    // {{{ getSubdocByXpath
    /**
     * gets document by xpath. if xpath directs to more than
     * one node, only the first node will be returned.
     *
     * @param    $xpath (string) xpath to target node
     * @param    $add_id_attribute (bool) whether to add db:id attribute or not
     *
     * @return    $doc (domxmlobject)
     */
    public function getSubdocByXpath($xpath, $add_id_attribute = true)
    {
        $subdoc = false;
        $ids = $this->getNodeIdsByXpath($xpath);

        if (isset($ids[0])) {
            $subdoc = $this->getSubdocByNodeId($ids[0], $add_id_attribute);
        }

        return $subdoc;
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

        return (isset($attributes[$attr_name])) ? $attributes[$attr_name] : false;
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
        $attributes = [];

        $query = $this->pdo->prepare(
            "SELECT xml.value
            FROM {$this->table_xml} AS xml
            WHERE xml.id = :node_id AND xml.type='ELEMENT_NODE' AND xml.id_doc = :doc_id"
        );
        $query->execute([
            'node_id' => $node_id,
            'doc_id' => $this->doc_id,
        ]);

        if ($result = $query->fetchObject()) {
            $matches = preg_split('/(="|"$|" )/', $result->value);
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

    // {{{ buildNode
    /**
     * @param $name
     * @param $attributes
     *
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
        $xml .= '/>';

        $doc = new \Depage\Xml\Document();
        $doc->loadXML($xml);

        return $doc->documentElement;
    }
    // }}}
    // {{{ removeIdAttr
    /**
     * recursively remove all db-id attributes from node
     *
     * @param    $node (domxmlnode) node to remove attribute from
     */
    public function removeIdAttr($node)
    {
        self::removeNodeAttr($node, $this->db_ns, $this->id_attribute);
    }
    // }}}

    // {{{ clearDoc
    /**
     * clean all nodes inside of document
     *
     * @return $doc (array)
     */
    public function clearDoc()
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

        $this->beginTransactionAltering();

        if ($info) {
            $query->execute([
                'id' => $info->id,
                'name' => $info->name,
                'rootid' => $info->rootid,
                'type' => $info->type,
                'ns' => $info->namespaces,
            ]);
        }

        $this->endTransaction();

        return $info;
    }
    // }}}
    // {{{ save
    /**
     * @param $xml
     * @return mixed
     * @throws xmlDbException
     */
    public function save(\DomDocument $xml)
    {
        $this->beginTransactionAltering();

        $doc_info = $this->clearDoc();
        $xml_text = $xml->saveXML();

        // @TODO get namespaces from document at this moment it is only per preg_match not by the domxml interface, because
        // @TODO namespace definitions are not available
        preg_match_all('/ xmlns:([^=]*)="([^"]*)"/', $xml_text, $matches, PREG_SET_ORDER);
        $namespaces = '';
        for ($i = 0; $i < count($matches); $i++) {
            if ($matches[$i][1] != $this->db_ns->ns) {
                $namespaces .= $matches[$i][0];
            }
        }

        // @TODO get document and entities or set html_entities as standard as long as php does not inherit the entites() function
        $doc_info->rootid = $this->saveNodePrivate($xml);
        $query = $this->pdo->prepare(
            "UPDATE {$this->table_docs}
            SET
                rootid = :rootid,
                ns = :ns,
                entities=''
            WHERE id = :doc_id"
        );

        $query->execute([
            'rootid' => $doc_info->rootid,
            'ns' => $namespaces,
            'doc_id' => $doc_info->id,
        ]);

        $this->endTransaction();

        return $doc_info->id;
    }
    // }}}

    // {{{ deleteNode
    /**
     * @param $node_id
     *
     * @return bool
     */
    public function deleteNode($node_id)
    {
        $success = false;

        $dth = $this->getDoctypeHandler();

        if ($dth->isAllowedDelete($node_id)) {
            $this->beginTransactionAltering();

            $success = $this->deleteNodePrivate($node_id);

            $this->updateLastChange();
            $this->endTransaction();
        }

        return $success;
    }
    // }}}
    // {{{ saveNode
    public function saveNode($node)
    {
        $this->beginTransactionAltering();

        $result = $this->saveNodePrivate($node);

        $this->endTransaction();

        return $result;
    }
    // }}}

    // {{{ addNode
    /**
     * @param $node
     * @param $target_id
     * @param $target_pos
     *
     * @return bool
     */
    public function addNode(\DomNode $node, $target_id, $target_pos = -1, $extras = [])
    {
        $success = false;
        $dth = $this->getDoctypeHandler();

        if ($dth->isAllowedAdd($node, $target_id)) {
            $dth->onAddNode($node, $target_id, $target_pos, $extras);

            $this->beginTransactionAltering();

            $success = $this->saveNodeIn($node, $target_id, $target_pos, true);

            $this->endTransaction();
        }

        return $success;
    }
    // }}}
    // {{{ addNodeByName
    /**
     * @param $name
     * @param $target_id
     * @param $target_pos
     * @return bool
     */
    public function addNodeByName($name, $target_id, $target_pos, $extras = [])
    {
        $success = false;
        $dth = $this->getDoctypeHandler();
        $newNode = $dth->getNewNodeFor($name);

        if ($newNode) {
            $success = $this->addNode($newNode, $target_id, $target_pos, $extras);
        }

        return $success;
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
        $this->beginTransactionAltering();

        $target_id = $this->getParentIdById($id_to_replace);
        $target_pos = $this->getPosById($id_to_replace);

        $this->deleteNodePrivate($id_to_replace);

        $changed_ids = [];
        $changed_ids[] = $this->saveNodeIn($node, $target_id, $target_pos, true);
        $changed_ids[] = $target_id;

        $this->endTransaction();

        return $changed_ids;
    }
    // }}}
    // {{{ duplicateNode
    /**
     * duplicates node in database, and inserts it in the next position
     *
     * @TODO behaviour should differ according to tree type - don't usually want to copy all sub nodes
     *
     * @param $node_id (int) db-id of node
     *
     * @return $success (bool)
     */
    public function duplicateNode($node_id, $recursive = false)
    {
        $success = false;

        // get parent and position for new node
        $target_id = $this->getParentIdById($node_id);
        $target_pos = $this->getPosById($node_id) + 1;
        $dth = $this->getDoctypeHandler();

        if ($dth->isAllowedMove($node_id, $target_id)) {
            $xmlDoc = $this->getSubdocByNodeId($node_id, false);
            $root_node = $xmlDoc;

            $this->clearCache();
            $this->beginTransactionAltering();

            $copy_id = $this->saveNodeIn($root_node, $target_id, $target_pos, $recursive);
            $success = $copy_id;

            $this->endTransaction();
            $dth->onCopyNode($node_id, $copy_id);
        }

        return $success;
    }
    // }}}

    // {{{ moveNode
    public function moveNode($node_id, $target_id, $target_pos)
    {
        $parent_id = false;
        $dth = $this->getDoctypeHandler();

        if (
            $node_id !== $target_id
            && $dth->isAllowedMove($node_id, $target_id)
        ) {
            $this->beginTransactionAltering();

            $parent_id = $this->moveNodePrivate($node_id, $target_id, $target_pos);

            $this->endTransaction();
            $dth->onMoveNode($node_id, $parent_id);
        }

        return $parent_id;
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
        $parent_id = false;
        $dth = $this->getDoctypeHandler();

        if (
            $node_id !== $target_id
            && $dth->isAllowedMove($node_id, $target_id)
        ) {
            $this->beginTransactionAltering();

            $position = $this->getTargetPos($target_id);
            $parent_id = $this->moveNodePrivate($node_id, $target_id, $position);

            $this->endTransaction();
            $dth->onMoveNode($node_id, $parent_id);
        }

        return $parent_id;
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
        $parent_id = false;
        $dth = $this->getDoctypeHandler();

        $target_parent_id = $this->getParentIdById($target_id);
        if (
            $node_id !== $target_id
            && $dth->isAllowedMove($node_id, $target_parent_id)
        ) {
            $this->beginTransactionAltering();

            $target_pos = $this->getPosById($target_id);
            $parent_id = $this->moveNodePrivate($node_id, $target_parent_id, $target_pos);

            $this->endTransaction();
            $dth->onMoveNode($node_id, $parent_id);
        }

        return $parent_id;
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
        $parent_id = false;
        $dth = $this->getDoctypeHandler();

        $target_parent_id = $this->getParentIdById($target_id);
        if (
            $node_id !== $target_id
            && $dth->isAllowedMove($node_id, $target_parent_id)
        ) {
            $this->beginTransactionAltering();

            $target_pos = $this->getPosById($target_id) + 1;
            $parent_id = $this->moveNodePrivate($node_id, $target_parent_id, $target_pos);

            $this->endTransaction();
            $dth->onMoveNode($node_id, $parent_id);
        }

        return $parent_id;
    }
    // }}}

    // {{{ copyNode
    public function copyNode($node_id, $target_id, $target_pos)
    {
        $copy_id = false;
        $dth = $this->getDoctypeHandler();

        if ($dth->isAllowedCopy($node_id, $target_id)) {
            $this->beginTransactionAltering();

            $copy_id = $this->copyNodePrivate($node_id, $target_id, $target_pos);

            $this->endTransaction();
            $dth->onCopyNode($node_id, $copy_id);
        }

        return $copy_id;
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
        $copy_id = false;
        $dth = $this->getDoctypeHandler();

        if ($dth->isAllowedCopy($node_id, $target_id)) {
            $this->beginTransactionAltering();

            $position = $this->getTargetPos($target_id);
            $copy_id = $this->copyNodePrivate($node_id, $target_id, $position);

            $this->endTransaction();
            $dth->onCopyNode($node_id, $copy_id);
        }

        return $copy_id;
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
        $copy_id = false;
        $dth = $this->getDoctypeHandler();

        $target_parent_id = $this->getParentIdById($target_id);
        if ($dth->isAllowedCopy($node_id, $target_parent_id)) {
            $this->beginTransactionAltering();

            $copy_id = $this->copyNodeWithOffset($node_id, $target_id);

            $this->endTransaction();
            $dth->onCopyNode($node_id, $copy_id);
        }

        return $copy_id;
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
        $copy_id = false;
        $dth = $this->getDoctypeHandler();

        $target_parent_id = $this->getParentIdById($target_id);
        if ($dth->isAllowedCopy($node_id, $target_parent_id)) {
            $this->beginTransactionAltering();

            $copy_id = $this->copyNodeWithOffset($node_id, $target_id, 1);

            $this->endTransaction();
            $dth->onCopyNode($node_id, $copy_id);
        }

        return $copy_id;
    }
    // }}}

    // {{{ deleteNodePrivate
    /**
     * unlinks and deletes a specific node from database.
     * re-indexes the positions of the remaining elements.
     *
     * @param     $node_id (int) db-id of node to delete
     *
     * @return    $deleted_ids (array) id of parent node
     */
    protected function deleteNodePrivate($node_id)
    {
        // get parent and position (enables other node positions to be updated after delete)
        $target_id = $this->getParentIdById($node_id);
        $target_pos = $this->getPosById($node_id);

        $dth = $this->getDoctypeHandler();

        if ($dth->onDeleteNode($node_id, $target_id)) {
            // delete the node
            $query = $this->pdo->prepare(
                "DELETE FROM {$this->table_xml}
                WHERE id_doc = :doc_id AND id = :node_id"
            );
            $query->execute([
                'doc_id' => $this->doc_id,
                'node_id' => $node_id,
            ]);

            // update position of remaining nodes
            $query = $this->pdo->prepare(
                "UPDATE {$this->table_xml}
                    SET pos=pos-1
                    WHERE id_parent = :node_parent_id AND pos > :node_pos AND id_doc = :doc_id"
            );
            $query->execute([
                'node_parent_id' => $target_id,
                'node_pos' => $target_pos,
                'doc_id' => $this->doc_id,
            ]);
        }
        return $target_id;
    }
    // }}}
    // {{{ moveNodePrivate
    /**
     * moves node in database
     *
     * // TODO prevent moving a node to its children
     *
     * @protected
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     * @param    $target_pos (int) position to move to
     *
     * @return   (int) id of new parent node
     */
    protected function moveNodePrivate($node_id, $target_id, $target_pos)
    {
        $result = false;
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
            $query->execute([
                'node_id' => $node_id,
                'doc_id' => $this->doc_id,
            ]);
            // update position on source position
            $query = $this->pdo->prepare(
                "UPDATE {$this->table_xml}
                SET pos=pos-1
                WHERE id_parent = :node_parent_id AND pos > :node_pos AND id_doc = :doc_id"
            );
            $query->execute([
                'node_parent_id' => $node_parent_id,
                'node_pos' => $node_pos,
                'doc_id' => $this->doc_id,
            ]);

            // update positions on target position
            $query = $this->pdo->prepare(
                "UPDATE {$this->table_xml}
                SET pos=pos+1
                WHERE id_parent = :target_id AND pos >= :target_pos AND id_doc = :doc_id"
            );
            $query->execute([
                'target_id' => $target_id,
                'target_pos' => $target_pos,
                'doc_id' => $this->doc_id,
            ]);
            // reattach node at target position
            $query = $this->pdo->prepare(
                "UPDATE {$this->table_xml}
                SET id_doc = :doc_id, id_parent = :target_id, pos = :target_pos
                WHERE id = :node_id"
            );
            $query->execute([
                'target_id' => $target_id,
                'target_pos' => $target_pos,
                'node_id' => $node_id,
                'doc_id' => $this->doc_id,
            ]);

            $this->updateLastChange();
            $result = $node_parent_id;
        }

        return $result;
    }
    // }}}
    // {{{ copyNodePrivate
    /**
     * copy node in database
     *
     * @public
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     * @param    $target_pos (int) pos to copy to
     */
    protected function copyNodePrivate($node_id, $target_id, $target_pos)
    {
        $xmlDoc = $this->getSubdocByNodeId($node_id, false);
        $root_node = $xmlDoc;
        $save_id = $this->saveNodeIn($root_node, $target_id, $target_pos, true);

        return $save_id;
    }
    // }}}
    // {{{ copyNodeWithOffset
    /**
     * copy node after another node
     *
     * @param    $node_id (int) db-id of node
     * @param    $target_id (int) db-id of target node
     * @param    $target_pos_offset (int) offset of target position
     */
    protected function copyNodeWithOffset($node_id, $target_id, $target_pos_offset = 0)
    {
        $target_parent_id = $this->getParentIdById($target_id);
        $target_pos = $this->getPosById($target_id) + $target_pos_offset;
        $copy_id = $this->copyNodePrivate($node_id, $target_parent_id, $target_pos);

        return $copy_id;
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

        $this->beginTransactionAltering();

        $attributes = $this->getAttributes($node_id);

        if (
            (
                isset($attributes[$attr_name])
                && $attributes[$attr_name] !== htmlspecialchars($attr_value)
            )
            || !isset($attributes[$attr_name])
        ) {
            $dth = $this->getDoctypeHandler();

            $attributes[$attr_name] = $attr_value;

            $dth->onSetAttribute($node_id, $attr_name, $attributes[$attr_name], $attr_value);

            $success = $this->saveAttributes($node_id, $attributes);
        }

        $this->endTransaction();

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

        $this->beginTransactionAltering();

        $attributes = $this->getAttributes($node_id);

        if (isset($attributes[$attr_name])) {
            unset($attributes[$attr_name]);

            $success = $this->saveAttributes($node_id, $attributes);
        }

        $this->endTransaction();

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
        $query = $this->pdo->prepare(
            "UPDATE {$this->table_xml} AS xml
            SET xml.value = :attr_str
            WHERE xml.id = :node_id AND xml.id_doc = :doc_id"
        );
        $success = $query->execute([
            'node_id' => $node_id,
            'attr_str' => $this->getAttributeString($attributes),
            'doc_id' => $this->doc_id,
        ]);

        $this->updateLastChange();

        return $success;
    }
    // }}}
    // {{{ removeNodeAttr
    /**
     * recursively remove attributes from node
     */
    public static function removeNodeAttr($node, $db_ns, $attribute)
    {
        if ($node->nodeType == XML_ELEMENT_NODE || $node->nodeType == XML_DOCUMENT_NODE) {
            list($xml, $node) = \Depage\Xml\Document::getDocAndNode($node);

            $xpath = new \DOMXPath($xml);
            $xpath->registerNamespace($db_ns->ns, $db_ns->uri);
            $xp_result = $xpath->query(".//@{$db_ns->ns}:{$attribute}", $node);

            foreach ($xp_result as $node) {
                $node->parentNode->removeAttributeNode($node);
            }
        }
    }
    // }}}
    // {{{ hashDomNode()
    /**
     * @brief hashDomNode
     *
     * @param mixed $node
     * @return void
     **/
    public function hashDomNode($node)
    {
        $doc = new \DOMDocument();
        $doc->formatOutput = false;

        list($d, $node) = \Depage\Xml\Document::getDocAndNode($node);
        $rootNode = $doc->importNode($node, true);
        $doc->appendChild($rootNode);

        self::removeIdAttr($rootNode);
        self::removeNodeAttr($rootNode, $this->db_ns, 'lastchange');
        self::removeNodeAttr($rootNode, $this->db_ns, 'lastchangeUid');
        self::removeNodeAttr($rootNode, $this->db_ns, 'docid');
        self::removeNodeAttr($rootNode, $this->db_ns, 'released');

        return hash("sha256", $doc->saveXML());
    }
    // }}}
    // {{{ getAttributeString
    /**
     * gets attribute string for saving
     *
     * @param $attributes (array) array of attribute values
     */
    protected function getAttributeString($attributes)
    {
        $attr_str = '';
        $autogeneratedAttr = [
            $this->db_ns->ns . ':' . $this->id_attribute,
            $this->db_ns->ns . ':released',
            $this->db_ns->ns . ':lastchange',
            $this->db_ns->ns . ':lastchangeUid',
        ];
        ksort($attributes);
        foreach($attributes as $name => $value) {
            if (!in_array($name, $autogeneratedAttr)) {
                $attr_str .= "$name=\"" . htmlspecialchars($value) . "\" ";
            }
        }

        return $attr_str;
    }
    // }}}

    // {{{ getFreeNodeIds
    /**
     * gets unused db-node-ids for saving nodes
     *
     * @param $needed (int) minimum number of ids, that are requested
     */
    protected function getFreeNodeIds($needed = 1, $preference = [])
    {
        $lastMax = 0;

        if (!empty($preference)) {
            $ids = str_repeat('?,', count($preference) - 1) . '?';
            $query = $this->pdo->prepare("SELECT id FROM {$this->table_xml} WHERE id IN ($ids)");
            $query->execute($preference);
            $results = $query->fetchAll(\PDO::FETCH_COLUMN);

            $free = array_flip(array_diff($preference, $results));
        } else {
            $free = [];
        }

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
            ) LIMIT :maxCount;",
            [\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false]);

        do {
            $query->execute([
                'start' => $lastMax,
                'maxCount' => $needed,
            ]);

            $found = false;
            while ($row = $query->fetchColumn()) {
                $id = (int) $row;
                $found = true;
                $lastMax = $id;
                $free[$id] = null;
            }
        } while (count($free) < $needed && $found);

        $num = count($free);

        if ($num < $needed) {
            $query = $this->pdo->prepare(
                "SELECT IFNULL(MAX(xml.id), 0) + 1 AS id_max
                FROM {$this->table_xml} AS xml"
            );
            $query->execute();
            $result = $query->fetchObject();

            $until = $needed - $num;
            for ($i = 0; $i < $until; $i++) {
                $free[$result->id_max + $i] = null;
            }
        }

        $this->free_element_ids = array_keys($free);
    }
    // }}}
    // {{{ addChildnodesByQuery
    /**
     * add child nodes of a node to DOMDocument
     *
     * @param   $query (object) database query to get next row
     * @param   $row (object) last result row for parent
     * @param   $parentNode (object) DOMElement of parent node or empty DOMDocument
     *
     * @return  $row (object) values of last queried row
     */
    protected function addChildnodesByQuery($query, $row, $parentNode)
    {
        $doc = $parentNode->ownerDocument ?? $parentNode;

        do {
            //get ELMEMENT_NODE
            if ($row->type == 'ELEMENT_NODE') {
                //create node
                if ($ns = $this->getNamespace($row->name)) {
                    $node = $doc->createElementNS($ns->uri, $row->name);
                } else {
                    $node = $doc->createElement($row->name);
                }
                $parentNode->appendChild($node);

                if ($row->lvl == 0) {
                    foreach($this->namespaces as $n) {
                        $node->setAttributeNS('http://www.w3.org/2000/xmlns/', "xmlns:{$n->ns}", $n->uri);
                    }

                    //add lastchange-data
                    $node->setAttributeNS($this->db_ns->uri, "{$this->db_ns->ns}:lastchange", $this->lastchange);
                    $node->setAttributeNS($this->db_ns->uri, "{$this->db_ns->ns}:lastchangeUid", $this->lastchangeUid);
                }

                //add attributes to node
                $matches = array_chunk(preg_split('/(="|"$|" )/', $row->value), 2);
                foreach($matches as $m) {
                    $attrName = trim($m[0]);
                    $attrValue = htmlspecialchars_decode($m[1] ?? '');

                    if ($attrName != '') {
                        if ($ns = $this->getNamespace($attrName)) {
                            $node->setAttributeNS($ns->uri, $attrName, $attrValue);
                        } else {
                            $node->setAttribute($attrName, $attrValue);
                        }
                    }
                }

                //add id_attribute to node
                $node->setAttributeNS($this->db_ns->uri, "{$this->db_ns->ns}:{$this->id_attribute}", $row->id);

            //get TEXT_NODES
            } else if ($row->type == 'TEXT_NODE') {
                $node = $doc->createTextNode(strtr($row->value, "\r", " "));
                $parentNode->appendChild($node);
            //get CDATA_SECTION
            } else if ($row->type == 'CDATA_SECTION_NODE') {
                // @todo CDATA not implemented yet
            //get COMMENT_NODE
            } else if ($row->type == 'COMMENT_NODE') {
                $node = $doc->createComment($row->value);
                $parentNode->appendChild($node);
            //get PROCESSING_INSTRUCTION
            } else if ($row->type == 'PI_NODE') {
                $node = $doc->createProcessingInstruction($row->name, $row->value);
                $parentNode->appendChild($node);
            //get ENTITY_REF Node
            } else if ($row->type == 'ENTITY_REF_NODE') {
                // @todo ENTITY_REF_NODE not implemented yet
            }

            $lastRow = $row;
            $row = $query->fetchObject();

            if ($lastRow->type == 'ELEMENT_NODE') {
                if ($row && $lastRow->lvl < $row->lvl) {
                    //add child_nodes
                    $row = $this->addChildnodesByQuery($query, $row, $node);
                }
            }
            if (!$row || $lastRow->lvl > $row->lvl) {
                return $row;
            }
        } while ($row);

        return $row;
    }
    // }}}
    // {{{ getTargetPos
    protected function getTargetPos($target_id)
    {
        $query = $this->pdo->prepare(
            "SELECT IFNULL(MAX(xml.pos), -1) + 1 AS pos
            FROM {$this->table_xml} AS xml
            WHERE xml.id_parent = :target_id AND id_doc = :doc_id"
        );
        $query->execute([
            'target_id' => $target_id,
            'doc_id' => $this->doc_id,
        ]);

        if ($object = $query->fetchObject()) {
            $result = $object->pos;
        } else {
            $result = null;
        }

        return $result;
    }
    // }}}

    // {{{ parseNodename()
    /**
     * @brief parseNodename
     *
     * @param mixed $name
     * @return void
     **/
    protected function getNamespace($name)
    {
        $parts = explode(":", $name, 2);

        if (!isset($parts[1])) {
            return false;
        }

        return $this->namespaces[$parts[0]];
    }
    // }}}
    // {{{ extractNamespaces
    protected function extractNamespaces($str)
    {
        $namespaces = [];
        $pName = '([a-zA-Z0-9]*)';
        $pAttr = '([^"]*)';
        preg_match_all("/xmlns:$pName=\"$pAttr\"/", $str, $ns_elements, PREG_SET_ORDER);

        foreach ($ns_elements AS $ns_element) {
            $namespaces[$ns_element[1]] = new XmlNs($ns_element[1], $ns_element[2]);
        }

        return $namespaces;
    }
    // }}}

    // {{{ saveNodePrivate
    protected function saveNodePrivate($node)
    {
        // get all nodes in array
        $node_array = [];
        $this->getNodeArrayForSaving($node_array, $node);
        $rootId = $node_array[0]['id'];

        if (is_null($rootId)) {
            $target_id = null;
            $target_pos = 0;
        } else {
            // set target_id/pos/doc
            $target_id = $this->getParentIdById($rootId);
            $target_pos = $this->getPosById($rootId);

            if ($target_id === false) {
                $target_id = null;
            }
            if ($target_pos === false) {
                $target_pos = null;
            }

            // delete old node
            $this->deleteNodePrivate($rootId);
        }

        return $this->saveNodeArray($node_array, $target_id, $target_pos, true);
    }
    // }}}
    // {{{ saveNodeIn
    protected function saveNodeIn($node, $target_id, $target_pos, $inc_children)
    {
        $this->removeIdAttr($node);

        $parent_id = $this->getParentIdById($target_id);

        // delete child nodes, if target is document
        if ($parent_id === false) {
            $this->pdo->exec('SET foreign_key_checks = 0;');
            $query = $this->pdo->prepare(
                "DELETE
                FROM {$this->table_xml}
                WHERE id_doc = :doc_id"
            );
            $query->execute([
                'doc_id' => $this->doc_id,
            ]);
            $this->pdo->exec('SET foreign_key_checks = 1;');
        }

        $position = $this->getTargetPos($target_id);

        if ($position) {
            if ($target_pos > $position || $target_pos == -1) {
                $target_pos = $position;
            }
        } else {
            $target_pos = 0;
        }

        // get all nodes in array
        $node_array = [];
        $this->getNodeArrayForSaving($node_array, $node);

        return $this->saveNodeArray($node_array, $target_id, $target_pos, $inc_children);
    }
    // }}}
    // {{{ saveNodeArray
    protected function saveNodeArray($node_array, $target_id, $target_pos, $inc_children)
    {
        $ids = [];

        foreach ($node_array as $node) {
            if ($node['id']) {
                $ids[] = $node['id'];
            }
        }

        $this->getFreeNodeIds(count($node_array), $ids);

        foreach ($node_array as $i => $node) {
            if (!is_null($node['id'])) {
                $index = array_search($node['id'], $this->free_element_ids);

                if ($index === false) {
                    $node_array[$i]['id'] = null;
                } else {
                    array_splice($this->free_element_ids, $index, 1);
                }
            }
        }

        foreach ($node_array as $i => $node) {
            if (is_null($node['id'])) {
                $node_array[$i]['id'] = array_shift($this->free_element_ids);
            }
        }

        // save root node
        $node_array[0]['id'] = $this->saveNodeToDb($node_array[0]['node'], $node_array[0]['id'], $target_id, $target_pos, true);
        $this->insertNodeQuery = null;

        if ($inc_children) {
            // save element nodes
            $this->saveNodesByType($node_array, true);
            // save other nodes
            $this->saveNodesByType($node_array, false);
        }

        $this->updateLastChange();

        return $node_array[0]['id'];
    }
    // }}}
    // {{{ saveNodesByType
    protected function saveNodesByType(&$node_array, $xml_element_node)
    {
        $nodes = count($node_array);

        for ($i = 1; $i < $nodes; $i++) {
            if (($node_array[$i]['node']->nodeType == XML_ELEMENT_NODE) == $xml_element_node) {
                $node_array[$i]['id'] = $this->saveNodeToDb($node_array[$i]['node'], $node_array[$i]['id'], $node_array[$node_array[$i]['parent_index']]['id'], $node_array[$i]['pos']);
            }
        }
        $this->insertNodeQuery = null;
    }
    // }}}
    // {{{ getNodeArrayForSaving
    /**
     * gets all nodes of a document in one array
     *
     * @protected
     *
     * @param    $node_array (array) list of nodes to add current node to
     * @param    $node (domxmlnode) current node
     * @param    $parent_index (int) index of parent node in created node list
     * @param    $pos (int) position of current node
     * @param    $stripwhitespace (bool) whether to strip whitespace from
     *           textnodes while saving
     */
    protected function getNodeArrayForSaving(&$node_array, $node, $parent_index = null, $pos = 0, $stripwhitespace = true)
    {
        $type = $node->nodeType;

        if ($type === XML_DOCUMENT_NODE) {
            $root_node = $node->documentElement;
            $this->getNodeArrayForSaving($node_array, $root_node, $parent_index, $pos, $stripwhitespace);
        } elseif ($type === XML_ELEMENT_NODE) {
            $id = $this->getNodeId($node);
            $node_array[] = [
                'id' => $id,
                'id_old' => $id,
                'parent_index' => $parent_index,
                'pos' => $pos,
                'node' => $node,
            ];

            $parent_index = count($node_array) - 1;
            $node_name = (($node->prefix != '') ? $node->prefix . ':' : '') . $node->localName;

            $preserveWhitespace = $this->getDoctypeHandler()->getPreserveWhitespace();

            if (
                !$stripwhitespace
                || in_array($node_name, $preserveWhitespace)
            ) {
                $stripwhitespace = false;
            }

            $tmp_node = $node->firstChild;
            $i = 0;
            while ($tmp_node != null) {
                if (
                    $tmp_node->nodeType != XML_TEXT_NODE
                    || (
                        !$stripwhitespace
                        || trim($tmp_node->textContent) != ''
                    )
                ) {
                    $this->getNodeArrayForSaving($node_array, $tmp_node, $parent_index, $i, $stripwhitespace);
                    $i++;
                }
                $tmp_node = $tmp_node->nextSibling;
            }
        } else {
            // is *_NODE
            $node_array[] = [
                'id' => null,
                'id_old' => null,
                'parent_index' => $parent_index,
                'pos' => $pos,
                'node' => $node,
            ];
        }
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
     * @param    $increase_pos (bool) whether to change positions in target nodes childlist
     *
     * @return    $id (int) db-id under which node has been saved
     */
    protected function saveNodeToDb($node, $id, $target_id, $target_pos, $increase_pos = false)
    {
        if (is_null($this->insertNodeQuery)) {
            $this->insertNodeQuery = $this->pdo->prepare(
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

        if (is_null($id) || !is_numeric($id)) {
            $id_query = null;
        } else {
            $id_query = (int) $id;
        }

        $node_name = null;
        if ($node->nodeType == XML_ELEMENT_NODE) {
            if ($node->prefix != '') {
                $name_query = $node->prefix . ':' . $node->localName;
            } else {
                $name_query = $node->localName;
            }

            $attributes = [];
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
                $query->execute([
                    'target_id' => $target_id,
                    'target_pos' => $target_pos,
                    'doc_id' => $this->doc_id,
                ]);
            }

            $node_type = 'ELEMENT_NODE';
            $node_data = $attr_str;
            $node_name = $name_query;
        } else if ($node->nodeType == XML_TEXT_NODE) {
            $node_type = 'TEXT_NODE';
            $node_data = $node->textContent;
        } else if ($node->nodeType == XML_COMMENT_NODE) {
            $node_type = 'COMMENT_NODE';
            $node_data = $node->textContent;
        } else if ($node->nodeType == XML_PI_NODE) {
            $node_type = 'PI_NODE';
            $node_data = $node->textContent;
            $node_name = $node->target;
        } else if ($node->nodeType == XML_ENTITY_REF_NODE) {
            $node_type = 'ENTITY_REF_NODE';
            $node_data = $node->nodeName;
        } else {
            throw new Exceptions\XmlDbException('Unknown DOM node type: "' . $node->nodeType . '".');
        }
        $node_data = \Normalizer::normalize($node_data);

        $this->insertNodeQuery->execute([
            'id_query' => $id_query,
            'target_id' => $target_id,
            'target_pos' => $target_pos,
            'name' => $node_name,
            'value' => $node_data,
            'type' => $node_type,
            'doc_id' => $this->doc_id,
        ]);

        if (is_null($id)) {
            $id = $this->pdo->lastInsertId();
        }

        if ($node->nodeType == XML_ELEMENT_NODE) {
            $node->setAttributeNS($this->db_ns->uri, $this->db_ns->ns . ':' . $this->id_attribute, $id);
        }

        return $id;
    }
    // }}}

    // {{{ beginTransactionAltering
    public function beginTransactionAltering()
    {
        return $this->xmlDb->beginTransactionAltering();
    }
    // }}}
    // {{{ beginTransactionNonAltering
    public function beginTransactionNonAltering()
    {
        return $this->xmlDb->beginTransactionNonAltering();
    }
    // }}}
    // {{{ endTransaction
    public function endTransaction()
    {
        $altered = $this->xmlDb->endTransaction();

        if ($altered) {
            $this->clearCache();
            $this->getDoctypeHandler()->onDocumentChange();
        }

        return $altered;
    }
    // }}}
    // {{{ updateLastChange
    /**
     * set or updates the lastchange date and uid for the current document
     *
     * @param $timestamp (int) optional timestamp, defaults to now
     * @param $uid (int) optional user id, defaults to current user, when user
     *        is set in xmlDb options
     */
    public function updateLastChange($timestamp = null, $uid = null) {
        $query = $this->pdo->prepare(
            "UPDATE {$this->table_docs}
            SET
                lastchange=:timestamp,
                lastchange_uid=IFNULL(:user_id, lastchange_uid)
            WHERE
                id=:doc_id;"
        );

        if (empty($timestamp)) {
            $timestamp = time();
        } else if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        if (!empty($uid)) {
            $user_id = $uid;
        } else if (!empty($this->xmlDb->options['userId'])) {
            $user_id = $this->xmlDb->options['userId'];
        } else {
            $user_id = null;
        }

        $params = [
            'doc_id' => $this->getDocId(),
            'timestamp' => date('Y-m-d H:i:s', $timestamp),
            'user_id' => $user_id,
        ];

        return ($query->execute($params)) ? $timestamp : false;
    }
    // }}}
    // {{{ clearCache
    /**
     * clears the node-cache
     */
    public function clearCache()
    {
        if (!is_null($this->doc_id)) {
            $this->cache->delete("{$this->table_docs}_d{$this->doc_id}/");
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
