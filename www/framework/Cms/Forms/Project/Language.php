<?php

namespace Depage\Cms\Forms\Project;

/**
 * brief Language Settings
 * Form for editing project languages
 */
class Language extends Base
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
        $params['label'] = _("Save Languages");

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
            "placeholder" => _("Language name"),
            "dataPath" => "//proj:language[@db:id = '$nodeId']/@name",
            //"validator" => "/[-_a-zA-Z0-9]+/",
            "required" => true,
            "class" => "node-name",
            "dataAttr" => [
                "nodeid" => $nodeId,
                "parentid" => $this->parentId,
            ],
        ]);

        $this->addText("shortname-$nodeId", [
            "label" => _("Language code"),
            "placeholder" => _("Short name"),
            "dataPath" => "//proj:language[@db:id = '$nodeId']/@shortname",
            "validator" => "/[-_a-zA-Z0-9]{2}/",
            "required" => true,
            "class" => "node-value",
            "helpMessageHtml" => sprintf(_("Language code in <a href=\"%s\" target=\"_blank\">ISO 639-1</a>"), "https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes"),
            "dataAttr" => [
                "nodeid" => $nodeId,
                "parentid" => $this->parentId,
            ],
        ]);

        $fs = $this->addFieldset("metadata-$nodeId", [
            "label" => _("Global metadata"),
        ]);

        $fs->addText("title-$nodeId", [
            "label" => _("Title"),
        ]);
        $fs->addText("keyword-$nodeId", [
            "label" => _("Keywords"),
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

        return !empty($title) ? $title : _("Add new language");
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
