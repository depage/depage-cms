<?php

namespace Depage\Cms\Forms\Project;

/**
 * brief Publish Settings
 * Form for editing project publish settings
 */
class Publish extends Base
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
        $params['label'] = _("Save Publish Settings");
        $params['autocomplete'] = false;

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
            "placeholder" => _("Name of publishing target"),
            "dataPath" => "//proj:publishTarget[@db:id = '$nodeId']/@name",
            "required" => true,
            "class" => "node-name",
            "dataAttr" => [
                "nodeid" => $nodeId,
                "parentid" => $this->parentId,
            ],
        ]);
        $this->addText("output_folder-$nodeId", [
            "label" => _("Output folder"),
            "placeholder" => _("URL, where to publish project to"),
            "required" => true,
            "dataPath" => "//proj:publishTarget[@db:id = '$nodeId']/@output_folder",
        ]);
        $this->addUrl("baseurl-$nodeId", [
            "label" => _("Base Url"),
            "placeholder" => _("Base URL of publish target"),
            "dataPath" => "//proj:publishTarget[@db:id = '$nodeId']/@baseurl",
        ]);
        $this->addText("output_user-$nodeId", [
            "label" => _("Username"),
            "placeholder" => _("Username"),
            "autocomplete" => "new-password",
            "dataPath" => "//proj:publishTarget[@db:id = '$nodeId']/@output_user",
        ]);
        $this->addPassword("output_password-$nodeId", [
            "label" => _("Password"),
            "placeholder" => _("Password"),
            "autocomplete" => "new-password",
            "dataPath" => "//proj:publishTarget[@db:id = '$nodeId']/@output_pass",
        ]);
        $this->addSingle("template_set-$nodeId", [
            "label" => _("Template Set"),
            "list" => [
                "html" => "html",
            ],
            "skin" => "select",
            "dataPath" => "//proj:publishTarget[@db:id = '$nodeId']/@template_set",
        ]);
        $this->addBoolean("mod_rewrite-$nodeId", [
            "label" => _("Server supports mod rewrite"),
            "dataPath" => "//proj:publishTarget[@db:id = '$nodeId']/@mod_rewrite",
        ]);
        $this->addBoolean("search-index-$nodeId", [
            "label" => _("Index this target for search"),
            "dataPath" => "//proj:publishTarget[@db:id = '$nodeId']/@index",
        ]);
        /*
        $this->addBoolean("default-$nodeId", [
            "label" => _("Publish to this target as default"),
            "dataPath" => "//proj:publishTarget[@db:id = '$nodeId']/@default",
        ]);
         */
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

        return !empty($title) ? $title : _("Add new publishing target");
    }
    // }}}
    // {{{ onValidate()
    /**
     * @brief onValidate
     *
     * @param mixed
     * @return void
     **/
    public function onValidate()
    {
        // @todo validate output folders
        return true;

    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
