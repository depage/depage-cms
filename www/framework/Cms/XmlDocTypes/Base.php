<?php

namespace Depage\Cms\XmlDocTypes;

class Base extends \Depage\XmlDb\XmlDocTypes\Base
{
    /**
     * @brief project
     **/
    protected $project = null;

    // {{{ constructor
    public function __construct($xmldb, $document) {
        parent::__construct($xmldb, $document);

        $this->project = $this->xmldb->options['project'];
    }
    // }}}

    // {{{ onDocumentChange()
    /**
     * @brief onDocumentChange
     *
     * @param mixed
     * @return void
     **/
    public function onDocumentChange()
    {
        parent::onDocumentChange();

        // @todo clear transform cache
        $templates = ["html", "atom", "debug"];
        $previewTypes = ["pre"];

        foreach ($templates as $template) {
            foreach ($previewTypes as $type) {
                $transformCache = new \Depage\Transformer\TransformCache($this->xmldb->pdo, $this->project->name, "$template-$type");
                $transformCache->clearFor($this->document->getDocId());
            }
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
