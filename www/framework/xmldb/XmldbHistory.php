<?php
/**
 * @file    modules/xmldb/XmldbHistory.php
 *
 * cms xmldb module
 *
 *
 * copyright (c) 2002-2014 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 *
 */

namespace depage\xmldb;

class XmldbHistory implements XmlGetter
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

    // {{{ constructor()
    public function __construct($table_prefix, $pdo) {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_NATURAL);

        $this->cache = $cache;

        $this->options = $options;

        $this->db_ns = new xmlns("db", "http://cms.depagecms.net/ns/database");

        $this->table_prefix = $table_prefix;
        $this->table_docs = $table_prefix . "_xmldocs";
        $this->table_xml = $table_prefix . "_xmlhistory";
    }
    // }}}

    // {{{ docExists()
    public function docExists($doc_id_or_name)
    {
    }
    // }}}

    // {{{ getDocuments()
    public function getDocuments($name = "")
    {
    }
    // }}}

    // {{{ getDocXml()
    public function getDocXml($doc_id_or_name, $add_id_attribute = true)
    {
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
