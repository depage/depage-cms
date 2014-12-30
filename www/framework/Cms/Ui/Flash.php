<?php
/**
 * @file    framework/CMS/UI/Flash.php
 *
 * depage cms ui module
 *
 *
 * copyright (c) 2002-2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Ui;

use \Depage\Html\Html;

class Flash extends Base {
    protected $autoEnforceAuth = true;

    // {{{ _init
    public function _init(array $importVariables = array()) {
        parent::_init($importVariables);

        $this->projectName = $this->urlSubArgs[0];

        // get cache instance
        $this->cache = \Depage\Cache\Cache::factory("xmldb", array(
            'host' => "localhost",
        ));
    }
    // }}}
    // {{{ index()
    function index() {
        return $this->flash();
    }
    // }}}
    // {{{ flash
    /**
     * function to show error messages
     *
     * @return  null
     */
    public function flash($standalone = "true", $page = "")
    {
        // logged in
        $h = new Html("flash.tpl", array(
            'project' => $this->projectName,
            'page' => $page,
            'standalone' => $standalone,
            'sid' => $_COOKIE[session_name()],
        ), $this->htmlOptions);

        return $h;
    }
    // }}}
    // {{{ rpc
    /**
     * function to show error messages
     *
     * @return  null
     */
    public function rpc()
    {
        $xmlInput = file_get_contents("php://input");
        //$this->log->log($xmlInput);

        return $this->handleRpc($xmlInput);
    }
    // }}}
    // {{{ test
    /**
     * function to show error messages
     *
     * @return  null
     */
    public function test($type = "start-project")
    {
        // {{{ get-config}
        if ($type == "get-config") {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><rpc:msg xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="get_config" /></rpc:msg>';
        // }}}
        // {{{ register-window
        } elseif ($type == "register-window") {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="register_window"><rpc:param name="sid">bf71afc29a363d08c8b9d75664670392</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="type">main</rpc:param></rpc:func></rpc:msg>';
        // }}}
        // {{{ start-project
        } elseif ($type == "start-project") {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="get_tree"><rpc:param name="sid">0d85e60b91e4ccf301d705f605523d78</rpc:param><rpc:param name="wid">0d85e60b91e4ccf301d705f605523d78</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="type">settings</rpc:param></rpc:func>,<rpc:func name="get_tree"><rpc:param name="sid">0d85e60b91e4ccf301d705f605523d78</rpc:param><rpc:param name="wid">0d85e60b91e4ccf301d705f605523d78</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="type">colors</rpc:param></rpc:func>,<rpc:func name="get_tree"><rpc:param name="sid">0d85e60b91e4ccf301d705f605523d78</rpc:param><rpc:param name="wid">0d85e60b91e4ccf301d705f605523d78</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="type">tpl_newnodes</rpc:param></rpc:func></rpc:msg>';
        // }}}
        // {{{ tree-pages
        } elseif ($type == "tree-pages") {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="get_tree"><rpc:param name="sid">bf71afc29a363d08c8b9d75664670392</rpc:param><rpc:param name="wid">bf71afc29a363d08c8b9d75664670392</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="type">pages</rpc:param></rpc:func></rpc:msg>';
        // }}}
        // {{{ tree-files
        } elseif ($type == "tree-files") {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="get_tree"><rpc:param name="sid">7b9a38377edb659e54ed354b902fe9f9</rpc:param><rpc:param name="wid">7b9a38377edb659e54ed354b902fe9f9</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="type">files</rpc:param></rpc:func></rpc:msg>';
        // }}}
        // {{{ tree-files-dir
        } elseif ($type == "tree-files-dir") {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="get_prop"><rpc:param name="sid">7b9a38377edb659e54ed354b902fe9f9</rpc:param><rpc:param name="wid">7b9a38377edb659e54ed354b902fe9f9</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">/projects/alonylightsonoff/</rpc:param><rpc:param name="file_type" /><rpc:param name="type">files</rpc:param></rpc:func></rpc:msg>';
        // }}}
        // {{{ tree-pagedata
        } elseif ($type == "tree-pagedata") {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="get_tree"><rpc:param name="sid">bf71afc29a363d08c8b9d75664670392</rpc:param><rpc:param name="wid">bf71afc29a363d08c8b9d75664670392</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="type">page_data</rpc:param><rpc:param name="id">10</rpc:param></rpc:func></rpc:msg>';
        // }}}
        // {{{ save-node
        } elseif ($type == "save-node") {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="save_node"><rpc:param name="sid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="wid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="data"><edit:text_headline lang="en" db:id="1950"><p>Design [dɪˈzaɪn]fg</p></edit:text_headline></rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func></rpc:msg>';
        // }}}
        // {{{ move-node-before
        } elseif ($type == "move-node-before") {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="move_node_before"><rpc:param name="sid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="wid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">1956</rpc:param><rpc:param name="target_id">1949</rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func>,<rpc:func name="keepAlive"><rpc:param name="sid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="wid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="project_name">depage</rpc:param></rpc:func></rpc:msg>';
        // }}}
        // {{{ delete-node
        } elseif ($type == "delete-node") {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="delete_node"><rpc:param name="sid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="wid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">6981</rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func>,<rpc:func name="keepAlive"><rpc:param name="sid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="wid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="project_name">depage</rpc:param></rpc:func></rpc:msg>';
        // }}}
        // {{{ duplicate-node
        } elseif ($type == "duplicate-node") {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="duplicate_node"><rpc:param name="sid">eb3a565e6006cbd4a8ed677b6dd26452</rpc:param><rpc:param name="wid">eb3a565e6006cbd4a8ed677b6dd26452</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">14235</rpc:param><rpc:param name="new_name">Textblock (copy)</rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func></rpc:msg>';
        // }}}
        // {{{ add-node
        } elseif ($type == "add-node") {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="keepAlive"><rpc:param name="sid">9adaf9361a3c631ae3ca9ccf27176bed</rpc:param><rpc:param name="wid">9adaf9361a3c631ae3ca9ccf27176bed</rpc:param><rpc:param name="project_name">depage</rpc:param></rpc:func>,<rpc:func name="add_node"><rpc:param name="sid">9adaf9361a3c631ae3ca9ccf27176bed</rpc:param><rpc:param name="wid">9adaf9361a3c631ae3ca9ccf27176bed</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="target_id">618</rpc:param><rpc:param name="type">page_data</rpc:param><rpc:param name="node_type">
<sec:text name="Headline">
    <edit:text_headline lang=""><p>Headline</p></edit:text_headline>
</sec:text>

</rpc:param><rpc:param name="new_name" /></rpc:func></rpc:msg>';
        // }}}
        // {{{ set-page-colorscheme
        } elseif ($type == "set-page-colorscheme") {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="set_page_colorscheme"><rpc:param name="sid">9adaf9361a3c631ae3ca9ccf27176bed</rpc:param><rpc:param name="wid">9adaf9361a3c631ae3ca9ccf27176bed</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">210</rpc:param><rpc:param name="colorscheme">cyan</rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func></rpc:msg>';
        // }}}
        // {{{ set-page-navigations
        } elseif ($type == "set-page-navigations") {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="set_page_navigations"><rpc:param name="sid">1ee10f1808e8465248ea3653c7feda07</rpc:param><rpc:param name="wid">1ee10f1808e8465248ea3653c7feda07</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">66</rpc:param><rpc:param name="navigations"><pg_navigation nav_featured="true" nav_tag_templates="false" nav_tag_cms="false" nav_tag_media="false" nav_tag_development="false" nav_tag_design="false" nav_tag_concept="false" nav_blog="false" nav_home="false" nav_hidden="false" nav_layout_include="false" nav_atom="false" nav_shortnews="false" /></rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func></rpc:msg>';
        // }}}
        // {{{ set-page-fileoptions
        } elseif ($type == "set-page-fileoptions") {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="set_page_file_options"><rpc:param name="sid">1ee10f1808e8465248ea3653c7feda07</rpc:param><rpc:param name="wid">1ee10f1808e8465248ea3653c7feda07</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">66</rpc:param><rpc:param name="multilang">true</rpc:param><rpc:param name="file_name" /><rpc:param name="file_type">php</rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func></rpc:msg>';
        // }}}
        // {{{ rename-node
        } elseif ($type == "rename-node") {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="rename_node"><rpc:param name="sid">83b6cbfc1937d90652bbae89cdc1ac40</rpc:param><rpc:param name="wid">83b6cbfc1937d90652bbae89cdc1ac40</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">560</rpc:param><rpc:param name="new_name">Intro 1</rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func></rpc:msg>';
        // }}}
        // {{{ get-imageprop
        } elseif ($type == "get-imageprop") {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="get_imageProp"><rpc:param name="sid">48d95d889856b61c5e4aa8cd4aa1b28d</rpc:param><rpc:param name="wid">48d95d889856b61c5e4aa8cd4aa1b28d</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="filepath">/projects/depageforms/</rpc:param><rpc:param name="filename">icon-help-depageforms.png</rpc:param></rpc:func></rpc:msg>';
        // }}}
        // {{{ get-videoprop
        } elseif ($type == "get-videoprop") {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="get_imageProp"><rpc:param name="sid">48d95d889856b61c5e4aa8cd4aa1b28d</rpc:param><rpc:param name="wid">48d95d889856b61c5e4aa8cd4aa1b28d</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="filepath">/projects/alonylightsonoff/</rpc:param><rpc:param name="filename">lights_on_off.wmv</rpc:param></rpc:func></rpc:msg>';
        }
        // }}}

        return $this->handleRpc($xmlInput);
    }
    // }}}
    // {{{ handleRpc
    /**
     * function to show error messages
     *
     * @return  null
     */
    protected function handleRpc($xmlInput)
    {
        if (!preg_match("/keepAlive/", $xmlInput)) {
            $this->log->log($xmlInput);
        }

        $this->prefix = $this->pdo->prefix . "_proj_" . $this->projectName;

        $this->xsltPath = "projects/" . $this->projectName . "/xslt/";
        $this->xmlPath = "projects/" . $this->projectName . "/xml/";

        $xmldb = new \Depage\XmlDb\XmlDb ($this->prefix, $this->pdo, $this->cache, array(
            'pathXMLtemplate' => $this->xmlPath,
            'userId' => $this->authUser->id,
        ));

        $funcHandler = new \Depage\Cms\Rpc\CmsFuncs($this->projectName, $this->pdo, $xmldb, $this->authUser);
        $msgHandler = new \Depage\Cms\Rpc\Message($funcHandler);

        //call
        $funcs = $msgHandler->parse($xmlInput);

        $results = array();
        foreach ($funcs as $func) {
            $func->add_args(array('ip' => $_SERVER['REMOTE_ADDR']));
            $tempval = $func->call();
            if (is_a($tempval, 'Depage\\Cms\\Rpc\\Func')) {
                $results[] = $tempval;
            }
        }
        /*
        if (count($pocket_updates) > 0) {
            send_updates();
        }
         */
        //$results = array_merge($results, $project->user->get_updates($project->user->sid));
        $results = array_merge($results, $funcHandler->getCallbacks());

        if (count($results) == 0) {
            $results[] = new \Depage\Cms\Rpc\Func('nothing', array('error' => 0));
        }

        return \depage\Cms\Rpc\Message::create($results);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
