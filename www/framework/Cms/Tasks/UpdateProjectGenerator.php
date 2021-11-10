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

        $task->addSubtask("updating project schema of {$this->project->name}", "\$generator->updateProjectSchema();", [], $this->initId);
        $task->addSubtask("syncing file library of {$this->project->name}", "\$generator->syncFileLibrary();", [], $this->initId);

        $this->queueDocumentTest();

        $updateSrc = $this->project->getProjectPath() . "xslt/update.php";
        if (file_exists($updateSrc)) {
            include($updateSrc);
        }

        $this->queueDocumentRelease();
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
    // {{{ queueDocumentTest()
    /**
     * @brief queueDocumentTest
     *
     * @return void
     **/
    public function queueDocumentTest()
    {
        $xmldb = $this->project->getXmlDb();
        $docs = $xmldb->getDocuments();

        $task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $this->taskName, $this->name);

        foreach ($docs as $doc) {
            $docId = $doc->getDocId();

            $task->addSubtask("testing document '$docId' in '{$this->project->name}'", "\$generator->testDoc(%s);", [$docId], $this->initId);
        }
    }
    // }}}
    // {{{ queueDocumentRelease()
    /**
     * @brief queueDocumentRelease
     *
     * @return void
     **/
    public function queueDocumentRelease()
    {
        $xmldb = $this->project->getXmlDb();
        $docs = $xmldb->getDocuments();

        $task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $this->taskName, $this->name);

        foreach ($docs as $doc) {
            $docId = $doc->getDocId();

            if ($doc->isReleased()) {
                $task->addSubtask("releasing document '$docId' in '{$this->project->name}'", "\$generator->releaseDoc(%s);", [$docId], $this->initId);
            }
        }
    }
    // }}}

    // {{{ updateProjectSchema()
    /**
     * @brief updateProjectSchema
     *
     * @return void
     **/
    public function updateProjectSchema()
    {
        $this->project->updateProjectSchema();
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
    // {{{ testDoc()
    /**
    * @briefreadDoc
    *
    * @param int $docId
    * @return void
    **/
    public function testDoc($docId)
    {
        $xmldb = $this->project->getXmlDb();

        $doc = $xmldb->getDoc($docId);
        $doc->getXml();
    }
    // }}}
    // {{{ releaseDoc()
    /**
    * @briefreleaseDoc
    *
    * @param int $docId
    * @return void
    **/
    public function releaseDoc($docId)
    {
        $xmldb = $this->project->getXmlDb();

        $doc = $xmldb->getDoc($docId);
        $doc->getHistory()->save($this->userId, true);
        $doc->clearCache();
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
