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
    public function createNewsletterSender($publishId, $newsletter, $from, $category)
    {
        $this->publishId = $publishId;
        $this->taskName = "Publishing '{$this->project->name}/{$this->publishId}'";

        $conf = $this->project->getPublishingTargets()[$this->publishId];

        $this->task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $this->taskName, $this->project->name);
        $this->task->addSubtask("initializing publishing task", "
            \$generator = %s;
            \$generator->queueSubtasks();
            \$generator->queueSendNewsletter(%s, %s, %s);
        ", [
            $this,
            $newsletter,
            $from,
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
    public function queueSendNewsletter($newsletter, $from, $category)
    {
        $this->task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $this->taskName, $this->project->name);
        $subscribers = $newsletter->getSubscribers($category);

        $this->task->beginTaskTransaction();

        foreach ($subscribers as $lang => $emails) {
            $mail = new \Depage\Mail\Mail($from);
            $mail->setSubject($newsletter->getSubject($lang))
                ->setHtmlText($newsletter->transform("live", $lang));
            // @todo add header List-Unsubscribe:

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
