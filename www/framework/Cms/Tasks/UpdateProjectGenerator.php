<?php
/**
 * @file    UpdateProjectGenerator.php
 *
 * description
 *
 * copyright (c) 2020 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Tasks;

/**
 * @brief UpdateProjectGenerator
 * Class UpdateProjectGenerator
 */
class UpdateProjectGenerator
{
    /**
     * @brief updatedDocuments
     **/
    protected $updatedDocuments = [];

    /**
     * @brief initId
     **/
    protected $initId = null;

    /**
     * @brief taskName
     **/
    protected $taskName = '';

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $taskName, $publishId, $userId, $project
     * @return void
     **/
    public function __construct($pdo, $project, $userId)
    {
        $this->pdo = $pdo;
        $this->userId = $userId;
        $this->project = $project;
    }
    // }}}

    // {{{ createUpdater()
    /**
     * @brief createUpdater
     *
     * @param mixed
     * @return void
     **/
    public function createUpdater($taskName = null)
    {
        $this->taskName = $taskName ?? "Updating project '{$this->project->name}'";

        $xslSrc = $this->project->getProjectPath() . "xslt/update/update.xsl";
        $xmldb = $this->project->getXmlDb();

        $task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $this->taskName, $this->name);
        $this->initId = $task->addSubtask("init", "
            \$generator = %s;
            \$project = \$generator->project;
            \$xsltProc = \$project->getXsltProcessor(%s);
        ", [
            $this,
            $xslSrc,
        ]);

        $task->beginTaskTransaction();

        $task->addSubtask("syncing file library of {$this->project->name}", "\$generator->syncFileLibrary();", [], $this->initId);

        $updateSrc = $this->project->getProjectPath() . "xslt/update.php";
        if (file_exists($updateSrc)) {
            include($updateSrc);
        }

        $task->addSubtask("releasing updated documents of {$this->project->name}", "\$generator->releaseDocuments();", [], $this->initId);
        $task->addSubtask("clearing transform cache of {$this->project->name}", "\$project->clearTransformCache();", [], $this->initId);

        $task->commitTaskTransaction();

        return $task;
    }
    // }}}

    // {{{ queueXslUpdate()
    /**
     * @brief queueXslUpdate
     *
     * @param mixed $xpath
     * @return void
     **/
    public function queueXslUpdate($xpath)
    {
        $xmldb = $this->project->getXmlDb();
        $ids = $xmldb->getNodeIdsByXpath($xpath);

        if (!empty($ids)) {
            $task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $this->taskName, $this->name);

            foreach ($ids as $id) {
                $task->addSubtask("updating xml for '$xpath' in '$id' in '{$this->project->name}'", "\$generator->updateXmlForNodeId(\$xsltProc, %s);", [$id], $this->initId);
            }
        }
    }
    // }}}

    // {{{ syncFileLibrary()
    /**
     * @brief syncFileLibrary
     *
     * @return void
     **/
    public function syncFileLibrary()
    {
        $fl = new \Depage\Cms\FileLibrary($this->pdo, $this->project);
        $fl->syncLibrary();
    }
    // }}}
    // {{{ updateXmlForNodeId()
    /**
    * @brief updateXmlForNodeId
    *
    * @param mixed $param
    * @return void
    **/
    public function updateXmlForNodeId($xsltProc, $id)
    {
        $xmldb = $this->project->getXmlDb();

        $doc = $xmldb->getDocByNodeId($id);
        if (!$doc) {
            return;
        }
        $docId = $doc->getDocId();
        if (!isset($this->updatedDocuments[$docId])) {
            $this->updatedDocuments[$docId] = $doc->isReleased();
        }
        $node = $doc->getSubdocByNodeId($id);

        if (!$newNode = $xsltProc->transformToDoc($node)) {
            // @todo add better error handling
            $messages = "";
            $errors = libxml_get_errors();
            foreach($errors as $error) {
                $messages .= $error->message . "\n";
            }

            $error = "Could not transform the XML document:\n" . $messages;

            throw new \Exception($error);
        }
        if (!$newNode->hasChildNodes()) {
            $doc->deleteNode($id);
        } else if ($node->saveXml() != $newNode->saveXml()) {
            $doc->saveNode($newNode);
        }
    }
    // }}}
    // {{{ releaseDocuments()
    /**
     * @brief releaseDocuments
     *
     * @param mixed
     * @return void
     **/
    public function releaseDocuments()
    {
        $xmldb = $this->project->getXmlDb();

        foreach ($this->updatedDocuments as $docId => $released) {
            if ($released) {
                $doc = $xmldb->getDoc($docId);
                $doc->getHistory()->save($this->userId, true);
                $doc->clearCache();
            }
        }
    }
    // }}}

    // {{{ __sleep()
    /**
     * @brief __sleep
     *
     * @param mixed
     * @return void
     **/
    public function __sleep()
    {
        return [
            'pdo',
            'taskName',
            'userId',
            'initId',
            'project',
        ];
    }
    // }}}
}



// vim:set ft=php sw=4 sts=4 fdm=marker et :
