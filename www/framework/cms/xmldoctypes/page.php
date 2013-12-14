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
}  

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
