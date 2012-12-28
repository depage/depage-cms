<?php

namespace depage\cms\xmldoctypes;
    
class pages extends \depage\xmldb\xmldoctypes\base {
    // {{{ variables
    protected $validParents = array(
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
        'pg:separator' => array(
            'dpg:pages',
            'pg:page',
            'pg:folder',
        ),
    );
    // }}}
}  

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
