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
        }

        $this->project = $this->getProject($this->projectName);
        if (!$this->project) {
            throw new \Depage\Cms\Exceptions\Project("not allowed");
        }

        $this->fs = \Depage\Fs\Fs::factory($this->project->getProjectPath() . "lib/");
    }
    // }}}

    // {{{ index()
    function index() {
        return $this->manager();
    }
    // }}}
    // {{{ manager()
    function manager($path = "") {
        $path = rawurldecode($path);
        $fl = new \Depage\Cms\FileLibrary($this->pdo, $this->project);
        $selected = $fl->syncLibraryTree($path);

        // construct template
        $hLib = new Html("projectLibrary.tpl", [
            'projectName' => $this->project->name,
            'tree' => $this->tree($selected),
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
    public function tree($selected = "")
    {
        $treeUrl = "project/{$this->projectName}/tree/files/{$selected}/";
        $uiTree = Tree::_factoryAndInit($this->conf, [
            'urlSubArgs' => [
                $this->projectName,
                "files",
            ],
            'urlPath' => $treeUrl,
            'pdo' => $this->pdo,
            'auth' => $this->auth,
            'xmldbCache' => $this->xmldbCache,
            'htmlOptions' => $this->htmlOptions,
        ]);

        return $uiTree->tree($selected);
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
        $targetPath = $this->project->getProjectPath() . "lib/" . $path;

        if (!is_dir($targetPath)) {
            return "";
        }

        $form = $this->upload($path);
        $uploadedFiles = $_SESSION['dpLibraryUploadedFiles'] ?? [];
        $_SESSION['dpLibraryUploadedFiles'] = [];
        $files = $this->fs->lsFiles(trim($path . "/*", '/'));

        $fl = new \Depage\Cms\FileLibrary($this->pdo, $this->project);
        $fl->syncFiles($path);

        return new Html("fileListing.tpl", [
            'form' => $form,
            'uploadedFiles' => $uploadedFiles,
            'path' => $path,
            'files' => $files,
            'project' => $this->project,
        ], $this->htmlOptions);
    }
    // }}}
    // {{{ delete()
    /**
     * @brief files
     *
     * @param mixed $path = "/"
     * @return void
     **/
    public function delete($path = "")
    {
        $files = $_POST['files'];

        foreach ($files as $file) {
            if (strpos($file, "libref://") === 0) {
                $file = substr($file, 9);

                $xmldb = $this->project->getXmlDb();
                $doc = $xmldb->getDoc("files");
                $dth = $doc->getDocTypeHandler();

                $dth->moveToTrash($file);
            }
        }
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
        $pathHash = sha1($targetPath);

        $form = new \Depage\Cms\Forms\Project\Upload("upload-to-lib-$pathHash", [
            'submitUrl' => DEPAGE_BASE . "project/{$this->project->name}/library/files/" . rawurlencode($path) . "/",
            'project' => $this->project,
            'targetPath' => $path,
            'class' => 'upload-to-lib',
        ]);
        $form->process();
        if ($form->validate()) {
            $values = $form->getValues();

            if (!empty($values['file']) && is_dir($targetPath)) {
                $_SESSION['dpLibraryUploadedFiles'] = [];
                foreach ($values['file'] as $file) {
                    // normalize extension to lowercase and escape filename
                    $filename = preg_replace_callback(
                        '/\.([^\.]*)$/',
                        function ($matches) {
                            return strtolower($matches[0]);
                        },
                        \Depage\Html\Html::getEscapedUrl($file['name'])
                    );
                    rename($file['tmp_name'], $targetPath . "/" . $filename);
                    $_SESSION['dpLibraryUploadedFiles'][] = $path . $filename;

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

                $form->clearSession(false);
                die();
            }
        }

        return $form;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
