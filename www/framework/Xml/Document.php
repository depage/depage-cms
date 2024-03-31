<?php
/**
 * @file    document.php
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Xml;

/**
 * @brief DOMDocument
 *
 * Serializable subclass of DOMDocument with helper methods especially
 * for html-content, and for removing up unwanted tags from html.
 */
class Document extends \DOMDocument
{
    public $contentType = "text/xml";
    public $charset = "UTF-8";

    // {{{ constructor()
    /**
     * @brief   htmldom class constructor
     *
     * @param   $version (string)
     * @param   $encoding (string)
     *
     * @return  (Depage::HtmlForm::Abstracts::HtmlDom) htmlDOM
     **/
    public function __construct($version = null, $encoding = null) {
        if (is_null($version)) {
            $version = "1.0";
        }
        if (is_null($encoding)) {
            $encoding = "UTF-8";
        }
        parent::__construct($version, $encoding);
    }
    // }}}

    // {{{ getDocAndNode()
    /**
     * @brief   helper function to get node and owner-document by document or node
     *
     * @param   $docOrNode \DOMNode
     *
     * @return  array($doc, $node)
     **/
    public static function getDocAndNode(\DOMNode $docOrNode)
    {
        if ($docOrNode->nodeType == XML_DOCUMENT_NODE) {
            $doc = $docOrNode;
            $node = $doc->documentElement;
        } else {
            $doc = $docOrNode->ownerDocument;
            $node = $docOrNode;
        }

        return array($doc, $node);
    }
    // }}}
    // {{{ fromDomDocument()
    /**
     * @brief fromDomDocument
     *
     * @param mixed
     * @return void
     **/
    static public function fromDomDocument(\DOMDocument $dom)
    {
        $xml = new Document();
        $rootNode = $dom->documentElement;
        $rootNode = $xml->importNode($rootNode, true);
        $xml->appendChild($rootNode);

        return $xml;
    }
    // }}}
    // {{{ replaceAttributeNames()
    /**
     * @brief replaceAttributeNames
     *
     * @param mixed $node
     * @param mixed $search
     * @param mixed $replace
     * @return void
     **/
    public static function replaceAttributeNames($node, $search, $replace):void
    {
        $xpath = new \DOMXPath($node->ownerDocument);
        if (is_string($search)) {
            $attrName = $search;

            $query = ".//@$attrName";
        } else if (is_array($search)) {
            $attrNS = $search[0];
            $attrName = $search[1];

            $prefix = explode(":", $attrName)[0];

            $xpath->registerNamespace($prefix, $attrNS);

            $query = ".//@$attrName";
        }
        $nodes = $xpath->query($query, $node);

        for ($i = $nodes->length - 1; $i >= 0; $i--) {
            $n = $nodes->item($i);
            $parent = $n->parentNode;

            if (is_string($replace)) {
                $parent->setAttribute($replace, $n->nodeValue);
            } else if (is_array($replace)) {
                $parent->setAttributeNS($replace[0], $replace[1], $n->nodeValue);
            }
            $parent->removeAttributeNode($n);
        }
    }
    // }}}

    // {{{ __toString()
    /**
     * @brief   unserializes htmldom-objects
     *
     * @param   $serialized (string)
     *
     * @return  (void)
     **/
    public function __toString() {
        return $this->saveXML();
    }
    // }}}
    // {{{ __serialize()
    public function __serialize():array
    {
        return [
            'xml' => $this->saveXML(),
        ];
    }
    // }}}
    // {{{ __unserialize()
    public function __unserialize(array $data):void
    {
        $this->loadXML($data['xml']);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
