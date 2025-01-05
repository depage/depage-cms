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
    public function addChildElements(): void
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

        $fs = $this->addFieldset("transformCache", [
            'label' => _("Transform Cache"),
        ]);
        $fs->addHtml("<p>" . _("If not all changes are visible on the live site after publishing, you may choose to clear the transform cache before publishing.<br>Usually this is not necessary.") . "</p>");
        $fs->addBoolean("clearTransformCache", [
            'label' => _("Clear transform cache before publishing"),
            'defaultValue' => false,
        ]);

    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
