<?php
/**
 * @file    framework/Cms/Ui/Project.php
 *
 * depage cms ui module
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depage.net]
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
            return $this->flashEdit();
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
    protected function settings()
    {
        $form = new \Depage\Cms\Forms\Project("edit-project-" . $this->project->id, array(
            "project" => $this->project,
            "projectGroups" => \Depage\Cms\ProjectGroup::loadAll($this->pdo),
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

        $h = new Html("box.tpl", array(
            'id' => "projects",
            'icon' => "framework/Cms/images/icon_projects.gif",
            'class' => "first",
            'title' => sprintf(_("Project '%s' Settings"), $this->project->name),
            'content' => array(
                $this->toolbar(),
                $form,
            ),
        ), $this->htmlOptions);

        return $h;
    }
    // }}}
    // {{{ import()
    /**
     * @brief import
     *
     * @param mixed
     * @return void
     **/
    protected function import()
    {
        $form = new \Depage\Cms\Forms\Import("import-project-" . $this->project->id);
        $form->process();

        if ($form->validate()) {
            // get cache instance
            $cache = \Depage\Cache\Cache::factory("xmldb", array(
                'host' => "localhost",
            ));

            $import = new \Depage\Cms\Import($this->project->name, $this->pdo, $cache);
            $value = $import->importProject("projects/{$this->project->name}/import/backup_full.xml");

            $form->clearSession();

            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
        }

        $h = new Html("box.tpl", array(
            'id' => "projects",
            'icon' => "framework/Cms/images/icon_projects.gif",
            'class' => "first",
            'title' => sprintf(_("Import Project '%s'"), $this->project->name),
            'content' => array(
                $this->toolbar(),
                $form,
            ),
        ), $this->htmlOptions);

        return $h;
    }
    // }}}

    // {{{ edit()
    function edit() {
        // construct template
        $hProject = new Html("flashedit.tpl", array(
            'flashUrl' => "project/{$this->projectName}/flash/flash/false",
            'previewUrl' => "project/{$this->projectName}/preview/html/noncached/",
        ), $this->htmlOptions);

        $h = new Html(array(
            'content' => array(
                $this->toolbar(),
                $hProject,
            ),
        ));

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
                $this->toolbar(),
                $hProject,
            ),
        ));

        return $h;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
