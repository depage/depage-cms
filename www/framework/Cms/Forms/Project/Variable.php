<?php

namespace Depage\Cms\Forms\Project;

/**
 * brief Variables Settings
 * Form for editing project variables
 */
class Variable extends Base
{
    // {{{ variables
    protected $project;
    protected $parentId;
    // }}}

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $name, $params
     * @return void
     **/
    public function __construct($name, $params)
    {
        $params['label'] = _("Save Variables");

        parent::__construct($name, $params);
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
        $nodeId = $this->dataNode->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $this->addText("name-$nodeId", [
            "label" => _("Name"),
            "placeholder" => _("Variable name"),
            "dataPath" => "//proj:variable[@db:id = '$nodeId']/@name",
            "validator" => "/[-_a-zA-Z0-9]+/",
            "required" => true,
            "class" => "node-name",
            "dataAttr" => [
                "nodeid" => $nodeId,
                "parentid" => $this->parentId,
            ],
        ]);

        $this->addText("value-$nodeId", [
            "label" => _("Value"),
            "placeholder" => _("Variable value"),
            "dataPath" => "//proj:variable[@db:id = '$nodeId']/@value",
            "class" => "node-value",
            "dataAttr" => [
                "nodeid" => $nodeId,
            ],
        ]);

    }
    // }}}
    // {{{ getFormTitle()
    /**
     * @brief getFormTitle
     *
     * @return void
     **/
    protected function getFormTitle()
    {
        $title = parent::getFormTitle();

        return !empty($title) ? $title : _("Add new variable");
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
