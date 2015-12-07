<?php

namespace Depage\Cms\Forms\Project;

/**
 * brief Project
 * Class Project
 */
class Base extends \Depage\Cms\Forms\XmlForm
{
    /**
     * @brief baseUrl of current project settings
     **/
    protected $baseUrl = "";

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
        if (!empty($params['parentId'])) {
            $this->parentId = $params['parentId'];
        }
        $this->baseUrl = "project/{$this->project->name}/";

        $groups = array();

        $params['dataAttr'] = array(
            "document" => "settings",
        );

        parent::__construct($name, $params);

        $this->jsAutosave = !$this->isNew();
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
        return isset($this->dataNode) && empty($this->dataNode->getAttribute("name"));

    }
    // }}}
    // {{{ getFormTitle()
    /**
     * @brief getFormTitle
     *
     * @param mixed
     * @return void
     **/
    protected function getFormTitle()
    {
        $title = "";

        if (isset($this->dataNode)) {
            $title = $this->dataNode->getAttribute("name");
        }

        return $title;
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
        $html = "";

        if ($title = $this->getFormTitle()) {
            if (empty($title)) {
                $title = _("New Tag");
            }
            $class = "sortable";
            if ($this->isNew()) {
                $class .= " new";
            }

            $html .= "<div class=\"$class\">";
            $html .= "<h1>" . htmlentities($title) . "</h1>";
            $html .= parent::__toString();
            $html .= "</div>";
        } else {
            $html .= parent::__toString();
        }

        return $html;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
