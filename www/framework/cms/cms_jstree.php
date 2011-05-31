<?php
/**
 * @file    framework/cms/cms_jstree.php
 *
 * depage cms jstree module
 *
 *
 * copyright (c) 2011 Lion Vollnhals [lion.vollnhals@googlemail.com]
 *
 * @author    Lion Vollnhals [lion.vollnhals@googlemail.com]
 */

class cms_jstree extends depage_ui {
    protected $html_options = array();
    protected $basetitle = "";
    protected $defaults = array(
        "db" => null,
        "auth" => null,
        "env" => "development",
    );

    // {{{ constructor
    public function __construct($options = NULL) {
        parent::__construct($options);

        // get database instance
        $this->pdo = new db_pdo (
            $this->options->db->dsn, // dsn
            $this->options->db->user, // user
            $this->options->db->password, // password
            array(
                'prefix' => $this->options->db->prefix, // database prefix
            )
        );

        // TODO init correctly
        $prefix = "dp_proj_{$this->pdo->prefix}";
        $this->xmldb = new \depage\xmldb\xmldb ($prefix, $this->pdo, \depage\cache\cache::factory($prefix));

        // get auth object
        $this->auth = auth::factory(
            $this->pdo, // db_pdo 
            $this->options->auth->realm, // auth realm
            DEPAGE_BASE, // domain
            $this->options->auth->method // method
        );

        // set html-options
        $this->html_options = array(
            'template_path' => __DIR__ . "/tpl/",
            'clean' => "space",
            'env' => $this->options->env,
        );

//        $this->delta_updates = new delta_updates ("dp_proj_{$this->pdo->prefix}", $this->pdo);
    }
    // }}}
    
    // {{{ create_node
    /**
     * @param $doc_id document id
     * @param $parent parent
     * @param $child child
     * @param $pos position for new child in parent
     */
    public function create_node() {
        $this->auth->enforce();

        $node = $this->node_from_request();
        $this->xmldb->add_node($_REQUEST["doc_id"], $node, null, $_REQUEST["position"]);   
    }
    // }}}

    // {{{ rename_node
    public function rename_node() {
        $this->auth->enforce();

        $this->xmldb->set_attribute($_REQUEST["doc_id"], $_REQUEST["id"], "name", $_REQUEST["name"]);
    }
    // }}}

    // {{{ move_node
    public function move_node() {
        $this->auth->enforce();

        $this->xmldb->move_node($_REQUEST["doc_id"], $_REQUEST["id"], $_REQUEST["target_id"], $_REQUEST["position"]);
    }
    // }}}

    // {{{ remove_node
    public function remove_node() {
        $this->auth->enforce();

        $this->xmldb->unlink_node($_REQUEST["doc_id"], $_REQUEST["id"]);
    }
    // }}}

    // {{{ get_children
    public function get_children() {
        $this->auth->enforce();

        $xsl = new DOMDocument();
        $xsl->load(__DIR__ . "/tpl/nodes_to_html.xsl", LIBXML_NOCDATA);

        $xslt = new XSLTProcessor();
        $xslt->importStylesheet($xsl);

        $subdoc = $this->xmldb->get_subdoc_by_elementId($_REQUEST["doc_id"], $_REQUEST["id"]);
        $html = \depage\cms\jstree_xml_to_html::toHTML(array($_REQUEST["id"] => $subdoc));

        return current($html);
    }
    // }}}

    // {{{ node_from_request
    private function node_from_request() {
        $parent = new DOMElement($_REQUEST["parent"]["type"]);
        foreach ($_REQUEST["parent"] as $attr => $value) {
            $parent->setAttribute($attr, $value);
        }

        $child = new DOMElement($_REQUEST["child"]["type"]);
        foreach ($_REQUEST["parent"] as $attr => $value) {
            $parent->setAttribute($attr, $value);
        }

        $parent->appendChild($child);
        return $parent;
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
