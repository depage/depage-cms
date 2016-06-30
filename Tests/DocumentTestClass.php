<?php

namespace Depage\XmlDb\Tests;

class DocumentTestClass extends \Depage\XmlDb\Document
{
    public $free_element_ids = [];
    // {{{ setDoctypeHandler
    public function setDoctypeHandler($dth)
    {
        $this->doctypeHandlers[$this->doc_id] = $dth;
    }
    // }}}

    // {{{ getPosById
    public function getPosById($id)
    {
        return parent::getPosById($id);
    }
    // }}}
    // {{{ getTargetPos
    public function getTargetPos($target_id)
    {
        return parent::getTargetPos($target_id);
    }
    // }}}
    // {{{ saveNodeToDb
    public function saveNodeToDb($node, $id, $target_id, $target_pos, $increase_pos = false)
    {
        return parent::saveNodeToDb($node, $id, $target_id, $target_pos, $increase_pos);
    }
    // }}}
    // {{{ getNodeArrayForSaving
    public function getNodeArrayForSaving(&$node_array, $node, $parent_index = null, $pos = 0, $stripwhitespace = true)
    {
        return parent::getNodeArrayForSaving($node_array, $node, $parent_index, $pos, $stripwhitespace);
    }
    // }}}
    // {{{ getFreeNodeIds
    public function getFreeNodeIds($total = 1, $preference = [])
    {
        return parent::getFreeNodeIds($total, $preference);
    }
    // }}}
    // {{{ saveNodeIn
    public function saveNodeIn($node, $target_id, $target_pos, $inc_children)
    {
        return parent::saveNodeIn($node, $target_id, $target_pos, $inc_children);
    }
    // }}}
    // {{{ saveNodePrivate
    public function saveNodePrivate($node)
    {
        return parent::saveNodePrivate($node);
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
