<?php
/**
 * @file    modules/XmlDb/XmlDbHistory.php
 *
 * cms xmlDb module
 *
 * copyright (c) 2002-2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\XmlDb;

class XmlDbHistory implements XmlGetter
{
    // {{{ variables
    private $pdo;
    private $db_ns;
    private $table_history;

    private $doc_ids = [];

    private $table_prefix = 'dp_';
    private $table_docs;

    private $options;
    // }}}

    // {{{ constructor
    public function __construct($table_prefix, $pdo, $cache, $options = [])
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_NATURAL);

        $this->xmlDb = new XmlDb($table_prefix, $pdo, $cache, $options);
        $this->db_ns = new XmlNs('db', 'http://cms.depagecms.net/ns/database');

        $this->table_prefix = $table_prefix;
        $this->table_docs = $table_prefix . '_xmldocs';
        $this->table_history = $table_prefix . '_history';
    }
    // }}}

    // {{{ docExists
    public function docExists($doc_id_or_name)
    {
        $result = false;

        $id = $this->xmlDb->docExists($doc_id_or_name);

        $query = $this->pdo->prepare("
            SELECT doc_id
            FROM {$this->table_history}
            WHERE doc_id = :id
            LIMIT 1
        ");

        $query->execute([
            'id' => $id,
        ]);

        if ($query->fetch()) {
            $result = $id;
        }

        return $result;
    }
    // }}}
    // {{{ getDocXml
    public function getDocXml($doc_id_or_name, $add_id_attribute = true)
    {
        $result = false;
        $id = $this->docExists($doc_id_or_name);

        $query = $this->pdo->prepare("
            SELECT doc_id, last_saved_at, published, xml
            FROM {$this->table_history}
            WHERE published = true
                AND doc_id = :id
            ORDER BY last_saved_at DESC
            LIMIT 1
        ");

        $query->execute([
            'id' => $id,
        ]);

        if ($doc = $query->fetchObject()) {
            $result = $doc->xml;

            $doc = new \Depage\Xml\Document();
            $doc->loadXml($result);

            if (!$add_id_attribute) {
                Document::removeNodeAttr($doc, $this->db_ns, 'id');
            }
        }

        return $doc;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
