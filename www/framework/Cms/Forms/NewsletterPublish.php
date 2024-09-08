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

class NewsletterPublish extends \Depage\HtmlForm\HtmlForm
{
    // {{{ variables
    protected $newsletter;
    // }}}

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
        $parameters['label'] = _("Send Now");
        $parameters['class'] = "newsletter publish";

        $this->newsletter = $parameters['newsletter'];

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
        $categories = $this->newsletter->getSubscriberCategories();
        $list = [];
        foreach ($categories as $cat) {
            if ($cat->name != "Rejected") {
                $list[$cat->name] = sprintf(ngettext("%s (%d recipient)", "%s (%d recipients)", $cat->count), $cat->name, $cat->count);
            }
        }
        $list["__custom"] = _("Custom Recipients");

        $this->addSingle("to", array(
            'label' => _("Recipients"),
            'class' => "recipients labels-on-top",
            'list' => $list,
        ));
        $this->addTextarea("emails", [
            'label' => _("Emails"),
            'class' => "labels-on-top",
        ]);

        $h = "";
        $h .= "<h1>" . _("Preview") . "</h1>";
        $h .= "<ul>";
        foreach (["de", "en"] as $lang) {
            $href = $this->newsletter->getPreviewUrl("live", $lang);
            $h .= "<li><a href=\"$href\" class=\"preview\">$lang</a>";
        }
        $h .= "</ul>";

        $this->addHtml($h);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
