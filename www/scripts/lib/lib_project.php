<?php
/**
 * @file    lib_project.php
 *
 * Project Library
 *
 * This file defines the classes for handling a project. The 
 * Project can be accessed and saved by different interfaces.
 * Until now there is only an interface on top of mysql.
 * there will hopefully come more.
 *
 *
 * copyright (c) 2002-2004 Frank Hellenkamp [jonas.info@gmx.net]
 *
 * @author    Frank Hellenkamp [jonas.info@gmx.net]
 *
 * $Id: lib_project.php,v 1.15 2004/11/12 19:45:31 jonas Exp $
 */

if (!function_exists('die_error')) require_once('lib_global.php');

/**
 * project class which defines procedures to handle project data
 * it has to be created with an interface. until now, there is 
 * only an mysql interface.
 */
class project {
    // {{{ static callable functions
    // {{{ factory()
    /**
     * provides an interface for generating project:: objects
     * with different access types.
     *
     * @public
     *
     * @param    $driver (string) name of interface to create
     *            now only 'mysql' is available
     * @param    $param (array) array of parameters, which are passed
     *            to new created object     
     */
     function &factory($driver, $param = array()) {
        $driver = strtolower($driver);
        $class = "project_acss_{$driver}";
        require_once("lib_project_acss_{$driver}.php");

        return new $class($param);
    }
    // }}}
    // {{{ domxml_new_doc()
    /**
     * creates a new domxml document with the global entities defined
     *
     * @public
     *
     * @return    $doc (domxmlobject) 
     */
    function domxml_new_doc() {
        global $conf;
        
        $docdef = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $docdef .= "<!DOCTYPE ttdoc [";
        foreach ($conf->global_entities as $entity) {
            $docdef .= "<!ENTITY {$entity} \"&amp;{$entity};\" >";
        }
        $docdef .= "]>";
        $docdef .= "<temp_root/>";
        $doc = domxml_open_mem($docdef);
        $root_node = $doc->document_element();
        $root_node->unlink_node();
        
        return $doc;
    }
    // }}}
    // {{{ domxml_open_mem()
    /**
     * creates a domxml document from a string and defines global
     * entities and global namespaces in it.
     * 
     * @public
     *
     * @param    $newdoc (string) document declaration string
     *
     * @return    $doc (domxmlobject)
     */
    function domxml_open_mem($newdoc) {
        global $conf;
        
        $docdef = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $docdef .= "<!DOCTYPE ttdoc [";
        foreach ($conf->global_entities as $entity) {
            $docdef .= "<!ENTITY {$entity} \"&amp;{$entity};\" >";
        }
        $docdef .= "]>";
        if (preg_match("/<((?:\w*:)?\w+)(.*?)>/s", $newdoc, $first_tag)) {
            $docdef .= "<{$first_tag[1]}";
            foreach($conf->ns as $ns_key => $ns) {
                if (!preg_match("/xmlns:{$ns['ns']}=/", $first_tag[2])) {
                    $docdef .= " xmlns:{$ns['ns']}=\"{$ns['uri']}\" ";
                }
            }
            $docdef .= "{$first_tag[2]}>";
            $docdef .= substr($newdoc, strpos($newdoc, $first_tag[0]) + strlen($first_tag[0]));
        } else {
            $docdef .= "<error />";
        }

        $doc = domxml_open_mem($docdef);

        return $doc;
    }
    // }}}
    // {{{ xpath_new_context()
    /**
     * creates a new xpath object with all global namespaces
     * registered.
     *
     * @public
     *
     * @param    $xmldoc (domxmlobject) domxml object to query with xpath
     *
     * @return    $context (xpathcontext)
     */
    function xpath_new_context(&$node) {
        global $conf;
        
        if (@$node->node_type() == XML_ELEMENT_NODE) {
            $xml_doc = $node->owner_document();
        } else {
            $xml_doc = $node;
        }
        $context = xpath_new_context($xml_doc);
        foreach($conf->ns as $ns_key => $ns) {
            xpath_register_ns($context, $ns['ns'], $ns['uri']);
        }
        
        return $context;
    }
    // }}}
    // {{{ search_for_id
    /**
     * searches document for given db:id attribute
     *
     * @public
     *
     * @param    $node (domxmlnode) node to search in
     * @param    $id (int) db-id to search for
     *
     * @return    $node (domxmlnode) node-object if found, null otherwise
     */
    function search_for_id($node, $id) {
        global $conf;

        $actual_id = $this->get_node_id($node);
        if ($actual_id == $id) {
            return $node;
        } else {
            $xpath_node = project::xpath_new_context($node);
            $xfetch = xpath_eval($xpath_node, "//*[@{$conf->ns['database']['ns']}:id = $id]", $node);
            if (count($xfetch->nodeset) == 1) {
                return $xfetch->nodeset[0];
            } else {
                return null;
            }
        }
    }
    // }}}
    // {{{ get_node_id()
    /**
     * gets node db-id from db-id attribute
     *
     * @public
     *
     * @param    $node (domxmlnode) node to get id from
     *
     * @return    $db_id (int)
     */
    function get_node_id(&$node) {
        return $this->xmldb->get_node_id($node);
    }        
    // }}}
    // {{{ get_project_path()
    /**
     * gets project path by project name
     *
     * @public
     *
     * @param    $projectName (string)
     *
     * @return    $path (string)
     */
    function get_project_path($project_name) {
        global $conf;

        return $conf->path_server_root . $conf->path_projects . '/' . str_replace(' ', '_', strtolower($project_name));
    }
    // }}}
    // }}}

