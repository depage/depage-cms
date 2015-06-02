<?php

namespace Depage\XmlDb\Tests;

class DocumentTestClass extends \Depage\XmlDb\Document
{
    public function getPosById($id)
    {
        return parent::getPosById($id);
    }
    public function getChildIdsByName($parent_id, $node_ns = '', $node_name = '', $attr_cond = null, $only_element_nodes = false)
    {
        return parent::getChildIdsByName($parent_id, $node_ns, $node_name, $attr_cond, $only_element_nodes);
    }
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
