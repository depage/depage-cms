<?php

namespace depage\cms\xmldoctypes;
    
class pages extends \depage\xmldb\xmldoctypes\base {
    // {{{ constructor
    function __construct($xmldb, $docId) {
        parent::__construct($xmldb, $docId);

        // list of elements that may created by a user
        $this->availableNodes = array(
            'pg:page' => (object) array(
                'name' => _("Page"),
                'new' => _("Untitled Page"),
                'icon' => "",
                'attributes' => array(),
            ),
            'pg:folder' => (object) array(
                'name' => _("Folder"),
                'new' => _("Untitled Folder"),
                'icon' => "",
                'attributes' => array(),
            ),
            'pg:redirect' => (object) array(
                'name' => _("Redirect"),
                'new' => _("Redirect"),
                'icon' => "",
                'attributes' => array(),
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
}  

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
