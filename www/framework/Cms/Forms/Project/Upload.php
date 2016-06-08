<?php

namespace Depage\Cms\Forms\Project;

/**
 * brief Project
 * Class Project
 */
class Upload extends \Depage\HtmlForm\HtmlForm
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
    public function addChildElements()
    {
        $this->addHtml("<h1>" . _("Upload files to:") . " " . htmlentities($this->targetPath) . "</h1>");

        $this->addHtml("<div class=\"dropArea\">");
            $this->addFile("file", [
                'label' => _("Choose File"),
                "maxNum" => 1000,
            ]);

            $this->addHtml("<div class=\"content\"></div>");
        $this->addHtml("</div>");
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
