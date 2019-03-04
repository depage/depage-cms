<?php
/**
 * @file    framework/Cms/Ui/Project.php
 *
 * depage cms ui module
 *
 *
 * copyright (c) 2002-2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Ui;

use \Depage\Html\Html;
use \Depage\Notifications\Notification;

class Project extends Base
{
    public $defaults = [
        'graphics' => [
            'extension'     => 'gd',
            'executable'    => '',
            'background'    => 'transparent',
            'optimize'      => false,
        ],
    ];
    // {{{ _init
    public function _init(array $importVariables = []) {
        parent::_init($importVariables);

        $this->projectName = $this->urlSubArgs[0];

        if (empty($this->projectName)) {
            throw new \Depage\Cms\Exceptions\Project("no project given");
        } else if ($this->projectName == "+") {
            $this->project = new \Depage\Cms\Project($this->pdo, $this->xmldbCache);
        } else {
            $this->project = $this->getProject($this->projectName);
            $this->project->setGraphicsOptions($this->options->graphics);
        }

        if (!$this->project) {
            throw new \Depage\Cms\Exceptions\Project("not allowed");
        }
    }
    // }}}

    // {{{ index()
    function index() {
        if ($this->projectName == "+") {
            return $this->settings();
        } else {
            return $this->edit();
        }
    }
    // }}}
    // {{{ settings()
    /**
     * @brief settings
     *
     * @param mixed
     * @return void
     **/
    public function settings($type = "")
    {
        $html = "";
        $infoHead = "";
        $infoText = "";
        $tabTitles = [
            "basic" => _("Project Settings"),
            "tags" => _("Tags"),
            "languages" => _("Languages"),
            "variables" => _("Variables"),
            "publishs" => _("Publish"),
            "backup" => _("Backup"),
            "import" => _("Import"),
        ];
        if (!$this->authUser->canEditTemplates()) {
            unset($tabTitles["variables"]);
            unset($tabTitles["backup"]);
            unset($tabTitles["import"]);
        }
        if ($this->projectName == "+") {
            $tabTitles = [
                "basic" => _("New Project"),
            ];
        }

        if ($type == "languages") {
            $infoHead = _("Languages");
            $infoText = _("depage-cms allows to have pages in multiple languages. Here you can add or edit the available languages.\nThe first language acts as the fallback-language if the page is not available in the users language. You can adjust the order by drag and drop.");

            $html .=  $this->settingsXmlForms("language");
        } else if ($type == "tags") {
            $infoHead = _("Tags");
            $infoText = _("Tags help you to categorize and filter your pages. Your templates have to support them though.\nYou can adjust the order by drag and drop.");
            $html .= $this->settingsXmlForms("tag");

            if ($this->authUser->canEditTemplates()) {
                $html .= "<hr>";
                $html .= $this->settingsXmlForms("navigation");
            }
        } else if ($type == "variables") {
            $infoHead = _("Variables");
            $infoText = _("Variables can be used for settings and can globally change the behaviour of various templates.");

            $html .= $this->settingsXmlForms("variable");
        } else if ($type == "publishs") {
            $infoHead = _("Publishing targets");
            $infoText = _("depage-cms allows to publish you pages to either a local folder or to another webserver to serve from. The first publishing targets acts as the default target. You can adjust the order by drag and drop.");

            $html .= $this->settingsXmlForms("publish");
        } else if ($type == "import") {
            $html .= $this->import();
        } else if ($type == "backup") {
            $html .= $this->backups();
        } else {
            $html .= $this->settings_basic();
        }

        if ($this->project->id != null) {
            $title = sprintf(_("Project Settings: %s"), $this->project->fullname);
        } else {
            $title = _("Add new Project");
        }

        $h = new Html("settings.tpl", [
            'content' => new Html("box.tpl", [
                'class' => "box-settings",
                'title' => $title,
                'content' => [
                    new Html("tabs.tpl", [
                        'baseUrl' => "project/" . $this->project->name . "/settings/",
                        'tabs' => $tabTitles,
                        'activeTab' => $type,
                    ]),
                    new Html("info.tpl", [
                        'title' => $infoHead,
                        'content' => $infoText,
                    ]),
                    $html,
                ],
            ]),
        ], $this->htmlOptions);

        return $h;
    }
    // }}}
    // {{{ settings-basic()
    /**
     * @brief basic settings
     *
     * @param mixed
     * @return void
     **/
    private function settings_basic()
    {
        $form = new \Depage\Cms\Forms\Project\Basic("edit-project-basic-" . $this->project->id, [
            'project' => $this->project,
            'projectGroups' => \Depage\Cms\ProjectGroup::loadAll($this->pdo),
        ]);
        $form->process();

        if ($form->validateAutosave()) {
            $values = $form->getValues();

            foreach ($values as $key => $val) {
                $this->project->$key = $val;
            }

            $this->project->save();
            $form->clearSession(false);

            \Depage\Depage\Runner::redirect(DEPAGE_BASE . "project/{$this->project->name}/settings/");
        }

        return $form;
    }
    // }}}
    // {{{ settingsXmlForms()
    /**
     * @brief tags settings
     *
     * @param mixed
     * @return void
     **/
    private function settingsXmlForms($type)
    {
        $settings = $this->project->getSettingsDoc();
        $formClass = "\\Depage\\Cms\\Forms\\Project\\" . ucfirst($type);
        if ($type == "publish") {
            $nodeName = "publishTarget";
        } else {
            $nodeName = $type;
        }
        $nodeIds = $settings->getNodeIdsByXpath("//proj:{$nodeName}s/proj:{$nodeName}");
        $parentId = $settings->getParentIdById($nodeIds[0]);
        $forms = [];

        foreach($nodeIds as $nodeId) {
            $xml = $settings->getSubdocByNodeId($nodeId);
            $form = new $formClass("edit-project-{$type}s-{$this->project->id}-{$nodeId}", [
                'project' => $this->project,
                'dataNode' => $xml,
                'parentId' => $parentId,
            ]);
            array_push($forms, $form);
        }

        $xml = new \Depage\Xml\Document();
        $xml->load(__DIR__ . "/../XmlDocTypes/SettingsXml/{$type}.xml");
        $languages = array_keys($this->project->getLanguages());
        \Depage\Cms\XmlDocTypes\Traits\MultipleLanguages::updateLangNodes($xml, $languages);
        $form = new $formClass("edit-project-{$type}s-{$this->project->id}-new", [
            'project' => $this->project,
            'dataNode' => $xml,
            'parentId' => $parentId,
        ]);
        array_push($forms, $form);

        foreach ($forms as $form) {
            $form->process();

            if ($form->validateAutosave()) {
                $node = $form->getValuesXml();
                if ($node->ownerDocument->documentElement->hasAttributeNS("http://cms.depagecms.net/ns/database", "lastchange")) {
                    $settings->saveNode($node);
                } else {
                    $settings->addNode($node, $parentId);
                }

                $form->clearSession(false);

                if ($type == "navigation") {
                    $type = "tag";
                }

                // @todo add hash for the currently selected element
                \Depage\Depage\Runner::redirect(DEPAGE_BASE . "project/{$this->project->name}/settings/{$type}s/");
            }
        }

        return "<div class=\"sortable-forms\">" . implode($forms) . "</div>";
    }
    // }}}
    // {{{ import()
    /**
     * @brief import
     *
     * @param mixed
     * @return void
     **/
    private function import()
    {
        $form = new \Depage\Cms\Forms\Project\Import("import-project-" . $this->project->id, [
            'project' => $this->project,
        ]);
        $form->process();

        if ($form->validate()) {
            $import = \Depage\Cms\Import::factory($this->project, $this->pdo);

            // @todo move cleaning back into import task (double pdo connection?)
            $import->cleanDocs();

            //$value = $import->importProject("projects/{$this->project->name}/import/backup_full.xml");
            //return;

            $task = $import->addImportTask("Import Project '{$this->project->name}'", "projects/{$this->project->name}/import/backup_full.xml");

            $form->clearSession();

            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
        }

        return $form;
    }
    // }}}
    // {{{ publish()
    /**
     * @brief publish
     *
     * @param mixed
     * @return void
     *
     * @todo implement adding documents to xmldb history when publishing and using these for xsl transformations
     **/
    public function publish()
    {
        if (!$this->authUser->canPublishProject()) {
            return $this->notAllowed(_("You are not allowed to publish this project."));
        }
        $form = new \Depage\Cms\Forms\Publish("publish-project-" . $this->project->id, [
            'project' => $this->project,
            'users' => \Depage\Auth\User::loadAll($this->pdo),
        ]);
        $form->process();

        if ($form->validate()) {
            $values = $form->getValues();
            $publishId = $values['publishId'];

            // release pages
            foreach ($values as $key => $value) {
                if ($value == true && preg_match('/page-(.*)/', $key, $matches)) {
                    $this->project->releaseDocument($matches[1], $this->authUser->id);
                }
            }
            $this->project->releaseDocument("pages", $this->authUser->id);

            $this->project->addPublishTask("Publish Project '{$this->project->name}/{$publishId}'", $publishId, $this->authUser->id);

            $form->clearSession();

            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
        }

        $title = sprintf(_("Publish Project '%s'"), $this->project->name);

        $h = new Html("publish.tpl", [
            'content' => new Html("box.tpl", [
                'id' => "projects",
                'icon' => "framework/Cms/images/icon_projects.gif",
                'class' => "box-publish",
                'title' => $title,
                'content' => [
                    $this->toolbar(),
                    $form,
                ],
            ]),
        ], $this->htmlOptions);

        return $h;
    }
    // }}}
    // {{{ release_pages()
    /**
     * @brief release_pages
     *
     * @param mixed
     * @return void
     *
     * @todo implement adding documents to xmldb history when publishing and using these for xsl transformations
     **/
    public function release_pages($docId = null)
    {
        if (!$this->authUser->canPublishProject()) {
            return $this->notAllowed(_("You are not allowed to release pages on this project."));
        }
        $form = new \Depage\Cms\Forms\ReleasePages("release-pages-" . $this->project->id, [
            'project' => $this->project,
            'users' => \Depage\Auth\User::loadAll($this->pdo),
            'selectedDocId' => $docId,
        ]);
        $form->process();

        if ($form->validate()) {
            $values = $form->getValues();
            $publishId = $values['publishId'];

            // release pages
            foreach ($values as $key => $value) {
                if ($value == true && preg_match('/page-(.*)/', $key, $matches)) {
                    $this->project->releaseDocument($matches[1], $this->authUser->id);
                }
            }
            $this->project->releaseDocument("pages", $this->authUser->id);

            $form->clearSession();

            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
        }

        $title = sprintf(_("Release Pages for Project '%s'"), $this->project->name);
        $previewUrl = "";

        $pages = $this->project->getPages($docId);
        if (count($pages) == 1) {
            $previewUrl = $this->project->getPreviewPath() . $pages[0]->url;
        }

        $h = new Html("publish.tpl", [
            'previewUrl' => $previewUrl,
            'content' => new Html("box.tpl", [
                'id' => "projects",
                'icon' => "framework/Cms/images/icon_projects.gif",
                'class' => "box-publish",
                'title' => $title,
                'content' => [
                    $this->toolbar(),
                    $form,
                ],
            ]),
        ], $this->htmlOptions);

        return $h;
    }
    // }}}
    // {{{ upload()
    /**
     * @brief upload
     *
     * @param mixed
     * @return void
     **/
    public function upload()
    {
        $libPath = "/" . implode("/", func_get_args());
        $targetPath = $this->project->getProjectPath() . "lib" . $libPath;

        $form = new \Depage\Cms\Forms\Project\FlashUpload("upload-to-lib", [
            'project' => $this->project,
            'targetPath' => $libPath,
        ]);
        $form->process();
        if ($form->validate()) {
            $values = $form->getValues();

            if (is_dir($targetPath)) {
                foreach ($values['file'] as $file) {
                    $filename = \Depage\Html\Html::getEscapedUrl($file['name']);
                    rename($file['tmp_name'], $targetPath . "/" . $filename);

                    $cachePath = $this->project->getProjectPath() . "lib/cache/";
                    if (is_dir($cachePath)) {
                        // remove thumbnails from cache inside of project if available
                        $cache = \Depage\Cache\Cache::factory("graphics", [
                            'cachepath' => $cachePath,
                        ]);
                        $cache->delete("lib" . $libPath . "/" . $filename . ".*");
                    }

                    // remove thumbnails from global graphics cache
                    $cache = \Depage\Cache\Cache::factory("graphics");
                    $cache->delete("projects/" . $this->project->name . "/lib" . $libPath . "/" . $filename . ".*");
                }
            }

            $activeUsers = \Depage\Auth\User::loadActive($this->pdo);
            $callback = new \Depage\Cms\Rpc\Func("get_update_prop_files", ['path' => $libPath . '/']);
            foreach ($activeUsers as $user) {
                $newN = new Notification($this->pdo);
                $newN->setData([
                    'sid' => $user->sid,
                    'tag' => "flashRpcUpdate." . $this->projectName,
                    'title' => $this->projectName,
                    'message' => $callback,
                ])
                ->save();
            }

            $form->clearValueOf("file");
        }

        return $form;
    }
    // }}}

    // {{{ edit()
    function edit($pageId = null) {
        $pageId = (int) $pageId;

        // construct template
        $hProject = new Html("projectmain.tpl", [
            'previewUrl' => $this->project->getPreviewPath(),
            'projectName' => $this->project->name,
            'pageId' => $pageId,
        ], $this->htmlOptions);

        $h = new Html([
            'content' => [
                $hProject,
            ],
        ]);

        return $h;
    }
    // }}}
    // {{{ add-new-post()
    function add_new_post() {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            //die();
        }

        return new \Depage\Json\Json([
            "pageId" => $this->project->addNewPost(),
        ]);
    }
    // }}}
    // {{{ newsletters()
    /**
     * @brief newsletters
     *
     * @param mixed
     * @return void
     **/
    public function newsletters()
    {
        $newsletters = \Depage\Cms\Newsletter::loadAll($this->pdo, $this->project);

        $h = new Html("newsletterList.tpl", [
            'user' => $this->authUser,
            'project' => $this->project,
            'newsletters' => $newsletters,
        ], $this->htmlOptions);

        return $h;
    }
    // }}}
    // {{{ newsletter_subscribers()
    /**
     * @brief newsletter_subscribers
     *
     * @param mixed
     * @return void
     *
     * @todo look into https://github.com/PHPOffice/PhpSpreadsheet for better export
     **/
    public function newsletter_subscribers($category = "")
    {
        $tableSubscribers = "{$this->pdo->prefix}_proj_{$this->project->name}_newsletter_subscribers";

        $params = [];
        $sql = "SELECT *
            FROM
                {$tableSubscribers} AS subscribers";

        if ($category !== "") {
            $sql .= " WHERE
                validation IS NULL AND
                category = :category";
            $params['category'] = $category;
        } else {
            $sql .= " WHERE
                validation IS NULL
                ORDER BY category";
        }

        $query = $this->pdo->prepare($sql);
        $query->execute($params);

        $filename = "{$this->project->name}-newsletter-subscribers-" . gmdate('Ymd-His') . ".csv";

        header('Content-Type: text/csv');
        header("Content-Disposition: attachment;filename=\"$filename\"");
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');
        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $fp = fopen('php://output', 'w');

        // add UTF-8 BOM
        fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));

        // add header
        $subscriber = $query->fetch(\PDO::FETCH_ASSOC);

        $keys = array_keys($subscriber);
        fputcsv($fp, $keys, ";");

        // add subscribers
        do {
            fputcsv($fp, $subscriber, ";");
        } while ($subscriber = $query->fetch(\PDO::FETCH_ASSOC));


        fclose($fp);

        die();
    }
    // }}}
    // {{{ backups()
    /**
     * @brief backup
     *
     * @return void
     **/
    private function backups()
    {
        $backup = new \Depage\Cms\Backup($this->pdo, $this->project);
        //$backup->makeAutoBackup();


        $form = new \Depage\Cms\Forms\BackupsRestore("backup-restore", [
            'backups' => $backup->getAutoBackups(),
        ]);
        $form->process();

        if ($form->validate()) {
            $backup->restoreFromFile($form->getValues()['file']);

            $form->clearSession();

            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
        }

        return $form;
    }
    // }}}
    // {{{ statistics()
    /**
     * @brief statistics
     *
     * @todo added embedded google analytics dashboard for projects with analytics account
     * @return void
     **/
    public function statistics()
    {

    }
    // }}}
    // {{{ details()
    function details($max = null) {
        $h = new Html([
            'content' => [
                $this->recent_changes($max),
            ],
        ], $this->htmlOptions);

        return $h;
    }
    // }}}
    // {{{ recent-changes()
    function recent_changes($max = null) {
        $pages = $this->project->getRecentlyChangedPages($max);
        $date = $this->project->getLastPublishDate();

        $h = new Html("changelist.tpl", [
            'previewPath' => $this->project->getPreviewPath(),
            'pages' => $pages,
            'lastPublishDate' => $date,
        ], $this->htmlOptions);

        return $h;
    }
    // }}}
    // {{{ update()
    /**
     * @brief update
     *
     * @param mixed
     * @return void
     **/
    public function update()
    {
        $updateSrc = $this->project->getProjectPath() . "xslt/update.php";

        if (file_exists($updateSrc)) {
            $this->project->addProjectUpdateTask();

            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
        } else {
            return "not update defined";
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
