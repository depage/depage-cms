<?php
/**
 * @file    modules/XmlDb/XmldbHistory.php
 *
 * cms xmldb module
 *
 * copyright (c) 2002-2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\XmlDb;

class XmlDbHistory implements XmlGetter
{
    // {{{ variables
    private $doc_ids = array();

    private $pdo;

    private $db_ns;

    private $table_prefix = 'dp_';
    private $table_docs;
    private $table_history;

    private $options;
    // }}}

    // {{{ constructor
    public function __construct($table_prefix, $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_NATURAL);

        $this->db_ns = new XmlNs("db", "http://cms.depagecms.net/ns/database");

        $this->table_prefix = $table_prefix;
        $this->table_docs = $table_prefix . "_xmldocs";
        $this->table_history = $table_prefix . "_history";
    }
    // }}}

    // {{{ docExists
    public function docExists($doc_id_or_name)
    {
        $result = false;

        $query = $this->pdo->prepare("SELECT h.doc_id AS id, docs.name, h.published
            FROM {$this->table_history} AS h
            JOIN {$this->table_docs} AS docs
            ON h.doc_id=docs.id
            WHERE published = true
            AND (
                id = :id
                OR name = :name
            )"
        );

        $query->execute(array(
            'id' => $doc_id_or_name,
            'name' => $doc_id_or_name,
        ));

        if ($doc = $query->fetchObject()) {
            $result = (int) $doc->id;
        }

        return $result;
    }
    // }}}
    // {{{ getDocXml
    public function getDocXml($doc_id_or_name, $add_id_attribute = true)
    {
        $result = false;

        $query = $this->pdo->prepare("SELECT h.doc_id AS id, docs.name, h.published, h.xml
            FROM {$this->table_history} AS h
            JOIN {$this->table_docs} AS docs
            ON h.doc_id=docs.id
            WHERE published = true
            AND (
                id = :id
                OR name = :name
            )"
        );

        $query->execute(array(
            'id' => $doc_id_or_name,
            'name' => $doc_id_or_name,
        ));

        if ($doc = $query->fetchObject()) {
            $result = $doc->xml;
        }

        if (!$add_id_attribute) {
            $doc = new \DomDocument();
            $doc->loadXml($result);
            Document::removeIdAttrStatic($doc, $this->db_ns, 'id');

            $result = $doc->saveXML();
        }

        return $result;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
