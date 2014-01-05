<?php

namespace depage\cms\xmldoctypes;

class page extends \depage\xmldb\xmldoctypes\base
{
    private $table_nodetypes;
    private $pathXMLtemplate;

    // {{{ constructor
    public function __construct($xmldb, $docId) {
        parent::__construct($xmldb, $docId);

        $this->pathXMLtemplate = $this->xmldb->options['pathXMLtemplate'];

        $this->table_nodetypes = $xmldb->table_nodetypes;
    }
    // }}}
    
    // {{{ addNodeType
    public function addNodeType($nodeName, $options) {
        $name = $options['name'];
        $data = array(
            'pos' => 0,
            'name' => $name,
            'newName' => $name,
            'icon' => '',
            'xmlTemplate' => '',
        );
        foreach ($data as $key => $value) {
            if (isset($options[$key])) {
                $data[$key] = $options[$key];
            }
        }
        $data['nodeName'] = $nodeName;
        if (isset($options['validParents'])) {
            $data['validParents'] = implode(",", $options['validParents']);
        } else {
            $data['validParents'] = "*";
        }

        $query = $this->xmldb->pdo->prepare(
            "INSERT {$this->table_nodetypes} SET
                pos = :pos,
                nodename = :nodeName,
                name = :name,
                newname = :newName,
                validparents = :validParents,
                icon = :icon,
                xmltemplate = :xmlTemplate;"
        );
        $query->execute($data);

        if (!empty($options['xmlTemplateData']) && (!empty($options['xmlTemplate']))) {
            file_put_contents($this->pathXMLtemplate . $options['xmlTemplate'], $options['xmlTemplateData']);
        }
    }
    // }}}
    // {{{ getNodeTypes
    public function getNodeTypes() {
        $nodetypes = array();
        $query = $this->xmldb->pdo->prepare(
            "SELECT 
                id,
                nodename as nodeName,
                name as name,
                newname as newName,
                validparents as validParents,
                icon as icon,
                xmltemplate as xmlTemplate
            FROM {$this->table_nodetypes} ORDER BY pos;"
        );
        $query->execute($data);

        do {
            $result = $query->fetchObject();
            
            if ($result) {
                $nodetypes[$result->id] = $result;
                $templatePath = $this->pathXMLtemplate . $result->xmlTemplate;

                // load template data
                $xml = new \depage\xml\document();
                $xml->load($templatePath);

                $data = "";
                foreach ($xml->documentElement->childNodes as $node) {
                    if ($node->nodeType != \XML_COMMENT_NODE) {
                        $data .= $xml->saveXML($node);
                    }
                }
                $nodetypes[$result->id]->xmlTemplateData = $data;

                // get date of last change
                $nodetypes[$result->id]->lastchange = filemtime($templatePath);
            }
        } while ($result);

        return $nodetypes;
    }
    // }}}
}  

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
