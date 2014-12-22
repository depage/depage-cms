<?php

namespace Depage\Cms\Forms;

/**
 * brief Project
 * Class Project
 */
class Project extends \Depage\Htmlform\Htmlform
{
    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $name, $params
     * @return void
     **/
    public function __construct($name, $params)
    {
        $groups = array();
        foreach($params['projectGroups'] as $g) {
            $groups[$g->id] = $g->name;
        }
        parent::__construct($name, $params);

        $this->addText("fullname", array(
            "label" => _("Display Name"),
            "required" => "true",
        ));
        $this->addText("name", array(
            "label" => _("Identifier"),
            "required" => "true",
            "validator" => "/[-a-zA-Z0-9_]+/"
        ));
        $this->addSingle("groupId", array(
            "label" => _("Project Group"),
            "list" => $groups,
            "skin" => "select",
        ));
    }
    // }}}
}

