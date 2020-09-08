<?php
/**
 * @file    Addon.php
 *
 * description
 *
 * copyright (c) 2020 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Ui;

/**
 * @brief Addon
 * Class Addon
 */
class Addon extends Base
{
    protected $autoEnforceAuth = true;
    protected $routeThroughIndex = true;

    // {{{ _init
    public function _init(array $importVariables = []) {
        parent::_init($importVariables);

        $this->projectName = $this->urlSubArgs[0];
        $this->addonName = $this->urlSubArgs[1];

        $this->project = $this->getProject($this->projectName);

        if (!$this->project) {
            throw new \Depage\Cms\Exceptions\Project("not allowed");
        }
    }
    // }}}

    // {{{ index()
    /**
     * @brief index
     *
     * @param mixed
     * @return void
     **/
    public function index()
    {

        $src = $this->project->getProjectPath() . "addons/{$this->addonName}/Ui.php";
        $class = ucfirst($this->projectName) . "\\{$this->addonName}\\Ui";

        if (file_exists($src)) {
            require_once($src);

            $handler = $class::_factoryAndInit($this->conf, [
                'projectName' => $this->projectName,
                'project' => $this->project,
            ]);
            $handler->urlSubArgs = $this->urlSubArgs;
            $handler->urlPath = $this->urlPath;

            return $handler->_run("/project/{$this->projectName}/addon/{$this->addonName}");
        }

        return $this->notfound();
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