    // {{{ functions
    // {{{ _test_pageObj_languages
    /**
     * checks page object, if all language are available and adds
     * or deletes languages, if nessecary
     *
     * @private
     *
     * @param    $xml_def (xmlobject) page object
     * @param    $multilang (bool) true if page is multilang page, else false
     * @param    $languages (array) array of available languages
     *
     * @return    $changed (bool) true if page has changed, false otherwise
     */
    function _test_pageObj_languages(&$xml_def, $multilang, $languages) {
        global $log;

        $changed = false;
        $actual_languages = array();
        $temp_nodes = array();
        $languages = array_keys($languages);

        $xpath_xml_def = project::xpath_new_context($xml_def);
        $xfetch = xpath_eval($xpath_xml_def, "//*[@lang]");
        if ($multilang == 'true' && count($xfetch->nodeset) > 0) {
            for ($i = 0; $i < count($xfetch->nodeset); $i++) {
                $lang_attr = $xfetch->nodeset[$i]->get_attribute('lang');
                if (!in_array($lang_attr, $actual_languages)) {
                    $actual_languages[] = $lang_attr;
                }
            }
            $langdiff = array_merge(array_diff($languages, $actual_languages), array_diff($actual_languages, $languages));
            if (count($langdiff) > 0) {
                $first_lang = $xfetch->nodeset[0]->get_attribute('lang');
                foreach ($xfetch->nodeset as $val) {
                    $parent_node = $val->parent_node();
                    if ($val->get_attribute('lang') == $first_lang) {
                        $temp_node = $xml_def->create_element('temp_node');
                        $parent_node->insert_before($temp_node, $val);
                        $temp_nodes[] = $temp_node;
                    }
                }
                
                for ($i = 0; $i < count($temp_nodes); $i++) {
                    $lang_nodes = array();
                    $temp_node = $temp_nodes[$i];
                    for ($j = 0; $j < count($actual_languages); $j++) {
                        $temp_node = $temp_node->next_sibling();
                        $lang_nodes[$temp_node->get_attribute('lang')] = $temp_node;
                        $lang_nodes[$j] = $temp_node;
                    }
                    
                    $parent_node = $temp_nodes[$i]->parent_node();
                    for ($j = 0; $j < count($languages); $j++) {
                        if ($lang_nodes[$languages[$j]] !== null) {
                            $temp_node = $lang_nodes[$languages[$j]]->clone_node(true);
                            $parent_node->insert_before($temp_node, $temp_nodes[$i]);
                        } else {
                            $temp_node = $lang_nodes[0]->clone_node(true);
                            $parent_node->insert_before($temp_node, $temp_nodes[$i]);
                            $this->xmldb->remove_id_attributes($temp_node);
                        }
                        $temp_node->set_attribute('lang', $languages[$j]);
                    }    
                    $temp_nodes[$i]->unlink_node();
                    foreach ($lang_nodes as $temp_node) {
                        $temp_node->unlink_node();
                    }
                }
                $changed = true;
            }
        } else if (count($xfetch->nodeset) > 0) {
            $first_lang = $xfetch->nodeset[0]->get_attribute('lang');
            if ($first_lang != '') {
                foreach ($xfetch->nodeset as $temp_node) {
                    if ($temp_node->get_attribute('lang') == $first_lang) {
                        $temp_node->set_attribute('lang', '');
                    } else {
                        $temp_node->unlink_node();
                    }
                }
                $changed = true;
            }
        }
            
        if ($changed) {
            return false;
        } else {
            return true;
        }
    }
    // }}}
    // }}}
}

