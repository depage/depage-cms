<?php

/**
 * @file    framework/Cms/Ui/Newsletter.php
 *
 * depage cms ui module
 *
 *
 * copyright (c) 2016 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Forms;


class Newsletter extends \Depage\HtmlForm\HtmlForm
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
        $parameters['label'] = _("Save");
        $parameters['jsAutosave'] = true;

        $this->newsletter = $parameters['newsletter'];
        $this->candidates = $parameters['candidates'];

        parent::__construct($name, $parameters, $this);
    }
    // }}}
    // {{{ addChildElements()
    /**
     * @brief addChildElements
     *
     * @return void
     **/
    public function addChildElements()
    {
        $this->addText("title", [
            'label' => _("Title"),
            'required' => true,
        ]);
        $this->addText("subject", [
            'label' => _("Subject"),
            'required' => true,
        ]);
        $this->addText("description", [
            'label' => _("Description"),
        ]);

        $fs = $this->addFieldset("unsentItems", [
            'label' => _("Unsent news items"),
        ]);
        foreach ($this->candidates as $c) {
            $fs->addBoolean("{$c->name}", [
                'label' => $c->url,
            ]);
        }

        $fs = $this->addFieldset("sentItems", [
            'label' => _("Already sent news items"),
        ]);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
