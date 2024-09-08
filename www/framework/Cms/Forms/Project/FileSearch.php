<?php

namespace Depage\Cms\Forms\Project;

/**
 * brief Project
 * Class Project
 */
class FileSearch extends \Depage\HtmlForm\HtmlForm
{
    // {{{ variables
    protected $project;
    // }}}

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
        $this->addSearch("query", [
            'label' => _("Search"),
            'placeholder' => _("Search for files"),
            'autofocus' => true,
        ]);
        $this->addSingle("mime", [
            'label' => _("File Type"),
            'defaultValue' => "*",
            'class' => 'edit-type',
            'list' => [
                '*' => _("All"),
                'image/*' => _("Image"),
                'video/*' => _("Video"),
                'audio/*' => _("Audio"),
                'application/pdf' => _("PDF"),
            ],
        ]);
        $this->addSingle("type", [
            'label' => _("Search Type"),
            'defaultValue' => 'filename',
            'class' => 'edit-type',
            'list' => [
                'filename' => _("Filename only"),
                'all' => _("All Metadata"),
            ],
        ]);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
