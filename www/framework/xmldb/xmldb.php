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

class xmldb {

    // {{{ variables
    private $doc_ids = array();

    private $pdo;
    private $cache;

    private $db_ns;

    private $table_prefix = 'dp_';
    private $table_docs;
    private $table_xml;
    private $table_nodetypes;

    private $options;
    // }}}

    // {{{ __get()
    /**
     * Get properties (basically read-only)
     *
     * @param $property
     * @return mixed
     */
    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }
    // }}}

    // {{{ constructor()
    public function __construct($table_prefix, $pdo, $cache, $options = array()) {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_NATURAL);

        $this->cache = $cache;

        $this->options = $options;

        $this->db_ns = new xmlns("db", "http://cms.depagecms.net/ns/database");

        $this->table_prefix = $table_prefix;
        $this->table_docs = $table_prefix . "_xmldocs";
        $this->table_xml = $table_prefix . "_xmltree";
        $this->table_nodetypes = $table_prefix . "_xmlnodetypes";
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

    // {{{ getDocuments()
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

    // {{{ getDoc()
    /**
     * Get xmldb\document
     *
     * @param $doc_id_or_name
     * @return bool|document
     */
    public function getDoc($doc_id_or_name) {
        if ($doc_id = $this->docExists($doc_id_or_name)) {
            return new document($this, $doc_id);
        }

        return false;
    }
    // }}}
    
    // {{{ getDocByNodeId()
    /**
     * Get xmldb\document
     *
     * @param $nodeId
     * @return bool|document
     */
    public function getDocByNodeId($nodeId) {
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
        $xml = false;

        if ($doc_id = $this->docExists($doc_id_or_name)) {
            $doc = new document($this, $doc_id);
            $xml = $doc->getXml($add_id_attribute);
        }

        return $xml;
    }
    // }}}

    // {{{ createDoc()
    /**
     * CreateDoc
     *
     * @param $doc_id_or_name
     * @return Document
     * @throws xmldbException
     */
    public function createDoc($doc_name, $doc_type = 'depage\xmldb\xmldoctypes\base') {
        // @TODO add option to generate doc name
        if (!is_string($doc_name)) {
            throw new xmldbException("You have to give a valid name to save a new document.");
        }

        $query = $this->pdo->prepare(
            "INSERT {$this->table_docs} SET
                name = :name, type = :type;"
        );
        $query->execute(array(
            'name' => $doc_name,
            'type' => $doc_type,
        ));

        $doc_id = $this->pdo->lastInsertId();

        $document = new Document($this, $doc_id);

        return $document;
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
            $this->cache->delete("{$this->table_docs}/d{$this->doc_id}/");

            return true;
        }

        return false;
    }
    // }}}
    
    // {{{ createTables()
    /**
     * Creates SQL tables for current settings
     */
    public function createTables() {
        $this->pdo->query("SET foreign_key_checks=0;");

        $this->pdo->query("CREATE TABLE IF NOT EXISTS `{$this->table_xml}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_doc` int(10) unsigned DEFAULT '0',
            `id_parent` int(10) unsigned DEFAULT NULL,
            `pos` mediumint(8) unsigned DEFAULT '0',
            `name` varchar(50) DEFAULT NULL,
            `value` mediumtext NOT NULL,
            `type` enum('ELEMENT_NODE','TEXT_NODE','CDATA_SECTION_NODE','PI_NODE','COMMENT_NODE','ENTITY_REF_NODE','WAIT_FOR_REPLACE','DELETED') NULL DEFAULT 'ELEMENT_NODE',

            PRIMARY KEY (`id`),
            KEY `SECONDARY` (`id_parent`,`id_doc`,`type`),
            KEY `THIRD` (`name`),
            KEY `id_doc` (`id_doc`),

            CONSTRAINT `{$this->table_xml}_ibfk_1` FOREIGN KEY (`id_parent`) REFERENCES `{$this->table_xml}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `{$this->table_xml}_ibfk_2` FOREIGN KEY (`id_doc`) REFERENCES `{$this->table_docs}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;");

        $this->pdo->query("CREATE TABLE IF NOT EXISTS `{$this->table_docs}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL DEFAULT '',
            `type` varchar(50) NOT NULL DEFAULT '',
            `ns` mediumtext NOT NULL,
            `entities` mediumtext NOT NULL,
            `rootid` int(10) unsigned DEFAULT NULL,
            `lastchange` timestamp DEFAULT '0000-00-00 00:00:00',
            `lastchange_uid` int(10) unsigned DEFAULT NULL,

            PRIMARY KEY (`id`),
            UNIQUE KEY `SECONDARY` (`name`),
            KEY `rootid` (`rootid`),
            KEY `lastchange_uid` (`lastchange_uid`),

            CONSTRAINT `{$this->table_docs}_ibfk_1` FOREIGN KEY (`lastchange_uid`) REFERENCES `dp_auth_user` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
        ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;");

        $this->pdo->query("CREATE TABLE `{$this->table_nodetypes}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `pos` int(10) unsigned NOT NULL,
            `nodename` varchar(255) NOT NULL DEFAULT '',
            `name` varchar(255) NOT NULL DEFAULT '',
            `newname` varchar(255) NOT NULL DEFAULT '',
            `validparents` varchar(255) NOT NULL DEFAULT '',
            `icon` varchar(255) NOT NULL DEFAULT '',
            `xmltemplate` varchar(255) NOT NULL DEFAULT '',

            PRIMARY KEY (`id`)/*,
            UNIQUE KEY `SECONDARY` (`nodename`) */
        ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;");

        $this->pdo->query("SET foreign_key_checks=1;");
    }
    // }}}
    
    // {{{ removeTables()
    /**
     * Removes SQL tables
     */
    public function removeTables() {
        $this->pdo->query("SET foreign_key_checks=0;");

        $this->pdo->query("DROP TABLE IF EXISTS `{$this->table_xml}`;");
        $this->pdo->query("DROP TABLE IF EXISTS `{$this->table_docs}`;");
        $this->pdo->query("DROP TABLE IF EXISTS `{$this->table_nodetypes}`;");

        $this->pdo->query("SET foreign_key_checks=1;");
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
