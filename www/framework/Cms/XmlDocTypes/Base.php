<?php

namespace Depage\Cms\XmlDocTypes;

class Base extends \Depage\XmlDb\XmlDocTypes\Base
{
    // {{{ constructor
    public function __construct($xmldb, $document) {
        parent::__construct($xmldb, $document);

        $this->project = $this->xmldb->options['project'];
    }
    // }}}
    // {{{ onDocumentChange()
    /**
     * On Document Change
     *
     * @return bool
     */
    public function onDocumentChange()
    {
        $pdo = $this->project->getPdo();
        // @todo get template name from project settings
        $transformCache = new \Depage\Transformer\TransformCache($pdo, $this->project->name, "html-pre");

        $transformCache->clearFor($this->document->getDocId());

        return true;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
