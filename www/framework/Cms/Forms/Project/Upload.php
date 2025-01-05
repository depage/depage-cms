<?php

namespace Depage\Cms\Forms\Project;

/**
 * brief Project
 * Class Project
 */
class Upload extends \Depage\HtmlForm\HtmlForm
{
    // {{{ variables
    protected $project;
    protected $targetPath;
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
        $this->targetPath = $params['targetPath'];

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
    public function addChildElements(): void
    {
        $this->addFile("file", [
            'label' => _("Upload new files by choosing or dragging"),
            'maxNum' => 1000,
            'dataAttr' => [
                'path' => $this->targetPath,
            ],
        ]);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
