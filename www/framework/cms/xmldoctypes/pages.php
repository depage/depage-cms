<?php

namespace depage\cms\xmldoctypes;

// TODO configure
define('XML_TEMPLATE_DIR', __DIR__ . '/xml_templates/');
    
class pages extends \depage\xmldb\xmldoctypes\base {

    // {{{ constructor
    public function __construct($xmldb, $docId) {
        parent::__construct($xmldb, $docId);

        // list of elements that may created by a user
        $this->availableNodes = array(
            'pg:page' => (object) array(
                'name' => _("Page"),
                'new' => _("Untitled Page"),
                'icon' => "",
                'attributes' => array(),
                'doc_type' => 'depage\cms\xmldoctypes\page',
                'xml_template' => 'page.xml'
            ),
            'pg:folder' => (object) array(
                'name' => _("Folder"),
                'new' => _("Untitled Folder"),
                'icon' => "",
                'attributes' => array(),
                'doc_type' => 'depage\cms\xmldoctypes\folder',
                'xml_template' => 'folder.xml',
            ),
            'pg:redirect' => (object) array(
                'name' => _("Redirect"),
                'new' => _("Redirect"),
                'icon' => "",
                'attributes' => array(),
                'doc_type' => 'depage\cms\xmldoctypes\redirect',
                'xml_template' => 'redirect.xml',
            ),
            'pg:separator' => (object) array(
                'name' => _("Separator"),
                'new' => "",
                'icon' => "",
                'attributes' => array(),
            ),
        );
        
        // list of valid parents given by nodename
        $this->validParents = array(
            'pg:page' => array(
                'dpg:pages',
                'pg:page',
                'pg:folder',
            ),
            'pg:folder' => array(
                'dpg:pages',
                'pg:page',
                'pg:folder',
            ),
            'pg:redirect' => array(
                'dpg:pages',
                'pg:page',
                'pg:folder',
            ),
            'pg:separator' => array(
                '*',
            ),
        );
    }
    // }}}

    // {{{ onAddNode()
    /**
     * On Add Node
     *
     * Creates a new xmldb document node with a uniquely generated name: _{$type}_hash.
     *
     * @param string $type
     * @return \depage\xmldb\document
     */
    public function onAddNode(\DomElement $node, $target_id, $target_pos) {

        if (isset($this->availableNodes[$node->nodeName])) {

            $properties = $this->availableNodes[$node->nodeName];

            if (isset($properties->doc_type) && isset($properties->xml_template)) {

                $doc_name = '_' . strtolower($properties->name) . '_' . sha1(uniqid(dechex(mt_rand(256, 4095))));

                $document = $this->xmldb->createDoc($doc_name, $properties->doc_type);

                $node->setAttribute('db:ref', $document->getDocId());

                $xml = $this->loadXmlTemplate($properties->xml_template);

                return $document->save($xml);
            }
        }

        return false;
    }
    // }}}
    // {{{ onDeleteNode()
    /**
     * On Delete Node
     *
     * Deletes an xmldb document by the given id.
     *
     * @param $doc_id
     * @return boolean
     */
    public function onDeleteNode($doc_id) {
        $this->xmldb->removeDoc($doc_id);
        return true;
    }
    // }}}
    // {{{ testDocument
    public function testDocument($node) {
        $xmlnav = new \depage\cms\xmlnav();

        $xmlnav->addUrlAttributes($node);
    }
    // }}}

    // {{{ loadXmlTemplate()
    /**
     * Load XML Template
     *
     * @param $template
     * @return \DOMDocument
     */
    private function loadXmlTemplate($template) {
        $doc = new \DOMDocument();
        $doc->load(XML_TEMPLATE_DIR . $template);
        return $doc;
    }
    // }}}
}  

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
