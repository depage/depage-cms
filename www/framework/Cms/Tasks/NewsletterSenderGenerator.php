<?php
/**
 * @file    NewsletterSenderGenerator.php
 *
 * description
 *
 * copyright (c) 2020 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Tasks;

/**
 * @brief NewsletterSenderGenerator
 * Class NewsletterSenderGenerator
 */
class NewsletterSenderGenerator extends PublishGenerator
{
    // {{{ create()
    /**
     * @brief create
     *
     * @return void
     **/
    public function createNewsletterSender($publishId, $newsletter, $category)
    {
        $this->publishId = $publishId;
        $this->taskName = "Publishing '{$this->project->name}/{$this->publishId}'";

        $conf = $this->project->getPublishingTargets()[$this->publishId];

        $this->task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $this->taskName, $this->project->name);
        $this->task->addSubtask("initializing publishing task", "
            \$generator = %s;
            \$generator->queueSubtasks();
            \$generator->queueSendNewsletter(%s, %s);
        ", [
            $this,
            $newsletter,
            $category
        ]);

        return $this->task;
    }
    // }}}
    // {{{ queueSendNewsletter()
    /**
     * @brief queueSendNewsletter
     *
     * @param mixed $category
     * @return void
     **/
    public function queueSendNewsletter($newsletter, $category)
    {
        $this->task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $this->taskName, $this->project->name);
        $subscribers = $newsletter->getSubscribers($category, true);

        $this->task->beginTaskTransaction();

        foreach ($subscribers as $lang => $emails) {
            $mail = new \Depage\Mail\Mail($newsletter->conf->from);
            $mail
                ->setListUnsubscribe($newsletter->conf->listUnsubscribe)
                ->setSubject($newsletter->getSubject($lang))
                ->setHtmlText($newsletter->transform("live", $lang));

            $initId = $this->task->addSubtask(
                "initializing mail",
                "\$mail = %s;
                \$newsletter = \$generator->getNewsletter(%s);
                ", [
                $mail,
                $newsletter->name,
            ]);

            foreach ($emails as $to) {
                $newsletter->queueSend($this->task, $initId, $to, $lang);
            }
        }

        $this->task->commitTaskTransaction();
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
