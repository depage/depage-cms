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

        //$params['jsAutosave'] = !$this->isNew();
        $params['dataAttr'] = array(
            "document" => "settings",
        );

        parent::__construct($name, $params);
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
        return false;

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

        if (isset($this->dataNode) && !empty($this->parentId)) {
            $html .= "<div class=\"sortable\">";
            $html .= "<h1>" . htmlentities($this->dataNode->getAttribute("name")) . "</h1>";
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
