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
            $this->newsletter = \Depage\Cms\Newsletter::create($this->pdo, $this->project);

            \Depage\Depage\Runner::redirect($this->getActionUrl("edit"));
        } else if ($this->newsletterName == "current") {
            $newsletters = \Depage\Cms\Newsletter::loadAll($this->pdo, $this->project);
            $this->newsletter = end($newsletters);
        } else {
            $this->newsletter = \Depage\Cms\Newsletter::loadByName($this->pdo, $this->project, $this->newsletterName);
        }
    }
    // }}}

    // {{{ getActionUrl()
    /**
     * @brief getActionUrl
     *
     * @param mixed $
     * @return void
     **/
    protected function getActionUrl($action = "", $newsletter = null)
    {
        if (is_null($newsletter)) {
            $newsletter = $this->newsletter;
        }
        $url = DEPAGE_BASE . "project/{$newsletter->project->name}/newsletter/{$newsletter->name}/{$action}/";

        return $url;
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
        $form = new \Depage\Cms\Forms\Newsletter("newsletter{$this->newsletter->name}", [
            'dataNode' => $this->newsletter->getXml(),
            'newsletter' => $this->newsletter,
            'candidates' => $candidates,
        ]);

        $form->process();
        if ($form->validateAutosave()) {
            $xml = $form->getValuesXml();
            $values = $form->getValues();
            $pages = [];
            foreach ($candidates as $c) {
                if ($values[$c->name]) {
                    $pages[] = $c;
                }
            }
            $this->newsletter->setNewsletterPages($pages, $xml);
        }

        $h = new Html("newsletterEdit.tpl", [
            'content' => [
                new Html("tabs.tpl", [
                    'baseUrl' => $this->newsletter->getBaseUrl(),
                    'tabs' => [
                        "edit" => _("Edit"),
                        "publish" => _("Publish"),
                    ],
                    'activeTab' => "edit",
                ]),
                new Html("info.tpl", [
                    'title' => _("Edit Newsletter"),
                    'content' => _("Please choose the news items you want to include in the newsletter:"),
                ]),
                $form,
            ],
            'previewUrl' => $this->newsletter->getPreviewUrl("pre"),
        ], $this->htmlOptions);

        return $h;
    }
    // }}}

    // {{{ publish()
    function publish() {
        $form = new \Depage\Cms\Forms\NewsletterPublish("newsletterPublish{$this->newsletter->name}", [
            'newsletter' => $this->newsletter,
        ]);

        $form->process();
        if ($form->validate()) {
            $values = $form->getValues();

            $this->newsletter->release($this->authUser->id);
            if ($values['to'] == "__custom") {
                // @todo set lang correctly
                $this->newsletter->sendTo("info@depage.net", $values['emails'], "de");
                $this->newsletter->sendTo("info@depage.net", $values['emails'], "en");
            } else {
                $this->newsletter->sendToSubscribers("info@depage.net", $values['to']);
            }
            $form->clearSession();

            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
        }

        $h = new Html("box.tpl", [
            'title' => "Send Newsletter",
            'content' => $form,
        ], $this->htmlOptions);

        $h = new Html("newsletterEdit.tpl", [
            'content' => [
                new Html("tabs.tpl", [
                    'baseUrl' => $this->newsletter->getBaseUrl(),
                    'tabs' => [
                        "edit" => _("Edit"),
                        "publish" => _("Publish"),
                    ],
                    'activeTab' => "publish",
                ]),
                new Html("info.tpl", [
                    'title' => _("Publish Newsletter"),
                    'content' => _("You can send the newsletter to a group of your subscribers (e.g. 'Default') or to a comma separated list of custom emails for testing.\nPlease note that only published news items will be included in the newsletter. So make sure, that you publish your site first before sending the newsletter!"),
                ]),
                $form,
            ],
            'previewUrl' => $this->newsletter->getPreviewUrl("live"),
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
