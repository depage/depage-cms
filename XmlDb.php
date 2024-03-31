<?php
/**
 * @file    modules/xmlDb/xmlDb.php
 *
 * cms xmlDb module
 *
 * copyright (c) 2002-2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 *
 */

namespace Depage\XmlDb;

use Depage\XmlDb\Exceptions\XmlDbException;

class XmlDb implements XmlGetter
{
    // {{{ variables
    public $pdo;
    protected $cache;
    protected $db_ns;

    protected $table_prefix = 'dp_';
    protected $table_docs;
    protected $table_xml;
    protected $table_nodetypes;

    protected $doc_ids = [];

    protected $transactions = 0;
    protected $alteringTransaction;

    public $options;
    // }}}
    // {{{ constructor
    public function __construct($table_prefix, $pdo, $cache, $options = [])
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_NATURAL);

        $this->cache = $cache;

        $this->options = $options;

        $this->db_ns = new XmlNs('db', 'http://cms.depagecms.net/ns/database');

        $this->table_prefix = $table_prefix;
        $this->table_docs = $table_prefix . '_xmldocs';
        $this->table_xml = $table_prefix . '_xmltree';
        $this->table_nodetypes = $table_prefix . '_xmlnodetypes';
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
     *
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
                $query->execute([
                    'doc_id' => $id,
                ]);

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
    public function getDocuments($name = "", $type = "")
    {
        $docs = [];

        $namequery = '';
        $where = [];
        $query_param = [];

        if ($name) {
            $where[] = 'name = :name';
            $query_param['name'] = $name;
        }
        if ($type) {
            $where[] = 'type = :type';
            $query_param['type'] = $type;
        }
        if (count($where) > 0) {
            $namequery = "WHERE " . implode(" AND ", $where);
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
     * Get xmlDb\document
     *
     * @param $doc_id_or_name
     * @return bool|document
     */
    public function getDoc($doc_id_or_name)
    {
        $doc = false;

        if ($doc_id = $this->docExists($doc_id_or_name)) {
            $doc = new Document($this, $doc_id);
        }

        return $doc;
    }
    // }}}
    // {{{ getDocByNodeId
    /**
     * Get xmlDb\document
     *
     * @param $nodeId
     * @return bool|document
     */
    public function getDocByNodeId($nodeId)
    {
        $doc = false;

        $query = $this->pdo->prepare(
            "SELECT xml.id_doc AS id_doc
            FROM {$this->table_xml} AS xml
            WHERE xml.id = :nodeId"
        );

        $query->execute([
            'nodeId' => $nodeId,
        ]);
        $result = $query->fetchObject();

        if ($result && $doc_id = $this->docExists($result->id_doc)) {
            $doc = new Document($this, $doc_id);
        }

        return $doc;
    }
    // }}}
    // {{{ getDocInfo()
    /**
     * @brief getDocInfo
     *
     * @param mixed $doc_id_or_name
     * @return void
     **/
    public function getDocInfo($doc_id_or_name)
    {
        if ($doc = $this->getDoc($doc_id_or_name)) {
            return $doc->getDocInfo();
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
    // {{{ getDocXmlXpath
    /**
     * @param $doc_id_or_name
     * @param bool $add_id_attribute
     * @return bool
     */
    public function getDocXmlXpath($doc_id_or_name, $xpath, $add_id_attribute = true)
    {
        $xml = false;

        if ($doc_id = $this->docExists($doc_id_or_name)) {
            $doc = new Document($this, $doc_id);
            $xml = $doc->getSubdocByXpath($xpath, $add_id_attribute);
        }

        return $xml;
    }
    // }}}

    // {{{ getSubdocByXpath
    /**
     * gets document by xpath. if xpath directs to more than
     * one node, only the first node will be returned.
     *
     * @param   $xpath (string) xpath to target node
     * @param   $add_id_attribute (bool) whether to add db:id attribute or not
     *
     * @return  $doc (domxmlobject)
     */
    public function getSubdocByXpath($xpath, $add_id_attribute = true)
    {
        $subdoc = false;
        $ids = $this->getNodeIdsByXpath($xpath);

        if (isset($ids[0])) {
            $subdoc = $this->getDocByNodeId($ids[0])->getSubdocByNodeId($ids[0], $add_id_attribute);
        }

        return $subdoc;
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
        $fallback = false;
        $tableSql = [];
        $tableParams = [];
        $condSql = [];
        $condParams = [];
        $xpathElements = $this->parseXpathElements($xpath);
        $levels = count($xpathElements) - 1;

        foreach ($xpathElements as $level => $element) {
            $element[] = '';
            list(,$divider, $ns, $name, $condition) = $element;

            if ($level == 0) {
                $tableSql[] = "SELECT l$levels.id FROM";
                if ($divider == '/') {
                    $condSql[] = 'l0.id_parent IS NULL';
                }
            } else {
                $tableSql[] = 'INNER JOIN';
                if ($divider == '/' && !$fallback) {
                    $parentLevel = $level - 1;
                    $condSql[] = "l$level.id_parent = l$parentLevel.id";
                } else {
                    $fallback = true;
                }
            }

            if ($positionArray = $this->parsePosition($condition)) {
                // fetch by name and position: "... ns:name[n] ..."
                extract($positionArray); // $operator, $position

                $op = $this->getConditionOperator($ns, $name);
                $tableSql[] = "(
                    SELECT *, @tpos := IF(@parent = sub$level.id_parent, @tpos + 1, 1) AS tpos, @parent := sub$level.id_parent
                    FROM {$this->table_xml} AS sub$level
                    WHERE sub$level.name $op ?
                    ORDER BY sub$level.id_parent, sub$level.pos
                ) l$level";

                $tableParams[] = $this->translateName($ns, $name);
                $condSql[] = "l$level.tpos {$this->cleanOperator($operator)} ?";
                $condParams[] = $position;
            } else {
                $tableSql[] = "{$this->table_xml} AS l$level";
                $op = $this->getConditionOperator($ns, $name);
                $condSql[] = "l$level.name $op ?";
                $condParams[] = $this->translateName($ns, $name);

                if ($condition != '') {
                    if ($attributes = $this->parseAttributes($condition)) {
                        // fetch by simple attributes: "ns:name[@attr1] ..."
                        $attributeCond = '';
                        foreach ($attributes as $attribute) {
                            extract($attribute); // $name, $operator, $value, $bool

                            if ($bool) {
                                $attributeCond .= $this->cleanOperator($bool);
                            }

                            if ($name == 'db:id') {
                                $attributeCond .= " l$level.id {$this->cleanOperator($operator)} ? ";
                                $condParams[] = $value;
                            } else if ($operator == '=' || $operator == '') {
                                $attributeCond .= " l$level.value REGEXP ? ";
                                $regExValue = (is_null($value)) ? '.*' : $value;
                                $condParams[] = "(^| )$name=\"$regExValue\"( |$)";
                            } else {
                                $fallback = true;
                            }
                        }

                        if (!empty($attributeCond)) {
                            $condSql[] = $attributeCond;
                        }
                    } else {
                        $fallback = true;
                    }
                }
            }

            if (!is_null($docId)) {
                $condSql[] = "l$level.id_doc = ?";
                $condParams[] = $docId;
            }
        }

        $ids = [];

        if ($xpathElements) {
            if ($fallback) {
                $tableSql[0] = "SELECT DISTINCT l$level.id_doc FROM";
            }

            $sql = implode(' ', $tableSql) . ' WHERE (' . implode(') AND (', $condSql) . ") ORDER BY l$level.id_parent, l$level.pos";
            $params = array_merge($tableParams, $condParams);

            $query = $this->pdo->prepare($sql);
            $query->execute($params);

            foreach ($query->fetchAll() as $result) {
                $ids[] = $result[0];
            }
        } else {
            $fallback = true;

            foreach ($this->getDocuments() as $doc) {
                $ids[] = $doc->getDocId();
            }
        }

        if ($fallback) {
            if (is_null($docId)) {
                $docIds = $ids;
            } else {
                $docIds = [$docId];
            }
            $nodeIds = $this->getNodeIdsByXpathDom($xpath, $docIds);
        } else {
            $nodeIds = $ids;
        }

        return $nodeIds;
    }
    // }}}
    // {{{ getNodeIdsByXpathDom
    protected function getNodeIdsByXpathDom($xpath, $docs = [])
    {
        $ids = [];

        foreach ($docs as $doc) {
            $domXpath = new \DomXpath($this->getDoc($doc)->getXml());
            $list = $domXpath->query($xpath);
            foreach ($list as $item) {
                $ids[] = $item->attributes->getNamedItem('id')->nodeValue;
            }
        }

        return $ids;
    }
    // }}}

    // {{{ parseXpathElements
    protected function parseXpathElements($xpath)
    {
        $pName = '(?:([^\/\[\]]*):)?([^\/\[\]]+)';
        $pCondition = '(?:\[(.*?)\])?';
        preg_match_all("/(\/+)$pName$pCondition/", $xpath, $levels, PREG_SET_ORDER);

        return $levels;
    }
    // }}}
    // {{{ parsePosition
    protected function parsePosition($condition)
    {
        $positionArray = [];
        $pOperator = '(=|!=|<|>|<=|>=)';
        $pPosition = '([0-9]+)';

        if (preg_match("/^\s*(?:(?:position\(\))\s*$pOperator)?\s*$pPosition\s*$/", $condition, $matches)) {
            $positionArray['operator'] = ($matches[1] == '') ? '=' : $matches[1];
            $positionArray['position'] = $matches[2];
        }

        return $positionArray;
    }
    // }}}
    // {{{ parseAttributes
    protected function parseAttributes($condition)
    {
        $cond_array = false;
        $temp_condition = $this->removeLiteralStrings($condition, $strings);

        if (preg_match('/^[\w\d@=: -<>\*]*$/', $temp_condition)) {
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
    // {{{ translateName
    protected function translateName($ns, $name)
    {
        $colon = (strlen($ns) && strlen($name)) ? ':' : '';

        return str_replace('*', '%', "$ns$colon$name");
    }
    // }}}
    // {{{ getConditionOperator
    protected function getConditionOperator($ns, $name)
    {
        if (strpos("*", $ns) !== false || strpos("*", $name) !== false) {
            return 'LIKE';
        } else {
            return '=';
        }
    }
    // }}}
    // {{{ getConditionAttributes
    protected function getConditionAttributes($conditionString, $strings)
    {
        $conditionArray = [];

        $pAttr = '@(\w[\w\d:]*)';
        $pOperator = '(=|!=|<|>|<=|>=)';
        $pBool = '(and|or|AND|OR)';
        $pString = '\$(\d*)';

        preg_match_all("/$pBool?\s*$pAttr\s*(?:$pOperator\s*$pString)?/", $conditionString, $conditions, PREG_SET_ORDER);

        $first = true;
        foreach ($conditions as $condition) {
            $bool = isset($condition[1]) ? $condition[1] : null;

            if ($first == $bool) {
                throw new XmlDbException('Invalid XPath syntax');
            }

            if ($first) {
                $first = false;
            };

            $conditionArray[] = [
                'bool' => $bool,
                'name' => $condition[2],
                'operator' => isset($condition[3]) ? $condition[3] : null,
                'value' => (isset($condition[4]) && $condition[4] != '') ? $strings[$condition[4]] : null,
            ];
        }

        return $conditionArray;
    }
    // }}}
    // {{{ removeLiteralStrings
    protected function removeLiteralStrings($text, &$strings)
    {
        $n = 0;
        $newText = '';
        $strings = [];

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
    // {{{ cleanOperator
    protected function cleanOperator($operator)
    {
        $cleaned = '';
        $operators = ['=', '!=', '<=', '>=', '<', '>', 'and', 'or'];

        if (in_array($operator, $operators)) {
            $cleaned = $operator;
        } else {
            throw new XmlDbException("Invalid XPath operator \"$operator\"");
        }

        return $cleaned;
    }
    // }}}

    // {{{ createDoc
    /**
     * CreateDoc
     *
     * @param $doctype (string) class-name of doctype for new document
     * @param $docName (string) optional name of document
     *
     * @return Document
     *
     * @throws xmlDbException
     */
    public function createDoc($doctype = 'Depage\XmlDb\XmlDoctypes\Base', $docName = null)
    {
        if (is_null($docName)) {
            // generate generic docname based on doctype
            $docName = '_' . substr($doctype, strrpos($doctype, "\\") + 1) . '_' . sha1(uniqid(dechex(mt_rand(256, 4095))));
        }
        if (!is_string($docName) || $this->docExists($docName)) {
            throw new XmlDbException("Invalid or duplicate document name \"$docName\"");
        }

        $query = $this->pdo->prepare(
            "INSERT {$this->table_docs} SET name = :name, type = :type, ns = '', entities = ''"
        );
        $query->execute([
            'name' => $docName,
            'type' => $doctype,
        ]);

        $docId = $this->pdo->lastInsertId();
        $document = new Document($this, $docId);

        return $document;
    }
    // }}}
    // {{{ duplicateDoc
    /**
     * @brief duplicateDoc
     *
     * @param $docNameOrId (mixed)
     * @param $newName (string) optional name for new document
     *
     * @return Document|bool
     **/
    public function duplicateDoc($docNameOrId, $newName = null)
    {
        $copy = false;
        $original = $this->getDoc($docNameOrId);

        if ($original !== false) {
            $info = $original->getDocInfo();
            $xml = $original->getXml(false);

            $copy = $this->createDoc($info->type, $newName);
            $copy->save($xml);
        }

        return $copy;
    }
    // }}}
    // {{{ removeDoc
    /**
     * @param $doc_id_or_name
     *
     * @return bool
     */
    public function removeDoc($doc_id)
    {
        $result = false;
        $doc_id = $this->docExists($doc_id);

        if ($doc_id !== false) {
            $query = $this->pdo->prepare(
                "DELETE
                FROM {$this->table_docs}
                WHERE id = :doc_id"
            );
            $query->execute([
                'doc_id' => $doc_id,
            ]);

            $this->cache->delete("{$this->table_docs}/d{$this->doc_id}/");

            $this->doc_ids = array_filter(
                $this->doc_ids,
                function($id) use ($doc_id)
                {
                    return $id != $doc_id;
                }
            );

            $result = true;
        }

        return $result;
    }
    // }}}

    // {{{ updateSchema
    /**
     * @brief updateSchema
     *
     * @param mixed
     *
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

        // schema for xmlDb
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

        $this->doc_ids = [];
    }
    // }}}

    // {{{ beginTransactionAltering
    public function beginTransactionAltering()
    {
        $this->alteringTransaction = true;

        return $this->beginTransactionNonAltering();
    }
    // }}}
    // {{{ beginTransactionNonAltering
    public function beginTransactionNonAltering()
    {
        if ($this->transactions == 0) {
            $this->pdo->beginTransaction();
        }
        $this->transactions++;

        return $this->transactions;
    }
    // }}}
    // {{{ endTransaction
    public function endTransaction()
    {
        $altered = false;
        $this->transactions--;

        if ($this->transactions == 0) {
            $this->pdo->commit();

            $altered = $this->alteringTransaction;
            $this->alteringTransaction = false;
        }

        return $altered;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
