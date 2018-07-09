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

class FileLibrary extends Base
{
    // {{{ _init
    public function _init(array $importVariables = []) {
        parent::_init($importVariables);

        $this->projectName = $this->urlSubArgs[0];

        if (empty($this->projectName)) {
            throw new \Depage\Cms\Exceptions\Project("no project given");
        } else {
            $this->project = $this->getProject($this->projectName);
            $this->fs = \Depage\Fs\Fs::factory($this->project->getProjectPath() . "lib/");
        }
    }
    // }}}

    // {{{ index()
    function index() {
        return $this->manager();
    }
    // }}}
    // {{{ manager()
    function manager($path = "") {
        // construct template
        $hLib = new Html("projectLibrary.tpl", [
            'projectName' => $this->project->name,
            'tree' => $this->tree(),
            'files' => $this->files($path),
        ], $this->htmlOptions);

        $h = new Html([
            'content' => [
                $hLib,
            ],
        ]);

        return $h;
    }
    // }}}
    // {{{ tree()
    /**
     * @brief tree
     *
     * @param mixed
     * @return void
     **/
    public function tree()
    {
        return new Html("treeLibrary.tpl", [
            'fs' => $this->fs,
        ], $this->htmlOptions);
    }
    // }}}
    // {{{ files()
    /**
     * @brief files
     *
     * @param mixed $path = "/"
     * @return void
     **/
    public function files($path = "")
    {
        $path = rawurldecode($path);
        $form = $this->upload($path);
        $files = $this->fs->lsFiles(trim($path . "/*", '/'));

        return new Html("fileListing.tpl", [
            'form' => $form,
            'path' => $path,
            'fs' => $this->fs,
            'files' => $files,
            'project' => $this->project,
        ], $this->htmlOptions);
    }
    // }}}
    // {{{ upload()
    /**
     * @brief upload
     *
     * @param mixed
     * @return void
     **/
    protected function upload($path = "")
    {
        $targetPath = $this->project->getProjectPath() . "lib/" . $path;

        $form = new \Depage\Cms\Forms\Project\Upload("upload-to-lib", [
            'submitUrl' => DEPAGE_BASE . "project/{$this->project->name}/library/manager/" . rawurlencode($path) . "/",
            'project' => $this->project,
            'targetPath' => $path,
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
                        $cache->delete("lib/" . $path . "/" . $filename . ".*");
                    }

                    // remove thumbnails from global graphics cache
                    $cache = \Depage\Cache\Cache::factory("graphics");
                    $cache->delete("projects/" . $this->project->name . "/lib/" . $path . "/" . $filename . ".*");
                }
            }

            $form->clearValueOf("file");
        }

        return $form;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
