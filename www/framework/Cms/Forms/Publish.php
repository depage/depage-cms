<?php

namespace Depage\Cms\Forms;

/**
 * brief Project
 * Class Project
 */
class Publish extends \Depage\HtmlForm\HtmlForm
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
        $params['submitLabel'] = _("Publish Now");

        $params['cancelUrl'] = DEPAGE_BASE;
        $params['cancelLabel'] = _("Cancel");

        $this->project = $params['project'];

        parent::__construct($name, $params);

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
        ]);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
