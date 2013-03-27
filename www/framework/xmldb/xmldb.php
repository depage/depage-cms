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
 */

namespace depage\xmldb;

use depage\xmldb;

class xmldb {

    // {{{ variables
    public $pdo;
    public $cache;
    private $db_ns;

    private $doc_ids = array();

    private $table_docs;
    private $table_xml;
    // }}}

    /* public */

    // {{{ constructor()
    public function __construct($tableprefix, $pdo, $cache) {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_NATURAL);

        $this->cache = $cache;

        $this->db_ns = new xmlns("db", "http://cms.depagecms.net/ns/database");

        $this->table_docs = $tableprefix . "_xmldocs";
        $this->table_xml = $tableprefix . "_xmltree";
    }
    // }}}

    // {{{ docExists()
    /**
     * gets the doc-id of a xml-document by name or id and checks if the document exists
     *
     * @param     $doc_id_or_name (mixed) id or name of the document
     * @return    (int) id of the document or false when document does not exist
     */
    public function docExists($doc_id_or_name) {
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

    // {{{ getDocList()
    /**
     * gets available documents in database
     *
     * @return    $docs (array) the key is the name of the document, the value is the document db-id.
     */
    public function getDocuments($name = "") {
        $docs = array();

        $namequery = "";
        $query_param = array();

        if ($name) {
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
            $docs[$doc->name] = new document($this, $doc->id);
        }

        return $docs;
    }
    // }}}

    // {{{
    /**
     *
     */
    public function getDoc($doc_id_or_name) {
        if ($doc_id = $this->docExists($doc_id_or_name)) {
            return new document($this, $doc_id);
        }

        return false;
    }
    // }}}

    // {{{ getDocXml()
    /**
     * @param $doc_id_or_name
     * @param bool $add_id_attribute
     * @return bool
     */
    public function getDocXml($doc_id_or_name, $add_id_attribute = true) {
        $this->beginTransaction();

        $xml = false;
        if ($doc_id = $this->docExists($doc_id_or_name)) {
            $doc = new document($this, $doc_id);
            $doc = $doc->getDocInfo();
            $xml = $this->getSubdocByNodeId($doc->id, $doc->rootid, $add_id_attribute);
        }

        $this->endTransaction();

        return $xml;
    }
    // }}}

    // {{{ saveDoc()
    /**
     * @param $xml
     * @return mixed
     * @throws xmldbException
     */
    public function saveDoc($xml) {
        if (!is_object($xml) || !(get_class($xml) == 'DOMDocument') || is_null($xml->documentElement)) {
            throw new xmldbException("This document is not a valid XML-Document");
        }

        $this->beginTransaction();

        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
            $doc = new document($this, $doc_id);
            $doc_info = $doc->getDocInfo();

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
    /**
     * @param $doc_id_or_name
     * @return bool
     */
    public function removeDoc($doc_id) {
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
            $this->clearCache($doc_id);

            return true;
        }

        return false;
    }
    // }}}

    // {{{ getPermissions()
    /**
     * @param $doc_id_or_name
     * @return bool
     */
    public function getPermissions($doc_id_or_name) {
        // @todo get this from document type
        $doc_id = $this->docExists($doc_id_or_name);

        if ($doc_id !== false) {
            return $this->getDoctypeHandler($doc_id)->getPermissions();
        }

        return false;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */