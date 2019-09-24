<?php

namespace Depage\Cms\Forms;

/**
 * brief Language Settings
 * Form for editing project languages
 */
class XmlForm extends \Depage\HtmlForm\HtmlForm
{
    /**
     * @brief dataNode
     **/
    protected $dataNode = null;

    /**
     * @brief dataNodeXpath
     **/
    protected $dataNodeXpath = null;

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $name, $params
     * @return void
     **/
    public function __construct($name, $params)
    {
        if (isset($params['dataNode'])) {
            list($this->dataDocument, $this->dataNode) = \Depage\Xml\Document::getDocAndNode($params['dataNode']);

            $this->dataNodeXpath = new \DOMXPath($this->dataDocument);
            $this->dataNodeXpath->registerNamespace("proj", "http://cms.depagecms.net/ns/project");
            $this->dataNodeXpath->registerNamespace("db", "http://cms.depagecms.net/ns/database");
        }

        parent::__construct($name, $params);

        $this->setDefaultValuesXml();
    }
    // }}}
    // {{{ setDefaultValues()
    /**
     * @brief setDefaultValues
     *
     * @param mixed
     * @return void
     **/
    public function setDefaultValuesXml()
    {
        if (!isset($this->dataNode)) {
            return;
        }

        foreach ($this->getElements() as $element) {
            if (empty($element->dataInfo)) {
                continue;
            }
            $value = "";
            preg_match("/(.*?)(\/@([-_a-z0-9]+))?$/i", $element->dataInfo, $matches);
            list($xpath, $nodeXpath, $attrXpath, $attrName) = $matches;

            $nodes = $this->dataNodeXpath->query($xpath);

            if ($nodes->length == 0 && $attrName == "href") {
                // handle @href and @href_id attributes
                $nodes = $this->dataNodeXpath->query($xpath . "_id");
            }
            if ($nodes->length == 0 && !empty($attrName)) {
                $nodes = $this->dataNodeXpath->query($nodeXpath);

                if ($nodes->length == 0) continue;

                $parentNode = $nodes->item(0);
                $parentNode->setAttribute($attrName, "");

                $node = $parentNode->getAttributeNode($attrName);
            } else if ($nodes->length == 0) {
                // @todo throw warning if nodelist is empty?
                continue;
            } else {
                $node = $nodes->item(0);
            }

            if ($element instanceof \Depage\HtmlForm\Elements\Boolean) {
                $value = $node->value == "true" ? true : false;
            } else if ($element instanceof \Depage\HtmlForm\Elements\Date) {
                $value = str_replace("/", "-", $node->value);
            } else if ($element instanceof \Depage\HtmlForm\Elements\Richtext) {
                $value = "";

                // update links with href_id
                $links = $this->dataNodeXpath->query("a[@href_id]", $node);

                foreach ($links as $n) {
                    $url = "pageref://" . $n->getAttribute("href_id");
                    $parent->setAttribute("href", $url);
                    $parent->removeAttribute("href_id");
                }
                foreach ($node->childNodes as $n) {
                    \Depage\XmlDb\Document::removeNodeAttr($n, new \Depage\XmlDb\XmlNs('db', 'http://cms.depagecms.net/ns/database'), "id");

                    $value .= $node->ownerDocument->saveHTML($n) . "\n";
                }

                if ($node->nodeName == "edit:table") {
                    $value = "<table><tbody>$value</tbody></table>";
                }
            } else if ($node->nodeName == 'href_id' && $node->nodeType == \XML_ATTRIBUTE_NODE) {
                // @todo user url path instead of id?
                $value = "pageref://{$node->nodeValue}";
            } else {
                $value = $node->nodeValue;
            }

            $element->setDefaultValue($value);
        }
    }
    // }}}
    // {{{ getValuesXml()
    /**
     * @brief getValuesXml
     *
     * @return void
     **/
    public function getValuesXml()
    {
        if (!isset($this->dataNode)) {
            return;
        }
        foreach ($this->getElements() as $element) {
            if (empty($element->dataInfo)) {
                continue;
            }
            $nodes = $this->dataNodeXpath->query($element->dataInfo);

            if ($nodes->length == 0 && substr($element->dataInfo, -6) == "/@href") {
                // handle @href and @href_id attributes
                $nodes = $this->dataNodeXpath->query($element->dataInfo . "_id");
            }
            if ($nodes->length == 0) {
                // @todo throw warning if nodelist is empty?
                continue;
            }
            $node = $nodes->item(0);

            if ($element instanceof \Depage\HtmlForm\Elements\Boolean) {
                $node->nodeValue = $element->getValue() === true ? "true" : "false";
            } else if ($element instanceof \Depage\HtmlForm\Elements\Number) {
                $node->nodeValue = $element->getStringValue();
            } else if ($element instanceof \Depage\HtmlForm\Elements\Date) {
                $node->nodeValue = str_replace("-", "/", $element->getValue());
            } elseif ($element instanceof \Depage\HtmlForm\Elements\Richtext) {
                $root = $element->getValue()->documentElement;

                while ($node->lastChild != null) {
                    $node->removeChild($node->lastChild);
                }
                if ($node->nodeName == "edit:table") {
                    // @todo check if this is stable in all browsers
                    $root = $root->getElementsByTagName("tbody")->item(0);
                }
                foreach ($root->childNodes as $n) {
                    $copy = $this->dataDocument->importNode($n, true);
                    $node->appendChild($copy);
                }
            } else if (in_array($node->nodeName, ['href_id', 'href']) && $node->nodeType == \XML_ATTRIBUTE_NODE) {
                $parent = $node->parentNode;
                $href = $element->getValue();
                if (substr($href, 0, 10) == "pageref://") {
                    $parent->setAttribute("href_id", substr($href, 10));
                    $parent->removeAttribute("href");
                } else {
                    $parent->setAttribute("href", $href);
                    $parent->removeAttribute("href_id");
                }
            } else if ($node->nodeType == \XML_ATTRIBUTE_NODE) {
                $node->parentNode->setAttribute($node->nodeName, $element->getValue());
            } else if ($node->nodeType == \XML_ELEMENT_NODE) {
                $node->nodeValue = htmlspecialchars($element->getValue());
            } else {
                $node->nodeValue = $element->getValue();
            }
        }

        return $this->dataNode;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
