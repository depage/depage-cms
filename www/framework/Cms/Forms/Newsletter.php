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


class Newsletter extends \Depage\Cms\Forms\XmlForm
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
        $nodes = $this->dataNodeXpath->query("//pg:meta/pg:title");
        foreach ($nodes as $node) {
            $lang = $node->getAttribute("lang");
            $nodeId = $node->getAttribute("db:id");

            $this->addText("title-$lang", [
                'label' => _("Title") . " ($lang)",
                'required' => true,
                'dataInfo' => "//*[@db:id = '$nodeId']/@value",
            ]);
        }

        $nodes = $this->dataNodeXpath->query("//pg:meta/pg:linkdesc");
        foreach ($nodes as $node) {
            $lang = $node->getAttribute("lang");
            $nodeId = $node->getAttribute("db:id");

            $this->addText("subject-$lang", [
                'label' => _("Subject") . " ($lang)",
                'required' => true,
                'dataInfo' => "//*[@db:id = '$nodeId']/@value",
            ]);
        }

        $nodes = $this->dataNodeXpath->query("//pg:meta/pg:desc");
        foreach ($nodes as $node) {
            $lang = $node->getAttribute("lang");
            $nodeId = $node->getAttribute("db:id");

            $this->addText("description-$lang", [
                'label' => _("Description") . " ($lang)",
                'dataInfo' => "//*[@db:id = '$nodeId']",
            ]);
        }

        $fs = $this->addFieldset("unsentItems", [
            'label' => _("Unsent news items"),
        ]);
        foreach ($this->candidates as $c) {
            $nodes = $this->dataNodeXpath->query("//sec:news[@db:docref='{$c->name}']");
            $fs->addBoolean("{$c->name}", [
                'label' => $c->url,
                'defaultValue' => $nodes->length == 1
            ]);
        }

        $fs = $this->addFieldset("sentItems", [
            'label' => _("Already sent news items"),
        ]);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
