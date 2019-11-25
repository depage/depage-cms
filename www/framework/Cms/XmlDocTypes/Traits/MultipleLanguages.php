<?php

namespace Depage\Cms\XmlDocTypes\Traits;

trait MultipleLanguages
{
    // {{{ testNodeLanguages
    protected function testNodeLanguages($node) {
        $languages = [];

        // get languages from settings
        $settings = $this->xmlDb->getDoc("settings");
        $nodes = $settings->getNodeIdsByXpath("//proj:language");
        foreach ($nodes as $nodeId) {
            $attr = $settings->getAttributes($nodeId);
            $languages[] = $attr['shortname'];
        }

        return self::updateLangNodes($node, $languages);
    }
    // }}}
    // {{{ updateLangNodes()
    /**
     * @brief updateLangNodes
     *
     * @param mixed $
     * @return void
     *
     * @todo check this code for bugs while reordering languages
     **/
    public static function updateLangNodes($node, $languages)
    {
        list($xml, $node) = \Depage\Xml\Document::getDocAndNode($node);

        if (count($languages) == 0) {
            return false;
        }

        $changed = false;
        $actual_languages = [];
        $temp_nodes = [];

        $xpath = new \DOMXPath($xml);
        $nodelist = $xpath->query("./descendant-or-self::node()[@lang]", $node);

        if ($nodelist->length > 0) {
            // search for languages used in document
            for ($i = 0; $i < $nodelist->length; $i++) {
                $lang_attr = $nodelist->item($i)->getAttribute('lang');
                if ($lang_attr == "") {
                    $lang_attr = "_new_language";
                    $nodelist->item($i)->setAttribute('lang', $lang_attr);
                }
                if (!in_array($lang_attr, $actual_languages)) {
                    $actual_languages[] = $lang_attr;
                }
            }

            if (implode(",", $languages) != implode(",", $actual_languages)) {
                $first_lang = $nodelist->item(0)->getAttribute('lang');

                // add temporary nodes as markers to insert new nodes
                foreach ($nodelist as $node) {
                    $parent_node = $node->parentNode;
                    if ($node->getAttribute('lang') == $first_lang || $node->getAttribute('lang') == "_new_language") {
                        $temp_node = $xml->createElement('temp_lang_node');
                        $parent_node->insertBefore($temp_node, $node);
                        $temp_nodes[] = $temp_node;
                    }
                }
                for ($i = 0; $i < count($temp_nodes); $i++) {
                    $lang_nodes = [];
                    $temp_node = $temp_nodes[$i];
                    $sibl_node = $temp_node->nextSibling;
                    $parent_node = $temp_node->parentNode;

                    // search for siblings with lang-nodes
                    while ($sibl_node && $sibl_node->nodeType == \XML_ELEMENT_NODE && $sibl_node->hasAttribute("lang")) {
                        $lang = $sibl_node->getAttribute('lang');
                        if ($lang != "_new_language") {
                            $lang_nodes[$lang] = $sibl_node;
                        } else {
                            $lang_nodes[] = $sibl_node;
                        }
                        $sibl_node = $sibl_node->nextSibling;
                    }
                    foreach ($languages as $key => $lang) {
                        if (isset($lang_nodes[$lang])) {
                            // move lang-node before temporary node, so we have the same order
                            // the language settings
                            $temp_node = $lang_nodes[$lang]->cloneNode(true);
                            $parent_node->insertBefore($temp_node, $temp_nodes[$i]);
                            $temp_node->setAttribute('lang', $lang);
                        } else {
                            // add new languages by copying existing lang-node
                            if (count($lang_nodes) > 0) {
                                $lang_node = reset($lang_nodes);

                                $temp_node = $lang_node->cloneNode(true);
                                $parent_node->insertBefore($temp_node, $temp_nodes[$i]);
                                \Depage\XmlDb\Document::removeNodeAttr($temp_node, new \Depage\XmlDb\XmlNs('db', 'http://cms.depagecms.net/ns/database'), "id");
                                $temp_node->setAttribute('lang', $lang);
                            }
                        }
                    }
                    // remove temporary and unused nodes
                    $parent_node->removeChild($temp_nodes[$i]);
                    foreach ($lang_nodes as $lang_node) {
                        $lang_node->parentNode->removeChild($lang_node);
                    }
                }
                $changed = true;
            }
        }

        return $changed;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
