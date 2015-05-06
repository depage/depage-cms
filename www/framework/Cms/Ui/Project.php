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

class Project extends Base
{
    // {{{ _init
    public function _init(array $importVariables = array()) {
        parent::_init($importVariables);

        $this->projectName = $this->urlSubArgs[0];

        if (empty($this->projectName)) {
            throw new \Depage\Cms\Exceptions\Project("no project given");
        } else if ($this->projectName == "+") {
            $this->project = new \Depage\Cms\Project($this->pdo);
        } else {
            $this->project = \Depage\Cms\Project::loadByName($this->pdo, $this->projectName);
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

        if ($type == "languages") {
            $html =  $this->settings_languages();
        } else if ($type == "navigation") {
            $html =  $this->settings_languages();
        } else if ($type == "tags") {
            $html =  $this->settings_tags();
        } else if ($type == "variables") {
            $html =  $this->settings_variables();
        } else if ($type == "publish") {
            $html =  $this->settings_publish();
        } else if ($type == "import") {
            $html = $this->import();
        } else {
            $html =  $this->settings_basic();
        }

        if ($this->project->id != null) {
            $title = sprintf(_("Project '%s' Settings"), $this->project->name);
        } else {
            $title = _("Add new Project");
        }
        $h = new Html("box.tpl", array(
            'id' => "projects",
            'icon' => "framework/Cms/images/icon_projects.gif",
            'class' => "first",
            'title' => $title,
            'content' => array(
                $this->toolbar(),
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

        if ($form->validate()) {
            $values = $form->getValues();

            foreach ($values as $key => $val) {
                $this->project->$key = $val;
            }

            $this->project->save();
            $form->clearSession();

            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
        }

        return $form;
    }
    // }}}
    // {{{ settings-languages()
    /**
     * @brief language settings
     *
     * @param mixed
     * @return void
     **/
    private function settings_languages()
    {
        $settings = $this->project->getSettingsDoc();
        $xml = $settings->getSubDocByXpath("//proj:languages");

        $form = new \Depage\Cms\Forms\Project\Languages("edit-project-languages-" . $this->project->id, array(
            'project' => $this->project,
            'dataNode' => $xml,
        ));
        $form->process();

        if ($form->validate()) {
            $node = $form->getValuesXml();
            $settings->saveNode($node);

            $form->clearSession();

            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
        }

        return $form;
    }
    // }}}
    // {{{ settings-tags()
    /**
     * @brief tags settings
     *
     * @param mixed
     * @return void
     **/
    private function settings_tags()
    {
        // @todo updated with multiple forms per element
        $settings = $this->project->getSettingsDoc();
        $xml = $settings->getSubDocByXpath("//proj:tags");

        $form = new \Depage\Cms\Forms\Project\Tags("edit-project-tags-" . $this->project->id, array(
            'project' => $this->project,
            'dataNode' => $xml,
        ));
        $form->process();

        if ($form->validate()) {
            $node = $form->getValuesXml();
            $settings->saveNode($node);

            $form->clearSession();

            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
        }

        return $form;
    }
    // }}}
    // {{{ settings-variables()
    /**
     * @brief variable settings
     *
     * @param mixed
     * @return void
     **/
    private function settings_variables()
    {
        $form = new \Depage\Cms\Forms\Project\Variables("edit-project-variables-" . $this->project->id, array(
            'project' => $this->project,
        ));
        $form->process();

        if ($form->validate()) {
            $values = $form->getValues();

            $form->clearSession();

            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
        }

        return $form;
    }
    // }}}
    // {{{ settings-publish()
    /**
     * @brief publish settings
     *
     * @param mixed
     * @return void
     **/
    private function settings_publish()
    {
        $settings = $this->project->getSettingsDoc();
        $xml = $settings->getSubDocByXpath("//proj:publishTargets");

        $form = new \Depage\Cms\Forms\Project\Publish("edit-project-publish-" . $this->project->id, array(
            'project' => $this->project,
            'dataNode' => $xml,
        ));
        $form->process();

        if ($form->validate()) {
            $node = $form->getValuesXml();
            $settings->saveNode($node);

            $form->clearSession();

            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
        }

        return $form;
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
            // get cache instance
            $cache = \Depage\Cache\Cache::factory("xmldb", array(
                'host' => "localhost",
            ));

            $import = new \Depage\Cms\Import($this->project->name, $this->pdo, $cache);
            $value = $import->importProject("projects/{$this->project->name}/import/backup_full.xml");
            return;

            $value = $import->addImportTask("Import Project '{$this->project->name}'", "projects/{$this->project->name}/import/backup_full.xml");

            $form->clearSession();

            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
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

        $h = new Html("changelist.tpl", array(
            'previewPath' => $this->project->getPreviewPath(),
            'pages' => $pages,
        ), $this->htmlOptions);

        return $h;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
