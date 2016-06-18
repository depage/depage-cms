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
        $params['label'] = _("Publish Now");

        $params['cancelUrl'] = DEPAGE_BASE;
        $params['cancelLabel'] = _("Cancel");
        $params['class'] = "lastchanged_pages";

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

        $formatter = new \Depage\Formatters\DateNatural();

        $pages = $this->project->getUnreleasedPages();
        $date = $this->project->getLastPublishDate();
        $previewPath = $this->project->getPreviewPath();

        $fs = $this->addFieldset("recentChanges", [
            'label' => _("Unreleased Pages"),
        ]);
        $fs->addHtml("<p>" . _("Please select the pages you want to publish:") . "</p>");

        foreach($pages as $page) {
            if ($page->lastchange->getTimestamp() > $date->getTimestamp()) {
                $fs->addHtml("<a href=\"" . $previewPath . $page->url . "\" class=\"button preview\" target=\"previewFrame\">" . _("Preview") . "</a>");
                $fs->addBoolean("page-" . $page->id, array(
                    'label' => $page->url,
                ));
            }
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
