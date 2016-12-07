<?php

namespace Depage\Cms\Forms;

/**
 * brief Project
 * Class Project
 */
class Publish extends ReleasePages
{
    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $name, $params
     * @return void
     **/
    public function __construct($name, $params = [])
    {
        parent::__construct($name, $params);

        $this->label = _("Publish Now");
    }
    // }}}
    // {{{ addChildElements()
    /**
     * @brief addChildElements
     *
     * @return void
     **/
    public function addChildElements()
    {
        $this->addHidden("action", [
            'defaultValue' => "publish",
        ]);

        $targets = $this->project->getPublishingTargets();
        $list = [];
        foreach ($targets as $id => $target) {
            $list[$id] = $target->name;
        }
        $this->addSingle("publishId", [
            'label' => _("Publish to"),
            'list' => $list,
            'defaultValue' => array_keys($list)[0],
        ]);

        parent::addChildElements();
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
