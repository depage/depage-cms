<?php
/**
 * @file    modules/xmldb/xmldb.php
 *
 * cms xmldb module
 *
 *
 * copyright (c) 2002-2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 *
 */

namespace Depage\XmlDb;

class XmlDb implements XmlGetter
{
    // {{{ variables
    private $doc_ids = array();

    private $pdo;
    private $cache;

    private $db_ns;

    private $table_prefix = 'dp_';
    private $table_docs;
    private $table_xml;
    private $table_nodetypes;
    private $transactions = 0;

    public $options;
    // }}}
    // {{{ constructor
    public function __construct($table_prefix, $pdo, $cache, $options = array())
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_NATURAL);

        $this->cache = $cache;

        $this->options = $options;

        $this->db_ns = new XmlNs("db", "http://cms.depagecms.net/ns/database");

        $this->table_prefix = $table_prefix;
        $this->table_docs = $table_prefix . "_xmldocs";
        $this->table_xml = $table_prefix . "_xmltree";
        $this->table_nodetypes = $table_prefix . "_xmlnodetypes";
    }
    // }}}

    // {{{ __get
    /**
     * Get properties (basically read-only)
     *
     * @param $property
     * @return mixed
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }
    // }}}

    // {{{ docExists
    /**
     * gets the doc-id of a xml-document by name or id and checks if the document exists
     *
     * @param     $doc_id_or_name (mixed) id or name of the document
     * @return    (int) id of the document or false when document does not exist
     */
    public function docExists($doc_id_or_name)
    {
        if (!isset($this->doc_ids[$doc_id_or_name])) {
            if ((int) $doc_id_or_name > 0) {

                $id = (int) $doc_id_or_name;

                // is already a doc-id
                $query = $this->pdo->prepare(
                    "SELECT docs.name AS docname
                    FROM {$this->table_docs} AS docs
                    WHERE docs.id = :doc_id"
                );
                $query->execute(array(
                    'doc_id' => $id,
                ));

                $result = $query->fetchObject();

                if ($result === false) {
                    // document does not exist
                    return false;
                }

                $name = $result->docname;

            } else {

                $name = $doc_id_or_name;

                $doc_list = $this->getDocuments($name);

                if (!isset($doc_list[$name])) {
                    // document does not exist
                    return false;
                }

                $id = $doc_list[$name]->getDocId();
            }

            $this->doc_ids[$id] = $id;
            $this->doc_ids[$name] = $id;
        }

        return $this->doc_ids[$doc_id_or_name];
    }
    // }}}

    // {{{ getDocuments
    /**
     * gets available documents in database
     *
     * @return    $docs (array) the key is the name of the document, the value is the document db-id.
     */
    public function getDocuments($name = "")
    {
        $docs = array();

        $namequery = "";
        $query_param = array();

        if ($name) {
            $namequery = "WHERE name = :name";
            $query_param = array(
                'name' => $name
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
            $docs[$doc->name] = new Document($this, $doc->id);
        }

        return $docs;
    }
    // }}}
    // {{{ getDoc
    /**
     * Get xmldb\document
     *
     * @param $doc_id_or_name
     * @return bool|document
     */
    public function getDoc($doc_id_or_name)
    {
        if ($doc_id = $this->docExists($doc_id_or_name)) {
            return new Document($this, $doc_id);
        }

        return false;
    }
    // }}}
    // {{{ getDocByNodeId
    /**
     * Get xmldb\document
     *
     * @param $nodeId
     * @return bool|document
     */
    public function getDocByNodeId($nodeId)
    {
        $query = $this->pdo->prepare(
            "SELECT
                xml.id_doc AS id_doc
            FROM {$this->table_xml} AS xml
            WHERE xml.id = :nodeId"
        );

        $query->execute(array(
            'nodeId' => $nodeId,
        ));
        $result = $query->fetchObject();

        if ($result && $doc_id = $this->docExists($result->id_doc)) {
            return new Document($this, $doc_id);
        }

        return false;
    }
    // }}}

    // {{{ getDocXml
    /**
     * @param $doc_id_or_name
     * @param bool $add_id_attribute
     * @return bool
     */
    public function getDocXml($doc_id_or_name, $add_id_attribute = true)
    {
        $xml = false;

        if ($doc_id = $this->docExists($doc_id_or_name)) {
            $doc = new Document($this, $doc_id);
            $xml = $doc->getXml($add_id_attribute);
        }

        return $xml;
    }
    // }}}

    // {{{ getSubDocByXpath
    /**
     * gets document by xpath. if xpath directs to more than
     * one node, only the first node will be returned.
     *
     * @param   $xpath (string) xpath to target node
     * @param   $add_id_attribute (bool) whether to add db:id attribute or not
     *
     * @return  $doc (domxmlobject)
     *
     * @todo    implement
     */
    public function getSubDocByXpath($xpath, $add_id_attribute = true)
    {
        return false;
    }
    // }}}
    // {{{ getNodeIdsByXpath
    /**
     * gets node_ids by xpath
     *
     * @attention this supports only a small subset of xpath-queries. so recheck source before using.
     *
     * @param   $this->doc_id (int) id of document
     * @param   $xpath (string) xpath to target node
     *
     * @return  $nodeids (array) array of found node ids
     *
     * @todo    implement full xpath specifications
     */
    public function getNodeIdsByXpath($xpath, $docId = null)
    {
        $pName = '(?:([^\/\[\]]*):)?([^\/\[\]]+)';
        $pCondition = '(?:\[(.*?)\])?';
        preg_match_all("/(\/+)$pName$pCondition/", $xpath, $xpathElements, PREG_SET_ORDER);

        $actualIds = array(null);

        $tables['sql'] = array();
        $tables['params'] = array();
        $conds['sql'] = array();
        $conds['params'] = array();

        foreach ($xpathElements as $level => $element) {
            $fetchedIds = array();
            $element[] = '';
            list(,$divider, $ns, $name, $condition) = $element;

            if ($level == 0) {
                $levels = count($xpathElements) - 1;
                $tables['sql'][] = "SELECT l$levels.id FROM";
                if ($divider == '/') {
                    $conds['sql'][] = "l$level.id_parent IS NULL";
                }
            } else {
                $tables['sql'][] = 'INNER JOIN';
                if ($divider == '/') {
                    $parentLevel = $level - 1;
                    $conds['sql'][] = "l$level.id_parent = l$parentLevel.id";
                }
            }

            $tables['sql'][] = "{$this->table_xml} AS l$level";
            $conds['sql'][] = "l$level.name LIKE ?";
            $conds['params'][] = $this->translateName($ns, $name);

            if (!is_null($docId)) {
                $conds['sql'][] = "l$level.id_doc = ?";
                $conds['params'][] = $docId;
            }

            if ($condition == '') {
                // fetch only by name "/ns:name ..."
            } else if (preg_match('/^([0-9]+)$/', $condition, $matches)) {
                // fetch by name and position: "... /ns:name[n] ..."
                $position = $matches[0] - 1;
                $conds['sql'][count($conds['sql']) - 1] .= " ORDER BY l$level.pos LIMIT $position,1";
            } else if ($attributes = $this->parseAttributes($condition)) {
                // fetch by simple attributes: "//ns:name[@attr1] ..."
                if ($attributes) {
                    $attributeCondition = '(';
                    foreach ($attributes as $attribute) {
                        if ($attribute['name'] == 'db:id') {
                            $attributeCondition .= " l$level.id = ? " . $attribute['operator'];
                            $conds['params'][] = $attribute['value'];
                        } else {
                            $attributeCondition .= " l$level.value REGEXP ? " . $attribute['operator'];
                            $value = (is_null($attribute['value'])) ? '.*' : $attribute['value'];
                            $conds['params'][] = "(^| ){$attribute['name']}=\"$value\"( |$)";
                        }
                    }
                    $attributeCondition .= ')';
                    $conds['sql'][] = $attributeCondition;
                }
            } else {
                // not yet implemented
            }
        }

        $sql = implode(' ', $tables['sql']) . ' WHERE ' . implode(' AND ', $conds['sql']);
        $params = array_merge($tables['params'], $conds['params']);

        $query = $this->pdo->prepare($sql);
        $query->execute($params);
        $results = $query->fetchAll();

        $fetchedIDs = array();
        foreach ($results as $result) {
            $fetchedIds[] = $result[0];
        }

        return $fetchedIds;
    }
    // }}}
    // {{{ translateName
    protected function translateName($ns, $name)
    {
        if ($ns == '*') {
            $translation = '%:';
        } elseif ($ns == '') {
            $translation = '';
        } else {
            $translation = "$ns:";
        }

        $translation .= ($name == '*') ? '%' : $name;

        return $translation;
    }
    // }}}

    // {{{ parseAttributes
    protected function parseAttributes($condition)
    {
        $cond_array = false;

        if (preg_match("/[\w\d@=: _-]*/", $temp_condition = $this->removeLiteralStrings($condition, $strings))) {
            /**
             * "//ns:name[@attr1] ..."
             * "//ns:name[@attr1 = 'string1'] ..."
             * "//ns:name[@attr1 = 'string1' and/or @attr2 = 'string2'] ..."
             */
            $cond_array = $this->getConditionAttributes($temp_condition, $strings);
        }

        return $cond_array;
    }
    // }}}
    // {{{ getConditionAttributes
    protected function getConditionAttributes($condition, $strings)
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
    // }}}
    // {{{ removeLiteralStrings
    protected function removeLiteralStrings($text, &$strings)
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

    // {{{ createDoc
    /**
     * CreateDoc
     *
     * @param string $docType class-name of doctype for new document
     * @param string $docName optional name of document
     * @return Document
     * @throws xmldbException
     */
    public function createDoc($docType = 'Depage\XmlDb\XmlDocTypes\Base', $docName = null)
    {
        if (is_null($docName)) {
            // generate generic docname based on doctype
            $docName = '_' . substr($docType, strrpos($docType, "\\") + 1) . '_' . sha1(uniqid(dechex(mt_rand(256, 4095))));
        }
        if (!is_string($docName)) {
            throw new XmlDbException("You have to give a valid name to save a new document.");
        }

        $query = $this->pdo->prepare(
            "INSERT {$this->table_docs} SET
                name = :name, type = :type;"
        );
        $query->execute(array(
            'name' => $docName,
            'type' => $docType,
        ));

        $docId = $this->pdo->lastInsertId();

        $document = new Document($this, $docId);

        return $document;
    }
    // }}}
    // {{{ duplicateDoc
    /**
     * @brief duplicateDoc
     *
     * @param mixed $docNameOrId
     * @param string $newName optional name for new document
     * @return bool success
     **/
    public function duplicateDoc($docNameOrId, $newName = null)
    {
        $original = $this->getDoc($docNameOrId);

        if ($original !== false) {
            $info = $original->getDocInfo();
            $xml = $original->getXml(false);

            $copy = $this->createDoc($info->type, $newName);
            $copy->save($xml);

            return $copy;
        }

        return false;
    }
    // }}}
    // {{{ removeDoc
    /**
     * @param $doc_id_or_name
     * @return bool
     */
    public function removeDoc($doc_id)
    {
        $doc_id = $this->docExists($doc_id);

        if ($doc_id !== false) {
            $query = $this->pdo->prepare(
                "DELETE
                FROM {$this->table_docs}
                WHERE id = :doc_id"
            );
            $query->execute(array(
                'doc_id' => $doc_id,
            ));
            $this->cache->delete("{$this->table_docs}/d{$this->doc_id}/");

            return true;
        }

        return false;
    }
    // }}}

    // {{{ updateSchema
    /**
     * @brief updateSchema
     *
     * @param mixed
     * @return void
     **/
    public function updateSchema()
    {
        $schema = new \Depage\Db\Schema($this->pdo);

        $pdoPrefix = $this->pdo->prefix;
        $tablePrefix = $this->table_prefix;

        $schema->setReplace(
            function ($tableName) use ($pdoPrefix, $tablePrefix)
            {
                if ($tableName == '_auth_user') {
                    return $pdoPrefix . $tableName;
                } else {
                    return $tablePrefix . $tableName;
                }
            }
        );

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0;');

        // schema for xmldb
        $schema->loadGlob(__DIR__ . '/Sql/*.sql');
        $schema->update();

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
    }
    // }}}
    // {{{ clearTables
    /**
     * Removes SQL tables
     */
    public function clearTables()
    {
        $this->pdo->query("DELETE FROM `{$this->table_docs}`;");
        $this->pdo->query("DELETE FROM `{$this->table_nodetypes}`;");
        $this->pdo->query("ALTER TABLE `{$this->table_docs}` AUTO_INCREMENT = 1;");
        $this->pdo->query("ALTER TABLE `{$this->table_nodetypes}` AUTO_INCREMENT = 1;");
    }
    // }}}

    // {{{ beginTransaction
    /**
     * wrap database begin transaction
     */
    public function beginTransaction()
    {
        if ($this->transactions == 0) {
            $this->pdo->beginTransaction();
        }
        $this->transactions++;
    }
    // }}}
    // {{{ endTransaction
    /**
     * wrap database end transaction
     */
    public function endTransaction()
    {
        $this->transactions--;
        if ($this->transactions == 0) {
            $this->pdo->commit();
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
