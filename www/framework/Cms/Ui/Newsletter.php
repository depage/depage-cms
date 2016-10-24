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

namespace Depage\Cms\Ui;

use \Depage\Html\Html;
use \Depage\Notifications\Notification;

class Newsletter extends Base
{
    // {{{ _init
    public function _init(array $importVariables = []) {
        parent::_init($importVariables);

        $this->projectName = $this->urlSubArgs[0];
        $this->newsletterName = $this->urlSubArgs[1];

        if (empty($this->projectName)) {
            throw new \Depage\Cms\Exceptions\Project("no project given");
        } else {
            $this->project = $this->getProject($this->projectName);
        }

        if (empty($this->newsletterName)) {
            throw new \Depage\Cms\Exceptions\Project("no newsletter given");
        } else if ($this->newsletterName == "+") {
            $this->newsletter = \Depage\Cms\Newsletter::create($this->project);
            // @todo redirect to newsletter url
        } else if ($this->newsletterName == "current") {
            // @todo get current newsletter and redirect to it
        } else {
            $this->newsletter = \Depage\Cms\Newsletter::loadByName($this->project, $this->newsletterName);
        }
    }
    // }}}

    // {{{ index()
    function index() {
        return $this->edit();
    }
    // }}}

    // {{{ edit()
    function edit() {
        $candidates = $this->newsletter->getCandidates();
        $form = new \Depage\Cms\Forms\Newsletter("newsletter{$this->newsletter}", [
            'newsletter' => $this->newsletter,
            'candidates' => $candidates,
        ]);

        $form->process();
        //if ($form->validateAutosave()) {
        if ($form->validate()) {
            $values = $form->getValues();
            $pages = [];
            foreach ($candidates as $c) {
                if ($values[$c->name]) {
                    $pages[] = $c;
                }
            }
            $xml = $this->newsletter->setNewsletterPages($pages);
        }

        $h = new Html("box.tpl", [
            'class' => "box-newsletter",
            'title' => _("Newsletter"),
            'liveHelp' => _("Edit your newsletter and choose which items to include"),
            'content' => [
                $form,
            ],
        ], $this->htmlOptions);

        return $h;
    }
    // }}}

    // {{{ details()
    function details($max = null) {
        $h = new Html([
            'content' => [
                "details"
            ],
        ], $this->htmlOptions);

        return $h;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
