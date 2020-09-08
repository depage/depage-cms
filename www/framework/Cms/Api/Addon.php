<?php
/**
 * @file    Task.php
 *
 * description
 *
 * copyright (c) 2020 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Api;

/**
 * @brief Task
 * Class Task
 */
class Addon extends \Depage\Cms\Ui\Base
{
    protected $autoEnforceAuth = false;
    protected $routeThroughIndex = true;

    // {{{ _init
    public function _init(array $importVariables = []) {
        parent::_init($importVariables);

        $this->projectName = $this->urlSubArgs[0];
        $this->addonName = $this->urlSubArgs[1];

        $this->project = \Depage\Cms\Project::loadByName($this->pdo, $this->xmldbCache, $this->projectName);

        if (!$this->project) {
            throw new \Depage\Cms\Exceptions\Project("not allowed");
        }
    }
    // }}}

    // {{{ index()
    /**
     * @brief upload
     *
     * @return object
     **/
    public function index()
    {
        $src = $this->project->getProjectPath() . "addons/{$this->addonName}/Api.php";
        $class = ucfirst($this->projectName) . "\\{$this->addonName}\\Api";

        if (file_exists($src)) {
            require_once($src);

            $handler = $class::_factoryAndInit($this->conf, [
                'projectName' => $this->projectName,
                'project' => $this->project,
            ]);
            $handler->urlSubArgs = $this->urlSubArgs;
            $handler->urlPath = $this->urlPath;

            return $handler->_run("/api/{$this->projectName}/addon/{$this->addonName}");
        }

        return $this->notfound();
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :

