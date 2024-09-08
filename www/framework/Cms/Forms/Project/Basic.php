<?php

namespace Depage\Cms\Forms\Project;

/**
 * brief Project
 * Class Project
 */
class Basic extends \Depage\HtmlForm\HtmlForm
{
    /**
     * @brief list of available project groups
     **/
    protected $groups = [];

    /**
     * @brief project object
     **/
    protected $project;

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $name, $params
     * @return void
     **/
    public function __construct($name, $params)
    {
        $this->project = $params['project'];
        $this->groups = [];

        foreach($params['projectGroups'] as $g) {
            $this->groups[$g->id] = $g->name;
        }

        $params['label'] = _("Save Project");

        parent::__construct($name, $params);

        $this->populate($this->project);
    }
    // }}}
    // {{{ isNew()
    /**
     * @brief isNew
     *
     * @param mixed
     * @return void
     **/
    public function isNew()
    {
        return $this->project->id == null;

    }
    // }}}
    // {{{ addChildElements()
    /**
     * @brief addChildElements
     *
     * @param mixed
     * @return void
     **/
    public function addChildElements()
    {
        $this->addText("fullname", [
            "label" => _("Display Name"),
            "required" => "true",
        ]);
        $this->addText("name", [
            "label" => _("Identifier"),
            "required" => "true",
            "validator" => "/[a-zA-Z0-9_]+/",
            "disabled" => $this->project->id !== null,
        ]);
        $this->addSingle("groupId", [
            "label" => _("Project Group"),
            "list" => $this->groups,
            "skin" => "select",
        ]);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
