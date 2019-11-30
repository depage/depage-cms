<?php

namespace Depage\Cms\Forms\Project;

/**
 * brief Tag Settings
 * Form for editing project tags
 */
class Tag extends Base
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
        $params['label'] = _("Save Tag");

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
            "placeholder" => _("Tag name"),
            "dataPath" => "//proj:tag[@db:id = '$nodeId']/@name",
            "validator" => "/[-_a-zA-Z0-9]+/",
            "required" => true,
            "class" => "node-name",
            "dataAttr" => [
                "nodeid" => $nodeId,
                "parentid" => $this->parentId,
            ],
        ]);

        $fs = $this->addFieldset("localized", [
            'label' => _("Localized labels"),
        ]);

        foreach ($this->dataNode->childNodes as $localized) {
            $lang = $localized->getAttribute("lang");

            $fs->addText("localized-$nodeId-$lang", [
                "label" => $lang,
                "placeholder" => _("Localized name") . " ($lang)",
                "dataPath" => "//proj:tag[@db:id = '$nodeId']/localized[@lang = '$lang']",
            ]);
        }
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

        return !empty($title) ? $title : _("Add new tag");
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
