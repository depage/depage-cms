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
    protected $pdo;
    protected $xmlDb;
    protected $db_ns;
    protected $table_history;

    protected $doc_ids = [];

    protected $table_prefix = 'dp_';
    protected $table_docs;

    protected $options;

    public $timestamp = null;
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
    // {{{ getDocInfo()
    /**
     * @brief getDocInfo
     *
     * @param mixed $
     * @return void
     **/
    public function getDocInfo($doc_id_or_name)
    {
        return $this->xmlDb->getDocInfo($doc_id_or_name);
    }
    // }}}
    // {{{ getDocXml
    public function getDocXml($doc_id_or_name, $add_id_attribute = true)
    {
        $xml = false;

        if ($doc_id = $this->docExists($doc_id_or_name)) {
            $doc = new Document($this, $doc_id);
            $history = $doc->getHistory();

            if (!is_null($this->timestamp)) {
                $xml = $history->getXml($this->timestamp, $add_id_attribute);
            } else {
                $xml = $history->getLastPublishedXml($add_id_attribute);
            }
        }

        return $xml;
    }
    // }}}
    // {{{ getDocXmlXpath
    public function getDocXmlXpath($doc_id_or_name, $xpath, $add_id_attribute = true)
    {
        $xml = false;

        if ($doc_id = $this->docExists($doc_id_or_name)) {
            $xmlFull = $this->getDocXml($doc_id_or_name, $add_id_attribute);
            if ($xmlFull === false) {
                throw new \Exception("Document could not be read from history");
            }

            $domXpath = new \DomXpath($xmlFull);
            $list = $domXpath->query($xpath);

            if ($list->length > 0) {
                $xml = new \Depage\Xml\Document();
                $xml->appendChild($xml->importNode($list->item(0), true));

                return $xml;
            }
        }

        return $xml;
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
    // {{{ __sleep()
    /**
     * allows Depage\Db\Pdo-object to be serialized
     */
    public function __sleep()
    {
        return array(
            'pdo',
            'xmlDb',
            'db_ns',
            'table_prefix',
            'table_docs',
            'table_history',
            'options',
        );
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
