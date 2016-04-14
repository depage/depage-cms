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
    // {{{ _init
    public function _init(array $importVariables = array()) {
        parent::_init($importVariables);

        $this->projectName = $this->urlSubArgs[0];

        if (empty($this->projectName)) {
            throw new \Depage\Cms\Exceptions\Project("no project given");
        } else if ($this->projectName == "+") {
            $this->project = new \Depage\Cms\Project($this->pdo, $this->xmldbCache);
        } else {
            $this->project = $this->getProject($this->projectName);
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
        $tabTitles = array(
            "basic" => _("Project Settings"),
            "tags" => _("Tags"),
            "languages" => _("Languages"),
            "variables" => _("Variables"),
            "publishs" => _("Publish"),
            "import" => _("Import"),
        );
        if (!$this->authUser->canEditTemplates()) {
            unset($tabTitles["variables"]);
            unset($tabTitles["import"]);
        }
        if ($this->projectName == "+") {
            $tabTitles = array(
                "basic" => _("New Project"),
            );
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
        } else {
            $html .= $this->settings_basic();
        }

        if ($this->project->id != null) {
            $title = sprintf(_("Project '%s' Settings"), $this->project->name);
        } else {
            $title = _("Add new Project");
        }

        $h = new Html("box.tpl", array(
            'id' => "box-settings",
            'class' => "box-settings",
            'title' => $title,
            'content' => array(
                new Html("tabs.tpl", array(
                    'baseUrl' => "project/" . $this->project->name . "/settings/",
                    'tabs' => $tabTitles,
                    'activeTab' => $type,
                )),
                new Html("info.tpl", [
                    'title' => $infoHead,
                    'content' => $infoText,
                ]),
                $html,
            ),
        ), $this->htmlOptions);

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
        $form = new \Depage\Cms\Forms\Project\Basic("edit-project-basic-" . $this->project->id, array(
            'project' => $this->project,
            'projectGroups' => \Depage\Cms\ProjectGroup::loadAll($this->pdo),
        ));
        $form->process();

        if ($form->validateAutosave()) {
            $values = $form->getValues();

            foreach ($values as $key => $val) {
                $this->project->$key = $val;
            }

            $this->project->save();
            $form->clearSession(false);

            //\Depage\Depage\Runner::redirect(DEPAGE_BASE);
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
        $forms = array();

        foreach($nodeIds as $nodeId) {
            $xml = $settings->getSubdocByNodeId($nodeId);
            $form = new $formClass("edit-project-{$type}s-{$this->project->id}-{$nodeId}", array(
                'project' => $this->project,
                'dataNode' => $xml,
                'parentId' => $parentId,
            ));
            array_push($forms, $form);
        }

        $xml = new \Depage\Xml\Document();
        $xml->load(__DIR__ . "/../XmlDocTypes/SettingsXml/{$type}.xml");
        $languages = array_keys($this->project->getLanguages());
        \Depage\Cms\XmlDocTypes\Traits\MultipleLanguages::updateLangNodes($xml, $languages);
        $form = new $formClass("edit-project-{$type}s-{$this->project->id}-new", array(
            'project' => $this->project,
            'dataNode' => $xml,
            'parentId' => $parentId,
        ));
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
        $form = new \Depage\Cms\Forms\Project\Import("import-project-" . $this->project->id, array(
            'project' => $this->project,
        ));
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
     **/
    public function publish()
    {
        $form = new \Depage\Cms\Forms\Publish("publish-project-" . $this->project->id, array(
            'project' => $this->project,
        ));
        $form->process();

        if ($form->validate()) {
            $publishId = $form->getValues()['publishId'];
            $this->project->addPublishTask("Publish Project '{$this->project->name}/{$publishId}'", $publishId);

            $form->clearSession();

            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
        }

        $title = sprintf(_("Publish Project '%s'"), $this->project->name);

        $h = new Html("box.tpl", array(
            'id' => "projects",
            'icon' => "framework/Cms/images/icon_projects.gif",
            'class' => "first",
            'title' => $title,
            'content' => array(
                $this->toolbar(),
                $form,
            ),
        ), $this->htmlOptions);

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

        $form = new \Depage\Cms\Forms\Project\Upload("upload-to-lib", array(
            'project' => $this->project,
            'targetPath' => $libPath,
        ));
        $form->process();
        if ($form->validate()) {
            $values = $form->getValues();

            if (is_dir($targetPath)) {
                foreach ($values['file'] as $file) {
                    rename($file['tmp_name'], $targetPath . "/" . $file['name']);
                }
            }

            $activeUsers = \Depage\Auth\User::loadActive($this->pdo);
            $callback = new \Depage\Cms\Rpc\Func("get_update_prop_files", array('path' => $libPath . '/'));
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
    function edit() {
        // construct template
        $hProject = new Html("flashedit.tpl", array(
            'flashUrl' => "project/{$this->projectName}/flash/flash/false",
            'previewUrl' => $this->project->getPreviewPath(),
        ));

        $h = new Html(array(
            'content' => array(
                $hProject,
            ),
        ), $this->htmlOptions);

        return $h;
    }
    // }}}
    // {{{ jsedit()
    function jsedit() {
        // cms tree
        $tree = Tree::_factoryAndInit($this->options, array(
            'pdo' => $this->pdo,
            'projectName' => $this->projectName,
        ));

        // construct template
        $hProject = new Html("projectmain.tpl", array(
            'tree_pages' => $tree->tree("pages"),
            'tree_document' => $tree->tree("testpage"),
        ), $this->htmlOptions);

        $h = new Html(array(
            'content' => array(
                $hProject,
            ),
        ));

        return $h;
    }
    // }}}

    // {{{ details()
    function details($max = null) {
        $h = new Html(array(
            'content' => array(
                $this->recent_changes($max),
            ),
        ), $this->htmlOptions);

        return $h;
    }
    // }}}
    // {{{ recent-changes()
    function recent_changes($max = null) {
        $pages = $this->project->getRecentlyChangedPages($max);
        $date = $this->project->getLastPublishDate();

        $h = new Html("changelist.tpl", array(
            'previewPath' => $this->project->getPreviewPath(),
            'pages' => $pages,
            'lastPublishDate' => $date,
        ), $this->htmlOptions);

        return $h;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
