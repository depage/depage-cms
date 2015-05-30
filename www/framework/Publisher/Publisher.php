<?php

namespace Depage\Publisher;

/**
 * brief Publisher
 * Class Publisher
 */
class Publisher
{
    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $pdo
     * @return void
     **/
    public function __construct($pdo, $transformer)
    {
        $this->pdo = $pdo;
        $this->transformer = $transformer;

    }
    // }}}
    // {{{ addPublishTask()
    /**
     * @brief addPublishTask
     *
     * @param mixed $param
     * @return void
     **/
    protected function addPublishTask($taskName)
    {
        $task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $taskName, $this->projectName);

        $initId = $task->addSubtask("init", "\$publisher = " . \Depage\Tasks\Task::escapeParam($this) . ";");

        $task->addSubtask("testing publish target", "\$publisher->testPublishTarget();", $initId);

        // transform pages
        foreach ($this->pages as $pageId) {
            foreach ($this->languages as $lang) {
                $task->addSubtask("transforming page $page->pageId in $lang", "\$publisher->transformPage($pageId, $lang);", $initId);
            }
        }

        // transform feeds
        foreach ($this->languages as $lang) {
            $task->addSubtask("transforming feed in $lang", "\$publisher->transformFeed($lang);", $initId);
        }

        // transform sitemap
        $task->addSubtask("transforming sitemap", "\$publisher->transformSitemap();", $initId);

        // transform htaccess
        $task->addSubtask("transforming htaccess", "\$publisher->transformHtaccess();", $initId);

        // publish file library
        foreach ($this->files as $file) {
            $task->addSubtask("publishing $file", "\$publisher->publishFile($file);", $initId);
        }

        // publish pages
        foreach ($this->pages as $pageId) {
            foreach ($this->languages as $lang) {
                $task->addSubtask("pubishing page $page->pageId in $lang", "\$publisher->publishPage($pageId, $lang);", $initId);
            }
        }

        // publish feeds
        foreach ($this->languages as $lang) {
            $task->addSubtask("publish feed in $lang", "\$publisher->publishFeed($lang);", $initId);
        }

        // publish sitemap
        $task->addSubtask("publishing sitemap", "\$publisher->publishSitemap();", $initId);

        // publish htaccess
        $task->addSubtask("publishing htaccess", "\$publisher->publishHtaccess();", $initId);

        return $task;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
