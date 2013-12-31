<?php
/**
 * @file    framework/cms/ui_base.php
 *
 * base class for cms-ui modules
 *
 *
 * copyright (c) 2011-2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace DepageLegacy\RPC;

class CmsFuncs {
    // {{{ get_config()
    /**
     * gets global configuration data and interface texts from db
     *
     * @public
     *
     * @return     $set_config (xmlfuncobj) configuration
     */ 
    function get_config() {
        global $conf;
        global $project;
        global $msgHandler;    
        
        $conf_array = array();
        
        $conf_array['app_name'] = $conf->app_name;
        $conf_array['app_version'] = $conf->app_version;
        
        $conf_array['thumb_width'] = $conf->thumb_width;
        $conf_array['thumb_height'] = $conf->thumb_height;
        $conf_array['thumb_load_num'] = $conf->thumb_load_num;
        
        $conf_array['interface_lib'] = "lib_interface.swf";
        
        /*
        $conf_array['interface_text'] = "";
        $lang = $conf->getTexts($conf->interface_language);
        foreach ($lang as $key => $val) {
            $conf_array['interface_text'] .= "<text name=\"$key\" value=\"" . $val . "\" />";
        }
         */
        
        /*
        $conf_array['interface_scheme'] = '';
        $colors = $conf->getScheme($conf->interface_scheme);
        foreach ($colors as $key => $val) {
            $conf_array['interface_scheme'] .= "<color name=\"$key\" value=\"" . htmlspecialchars($val) . "\" />";
        }
        */
        
        /*
        $conf_array['projects'] = $project->get_avail_projects();
         */
        
        /*
        foreach($conf->ns as $ns_key => $ns) {
            $conf_array['namespaces'] .= "<namespace name=\"$ns_key\" prefix=\"{$ns['ns']}\" uri=\"{$ns['uri']}\"/>";
        }
         */
        
        $conf_array['url_page_scheme_intern'] = $conf->url_page_scheme_intern;
        $conf_array['url_lib_scheme_intern'] = $conf->url_lib_scheme_intern;
        
        $conf_array['global_entities'] = '';
        foreach ($conf->global_entities as $val) {
            $conf_array['global_entities'] .= "<entity name=\"$val\"/>";
        }
        
        $conf_array['output_file_types'] = '';
        foreach ($conf->output_file_types as $key => $val) {
            $conf_array['output_file_types'] .= "<output_file_type name=\"$key\" extension=\"" . $val["extension"] . "\"/>";
        }

        $conf_array['output_encodings'] = '';
        foreach ($conf->output_encodings as $val) {
            $conf_array['output_encodings'] .= "<output_encoding name=\"$val\" />";
        }
        
        $conf_array['output_methods'] = '';
        foreach ($conf->output_methods as $val) {
            $conf_array['output_methods'] .= "<output_method name=\"$val\" />";
        }
        
        /*
        $conf_array['users'] = $project->user->get_userlist();
         */

        return new Func('set_config', $conf_array);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
