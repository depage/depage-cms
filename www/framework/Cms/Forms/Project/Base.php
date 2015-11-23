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
        $this->baseUrl = "project/{$this->project->name}/";

        $groups = array();

        $params['cancelUrl'] = DEPAGE_BASE;
        $params['cancelLabel'] = _("Cancel");
        $params['jsAutosave'] = true;
        $params['dataAttr'] = array(
            "document" => "settings",
        );

        parent::__construct($name, $params);
    }
    // }}}
    // {{{ getTabs()
    /**
     * @brief getTabs
     *
     * @return array tabs
     **/
    public static function getTabs()
    {
        $tabs = array(
            "basic" => _("Project Settings"),
            "tags" => _("Tags"),
            "languages" => _("Languages"),
            "variables" => _("Variables"),
            "publish" => _("Publish"),
            "import" => _("Import"),
        );

        return $tabs;
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
        if ($this->project->id !== null) {
            $tabs = self::getTabs();
            $class = get_class($this);
            $class = strtolower(substr($class, strrpos($class, "\\") + 1));

            $html .= "<ul class=\"tabs\">";
            foreach ($tabs as $id => $title) {
                $className = "tab";

                if ($id == $class) {
                    $className .= " active";
                }

                $html .= "<li class=\"$className\"><a href=\"{$this->baseUrl}settings/$id/\">" . htmlentities($title) . "</a></li>";
            }
            $html .= "</ul>";
        }

        $html .= parent::__toString();

        return $html;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
