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
        $parameters['class'] = "newsletter edit";

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
                'lang' => $lang,
                'required' => true,
                'dataPath' => "//*[@db:id = '$nodeId']/@value",
            ]);
        }

        $nodes = $this->dataNodeXpath->query("//pg:meta/pg:linkdesc");
        foreach ($nodes as $node) {
            $lang = $node->getAttribute("lang");
            $nodeId = $node->getAttribute("db:id");

            $this->addText("subject-$lang", [
                'label' => _("Subject") . " ($lang)",
                'lang' => $lang,
                'required' => true,
                'dataPath' => "//*[@db:id = '$nodeId']/@value",
            ]);
        }

        $nodes = $this->dataNodeXpath->query("//pg:meta/pg:desc");
        foreach ($nodes as $node) {
            $lang = $node->getAttribute("lang");
            $nodeId = $node->getAttribute("db:id");

            $this->addText("description-$lang", [
                'label' => _("Description") . " ($lang)",
                'lang' => $lang,
                'dataPath' => "//*[@db:id = '$nodeId']",
            ]);
        }

        $count = 0;
        $fs = $this->addFieldset("unsentItems", [
            'label' => _("Unsent news items"),
        ]);
        $fs->addHtml("<div class=\"scrollable-content\">");
        foreach ($this->candidates as $c) {
            if (!$c->alreadyUsed) {
                $count++;
                $nodes = $this->dataNodeXpath->query("//sec:news[@db:docref='{$c->name}']");
                $fs->addBoolean("{$c->name}", [
                    'label' => $c->url,
                    'defaultValue' => $nodes->length == 1
                ]);
            }
        }
        if ($count == 0) {
            $fs->addHtml("<p>" . _("No news items available.") . "</p>");
        }
        $fs->addHtml("</div>");

        $count = 0;
        $fs = $this->addFieldset("sentItems", [
            'label' => _("News items included in other newsletters"),
            'class' => "detail",
        ]);
        $fs->addHtml("<div class=\"scrollable-content\">");
        foreach ($this->candidates as $c) {
            if ($c->alreadyUsed) {
                $count++;
                $nodes = $this->dataNodeXpath->query("//sec:news[@db:docref='{$c->name}']");
                $fs->addBoolean("{$c->name}", [
                    'label' => $c->url,
                    'defaultValue' => $nodes->length == 1
                ]);
            }
        }
        $fs->addHtml("</div>");
        if ($count == 0) {
            $fs->addHtml("<p>" . _("No news items available.") . "</p>");
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
