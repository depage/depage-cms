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

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $taskName, $publishId, $userId, $project
     * @return void
     **/
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
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
    // {{{ create()
    /**
     * @brief create
     *
     * @return void
     **/
    public function create($taskName, $publishId, $userId, $project)
    {
        $this->taskName = $taskName;
        $this->publishId = $publishId;
        $this->userId = $userId;
        $this->project = $project;

        $conf = $this->project->getPublishingTargets()[$this->publishId];

        $this->task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $this->taskName, $this->project->name);
        $this->task->addSubtask("initializing publishing task", "
            \$generator = %s;
            \$generator->queueSubtasks();
        ", [
            $this,
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

        $transformCache = new \Depage\Transformer\TransformCache(
            $this->pdo,
            $this->project->name,
            $conf->template_set . "-live-" . $this->publishId
        );
        //$transformCache = null;

        $transformer = \Depage\Transformer\Transformer::factory(
            "live",
            $this->project->getXmlGetter(),
            $this->project->name,
            $conf->template_set,
            $transformCache
        );

        $transformer->setBaseUrl(
            $this->project->getBaseUrl($this->publishId)
        );
        $transformer->setBaseUrlStatic(
            $this->project->getBaseUrlStatic($this->publishId)
        );
        $transformer->routeHtmlThroughPhp = $conf->mod_rewrite == "true";

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
            'cachePath' => $projectPath . "lib/cache/graphics/",
            'relativePath' => $projectPath,
        ]);
    }
    // }}}

    // {{{ queueSubtasks()
    /**
     * @brief queueSubtasks
     *
     * @param mixed
     * @return void
     **/
    public function queueSubtasks()
    {
        $publisher = $this->getPublisher();
        $publisher->testConnection();

        $this->task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $this->taskName, $this->project->name);

        $this->project->setPreviewType("live");
        $xmlgetter = $this->project->getXmlGetter();

        $apiAvailable = $this->project->isApiAvailable();

        $this->releaseDocuments();

        $this->queueInit();
        $this->queuePublishFiles();
        if ($apiAvailable) {
            $this->queueUpdateSchema();
        }
        $this->queuePublishPages();
        $this->queuePublishSitemap();
        $this->queuePublishAtom();
        $this->queuePublishIndex();
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
        $projectPath = $this->project->getProjectPath();
        $conf = $this->project->getPublishingTargets()[$this->publishId];

        $fsLocal = \Depage\Fs\Fs::factory($projectPath);

        // getting als files in library
        $files = $fsLocal->lsFiles("lib/*");
        $dirs = $fsLocal->lsDir("lib/*");
        while (count($dirs) > 0) {
            $dir = array_pop($dirs);
            $files = array_merge($files, $fsLocal->lsFiles($dir . "/*"));
            $dirs = array_merge($dirs, $fsLocal->lsDir($dir . "/*"));
        }

        // publish file library
        foreach ($files as $file) {
            $this->task->addSubtask("publishing $file", "\$publisher->publishFile(%s, %s);", [
                $projectPath . $file,
                $file,
            ], $this->initId);
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
        }
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
        $this->task->addSubtask("publishing sitemap", "
            \$project = \$generator->getProject();
            \$publisher->publishString(
                \$project->generateSitemap(%s),
                %s
            );", [
                $this->publishId,
                "sitemap.xml",
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
    // {{{ queuePublishFinish()
    /**
     * @brief queuePublishFinish
     *
     * @return void
     **/
    protected function queuePublishFinish()
    {
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

        foreach ($newsletters as $newsletter) {
            // @todo check if newsletter has been published
            foreach ($languages as $lang => $name) {
                $this->task->addSubtask("publishing newsletter {$newsletter->name}", "
                    \$newsletter = \Depage\Cms\Newsletter::loadByName(\$pdo, \$project, %s);
                    \$publisher->publishString(
                        \$newsletter->transform(\"live\", \"$lang\"),
                        %s
                    );", [
                        $newsletter->name,
                        "$lang/newsletter/{$newsletter->name}.html",
                ], $this->initId);
            }
        }
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
    protected function releaseDocuments()
    {
        $xmldb = $this->project->getXmlDb($this->userId);
        $docs = $xmldb->getDocuments();

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
