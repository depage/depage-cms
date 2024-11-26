<?php
/**
 * @file    Publish.php
 *
 * description
 *
 * copyright (c) 2020 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Tasks;

/**
 * @brief Publish
 * Class Publish
 */
class PublishGenerator
{
    /**
     * @brief pdo
     **/
    protected $pdo = null;

    /**
     * @brief task
     **/
    protected $task = null;

    /**
     * @brief taskName
     **/
    protected $taskName = null;

    /**
     * @brief publishId
     **/
    protected $publishId = null;

    /**
     * @brief userId
     **/
    protected $userId = null;

    /**
     * @brief proj
     **/
    protected $project = null;

    /**
     * @brief initId
     */
    protected $initId = null;

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

    // {{{ testConnection()
    /**
     * @brief testConnection
     *
     * @return void
     **/
    public function testConnection()
    {

    }
    // }}}
    // {{{ createPublisher()
    /**
     * @brief createPublisher
     *
     * @return void
     **/
    public function createPublisher($publishId, $releasePages = [], $clearTransformCache = false)
    {
        $this->publishId = $publishId;
        $this->taskName = "Publishing '{$this->project->name}/{$this->publishId}'";

        $conf = $this->project->getPublishingTargets()[$this->publishId];

        $this->task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $this->taskName, $this->project->name);
        $this->task->addSubtask("initializing publishing task", "
            \$generator = %s;
            \$generator->queueSubtasks(%s, %s);
        ", [
            $this,
            $releasePages,
            $clearTransformCache,
        ]);

        return $this->task;
    }
    // }}}

    // {{{ getProject()
    /**
     * @brief getProject
     *
     * @param mixed
     * @return void
     **/
    public function getProject()
    {
        return $this->project;
    }
    // }}}
    // {{{ getPublisher()
    /**
     * @brief getPublisher
     *
     * @param mixed
     * @return void
     **/
    public function getPublisher()
    {
        $conf = $this->project->getPublishingTargets()[$this->publishId];

        $publishPdo = clone $this->pdo;
        $publishPdo->prefix = $this->pdo->prefix . "_proj_" . $this->project->name;

        $fs = \Depage\Fs\Fs::factory($conf->output_folder, [
            'user' => $conf->output_user,
            'pass' => $conf->output_pass,
        ]);

        return new \Depage\Publisher\Publisher($publishPdo, $fs, $this->publishId);
    }
    // }}}
    // {{{ getUrl()
    /**
     * @brief getUrl
     *
     * @param mixed
     * @return void
     **/
    public function getUrls()
    {
        $publishPdo = clone $this->pdo;
        $publishPdo->prefix = $this->pdo->prefix . "_proj_" . $this->project->name;

        return new \Depage\Publisher\Urls($publishPdo, $this->publishId);
    }
    // }}}
    // {{{ getTransformCache()
    /**
     * @brief getTransformCache
     *
     * @param mixed
     * @return void
     **/
    public function getTransformCache()
    {
        $conf = $this->project->getPublishingTargets()[$this->publishId];

        return new \Depage\Transformer\TransformCache(
            $this->pdo,
            $this->project->name,
            $conf->template_set . "-live-" . $this->publishId
        );
    }
    // }}}
    // {{{ getTransformer()
    /**
     * @brief getTransformer
     *
     * @param mixed
     * @return void
     **/
    public function getTransformer()
    {
        $conf = $this->project->getPublishingTargets()[$this->publishId];

        $xmlgetter = $this->project->getXmlGetter();

        $transformCache = $this->getTransformCache();
        //$transformCache = null;

        $transformer = \Depage\Transformer\Transformer::factory(
            "live",
            $this->project->getXmlGetter(),
            $this->project,
            $conf->template_set,
            $transformCache
        );

        $transformer->publishId = $this->publishId;

        $transformer->setBaseUrl(
            $this->project->getBaseUrl($this->publishId)
        );
        $transformer->setBaseUrlStatic(
            $this->project->getBaseUrlStatic($this->publishId)
        );
        $transformer->routeHtmlThroughPhp = $this->project->getProjectConfig()->routeHtmlThroughPhp;

        return $transformer;
    }
    // }}}
    // {{{ getIndexer()
    /**
     * @briefgetIndexer
     *
     * @param mixed
     * @return void
     **/
    public function getIndexer()
    {
        return new \Depage\Search\Indexer();
    }
    // }}}
    // {{{ getImgUrl()
    /**
     * @brief getImgUrl
     *
     * @param mixed
     * @return void
     **/
    public function getImgUrl()
    {
        $options = $this->project->getGraphicsOptions();
        $projectPath = $this->project->getProjectPath();

        return new \Depage\Graphics\Imgurl([
            'extension' => $options->extension,
            'executable' => $options->executable,
            'optimize' => $options->optimize,
            'baseUrl' => $this->project->getBaseUrl($this->publishId),
            'baseUrlStatic' => $this->project->getBaseUrlStatic($this->publishId),
            'cachePath' => $projectPath . "lib/cache/graphics/",
            'relPath' => $projectPath,
        ]);
    }
    // }}}
    // {{{ getNewsletter()
    /**
     * @brief getNewsletter
     *
     * @param mixed $name
     * @return void
     **/
    public function getNewsletter($name)
    {
        return \Depage\Cms\Newsletter::loadByName($this->pdo, $this->project, $name);
    }
    // }}}

    // {{{ queueSubtasks()
    /**
     * @brief queueSubtasks
     *
     * @param mixed
     * @return void
     **/
    public function queueSubtasks($releasePages = [], $clearTransformCache = false)
    {
        $publisher = $this->getPublisher();
        $publisher->testConnection();

        $this->task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $this->taskName, $this->project->name);

        $this->project->setPreviewType("live");
        $xmlgetter = $this->project->getXmlGetter();

        $apiAvailable = $this->project->isApiAvailable();

        $this->releaseDocuments($releasePages);

        if ($clearTransformCache) {
            $this->getTransformCache()->clearAll();
        }

        $this->queueInit();
        $this->queuePublishPageList();
        $this->queuePublishIndex();
        $this->queuePublishFiles();
        if ($apiAvailable) {
            $this->queueUpdateSchema();
        }
        $this->queuePublishPages();
        $this->queuePublishSitemap();
        $this->queuePublishAtom();
        $this->queuePublishFinish();
        if ($this->project->hasNewsletter()) {
            $this->queuePublishNewsletter();
        }
        $this->queueCleanup();
    }
    // }}}

    // {{{ queueInit()
    /**
     * @brief queueInit
     *
     * @param mixed
     * @return void
     **/
    protected function queueInit()
    {
        $this->initId = $this->task->addSubtask("init", "
            clearstatcache();
            \$generator = %s;
            \$publisher = \$generator->getPublisher();
            \$transformer = \$generator->getTransformer();
            \$urls = \$generator->getUrls();
            \$indexer = \$generator->getIndexer();
            \$imgUrl = \$generator->getImgUrl();
        ", [
            $this,
        ]);

        $this->task->addSubtask("resetting publishing state", "\$publisher->resetPublishedState();", [], $this->initId);

    }
    // }}}
    // {{{ queuePublishFiles()
    /**
     * @brief queuePublishFiles
     *
     * @return void
     **/
    protected function queuePublishFiles()
    {
        $libPath = $this->project->getProjectPath() . "lib/";
        $conf = $this->project->getPublishingTargets()[$this->publishId];

        $fl = new \Depage\Cms\FileLibrary($this->pdo, $this->project);

        $fl->syncLibrary();
        $folders = $fl->getAllFolderIds();

        foreach ($folders as $folderId) {
            $this->task->beginTaskTransaction();

            // adding tasks for all files
            $files = $fl->getFilesInFolder($folderId);
            foreach ($files as $file) {
                $this->task->addSubtask("publishing $file->fullname", "\$publisher->publishFileWithHash(%s, %s, %s);", [
                    $libPath . $file->fullname,
                    "lib/" . $file->fullname,
                    $file->hash,
                ], $this->initId);
            }

            $this->task->commitTaskTransaction();
        }
    }
    // }}}
    // {{{ queueUpdateSchema()
    /**
     * @brief queueUpdateSchema
     *
     * @return void
     **/
    protected function queueUpdateSchema()
    {
        // call updateSchema API
        $this->task->addSubtask("updating schema", "
            \$request = new \\Depage\\Http\\Request(%s);
            \$request->allowUnsafeSSL = true;
            \$response = \$request->execute();
            \$result = \$response->getJson();

            if (!\$result['success']) {
                throw new \\Exception(\$result['error']);
            }
        ", [
            $this->project->getBaseUrl($this->publishId) . "api/updateschema/",
        ], $this->initId);
    }
    // }}}
    // {{{ queuePublishPages()
    /**
     * @brief queuePublishPages
     *
     * @return void
     **/
    protected function queuePublishPages()
    {
        $apiAvailable = $this->project->isApiAvailable();
        $baseUrl = $this->project->getBaseUrl($this->publishId);
        $projectPath = $this->project->getProjectPath();
        $languages = $this->project->getLanguages();
        $pages = $this->project->getXmlNav()->getPublicPages($this->project->getLastPublishDate());

        $i = 0;
        $this->task->beginTaskTransaction();

        foreach ($pages as $page) {
            foreach ($languages as $lang => $name) {
                $target = $lang . $page->url;

                // transform page
                $pubId = $this->task->addSubtask(
                    "transforming $target",
                    "\$html = \$transformer->transformUrl(%s, %s);",
                    [$page->url, $lang],
                    $this->initId
                );

                // generate and add images that are generated automatically
                $this->task->addSubtask(
                    "generating images for $target",
                    "\$images = \$indexer->loadXml(\$html, %s)->getImages();
                    foreach (\$images as \$i) {
                        \$imgUrl->render(\$i);
                        if (\$imgUrl->rendered) {
                            \$publisher->publishFile(%s . 'lib/cache/graphics/' . \$imgUrl->id, 'lib/cache/graphics/' . \$imgUrl->id);
                        }
                    }
                    ",
                    [$baseUrl . $target, $projectPath],
                    $pubId
                );

                // publish page
                $this->task->addSubtask(
                    "publishing $target",
                    "\$publisher->publishString(\$html, %s, \$updated);",
                    [$target],
                    $pubId
                );

                // index page
                if ($apiAvailable) {
                    $this->task->addSubtask(
                        "indexing $target",
                        "if (\$updated) {
                            \$request = new \\Depage\\Http\\Request(%s);
                            \$request->allowUnsafeSSL = true;
                            \$response = \$request->setPostData(%s)->execute();
                        }",
                        [$baseUrl . "api/search/index/", ["url" => $baseUrl . $target]],
                        $pubId
                    );
                }
            }

            // add canonical urls
            $this->task->addSubtask(
                "adding canonical url for $target",
                "\$urls->addUrl(%s, %s, %s);",
                [$page->pageId, $page->url, $page->pageOrder],
                $pubId
            );
            $i++;

            if ($i % 50 == 0) {
                $this->task->commitTaskTransaction();
                $this->task->beginTaskTransaction();
            }
        }
        $this->task->commitTaskTransaction();
    }
    // }}}
    // {{{ queuePublishSitemap()
    /**
     * @brief queuePublishSitemap
     *
     * @return void
     **/
    protected function queuePublishSitemap()
    {
        $languages = $this->project->getLanguages();

        foreach ($languages as $lang => $name) {
            $this->task->addSubtask("publishing sitemap ($lang)", "
                \$project = \$generator->getProject();
                \$publisher->publishString(
                    \$project->generateSitemap(%s, %s),
                    %s
                );", [
                    $this->publishId,
                    $lang,
                    "$lang/sitemap.xml",
            ], $this->initId);
        }

        $this->task->addSubtask("publishing sitemap list", "
            \$project = \$generator->getProject();
            \$publisher->publishString(
                \$project->generateSitemap(%s),
                'sitemap.xml'
            );", [
                $this->publishId,
        ], $this->initId);
    }
    // }}}
    // {{{ queuePublishAtom()
    /**
     * @brief queuePublishAtom
     *
     * @return void
     **/
    protected function queuePublishAtom()
    {
        $languages = $this->project->getLanguages();

        foreach ($languages as $lang => $name) {
            $this->task->addSubtask("publishing atom feed ($lang)", "
                \$project = \$generator->getProject();
                \$publisher->publishString(
                    \$project->generateAtomFeed(%s, %s),
                    %s
                );", [
                    $this->publishId,
                    $lang,
                    "$lang/atom.xml",
            ], $this->initId);
        }
    }
    // }}}
    // {{{  queuePublishIndex()
    /**
     * @brief  queuePublishIndex
     *
     * @return void
     **/
    protected function queuePublishIndex()
    {
        $this->task->addSubtask("publishing htaccess", "
            \$project = \$generator->getProject();
            \$publisher->publishString(
                \$project->generateHtaccess(%s),
                %s
            );", [
                $this->publishId,
                ".htaccess",
        ], $this->initId);

        $this->task->addSubtask("publishing index", "
            \$project = \$generator->getProject();
            \$publisher->publishString(
                \$project->generateIndex(%s),
                %s
            );", [
                $this->publishId,
                "index.php",
        ], $this->initId);

        // @todo updated with humans.txt
        // http://humanstxt.org/Standard.html
        $version = \Depage\Depage\Runner::getName() . " " . \Depage\Depage\Runner::getVersion() . "\npublished at ";
        $this->task->addSubtask("publishing info", "
            \$publisher->publishString(
                %s . date('r'),
                %s
            );", [
                $version,
                "publishInfo.txt",
        ], $this->initId);
    }
    // }}}
    // {{{  queuePublishPageList()
    /**
     * @brief queuePublishPageList
     *
     * @return void
     **/
    protected function queuePublishPageList()
    {
        $this->task->addSubtask("publishing index", "
            \$project = \$generator->getProject();
            \$publisher->publishString(
                \$project->generatePageIndex(%s),
                %s
            );", [
                $this->publishId,
                "lib/pageindex.php",
        ], $this->initId);
    }
    // }}}
    // {{{ queuePublishFinish()
    /**
     * @brief queuePublishFinish
     *
     * @return void
     **/
    protected function queuePublishFinish()
    {
        // @todo updated with humans.txt
        // http://humanstxt.org/Standard.html
        $version = \Depage\Depage\Runner::getName() . " " . \Depage\Depage\Runner::getVersion() . "\npublished at ";
        $this->task->addSubtask("publishing info", "
            \$publisher->publishString(
                %s . date('r'),
                %s
            );", [
                $version,
                "publishInfo.txt",
        ], $this->initId);

        $newlyPublishedPages = [];

        $languages = $this->project->getLanguages();
        $baseUrl = $this->project->getBaseUrl($this->publishId);
        $unpublishedPages = $this->project->getXmlNav()->getUnpublishedPages(
            $this->project->getLastPublishDate(),
            true
        );
        foreach($unpublishedPages as $p) {
            foreach ($languages as $lang => $name) {
                $newlyPublishedPages[] = $baseUrl . $lang . $p->url;
            }
        }

        $this->task->addSubtask("notifying", "\$generator->sendPublishingFinishedNotification(%s);", [
            $newlyPublishedPages,
        ], $this->initId);
    }
    // }}}
    // {{{ queuePublishNewsletter()
    /**
     * @brief queuePublishNewsletter
     *
     * @return void
     **/
    protected function queuePublishNewsletter()
    {
        $languages = $this->project->getLanguages();

        // @todo check for number of connections
        $newsletters = \Depage\Cms\Newsletter::loadReleased($this->pdo, $this->project);

        $this->task->beginTaskTransaction();

        foreach ($newsletters as $newsletter) {
            // @todo check if newsletter has been published
            foreach ($languages as $lang => $name) {
                $this->task->addSubtask("publishing newsletter {$newsletter->name}", "
                    \$newsletter = \$generator->getNewsletter(%s);
                    \$publisher->publishString(
                        \$newsletter->transform(\"live\", \"$lang\"),
                        %s
                    );", [
                        $newsletter->name,
                        "$lang/newsletter/{$newsletter->name}.html",
                ], $this->initId);
            }
        }

        $this->task->commitTaskTransaction();
    }
    // }}}
    // {{{ queueCleanup()
    /**
     * @brief queueCleanup
     *
     * @return void
     **/
    protected function queueCleanup()
    {
        $baseUrl = $this->project->getBaseUrl($this->publishId);
        $apiAvailable = $this->project->isApiAvailable();

        $this->task->addSubtask("removing leftover files", "
            \$files = \$publisher->unpublishRemovedFiles();
            if (%s) {
                \$request = new \\Depage\\Http\\Request(%s);
                \$request->allowUnsafeSSL = true;
                foreach (\$files as \$file) {
                    \$response = \$request->setPostData(array('url' => %s . '/' . \$file))->execute();
                }
            }
        ", [
            $apiAvailable,
            $baseUrl . "/api/search/remove/",
            $baseUrl,
        ], $this->initId);

    }
    // }}}

    // {{{ releaseDocuments()
    /**
     * @brief releaseDocuments
     *
     * @param mixed
     * @return void
     **/
    protected function releaseDocuments($releasePages = [])
    {
        $xmldb = $this->project->getXmlDb($this->userId);
        $docs = $xmldb->getDocuments();

        // save pages in history
        foreach ($releasePages as $page) {
            $this->project->releaseDocument($page, $this->userId);
        }

        // save folders in history
        foreach ($docs as $doc) {
            $info = $doc->getDocInfo();
            if ($info->type == "Depage\\Cms\\XmlDocTypes\\Folder") {
                $this->project->releaseDocument($info->name, $this->userId);
            }
        }

        // save current general documents in history
        $this->project->releaseDocument("pages", $this->userId);
        $this->project->releaseDocument("settings", $this->userId);
        $this->project->releaseDocument("colors", $this->userId);
    }
    // }}}
    // {{{ sendPublishingFinishedNotification()
    /**
     * @brief sendPublishingFinishedNotification
     *
     * @param mixed $
     * @return void
     **/
    public function sendPublishingFinishedNotification($pages)
    {
        if (empty($pages)) {
            return;
        }
        $conf = $this->project->getProjectConfig();

        $title = sprintf(_("%s published"), $this->project->fullname);

        $message = _("The following pages got published:\n\n") .
            implode("\n", $pages);

        foreach($conf->publishNotifications as $email) {
            try {
                $user = \Depage\Auth\User::loadByEmail($this->pdo, $email);

                $newN = new \Depage\Notifications\Notification($this->pdo);
                $newN->setData([
                    'uid' => $user->id,
                    'tag' => "mail.project-published",
                    'title' => $title,
                    'message' => $message,
                ])
                ->save();
            } catch (\Exception $e) {
                break;
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
            'publishId',
            'userId',
            'project',
        ];
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
