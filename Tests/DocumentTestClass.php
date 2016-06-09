<?php

namespace Depage\XmlDb\Tests;

class DocumentTestClass extends \Depage\XmlDb\Document
{
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
    // {{{ updateLastChange
    public function updateLastChange($timestamp = null, $uid = null)
    {
        return parent::updateLastChange($timestamp, $uid);
    }
    // }}}
    // {{{ getNodeArrayForSaving
    public function getNodeArrayForSaving(&$node_array, $node, $parent_index = null, $pos = 0, $stripwhitespace = true)
    {
        return parent::getNodeArrayForSaving($node_array, $node, $parent_index, $pos, $stripwhitespace);
    }
    // }}}
    // {{{ getFreeNodeIds
    public function getFreeNodeIds($needed = 1)
    {
        return parent::getFreeNodeIds($needed);
    }
    // }}}
    // {{{ saveNodeIn
    public function saveNodeIn($node, $target_id, $target_pos = -1, $inc_children = true)
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
