<?php

namespace Depage\Cms\Forms\Project;

/**
 * brief Project
 * Class Project
 */
class Import extends Base
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
        $params['label'] = _("Import Now");

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
        $this->addHtml("<h1>" . _("Project Import") . "</h1>");
        $this->addHtml("<p>" . _("Import xml dataset from depage-cms 1.5") . "</p>");
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
