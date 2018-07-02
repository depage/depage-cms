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
        }
    }
    // }}}

    // {{{ index()
    function index() {
        return $this->library();
    }
    // }}}
    // {{{ library()
    function library($path = "/") {
        // construct template
        $hLib = new Html("projectLibrary.tpl", [
            'projectName' => $this->project->name,
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
    // {{{ files()
    /**
     * @brief files
     *
     * @param mixed $path = "/"
     * @return void
     **/
    public function files($path = "/")
    {
        $libPath = $this->project->getProjectPath() . "lib/";

        return $libPath;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
