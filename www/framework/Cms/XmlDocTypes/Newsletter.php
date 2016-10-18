<?php

namespace Depage\Cms\XmlDocTypes;

class Newsletter extends Base
{
    use Traits\MultipleLanguages;

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

        return true;

    }
    // }}}

    // {{{ testDocument
    public function testDocument($node) {
        $changed = $this->testNodeLanguages($node);

        $this->addReleaseStatusAttributes($node->firstChild);

        return $changed;
    }
    // }}}
    // {{{ testDocumentForHistory
    public function testDocumentForHistory($xml) {
        parent::testDocumentForHistory($xml);

        $xml->firstChild->setAttributeNS("http://cms.depagecms.net/ns/database", "db:released", "true");
    }
    // }}}
    // {{{ addReleaseStatusAttributes()
    /**
     * @brief addReleaseStatusAttributes
     *
     * @param mixed $
     * @return void
     **/
    public function addReleaseStatusAttributes($node)
    {
        $info = $this->document->getDocInfo();
        $versions = array_values($this->document->getHistory()->getVersions(true, 1));

        // @todo fix not to get from timestamp when sha1 did no change
        if (count($versions) > 0 && $info->lastchange->getTimestamp() < $versions[0]->lastsaved->getTimestamp()) {
            $node->setAttributeNS("http://cms.depagecms.net/ns/database", "db:released", "true");
        } else {
            $node->setAttributeNS("http://cms.depagecms.net/ns/database", "db:released", "false");
        }
    }
    // }}}

}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
