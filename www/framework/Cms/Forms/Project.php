<?php

namespace Depage\Cms\Forms;

/**
 * brief Project
 * Class Project
 */
class Project extends \Depage\HtmlForm\HtmlForm
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
        $this->project = $params['project'];

        $params['label'] = _("Save Project");

        $params['cancelUrl'] = DEPAGE_BASE;
        $params['cancelLabel'] = _("Cancel");

        parent::__construct($name, $params);

        $this->addText("fullname", array(
            "label" => _("Display Name"),
            "required" => "true",
        ));
        $this->addText("name", array(
            "label" => _("Identifier"),
            "required" => "true",
            "validator" => "/[-a-zA-Z0-9_]+/",
            "disabled" => $this->project->id !== null,
        ));
        $this->addSingle("groupId", array(
            "label" => _("Project Group"),
            "list" => $groups,
            "skin" => "select",
        ));

        $this->populate($this->project);
    }
    // }}}
    // {{{ __toString()
    /**
     * @brief __toString
     *
     * @param mixed
     * @return void
     **/
    public function __toString()
    {
        $html = parent::__toString();

        if ($this->project->id !== null) {
            $html .= "<div class=\"bottom\"><a href=\"project/{$this->project->name}/import/\" class=\"button\">" . _("Import Project") . "</a></div>";
        }

        return $html;
    }
    // }}}
}

