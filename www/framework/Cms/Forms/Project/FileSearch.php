<?php

namespace Depage\Cms\Forms\Project;

/**
 * brief Project
 * Class Project
 */
class FileSearch extends \Depage\HtmlForm\HtmlForm
{
    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $name, $params
     * @return void
     **/
    public function __construct($name, $params)
    {
        $this->project = $params['project'];
        $params['jsAutosave'] = true;

        parent::__construct($name, $params);
    }
    // }}}
    // {{{ addChildElements()
    /**
     * @brief addChildElements
     *
     * @param mixed
     * @return void
     **/
    public function addChildElements()
    {
        $this->addText("query", [
            'label' => _("Search"),
        ]);
        $this->addSingle("mime", [
            'label' => _("Type"),
            'defaultValue' => "*",
            'list' => [
                '*' => _("All"),
                'image/*' => _("Image"),
                'video/*' => _("Video"),
                'audio/*' => _("Audio"),
                'application/pdf' => _("PDF"),
            ],
        ]);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