// {{{ class node_types
/**
 * defines basic XML node types, to check nodes for
 *
 * @todo    change not to test real XML nodes but only the nodeNames or
 *            perhabs throw the whole class and use regular expressions instead
 *
 * @todo    delete after refactoring lib_project, because not needed anymore
 */
class nodeType {
    /**
     * check if node is page or folder node
     *
     * @param    $node (xml_node) node to test
     *
     * @return    $isPageNode (bool) true, if it is page node, false otherwise
     */
    function isPageNode(&$node) {
        global $conf;
        
        return (
            $node != null 
            && $node->prefix() == $conf->ns['page']['ns'] 
            && ($node->node_name() == 'page' || $node->node_name() == 'folder')
        );
    }

    /**
     * check if node is not page or folder node
     *
     * @param    $node (xml_node) node to test
     *
     * @return    $isNotPageNode (bool) true, if it is not page node, false otherwise
     */
    function isNotPageNode(&$node) {
        return !nodeType::isPageNode(&$node);
    }

    /**
     * check if node is folder node
     * 
     * @param    $node (xml_node) node to test
     *
     * @return    $isFolderNode (bool) true, if it is folder node, false otherwise
     */
    function isFolderNode(&$node) {
        global $conf;
        
        return (
            $node != null 
            && $node->prefix() == $conf->ns['page']['ns'] 
            && $node->node_name() == 'folder'
        );
    }

    /**
     * check if node is not folder node
     *
     * @param    $node (xml_node) node to test
     *
     * @return    $isNotFolderNode (bool) true, if it is not folder node, false otherwise
     */
    function isNotFolderNode(&$node) {
        return !nodeType::isFolderNode(&$node);
    }

    /**
     * check if node is tree node (page or section or project)
     *
     * @param    $node (xml_node) node to test
     *
     * @return    $isTreeNode (bool) true, if it is tree node, false otherwise
     */
    function isTreeNode(&$node) {
        global $conf;
        
        return (
            $node != null 
            && (
                $node->prefix() == $conf->ns['page']['ns'] 
                || $node->prefix() == $conf->ns['section']['ns'] 
                || $node->prefix() == $conf->ns['project']['ns']
            )
        );
    }

    /**
     * check if node is not tree node (page or section or project)
     *
     * @param    $node (xml_node) node to test
     *
     * @return    $isNotTreeNode (bool) true, if it is not tree node, false otherwise
     */
    function isNotTreeNode(&$node) {
        return !nodeType::isTreeNode(&$node);
    }

    /**
     * check if node is a template node 
     *
     * @param    $node (xml_node) node to test
     *
     * @return    $isTemplateNode (bool) true, if it is template node, false otherwise
     */
    function isTemplateNode($node) {
        global $conf;
        
        return (
            $node != null 
            && (
                ($node->prefix() == $conf->ns['page']['ns'] && ($node->node_name() == 'template' || $node->node_name() == 'folder'))
                || ($node->prefix() == $conf->ns['project']['ns'] && $node->node_name() == 'templates_publish')
            )
        );
    }

    /**
     * check if node is not a template node
     *
     * @param    $node (xml_node) node to test
     *
     * @return    $isNotTemplateNode (bool) true, if it is not a template node, false otherwise
     */
    function isNotTemplateNode(&$node) {
        return !nodeType::isTemplateNode(&$node);
    }
}
// }}}

/**
 * Main
 */

//init project object
$project = project::factory($conf->project_interface);

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
