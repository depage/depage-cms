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
    public function saveNodeToDb($node, $id, $target_id, $target_pos, $increase_pos = false)
    {
        return parent::saveNodeToDb($node, $id, $target_id, $target_pos, $increase_pos);
    }
    public function updateLastChange($timestamp = null, $uid = null)
    {
        return parent::updateLastChange($timestamp, $uid);
    }
    public function getNodeArrayForSaving(&$node_array, $node, $parent_index = null, $pos = 0, $stripwhitespace = true)
    {
        return parent::getNodeArrayForSaving($node_array, $node, $parent_index, $pos, $stripwhitespace);
    }
    public function getFreeNodeIds($needed = 1)
    {
        return parent::getFreeNodeIds($needed);
    }
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
