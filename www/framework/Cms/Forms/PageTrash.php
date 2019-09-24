<?php

namespace Depage\Cms\Forms;

/**
 * brief BackupsRestore
 * Class BackupsRestore
 */
class PageTrash extends \Depage\HtmlForm\HtmlForm
{
    // {{{ __construct()
    /**
     * @brief   HtmlForm class constructor
     *
     * @param  string $name       form name
     * @param  array  $parameters form parameters, HTML attributes
     * @param  object $form       parent form object reference (not used in this case)
     * @return void
     **/
    public function __construct($name, $parameters = array(), $form = null)
    {
        $parameters['label'] = _("Empty page trash");

        parent::__construct($name, $parameters, $this);
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
        $this->addHtml("<h1>" . _("Page Trash") . "</h1>");
        $this->addHtml("<p>" . _("This will finally remove all deleted pages from the page trash.") . "</p>");
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
