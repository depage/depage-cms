<?php

namespace Depage\Cms\XmlDocTypes;

class Settings extends \Depage\XmlDb\XmlDocTypes\Base {
    use Traits\MultipleLanguages;

    // @todo clean cache after updating languages

    // {{{ testDocument
    public function testDocument($node) {
        return $this->testNodeLanguages($node);
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

        // @todo clear page cache
        error_log("settings onDocumentChange");

        return true;

    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */

