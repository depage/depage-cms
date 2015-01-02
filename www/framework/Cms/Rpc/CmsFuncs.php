<?php
/**
 * @file    framework/cms/ui_base.php
 *
 * base class for cms-ui modules
 *
 *
 * copyright (c) 2011-2012 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Rpc;

class CmsFuncs {
    protected $projectName;
    protected $callbacks = array();

    // {{{ __construct
    function __construct($project, $pdo, $xmldb, $user) {
        $this->projectName = $project;
        $this->pdo = $pdo;
        $this->xmldb = $xmldb;
        $this->user = $user;

        $this->log = new \Depage\Log\Log();
    }
    // }}}

    // {{{ keepAlive()
    function keepAlive($args) {
        // @todo implement
    }
    // }}}
    // {{{ register_window()
    function register_window($args) {
        return new Func('registered_window', array('wid' => $args['sid'], 'user_level' => $this->user->level, 'error' => false));
    }
    // }}}
    // {{{ get_config()
    /**
     * gets global configuration data and interface texts from db
     *
     * @public
     *
     * @return     $set_config (xmlfuncobj) configuration
     */
    function get_config() {
        $conf_array = array();

        $conf_array['app_name'] = \Depage\Depage\Runner::getName();
        $conf_array['app_version'] = \Depage\Depage\Runner::getVersion();

        $conf_array['thumb_width'] = 85;
        $conf_array['thumb_height'] = 72;
        $conf_array['thumb_load_num'] = 100;

        $conf_array['interface_lib'] = "framework/Cms/lib/lib_interface.swf";

        $conf_array['interface_text'] = "";
        $lang = $this->getTexts();
        foreach ($lang as $key => $val) {
            $conf_array['interface_text'] .= "<text name=\"$key\" value=\"" . htmlspecialchars($val) . "\" />";
        }

        $conf_array['interface_scheme'] = '';
        $colors = $this->getScheme(__DIR__ . "/../interface.ini");
        foreach ($colors as $key => $val) {
            $conf_array['interface_scheme'] .= "<color name=\"$key\" value=\"" . htmlspecialchars($val) . "\" />";
        }

        $conf_array['projects'] = "<project name=\"$this->projectName\" preview=\"true\" />";

        $conf_array['namespaces'] = "";
        $namespaces = $this->getGlobalNamespaces();
        foreach($namespaces as $ns_key => $ns) {
            $conf_array['namespaces'] .= "<namespace name=\"$ns_key\" prefix=\"{$ns['ns']}\" uri=\"{$ns['uri']}\"/>";
        }

        $conf_array['url_page_scheme_intern'] = "pageref";
        $conf_array['url_lib_scheme_intern'] = "libref";

        $conf_array['global_entities'] = '';
        $globalEntities = array_keys($this->getGlobalEntities());
        foreach ($globalEntities as $val) {
            // @todo check entities for right format
            $conf_array['global_entities'] .= "<entity name=\"$val\"/>";
        }

        $conf_array['output_file_types'] = '';
        $fileTypes = $this->getGlobalFiletypes();
        foreach ($fileTypes as $key => $val) {
            $conf_array['output_file_types'] .= "<output_file_type name=\"$key\" extension=\"" . $val["extension"] . "\"/>";
        }

        $conf_array['output_encodings'] = '';
        $outputEncodings = $this->getGlobalOutputEncodings();
        foreach ($outputEncodings as $val) {
            $conf_array['output_encodings'] .= "<output_encoding name=\"$val\" />";
        }

        $conf_array['output_methods'] = '';
        $outputMethods = $this->getGlobalOutputMethods();
        foreach ($outputMethods as $val) {
            $conf_array['output_methods'] .= "<output_method name=\"$val\" />";
        }

        $conf_array['users'] = $this->getUserList();

        return new Func('set_config', $conf_array);
    }
    // }}}
    // {{{ get_project()
    function get_project($args) {
        $data = array();

        if ($xml = $this->xmldb->getDocXml("settings")) {
            $data['name'] = $this->projectName;
            $data['settings'] = $xml->saveXML($xml->documentElement);
            $data['users'] = $this->getUserList();
        } else {
            $data['error'] = true;
        }

        return new Func('set_project_data', $data);
    }
    // }}}
    // {{{ get_tree()
    function get_tree($args) {
        $callbackFunc = "update_tree_{$args['type']}";

        $data = array();
        $project_name = $this->projectName;

        if ($args['type'] == 'settings') {
            $data['data'] = $this->getTreeSettings();
        } elseif ($args['type'] == 'colors') {
            $data['data'] = $this->getTreeColors();
        } elseif ($args['type'] == 'tpl_newnodes') {
            $data['data'] = $this->getTreeTplNewnodes();
        } elseif ($args['type'] == 'pages') {
            $data['data'] = $this->getTreePages();
        } elseif ($args['type'] == 'page_data') {
            $data['data'] = $this->getTreePagedata($args['id']);
        } elseif ($args['type'] == 'files') {
            $data['data'] = $this->getTreeFiles();
        }

        if (!$data['data']) {
            $data['error'] = true;
        }

        return new Func($callbackFunc, $data);
    }
    // }}}
    // {{{ get_imageProp()
    function get_imageProp($args) {
        $info = array();
        $data = array();
        $filename = "projects/{$this->projectName}/lib{$args['filepath']}{$args['filename']}";

        $mediainfo = new \Depage\Media\MediaInfo(array(
            'cache' => \Depage\Cache\Cache::factory("mediainfo"),
        ));
        $info = $mediainfo->getInfo($filename);

        if ($info['exists']) {
            $sizeFormatter = new \Depage\Formatters\FileSize();
            $dateFormatter = new \Depage\Formatters\DateNatural();

            $info['name'] = $args['filename'];
            $info['path'] = $args['filepath'];
            $info['filesize'] = $sizeFormatter->format($info['filesize']);
            $info['date'] = $dateFormatter->format($info['date'], true);

            $this->log->log($info);
        }
        foreach ($info as $key => $value) {
            if (is_bool($value)) {
                $info[$key] = $info[$key] ? "true" : "false";
            }
        }

        return new Func('set_imageProp', $info);
    }
    // }}}
    // {{{ get_prop()
    function get_prop($args) {
        $data = array();
        $callbackFunc = "update_prop_{$args['type']}";

        if ($args['type'] == 'files' && !empty($args['id'])) {
            $data['data'] = $this->getFilesForPath($args['id']);
        }

        return new Func($callbackFunc, $data);
    }
    // }}}
    // {{{ save_node()
    function save_node($args) {
        $node = $args['data'][0];
        $nodeId = $node->getAttributeNS("http://cms.depagecms.net/ns/database", "id");
        $changedIds = array();

        $xmldoc = $this->xmldb->getDocByNodeId($nodeId);
        if ($xmldoc) {
            $changedIds[] = $xmldoc->saveNode($node);
        }
        $changedIds[] = $nodeId;

        $this->addCallback($args['type'], $changedIds);
    }
    // }}}
    // {{{ add_node()
    function add_node($args) {
        $targetId = $args['target_id'];
        $newNodes = $args['node_type'];
        $newName = $args['new_name'];
        $changedIds = array();

        $xmldoc = $this->xmldb->getDocByNodeId($targetId);
        if ($xmldoc) {
            // {{{ tree-type specific actions
            if ($args['type'] == "pages") {
                // create node for page-types
                $newNodes = array();
                $tempdoc = new \DOMDocument();
                $tempdoc->loadXML('<?xml version="1.0" encoding="UTF-8" ?><pg:' . $newNodes . ' xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:db="http://cms.depagecms.net/ns/database" multilang="true" file_type="html" />');
                $newNodes[] = $tempdoc->documentElement;
            } else if ($args['type'] == "colors") {
                // init newNodes for colors
                $newNodes = array();
                $tempdoc = new \DOMDocument();
                $tempdoc->loadXML('<?xml version="1.0" encoding="UTF-8" ?><proj:colorscheme xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" name="' . htmlentities($newName) . '" />');
                $newNodes[] = $tempdoc->documentElement;

                // adjust target id -> colorscheme are always added to root
                $xmldoc = $this->xmldb->getDoc("colors");
                $targetId = $xmldoc->getDocInfo()->rootid;
            }
            // }}}
            foreach($newNodes as $i => $node) {
                $savedId = $xmldoc->addNode($node, $targetId);
                if (!empty($newName)) {
                    //$xmldoc->setAttribute($savedId, "name", $newName);
                }

                // {{{ tree-type specific actions
                if ($args['type'] == "pages") {
                    // add document data to page data document
                    $dbRef = (int) $node->getAttribute("db:docref");
                    if (isset($args["xmldata"][$i]) && $dbRef > 0) {
                        $pagedataDoc = $this->xmldb->getDoc($dbRef);
                        $rootId = $pagedataDoc->getDocInfo()->rootid;

                        $pagedataDoc->addNode($args["xmldata"][$i], $rootId);
                    }
                }
                // }}}
                $changedIds[] = $savedId;
            }
        }
        $changedIds[] = $targetId;

        $this->addCallback($args['type'], $changedIds, $changedIds[0]);
    }
    // }}}
    // {{{ rename_node()
    function rename_node($args) {
        $nodeId = $args['id'];
        $newName = $args['new_name'];
        $changedIds = array();

        $xmldoc = $this->xmldb->getDocByNodeId($nodeId);
        if ($xmldoc) {
            $xmldoc->setAttribute($nodeId, "name", $newName);
        }
        $changedIds[] = $nodeId;

        $this->addCallback($args['type'], $changedIds);
    }
    // }}}
    // {{{ move_node_in()
    function move_node_in($args) {
        $nodeId = $args['id'];
        $targetId = $args['target_id'];

        $xmldoc = $this->xmldb->getDocByNodeId($nodeId);
        if ($xmldoc) {
            $xmldoc->moveNodeIn($nodeId, $targetId);
        }

        $this->addCallback($args['type'], array($nodeId, $targetId));
    }
    // }}}
    // {{{ move_node_before()
    function move_node_before($args) {
        $nodeId = $args['id'];
        $targetId = $args['target_id'];

        $xmldoc = $this->xmldb->getDocByNodeId($nodeId);
        if ($xmldoc) {
            $xmldoc->moveNodeBefore($nodeId, $targetId);
        }

        $this->addCallback($args['type'], array($nodeId, $targetId));
    }
    // }}}
    // {{{ move_node_after()
    function move_node_after($args) {
        $nodeId = $args['id'];
        $targetId = $args['target_id'];

        $xmldoc = $this->xmldb->getDocByNodeId($nodeId);
        if ($xmldoc) {
            $xmldoc->moveNodeAfter($nodeId, $targetId);
        }

        $this->addCallback($args['type'], array($nodeId, $targetId));
    }
    // }}}
    // {{{ copy_node_in()
    function copy_node_in($args) {
        $nodeId = $args['id'];
        $targetId = $args['target_id'];

        $xmldoc = $this->xmldb->getDocByNodeId($nodeId);
        if ($xmldoc) {
            $xmldoc->copyNodeIn($nodeId, $targetId);
        }

        $this->addCallback($args['type'], array($nodeId, $targetId));
    }
    // }}}
    // {{{ copy_node_before()
    function copy_node_before($args) {
        $nodeId = $args['id'];
        $targetId = $args['target_id'];

        $xmldoc = $this->xmldb->getDocByNodeId($nodeId);
        if ($xmldoc) {
            $xmldoc->copyNodeBefore($nodeId, $targetId);
        }

        $this->getCallback($args['type'], array($nodeId, $targetId));
    }
    // }}}
    // {{{ copy_node_after()
    function copy_node_after($args) {
        $nodeId = $args['id'];
        $targetId = $args['target_id'];

        $xmldoc = $this->xmldb->getDocByNodeId($nodeId);
        if ($xmldoc) {
            $xmldoc->copyNodeAfter($nodeId, $targetId);
        }

        $this->addCallback($args['type'], array($nodeId, $targetId));
    }
    // }}}
    // {{{ duplicate_node()
    function duplicate_node($args) {
        $nodeId = $args['id'];
        $newName = $args['new_name'];
        $changedIds = array();

        $xmldoc = $this->xmldb->getDocByNodeId($nodeId);
        if ($xmldoc) {
            $savedId = $xmldoc->duplicateNode($nodeId, $args['type'] == "page_data" ||  $args['type'] == "colors");
            if (!empty($newName)) {
                $xmldoc->setAttribute($savedId, "name", $newName);
            }
            $changedIds[] = $savedId;
        }
        $changedIds[] = $nodeId;

        $this->addCallback($args['type'], $changedIds, $changedIds[0]);
    }
    // }}}
    // {{{ delete_node()
    function delete_node($args) {
        $nodeId = $args['id'];

        $xmldoc = $this->xmldb->getDocByNodeId($nodeId);
        if ($xmldoc) {
            $parentId = $xmldoc->unlinkNode($nodeId);
        }

        $this->addCallback($args['type'], array($nodeId, $parentId));
    }
    // }}}
    // {{{ set_page_colorscheme()
    function set_page_colorscheme($args) {
        $nodeId = $args['id'];
        $colorscheme = $args['colorscheme'];

        $xmldoc = $this->xmldb->getDocByNodeId($nodeId);
        if ($xmldoc) {
            $xmldoc->setAttribute($nodeId, "colorscheme", $colorscheme);
        }

        $this->addCallback($args['type'], array($nodeId));
    }
    // }}}
    // {{{ set_page_navigations()
    function set_page_navigations($args) {
        $nodeId = $args['id'];
        $navigationNode = $args['navigations'][0];

        $xmldoc = $this->xmldb->getDocByNodeId($nodeId);
        if ($xmldoc) {
            foreach($navigationNode->attributes as $attr) {
                $xmldoc->setAttribute($nodeId, $attr->name, $attr->value);
            }
        }

        $this->addCallback($args['type'], array($nodeId));
        $this->addCallback('pages', array($nodeId));

        return new Func('preview_update', array('error' => 0));
    }
    // }}}
    // {{{ set_page_file_options()
    function set_page_file_options($args) {
        $nodeId = $args['id'];
        $multilang = $args['multilang'];
        $filetype = $args['file_type'];

        $xmldoc = $this->xmldb->getDocByNodeId($nodeId);
        if ($xmldoc) {
            $xmldoc->setAttribute($nodeId, "multilang", $multilang);
            $xmldoc->setAttribute($nodeId, "file_type", $filetype);
        }

        $this->addCallback($args['type'], array($nodeId));
        $this->addCallback('pages', array($nodeId));

        return new Func('preview_update', array('error' => 0));
    }
    // }}}

    // {{{ getTexts()
    protected function getTexts() {
        return array(
            'all_comment' => _("comment"),
            'auth_no_right' => _("Sorry, you don't have the authentification to change \"%name%\"."),
            'auth_not_allowed' => _("You are not allowed to do this!"),
            'auth_not_loggedin' => _("You are not logged in."),
            'auth_wrong_credentials' => _("Incorrect Username or Password!"),
            'button_link_target_blank' => _("new window"),
            'button_link_target_self' => _("existing window"),
            'buttontext_tree_releasetemp' => _("release XSLT"),
            'buttontext_tree_upload' => _("upload..."),
            'buttontip_filelist_detail' => _("show details"),
            'buttontip_filelist_thumbnail' => _("show thumbnails"),
            'buttontip_format_bold' => _("bold"),
            'buttontip_format_italic' => _("italic"),
            'buttontip_format_link' => _("link"),
            'buttontip_format_small' => _("small"),
            'buttontip_link_target' => _("linktarget"),
            'buttontip_table_row_add' => _("add row"),
            'buttontip_table_row_del' => _("delete row"),
            'buttontip_tree_delete' => _("delete"),
            'buttontip_tree_duplicate' => _("duplicate"),
            'buttontip_tree_new' => _("add"),
            'buttontip_tree_newfolder' => _("new folder"),
            'buttontip_tree_releasetemp' => _("release templates to be available for all user"),
            'buttontip_tree_upload' => _("upload new file"),
            'changed_by' => _("by"),
            'date_day_0' => _("Sun"),
            'date_day_1' => _("Mon"),
            'date_day_2' => _("Tue"),
            'date_day_3' => _("Wed"),
            'date_day_4' => _("Thu"),
            'date_day_5' => _("Fri"),
            'date_day_6' => _("Sat"),
            'date_format' => _("%D%, %d% %MM% %y% at %h%:%m%:%s%"),
            'date_format_short' => _("%M%/%d%/%y%"),
            'date_month_1' => _("Jan"),
            'date_month_10' => _("Oct"),
            'date_month_11' => _("Nov"),
            'date_month_12' => _("Dec"),
            'date_month_2' => _("Feb"),
            'date_month_3' => _("Mar"),
            'date_month_4' => _("Apr"),
            'date_month_5' => _("May"),
            'date_month_6' => _("Jun"),
            'date_month_7' => _("Jul"),
            'date_month_8' => _("Aug"),
            'date_month_9' => _("Sep"),
            'date_time' => _("h"),
            'date_time_at' => _("at"),
            'date_time_format_short' => _("%h%:%m%"),
            'error' => _("Error"),
            'error_ftp' => _("Filetransfer Error:<br>"),
            'error_ftp_connect' => _("Could not connect to:"),
            'error_ftp_login' => _("Could not login as:"),
            'error_ftp_write' => _("Could not write to:"),
            'error_invalid_page_id' => _("Not a valid page-id."),
            'error_node_deleted' => _("Node has been deleted by another user."),
            'error_parsexml-10' => _("An end-tag was encountered without a matching start-tag."),
            'error_parsexml-2' => _("A CDATA section is not properly terminated."),
            'error_parsexml-3' => _("The XML declaration is not properly terminated."),
            'error_parsexml-4' => _("The DOCTYPE declaration is not properly terminated."),
            'error_parsexml-5' => _("A comment is not properly terminated."),
            'error_parsexml-6' => _("An XML element is malformed."),
            'error_parsexml-7' => _("The Flashplayer is out of memory."),
            'error_parsexml-8' => _("An attribute value is not properly terminated."),
            'error_parsexml-9' => _("A start-tag is not matched with an end-tag."),
            'error_prop_xslt_template' => _("An error occured while parsing the template:"),
            'filetip_filedate' => _("last changed: "),
            'filetip_filesize' => _("size: "),
            'filetip_imagesize' => _("dimensions: "),
            'inhtml_backup_name_complete' => _("Complete Backup from"),
            'inhtml_backup_not_restored' => _("Backup not restored"),
            'inhtml_backup_not_saved' => _("Backup not saved"),
            'inhtml_backup_restored' => _("Backup restored"),
            'inhtml_backup_restored_info' => _("The project '%project%' has been restored from '%file%'."),
            'inhtml_backup_saved' => _("Backup saved"),
            'inhtml_backup_saved_info' => _("The project '%project%' has been saved in '%file%'"),
            'inhtml_cancel' => _("Cancel"),
            'inhtml_connection_closed' => _("connection to the server has closed unexpectedly."),
            'inhtml_connection_closed_title' => _("connection lost"),
            'inhtml_dialog_upload_button' => _("upload..."),
            'inhtml_dialog_upload_text' => _("Please choose the files, you want to upload to the file-library to '%path%'. You can upload %maxsize% max.<br/><br/><b>Attention: Existing file will be overwritten without confimation!</b>"),
            'inhtml_dialog_upload_title' => _("%app_name% upload"),
            'inhtml_dialog_upload_uploaded' => _("The file(s) have been uploaded."),
            'inhtml_extra_functions' => _("Additional actions"),
            'inhtml_last_publishing' => _("Last publishing"),
            'inhtml_lastchanged_pages' => _("Recently changed pages"),
            'inhtml_logout_headline' => _("Bye bye!"),
            'inhtml_logout_relogin' => _("You can relogin <a href=\".\">here</a>."),
            'inhtml_logout_text' => _("Thank you for using %app_name%."),
            'inhtml_main_title' => _("%app_name% %app_version%"),
            'inhtml_needed_flash' => _("You need the Macromedia Flash Player%minversion%, to use %app_name%."),
            'inhtml_no_import' => _("For this project there is no import-routine defined."),
            'inhtml_noscript' => _("You need to activate Javascript, to use %app_name%."),
            'inhtml_preview_error' => _("Error in transformation"),
            'inhtml_project_add' => _("Add new project"),
            'inhtml_project_name_short' => _("Name"),
            'inhtml_project_name_long' => _("name of project"),
            'inhtml_project_add_submit' => _("Add"),
            'inhtml_project_add_wrong' => _("The name of the project may only contain letters and numbers, no spaces or special characers allowed:<br> Please choose another name."),
            'inhtml_project_add_exists' => _("This project exists already:<br> Please choose another name."),
            'inhtml_projects_backup_restore' => _("restore project from backup"),
            'inhtml_projects_backup_save' => _("backup project"),
            'inhtml_projects_edit' => _("edit"),
            'inhtml_projects_preview' => _("preview"),
            'inhtml_projects_projects' => _("Projects"),
            'inhtml_projects_publish' => _("publish"),
            'inhtml_require_javascript' => _("You have to activate javascript to use %app_name%."),
            'inhtml_require_title' => _("requirements"),
            'inhtml_status_title' => _("%app_name% %app_version% // status"),
            'inhtml_toolbar_edit' => _("edit page"),
            'inhtml_toolbar_help' => _("help"),
            'inhtml_toolbar_home' => _("home"),
            'inhtml_toolbar_logout' => _("logout"),
            'inhtml_toolbar_reload' => _("reload"),
            'inhtml_user_administer' => _("manage users"),
            'msg_choose_file' => _("Please, choose a file"),
            'msg_choose_file_filter_height' => _("Height: "),
            'msg_choose_file_filter_type' => _("Type: "),
            'msg_choose_file_filter_width' => _("Width: "),
            'msg_choose_file_link' => _("Please, choose a file to link to:"),
            'msg_choose_img' => _("Please, choose an image:"),
            'msg_choose_page' => _("Please, choose a page to link to:"),
            'msg_delete_from_tree' => _("Do you want to delete \"%name%\"?"),
            'name_tree_project_settings' => _("[project settings]"),
            'output_type_none' => _("none"),
            'prop_name_description' => _("description"),
            'prop_name_edit_a' => _("link"),
            'prop_name_edit_audio' => _("audio"),
            'prop_name_edit_colorscheme_none' => _("[none]"),
            'prop_name_edit_date' => _("date"),
            'prop_name_edit_flash' => _("flash"),
            'prop_name_edit_icon_default' => _("[auto]"),
            'prop_name_edit_img' => _("image"),
            'prop_name_edit_img_caption' => _("image caption"),
            'prop_name_edit_img_copyright' => _("copyright"),
            'prop_name_edit_img_thumb' => _("thumb"),
            'prop_name_edit_img_zoom' => _("zoom"),
            'prop_name_edit_plain_source' => _("source code"),
            'prop_name_edit_table' => _("table"),
            'prop_name_edit_text_formatted' => _("text"),
            'prop_name_edit_list_formatted' => _("list"),
            'prop_name_edit_text_headline' => _("headline"),
            'prop_name_edit_time' => _("time"),
            'prop_name_edit_type' => _("type"),
            'prop_name_edit_video' => _("video"),
            'prop_name_page_colorscheme' => _("colorscheme"),
            'prop_name_page_date' => _("last change"),
            'prop_name_page_desc' => _("description"),
            'prop_name_page_file' => _("page type"),
            'prop_name_page_icon' => _("icon"),
            'prop_name_page_linkdesc' => _("linkinfo"),
            'prop_name_page_navigation' => _("navigation"),
            'prop_name_page_title' => _("title"),
            'prop_name_pg_template' => _("type"),
            'prop_name_proj_bak_backup_auto' => _("backup"),
            'prop_name_proj_bak_backup_man' => _("manually"),
            'prop_name_proj_bak_restore_data' => _("database"),
            'prop_name_proj_bak_restore_lib' => _("file-library"),
            'prop_name_proj_colorscheme' => _("colors"),
            'prop_name_proj_global_file_path' => _("file path"),
            'prop_name_proj_global_file_xsl_template' => _("XSL Template"),
            'prop_name_proj_language' => _("short name"),
            'prop_name_proj_navigation' => _("short name"),
            'prop_name_proj_publish' => _("publish"),
            'prop_name_proj_template_set_encoding' => _("encoding"),
            'prop_name_proj_template_set_method' => _("output method"),
            'prop_name_proj_variable' => _("variable"),
            'prop_name_title' => _("title"),
            'prop_name_xslt_newnode' => _("template for new elements"),
            'prop_name_xslt_template' => _("xsl-template"),
            'prop_name_xslt_valid_parent' => _("valid parents"),
            'prop_page_file_file_name_auto' => _("automatic"),
            'prop_page_file_multilang' => _("multiple languages"),
            'prop_proj_filelist_hidefiles' => _("Hide files with wrong filetype or wrong image size."),
            'prop_proj_filelist_showfiles' => _("Show files with wrong filetype or wrong image size."),
            'prop_tt_a_name' => _("name"),
            'prop_tt_audio_filepath' => _("path to audio-file"),
            'prop_tt_bak_automatic' => _("automatic Backup"),
            'prop_tt_bak_backup_button_start' => _("backup now"),
            'prop_tt_bak_backup_progress' => _("%description%<br>%percent% done<br>remaining: %remaining%"),
            'prop_tt_bak_backup_type_all' => _("full backup"),
            'prop_tt_bak_backup_type_data' => _("data only"),
            'prop_tt_bak_backup_type_lib' => _("file-library only"),
            'prop_tt_bak_date_every_day' => _("every day"),
            'prop_tt_bak_date_every_month' => _("every month"),
            'prop_tt_bak_date_every_week' => _("every week"),
            'prop_tt_bak_restore_button_start' => _("restore"),
            'prop_tt_bak_restore_content' => _("documents"),
            'prop_tt_bak_restore_db_colorschemes' => _("colorschemes"),
            'prop_tt_bak_restore_db_content' => _("pages"),
            'prop_tt_bak_restore_db_settings' => _("settings"),
            'prop_tt_bak_restore_db_templates' => _("templates"),
            'prop_tt_bak_restore_overwrite' => _("clean up library before restoring"),
            'prop_tt_bak_restore_type_clear' => _("clear file-library first"),
            'prop_tt_bak_restore_type_replace' => _("replace existing files"),
            'prop_tt_colorscheme_newcolor' => _("_new_color"),
            'prop_tt_flash_filepath' => _("path to flash-file"),
            'prop_tt_img_alt' => _("description"),
            'prop_tt_img_altdesc' => _("alt"),
            'prop_tt_img_choose' => _("..."),
            'prop_tt_img_filepath' => _("path to image"),
            'prop_tt_img_href' => _("link"),
            'prop_tt_publish_folder_baseurl' => _("base-url"),
            'prop_tt_publish_folder_button_start' => _("publish now"),
            'prop_tt_publish_folder_pass' => _("password"),
            'prop_tt_publish_folder_progress' => _("%description%<br>%percent% done<br>remaining: %remaining%"),
            'prop_tt_publish_folder_targetpath' => _("target path"),
            'prop_tt_publish_folder_user' => _("user"),
            'prop_tt_template_set_indent' => _("indent source"),
            'prop_tt_text_formatted_loading' => _("please wait ... initializing text-styles"),
            'prop_tt_video_filepath' => _("path to video-file"),
            'prop_tt_xslt_active' => _("active"),
            'register_name_colors' => _("colors"),
            'register_name_edit_pages' => _("edit pages"),
            'register_name_files' => _("files"),
            'register_name_login' => _("login"),
            'register_name_settings' => _("settings"),
            'register_name_templates' => _("templates"),
            'register_preview_menu_auto_choose' => _("after choosing"),
            'register_preview_menu_auto_choose_save' => _("after choosing/changing"),
            'register_preview_menu_feedback' => _("enable feedback"),
            'register_preview_menu_headline' => _("preview"),
            'register_preview_menu_man' => _("manually"),
            'register_preview_menu_preview' => _("preview"),
            'register_preview_menu_type' => _("template set"),
            'register_tip_colors' => _("edit colors and colorschemes..."),
            'register_tip_edit_pages' => _("add, edit and delete pages..."),
            'register_tip_files' => _("file-library"),
            'register_tip_preview' => _("preview"),
            'register_tip_settings' => _("edit settings..."),
            'register_tip_templates' => _("edit templates..."),
            'start_config_loaded' => _("configuration loaded."),
            'start_loaded' => _("interface loaded."),
            'start_loaded_project' => _("project loaded."),
            'start_loading_project' => _("loading project..."),
            'start_loading_version' => _("<b>%app_name%</b><br>[%app_version%]<br><br>preloading...<br>%loading%"),
            'start_login_button' => _("login"),
            'start_login_explain_pass' => _("password"),
            'start_login_explain_user' => _("user"),
            'start_login_wrong_login' => _("Login failed. Please try again."),
            'start_pocket_connected' => _("connection established."),
            'start_pocket_reconnect' => _("connecting to server..."),
            'start_preload' => _("loading interface..."),
            'start_projectdata' => _("project data"),
            'task_backup_colorschemes' => _("backup colorschemes"),
            'task_backup_content' => _("backup content"),
            'task_backup_lib' => _("backup library"),
            'task_backup_newnodes' => _("backup element templates"),
            'task_backup_settings' => _("backup settings"),
            'task_backup_templates' => _("backup xslt templates"),
            'task_publish_caching_colorschemes' => _("caching colorschemes"),
            'task_publish_caching_languages' => _("caching languages"),
            'task_publish_caching_navigation' => _("caching navigation"),
            'task_publish_caching_pages' => _("caching pages"),
            'task_publish_caching_settings' => _("caching settings"),
            'task_publish_caching_templates' => _("caching templates"),
            'task_publish_feeds' => _("publishing atom feeds"),
            'task_publish_processing_indexes' => _("publishing index"),
            'task_publish_processing_lib' => _("publishing library"),
            'task_publish_processing_pages' => _("preprocessing pages"),
            'task_publish_publishing_pages' => _("publishing pages"),
            'task_publish_sitemap' => _("publishing sitemap"),
            'task_publish_testing_connection' => _("testing connection to publishing host"),
            'time_calculating' => _("(calculating)"),
            'time_min' => _("minutes"),
            'time_sec' => _("seconds"),
            'tree_after_copy' => _("(copy)"),
            'tree_headline_colors' => _("colorschemes"),
            'tree_headline_files' => _("file-library"),
            'tree_headline_page_data' => _("document"),
            'tree_headline_pages' => _("site"),
            'tree_headline_settings' => _("settings"),
            'tree_headline_tpl_newnodes' => _("element-templates"),
            'tree_headline_tpl_templates' => _("XSL-templates"),
            'tree_name_color_global' => _("global colors"),
            'tree_name_metatags' => _("Meta*"),
            'tree_name_new_colorscheme' => _("colorscheme"),
            'tree_name_new_folder' => _("folder"),
            'tree_name_new_new_node' => _("element"),
            'tree_name_new_page' => _("page"),
            'tree_name_new_page_empty' => _("[empty page]"),
            'tree_name_new_redirect' => _("redirect"),
            'tree_name_new_separator' => _("separator"),
            'tree_name_new_template' => _("template"),
            'tree_name_separator' => _("                    "),
            'tree_name_settings_bak' => _("backup"),
            'tree_name_settings_bak_backup' => _("backup"),
            'tree_name_settings_bak_restore' => _("restore"),
            'tree_name_settings_global_files' => _("global files"),
            'tree_name_settings_languages' => _("languages"),
            'tree_name_settings_navigation' => _("navigation"),
            'tree_name_settings_publish' => _("Publish"),
            'tree_name_settings_template_sets' => _("template-sets"),
            'tree_name_settings_variables' => _("variables"),
            'tree_name_untitled' => _("(untitled)"),
            'tree_nodata' => _(" loading..."),
            'user_unknown' => _("(unknown)"),
            'auth_access' => _("Authentication"),
            'auth_no_access' => _("You have no Authentication to access this item"),
            'js_dlg_backup_save' => _("Which backup of '%project%' do you want to restore?"),
            'js_dlg_publish' => _("Do you want to publish '%project%' now?"),
            'prop_tt_text_formatted_maxchars' => _("%chars% of %maxchars% characters max left"),
            'task_publish_progress' => _("%percent%% finishing in %time_until_end%min</p>"),
        );
    }
    // }}}
    // {{{ getGlobalEntities()
    protected function getGlobalEntities() {
        return array(
            'nbsp' => '#160',
            'auml' => '#228',
            'ouml' => '#246',
            'uuml' => '#252',
            'Auml' => '#196',
            'Ouml' => '#214',
            'Uuml' => '#220',
            'mdash' => '#8212',
            'ndash' => '#8211',
            'copy' => '#169',
            'euro' => '#8364',
        );
    }
    // }}}
    // {{{ getGlobalNamespaces()
    protected function getGlobalNamespaces() {
        return array(
            'xsl' => array('ns' => 'xsl', 'uri' => "http://www.w3.org/1999/XSL/Transform"),
            'rpc' => array('ns' => 'rpc', 'uri' => "http://cms.depagecms.net/ns/rpc"),
            'database' => array('ns' => 'db', 'uri' => "http://cms.depagecms.net/ns/database"),
            'project' => array('ns' => 'proj', 'uri' => "http://cms.depagecms.net/ns/project"),
            'page' => array('ns' => 'pg', 'uri' => "http://cms.depagecms.net/ns/page"),
            'section' => array('ns' => 'sec', 'uri' => "http://cms.depagecms.net/ns/section"),
            'edit' => array('ns' => 'edit', 'uri' => "http://cms.depagecms.net/ns/edit"),
            'backup' => array('ns' => 'backup', 'uri' => "http://cms.depagecms.net/ns/backup"),
        );
    }
    // }}}
    // {{{ getGlobalFiletypes()
    protected function getGlobalFiletypes() {
        return array(
            'html' => Array(
                'dynamic' => false,
                'extension' => 'html'
            ),
            'shtml' => Array(
                'dynamic' => true,
                'extension' => 'shtml'
            ),
            'text' => Array(
                'dynamic' => false,
                'extension' => 'txt'
            ),
            'php' => Array(
                'dynamic' => true,
                'extension' => 'php'
            ),
            'php5' => Array(
                'dynamic' => true,
                'extension' => 'php5'
            ),
        );
    }
    // }}}
    // {{{ getGlobalOutputEncodings()
    protected function getGlobalOutputEncodings() {
        return array(
            'UTF-8',
            'ISO-8859-1',
        );
    }
    // }}}
    // {{{ getGlobalOutputMethods()
    protected function getGlobalOutputMethods() {
        return array(
            'html',
            'xhtml',
            'xml',
            'text',
        );
    }
    // }}}
    // {{{ getUserList()
    function getUserList(){
        $users = \depage\Auth\User::loadAll($this->pdo);

        $xml = "";
        foreach ($users as $user) {
            $xml .= "<user name=\"" . htmlspecialchars($user->name) . "\" fullname=\"" . htmlspecialchars($user->fullname) . "\" uid=\"" . htmlspecialchars($user->id) . "\" level=\"" . htmlspecialchars($user->level) . "\" />";
        }

        return $xml;
    }
    // }}}
    // {{{ getScheme()
    /**
     * get interface-color-scheme
     *
     * @public
     *
     * @param    $schemefile (string) name of interface scheme ini-file
     *
     * @return    $scheme (array)
     */
    function getScheme($schemefile){
        $scheme = parse_ini_file($schemefile, false);

        return $scheme;
    }
    // }}}
    // {{{ getTreeSettings()
    function getTreeSettings() {
        $xml = $this->xmldb->getDocXml("settings");

        return $xml->saveXML($xml->documentElement);
    }
    // }}}
    // {{{ getTreeColors()
    function getTreeColors() {
        $xml = $this->xmldb->getDocXml("colors");

        return $xml->saveXML($xml->documentElement);
    }
    // }}}
    // {{{ getTreeTplNewnodes()
    function getTreeTplNewnodes() {
        $doctypes = new \depage\cms\xmldoctypes\page($this->xmldb, 0);
        $nodetypes = $doctypes->getNodeTypes();

        $xml = "<proj:tpl_newnodes db:name=\"tree_nodename_newnodes_root\"";
            $xml .= " xmlns:proj=\"http://cms.depagecms.net/ns/project\"";
            $xml .= " xmlns:db=\"http://cms.depagecms.net/ns/database\"";
            $xml .= " xmlns:pg=\"http://cms.depagecms.net/ns/page\"";
            $xml .= " xmlns:edit=\"http://cms.depagecms.net/ns/edit\"";
            $xml .= " xmlns:section=\"http://cms.depagecms.net/ns/section\"";
        $xml .= ">";

        foreach($nodetypes as $t) {
            $xml .= "<pg:newnode name=\"" . htmlspecialchars($t->name) . "\" db:id=\"$t->id\">";
                $xml .= "<edit:newnode_valid_parents>" . htmlspecialchars($t->validParents) . "</edit:newnode_valid_parents>";
                $xml .= "<edit:newnode>" . htmlspecialchars($t->xmlTemplateData) . "</edit:newnode>";
            $xml .= "</pg:newnode>";
        }

        $xml .= "</proj:tpl_newnodes>";

        return $xml;
    }
    // }}}
    // {{{ getTreePages()
    function getTreePages() {
        $xml = $this->xmldb->getDocXml("pages");

        return $xml->saveXML($xml->documentElement);
    }
    // }}}
    // {{{ getTreePagedata()
    function getTreePagedata($id) {
        $xml = $this->xmldb->getDocXml($id);

        if ($xml) {
            $root = $xml->documentElement;
            $meta = $xml->getElementsByTagNameNS("http://cms.depagecms.net/ns/page", "meta")->item(0);

            if ($meta) {
                // copy global lastchange attributes to meta attribute for backwards compatibility
                $meta->setAttribute("lastchange_UTC", $root->getAttribute("db:lastchange"));
                $meta->setAttribute("lastchange_uid", $root->getAttribute("db:lastchangeUid"));
            }

            return $xml->saveXML($xml->documentElement);
        }
        return false;
    }
    // }}}
    // {{{ getTreeFiles()
    function getTreeFiles() {
        $path = "projects/{$this->projectName}/lib/";

        $dirXML = "<proj:dir xmlns:proj=\"http://cms.depagecms.net/ns/project\" xmlns:db=\"http://cms.depagecms.net/ns/database\" db:invalid=\"name\" name=\"" . htmlentities($this->projectName) . "\">";
        $dirXML .= $this->getTreeDirectoriesForPath($path);
        $dirXML .= "</proj:dir>";

        return $dirXML;
    }
    // }}}
    // {{{ getTreeDirectoriesForPath()
    function getTreeDirectoriesForPath($path) {
        $dirs = glob($path . "*", \GLOB_ONLYDIR | \GLOB_MARK);
        $dirXML = "";

        foreach ($dirs as $dir) {
            $dirXML .= "<proj:dir name=\"" . htmlentities(basename($dir)) . "\">";
            $dirXML .= $this->getTreeDirectoriesForPath($dir);
            $dirXML .= "</proj:dir>";
        }

        return $dirXML;
    }
    // }}}
    // {{{ getFilesForPath()
    function getFilesForPath($path) {
        $files = glob("projects/" . $this->projectName . "/lib" . $path . "*", \GLOB_MARK);
        $dirXML = "";

        $mediainfo = new \Depage\Media\MediaInfo(array(
            'cache' => \Depage\Cache\Cache::factory("mediainfo"),
        ));
        $sizeFormatter = new \Depage\Formatters\FileSize();
        $dateFormatter = new \Depage\Formatters\DateNatural();

        $dirXML .= "<proj:files xmlns:proj=\"http://cms.depagecms.net/ns/project\"><proj:filelist dir=\"" . htmlentities($path) . "\">";
        foreach ($files as $file) {
            if (substr($file, -1) != "/") {
                $info = $mediainfo->getInfo($file);
                $data = array(
                    'name' => $info['name'],
                    //'path' => $path,
                    'size' => $sizeFormatter->format($info['filesize']),
                    'date' => $info['date']->format("Y/m/d H:i:s"),
                    'type' => $info['mime'],
                    'extension' => $info['extension'],
                );
                if (isset($info['width'])) {
                    $data['width'] = $info['width'];
                    $data['height'] = $info['height'];
                }

                $this->log->log($data);

                $dirXML .= "<file";
                foreach ($data as $key => $value) {
                    $dirXML .= " $key=\"" . htmlspecialchars($value) . "\"";
                }
                $dirXML .= " />";
            }
        }
        $dirXML .= "</proj:filelist></proj:files>";

        return $dirXML;
    }
    // }}}

    // {{{ addCallback()
    function addCallback($type, $ids = array(), $newActiveId = null) {
        // adding callbacks that are for all logged in users
        if ($type == 'settings') {
            $this->callbacks[] = $this->getCallbackForSettings($ids);
        } elseif ($type == 'colors') {
            $this->callbacks[] = $this->getCallbackForColors($ids);
        } elseif ($type == 'tpl_newnodes') {
        } elseif ($type == 'pages') {
            $this->callbacks[] = $this->getCallbackForPages($ids);
        } elseif ($type == 'page_data') {
            $this->callbacks[] = $this->getCallbackForPagedata($ids);
        }

        // add updates to rcp-updates table
        $query = $this->pdo->prepare(
            "INSERT INTO {$this->pdo->prefix}_rpc_updates
                (sid, projectname, message) VALUES (:sid, :projectname, :message)"
        );
        $activeUsers = \Depage\Auth\User::loadActive($this->pdo);
        foreach ($this->callbacks as $callback) {
            foreach ($activeUsers as $user) {
                if ($user->sid != $this->user->sid) {
                    $query->execute(array(
                        'sid' => $user->sid,
                        'projectname' => $this->projectName,
                        'message' => $callback,
                    ));
                }
            }
        }

        // add callback for setting active id
        if (!is_null($newActiveId)) {
            $this->callbacks[] = new Func("set_activeId_{$type}", array('id' => $newActiveId));
        }
    }
    // }}}
    // {{{ getCallbacks()
    function getCallbacks() {
        $this->pdo->beginTransaction();

        // get callbacks
        $query = $this->pdo->prepare(
            "SELECT
                message
            FROM {$this->pdo->prefix}_rpc_updates
            WHERE
                projectname = :projectName AND
                sid = :sid"
        );
        $query->execute(array(
            'projectName' => $this->projectName,
            'sid' => $this->user->sid,
        ));
        while ($result = $query->fetchObject()) {
            $this->callbacks[] = $result->message;
        }

        // delete used callbacks
        $query = $this->pdo->prepare(
            "DELETE FROM {$this->pdo->prefix}_rpc_updates
            WHERE
                projectname = :projectName AND
                sid = :sid"
        );
        $query->execute(array(
            'projectName' => $this->projectName,
            'sid' => $this->user->sid,
        ));

        $this->pdo->commit();

        return $this->callbacks;
    }
    // }}}
    // {{{ getCallbackForPages()
    function getCallbackForPages($ids = array()) {
        $data = array();

        $data['data'] = $this->getTreePages();

        return new Func("update_tree_pages", $data);
    }
    // }}}
    // {{{ getCallbackForSettings()
    function getCallbackForSettings($ids = array()) {
        $data = array();

        $data['data'] = $this->getTreeSettings();

        return new Func("update_tree_settings", $data);
    }
    // }}}
    // {{{ getCallbackForColors()
    function getCallbackForColors($ids = array()) {
        $data = array();

        $data['data'] = $this->getTreeColors();

        return new Func("update_tree_colors", $data);
    }
    // }}}
    // {{{ getCallbackForPagedata()
    function getCallbackForPagedata($ids = array()) {
        $data = array();

        for ($i = 0; $i < count($ids); $i++) {
            $data['id' . ($i + 1)] = $ids[$i];
        }
        $data['id_num'] = count($ids);

        return new Func("get_update_tree_page_data", $data);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
