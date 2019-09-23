<?php

namespace Depage\Cms\Forms;

/**
 * brief BackupsRestore
 * Class BackupsRestore
 */
class BackupsRestore extends \Depage\HtmlForm\HtmlForm
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
        $parameters['label'] = _("Restore Backup");

        $this->backups = $parameters['backups'];

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
        $options = [];

        foreach ($this->backups as $backup) {
            $options[$backup->file] = sprintf("Backup from %s", $backup->date->format("Y-m-d H:i:s"));
        }

        $this->addHtml("<h1>" . _("Backups") . "</h1>");
        $this->addSingle("file", [
            'label' => _("Restore Backup from:"),
            'list' => $options,
            'required' => true,
        ]);

        $this->addHtml("<p>" . _(" This will delete all current project pages and settings and will restore all data from the backup file. This action cannot be undone.") . "</p>");
        $this->addBoolean("really", [
            'required' => true,
            'label' => _("Do you really want to restore this backup?"),
        ]);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
