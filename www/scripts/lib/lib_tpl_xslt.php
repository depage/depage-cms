<?php
/**
 * @file	lib_tpl_xslt.php
 *
 * XML/XSL Transformation Library
 *
 * This file provides support
 *
 *
 * copyright (c) 2002-2004 Frank Hellenkamp [jonas.info@gmx.net]
 *
 * @author	Frank Hellenkamp [jonas.info@gmx.net]
 *
 * $Id: lib_tpl_xslt.php,v 1.13 2004/07/08 00:28:56 jonas Exp $
 */

// {{{ define and includes
if (!function_exists('die_error')) require_once('lib_global.php');
require_once('lib_tpl.php');
require_once('lib_xmldb.php');
require_once('lib_project.php');
// }}}

/**
 * provides xslt processing support
 */
class tpl_engine_xslt extends tpl_engine {
	// {{{ class variables
	/**
	 * definition of supported output types
	 */
	var $methods = array(
		'html' => 'text/html',
		'xhtml' => 'text/html',
		'xml' => 'application/xml',
		'text' => 'application/text',
	);
	// }}}
	// {{{ constructor
	/**
	 * constructor, initializes main variables
	 *
	 * @public
	 *
	 * @param	$sid (string) actual session id
	 * @param	$wid (string) actual window id
	 * @param	$file_path (string) relative path to actual page
	 * @param	$isPreview (bool) true if transforming is preview or transforming is publishing
	 */
	function tpl_engine_xslt($param) {
		$this->sid = $param['sid'];
		$this->wid = $param['wid'];
		$this->actual_path = $param['file_path'];
		
		if (isset($param['isPreview'])) {
			$this->isPreview = $param['isPreview'];
		} else {
			$this->isPreview = true;
		}
		
		$this->use = 'sablotron';
		
		$this->pages = array();
		$this->page_refs = array();
		$this->colors = array();
		$this->templates = array();
		$this->navigations = array();
		$this->languages = array();
		$this->settings = array();
	}
	// }}}
	// {{{ generate_page_redirect()
	/**
	 * generates the code for index page from template
	 *
	 * @public
	 *
	 * @param	$project_name (string) project name
	 * @param	$type (string) name of template-set that should be used
	 * @param	$lang (string) current language
	 * @param	$use_cached_template (bool) true, if to use cached template, false for using template from db for previewing
	 */
	function generate_page_redirect($project_name, $type, $lang = '', $use_cached_template = true) {
		global $conf;
		
		if ($lang == '') {
			$output_languages = array();
			$xml_temp = $this->get_languages($project_name);
			$xpath_temp = project::xpath_new_context($xml_temp);
			$xfetch = xpath_eval($xpath_temp, "/{$conf->ns['project']['ns']}:languages/{$conf->ns['project']['ns']}:language/@shortname");
			for ($i = 0; $i < count($xfetch->nodeset); $i++) {
				$output_languages[$i] = $xfetch->nodeset[$i]->get_content();
			}
			$lang = $output_languages[0];
		}
		
		$this->project = $project_name;
		$this->type = $type;
		$this->id = -1;
		$this->lang = $lang;
		$this->use_cached_template = $use_cached_template;
		$this->ids_used = array();
		$transformed = array();
		
		$settings = $this->get_settings($project_name, $type);
		$tempNode = $settings->document_element();
		
		$this->method = $tempNode->get_attribute('method');
		$this->indent = $tempNode->get_attribute('indent');
		$this->content_encoding = $tempNode->get_attribute('encoding');
		$this->content_type = $this->methods[$this->method];
		
		//set variables
		$this->variables = array(
			'tt_actual_id' => "'{$this->id}'",
			'tt_lang' => "'{$this->lang}'",
			'tt_multilang' => "/{$conf->ns['page']['ns']}:page/@multilang",
			'content_type' => "'{$this->content_type}'",
			'content_encoding' => "'{$this->content_encoding}'",
		);
		
		//process data
		if ($this->use == 'sablotron') {
			// Allocate a new XSLT processor
			$schemeHandlerArray = array(
				'get_all' => 'urlSchemeHandler',
			);
			$xh = xslt_create();
			xslt_set_scheme_handlers($xh, $schemeHandlerArray);
			
			// Process the document
			$result = xslt_process($xh, "get:redirect", "get:template/{$this->type}/" . ($this->use_cached_template ? 'cached' : 'noncached'), null, $arguments = null, $param = null);
			if (!$result) {
				error_log("ERROR " . xslt_errno($xh) . ": " . xslt_error($xh) . ".\n");
				$this->error .= "ERROR " . xslt_errno($xh) . ": " . xslt_error($xh) . ".\n";
			} else {
				$this->error = "";
			}
			xslt_free($xh);
			
			$transformed['value'] = $this->_post_transform($project_name, $type, $result);
			$transformed['content_type'] = $this->content_type;
			$transformed['content_encoding'] = $this->content_encoding;
		}
		return $transformed;
	}
	// }}}
	// {{{ transform
	/**
	 * generates the code for index page from template
	 *
	 * @public
	 *
	 * @param	$project_name (string) project name
	 * @param	$type (string) name of template-set that should be used
	 * @param	$id (int) id of page to transform
	 * @param	$lang (string) current language
	 * @param	$use_cached_template (bool) true, if to use cached 
	 *			template, false for using template from db for previewing
	 *
	 * @return	$transformed (string) transformed data
	 */
	function transform($project_name, $type, $id, $lang, $use_cached_template = true) {
		global $conf, $log;
		
		$this->project = $project_name;
		$this->type = $type;
		$this->id = $id;
		$this->lang = $lang;
		$this->use_cached_template = $use_cached_template;
		$this->ids_used = array();
		$transformed = array();
		
		if ($this->id != null) {
			$settings = $this->get_settings($project_name, $type);
			$tempNode = $settings->document_element();
			
			$this->method = $tempNode->get_attribute('method');
			$this->indent = $tempNode->get_attribute('indent');
			$this->content_encoding = $tempNode->get_attribute('encoding');
			$this->content_type = $this->methods[$this->method];
		
			//set variables
			$this->variables = array(
				'tt_actual_id' => "'{$this->id}'",
				'tt_lang' => "'{$this->lang}'",
				'tt_multilang' => "/{$conf->ns['page']['ns']}:page/@multilang",
				'content_type' => "'{$this->content_type}'",
				'content_encoding' => "'{$this->content_encoding}'",
			);
			
			//get color variables
			$xml_page = $this->get_page($id);
			$xpath_page = project::xpath_new_context($xml_page);
			$xfetch = xpath_eval($xpath_page, "//{$conf->ns['page']['ns']}:page_data/{$conf->ns['page']['ns']}:meta/@colorscheme");
			if (!is_array($xfetch->nodeset)) {
				exit("<body />");
			}
			$this->variables['tt_actual_colorscheme'] = "'" . $xfetch->nodeset[0]->value() . "'";

			$xml_colors = $this->get_colors($this->project);
			$xpath_colors = project::xpath_new_context($xml_colors);
			
			// get available colorschemes
			$colorschemes = array();
			$xfetch = xpath_eval($xpath_colors, "/{$conf->ns['project']['ns']}:colorschemes/{$conf->ns['project']['ns']}:colorscheme");
			if (count($xfetch->nodeset) > 0) {
				foreach ($xfetch->nodeset as $tcs) {
					if ($tcs->get_attribute('name') != "tree_name_color_global") {
						$colorschemes[] = "'" . $tcs->get_attribute('name') . "'";
					}
				}
			}
			if (!in_array($this->variables['tt_actual_colorscheme'], $colorschemes)) {
				$this->variables['tt_actual_colorscheme'] = $colorschemes[0];
			}
			
			// add global colors
			$xfetch = xpath_eval($xpath_colors, "/{$conf->ns['project']['ns']}:colorschemes/{$conf->ns['project']['ns']}:colorscheme[@{$conf->ns['database']['ns']}:name=\"tree_name_color_global\"]/color");
			for ($i = 0; $i < count($xfetch->nodeset); $i++) {
				$this->variables['ttc_' . $xfetch->nodeset[$i]->get_attribute('name')] = "'" . $xfetch->nodeset[$i]->get_attribute('value') . "'";
			}
			// add colors from colorscheme
			$xfetch = xpath_eval($xpath_colors, "/{$conf->ns['project']['ns']}:colorschemes/{$conf->ns['project']['ns']}:colorscheme[@name=" . $this->variables['tt_actual_colorscheme'] . "]/color");
			for ($i = 0; $i < count($xfetch->nodeset); $i++) {
				$this->variables['ttc_' . $xfetch->nodeset[$i]->get_attribute('name')] = "'" . $xfetch->nodeset[$i]->get_attribute('value') . "'";
			}
			
			//process data
			if ($this->use == 'sablotron') {
				// Allocate a new XSLT processor
				$schemeHandlerArray = array(
					'get_all' => 'urlSchemeHandler',
				);
				$xh = xslt_create();
				xslt_set_scheme_handlers($xh, $schemeHandlerArray);
				xslt_set_encoding($xh, $this->content_encoding);
				
				// Process the document
				$result = xslt_process($xh, "get:page/{$this->id}", "get:template/{$this->type}/" . ($this->use_cached_template ? 'cached' : 'noncached'), null, $arguments = null, $param = null);
				if (!$result) {
					$this->error .= "ERROR " . xslt_errno($xh) . ": " . xslt_error($xh) . ".\n";
					$transformed = false;
				} else {
					$transformed['value'] = $this->_post_transform($project_name, $type, $result);
					$transformed['content_type'] = $this->content_type;
					$transformed['content_encoding'] = $this->content_encoding;
					$this->error = "";
				}
				xslt_free($xh);
			
				return $transformed;
			}
		} else {
			$this->error = "ERROR";
			
			return false;
		}
	}
	
	/**
	 * performs actions needed after transforming data by templates
	 *
	 * @private
	 *
	 * @param	$project_name (string) project name
	 * @param	$type (string) name of template set
	 * @param	$transformed (string) previously transformed data
	 *
	 * @return	$transformed (string) posttransformed data
	 */
	function _post_transform($project_name, $type, $transformed) {
		if (function_exists('mb_encode_numericentity')) {
			$transformed = $this->_to_entity($transformed, $this->content_encoding);
		}
		
		return $transformed;
	}
	
	
	/**
	 * replaces unicode characters with specific unicode entities
	 *
	 * @private
	 *
	 * @param	$tempstring (string) strig to convert
	 * @param	$encoding (string) target character encoding
	 *
	 * @return	$encoded (string) the endoded string
	 */
	function _to_entity($tempstring, $encoding) {
		$f = 0xffff;
		$convmap = array(
			/* <!ENTITY % HTMLlat1 PUBLIC "-//W3C//ENTITIES Latin 1//EN//HTML"> %HTMLlat1; */
			160,  255, 0, $f,
			/* <!ENTITY % HTMLsymbol PUBLIC "-//W3C//ENTITIES Symbols//EN//HTML"> %HTMLsymbol; */
			402,  402, 0, $f,  913,  929, 0, $f,  931,  937, 0, $f,
			945,  969, 0, $f,  977,  978, 0, $f,  982,  982, 0, $f,
			8226, 8226, 0, $f, 8230, 8230, 0, $f, 8242, 8243, 0, $f,
			8254, 8254, 0, $f, 8260, 8260, 0, $f, 8465, 8465, 0, $f,
			8472, 8472, 0, $f, 8476, 8476, 0, $f, 8482, 8482, 0, $f,
			8501, 8501, 0, $f, 8592, 8596, 0, $f, 8629, 8629, 0, $f,
			8656, 8660, 0, $f, 8704, 8704, 0, $f, 8706, 8707, 0, $f,
			8709, 8709, 0, $f, 8711, 8713, 0, $f, 8715, 8715, 0, $f,
			8719, 8719, 0, $f, 8721, 8722, 0, $f, 8727, 8727, 0, $f,
			8730, 8730, 0, $f, 8733, 8734, 0, $f, 8736, 8736, 0, $f,
			8743, 8747, 0, $f, 8756, 8756, 0, $f, 8764, 8764, 0, $f,
			8773, 8773, 0, $f, 8776, 8776, 0, $f, 8800, 8801, 0, $f,
			8804, 8805, 0, $f, 8834, 8836, 0, $f, 8838, 8839, 0, $f,
			8853, 8853, 0, $f, 8855, 8855, 0, $f, 8869, 8869, 0, $f,
			8901, 8901, 0, $f, 8968, 8971, 0, $f, 9001, 9002, 0, $f,
			9674, 9674, 0, $f, 9824, 9824, 0, $f, 9827, 9827, 0, $f,
			9829, 9830, 0, $f,
			/* <!ENTITY % HTMLspecial PUBLIC "-//W3C//ENTITIES Special//EN//HTML"> %HTMLspecial; */
			/* These ones are excluded to enable HTML: 34, 38, 60, 62 */
			338,  339, 0, $f,  352,  353, 0, $f,  376,  376, 0, $f,
			710,  710, 0, $f,  732,  732, 0, $f, 8194, 8195, 0, $f,
			8201, 8201, 0, $f, 8204, 8207, 0, $f, 8211, 8212, 0, $f,
			8216, 8218, 0, $f, 8218, 8218, 0, $f, 8220, 8222, 0, $f,
			8224, 8225, 0, $f, 8240, 8240, 0, $f, 8249, 8250, 0, $f,
			8364, 8364, 0, $f
		);
		return mb_encode_numericentity($tempstring, $convmap, $encoding);
	}
	// }}}
	// {{{ get_page()
	/**
	 * gets page by id from database (for previewing) or from filesystem (for publishing)
	 *
	 * @public
	 *
	 * @param	$id (int) id of page
	 *
	 * @return	$data (xmlobject) xmldata of page
	 */
	function &get_page($id) {
		global $conf, $project, $log;
		
		if ($id == '') {
			return "<error>can't get page without id</error>";
		}
		$data_id = $project->get_page_data_id_by_page_id($this->project, $id);
		if (!isset($this->pages[$data_id])) {
			if ($this->isPreview) {
				$temp_xml = $project->get_page_data($this->project, $data_id);

				$this->pages[$data_id] = $project->domxml_new_doc();
				$root_node = $this->pages[$data_id]->create_element_ns($conf->ns['page']['uri'], 'page', $conf->ns['page']['ns']);
				foreach ($conf->ns as $ns_name => $ns) {
					if ($ns_name != 'page') {
						$root_node->set_attribute("xmlns:{$ns['ns']}", $ns['uri']);
					}
				}
				$page_attributes = $project->get_page_attributes($this->project, $id);
				foreach ($page_attributes as $name => $value) {
					$root_node->set_attribute($name, $value);
				}
				if (!method_exists($temp_xml, "document_element")) {
					exit("<body />");
				}
				$temp_node = $temp_xml->document_element();
				$root_node->append_child($temp_node->clone_node(true));
				$this->pages[$data_id]->append_child($root_node);
				$this->ids_used[] = $data_id;
			} else {
				$this->pages[$data_id] = domxml_open_file($project->get_project_path($this->project) . "/publish/page{$id}.xml");
			}
			
		}
		return $this->pages[$data_id];
	}
	// }}}
	// {{{ get_page_redirect()
	/**
	 * gets xml data to generate a page redirect
	 *
	 * @public
	 *
	 * @param	$id (int) id of page to redirect to
	 *
	 * @return	$data (xmlobject) data
	 */
	function &get_page_redirect($id) {
		global $conf;
		
		$docdef = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
		$docdef .= "<!DOCTYPE ttdoc [";
		for ($i = 0; $i < count($conf->global_entities); $i++) {
			$docdef .= "<!ENTITY {$conf->global_entities[$i]} \"&amp;{$conf->global_entities[$i]};\" >";
		}
		$docdef .= "]>";
		$docdef .= "<{$conf->ns['page']['ns']}:redirect ";
		foreach($conf->ns as $ns_key => $ns) {
			$docdef .= " xmlns:{$ns['ns']}=\"{$ns['uri']}\" ";
		}
		$docdef .= " />";
		
		
		return domxml_open_mem($docdef);
	}
	// }}}
	// {{{ get_template()
	/**
	 * gets template data
	 *
	 * gets template data from cached template on preview and previewing page.
	 * gets template data from db on preview a template set.
	 * gets preview from publishing cache on publishing
	 *
	 * @public
	 *
	 * @param	$project_name (string) name of project
	 * @param	$type (string) name of template set
	 * @param	$cached (bool) true to get cached template, false to get from db
	 * @param	$variables (array) array of variables to be added to template
	 *
	 * @return	$template (string) template string
	 *
	 * @bug		extra data also added tomemcached template?
	 */
	function &get_template($project_name, $type, $cached, $variables = array()) {
		global $conf, $project, $log;
		
		if ($this->isPreview) {
			if ($cached) {
				$this->templates[$project_name][$type] = $this->_get_template_from_cache($this->project, $this->type);
			} else {
				$this->templates[$project_name][$type] = $this->_get_template_from_db($this->project, $this->type);
			}
		} else {
			$this->templates[$project_name][$type] = domxml_open_file($project->get_project_path($project_name) . '/publish/template.xsl');
		}
		
		$actual_template = $this->templates[$project_name][$type];
		
		$this->add_output_settings_to_template($actual_template, $project_name, $type);
		$this->add_variables_to_template($actual_template, $variables);
		
		return $actual_template;
	}
		
	/**
	 * gets template from db
	 *
	 * @private
	 *
	 * @param	$project_name (string) project name
	 * @param	$type (string) name of template set
	 *
	 * @return	$template (string) template string
	 */
	function &_get_template_from_db($project_name, $type) {
		global $conf, $project;
	
		$docdef = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
		$docdef .= "<!DOCTYPE xsl:stylesheet [\n";
		for ($i = 0; $i < count($conf->global_entities); $i++) {
			$docdef .= "<!ENTITY {$conf->global_entities[$i]} \"&{$conf->global_entities_values[$conf->global_entities[$i]]};\">\n";
		}
		$docdef .= "]>\n";
		$docdef .= "<xsl:stylesheet version=\"1.0\"";
		foreach($conf->ns as $ns_key => $ns) {
			$docdef .= " xmlns:{$ns['ns']}=\"{$ns['uri']}\" ";
		}
		
		$docdef .= " extension-element-prefixes=\"";
		foreach($conf->ns as $ns_key => $ns) {
			$docdef .= "{$ns['ns']} ";
		}
		$docdef .= "\">\n";
		
		$template_contents = $project->get_tpl_template_contents($project_name, $type);
		foreach($template_contents as $template) {
			$docdef .= "\n$template\n";
		}

		$docdef .= "</xsl:stylesheet>";
		$xslt_doc = domxml_open_mem($docdef);
		
		return $xslt_doc;
	}
	
	/**
	 * gets template from cache
	 *
	 * @private
	 *
	 * @param	$project_name (string) project name
	 * @param	$type (string) name of template set
	 *
	 * @return	$template (string) template string
	 */
	function &_get_template_from_cache($project_name, $type) {
		global $conf, $project;
		
		$filename = $project->get_project_path($project_name) . "/cache/xslt_{$type}.xsl";
		if (!file_exists($filename)) {
			$this->cache_template($project_name, $type);
		}
		$xslt_doc = domxml_open_file($filename);
		
		return $xslt_doc;
	}
	// }}}
	// {{{ cache_template()
	/**
	 * writes functional template to template cache for normal using
	 *
	 * @public
	 *
	 * @param	$project_name (string) project name
	 * @param	$type (string) name of template set
	 */
	function cache_template($project_name, $type) {
		global $conf, $project;
		
		$filename = $project->get_project_path($project_name) . "/cache/xslt_{$type}.xsl";
		$xslt_doc = $this->_get_template_from_db($project_name, $type, array());
		$xslt_doc->dump_file($filename, false, true);
		
		$xslt_doc->free();
	}
	// }}}
	// {{{ add_variables_to_template()
	/**
	 * adds variable names to xslt template to make global variables available
	 *
	 * @public
	 *
	 * @param	$xslt_doc (xmlobject) xslt template object
	 * @param	$variables (array) array of variable names and values to add
	 */
	function add_variables_to_template(&$xslt_doc, $variables = array()) {
		global $conf;
		
		$variables_keys = array_keys($variables);
		$temp_node = $xslt_doc->document_element();
		$temp_node = $temp_node->first_child();
		$temp_node = $temp_node->next_sibling();
		for ($i = 0; $i < count($variables_keys); $i++) {
			$temp_add_node = $xslt_doc->create_element_ns($conf->ns['xsl']['uri'], 'variable');
			$temp_add_node->set_attribute('name', $variables_keys[$i]);
			$temp_add_node->set_attribute('select', $variables[$variables_keys[$i]]);
			$xslt_doc->insert_before($temp_add_node, $temp_node->next_sibling());
			
			$temp_add_node = $xslt_doc->create_text_node("\n");
			$xslt_doc->insert_before($temp_add_node, $temp_node->next_sibling());
		}
	}
	// }}}
	// {{{ add_output_settings_to_template()
	/**
	 * adds output settings to xslt template
	 *
	 * @public
	 *
	 * @param	$xslt_doc (xmlobject) xslt template object
	 * @param	$project_name (string) project name
	 * @param	$type (string) name of template set
	 */
	function add_output_settings_to_template(&$xslt_doc, $project_name, $type) {
		global $conf;
		
		$temp_node = $xslt_doc->document_element();
		$temp_node = $temp_node->first_child();
		
		$temp_add_node = $xslt_doc->create_element_ns($conf->ns['xsl']['uri'], 'output');
		$temp_add_node->set_attribute('method', $this->method);
		if ($this->method == 'xml' || $this->method == 'xhtml') {
			$temp_add_node->set_attribute('omit-xml-declaration', 'no');
		} else {
			$temp_add_node->set_attribute('omit-xml-declaration', 'yes');
		}
		$temp_add_node->set_attribute('encoding', $this->content_encoding);
		$temp_add_node->set_attribute('indent', $this->indent);
		$xslt_doc->insert_before($temp_add_node, $temp_node->next_sibling());
		
		$temp_add_node = $xslt_doc->create_text_node("\n");
		$xslt_doc->insert_before($temp_add_node, $temp_node->next_sibling());
	}
	// }}}
	// {{{ get_navigation()
	/**
	 * gets document hirarchy from db and replaces current navigation in cache
	 *
	 * @public
	 *
	 * @param	$project_name (string) project name
	 * 
	 * @return 	$navigation (xmlobject) navigation hirarchy
	 */
	function &get_navigation($project_name) {
		global $conf, $project, $log;
		
		if (!isset($this->navigations[$project_name])) {
			if ($this->isPreview) {
				$this->navigations[$project_name] = $project->get_page_struct($project_name);
			} else {
				$this->navigations[$project_name] = domxml_open_file($project->get_project_path($project_name) . '/publish/navigation.xml');
			}
		}
		return $this->navigations[$project_name];
	}
	// }}}
	// {{{ get_languages()
	/**
	 * gets available project languages from db
	 *
	 * @public
	 *
	 * @param	$project_name (string) project name
	 *
	 * @return	$language (xmlobject) languages
	 */
	function &get_languages($project_name) {
		global $conf, $project, $log;
		
		if (!isset($this->languages[$project_name])) {
			if ($this->isPreview) {
				$this->languages[$project_name] = $project->get_languages_xml($project_name);
			} else {
				$this->languages[$project_name] = domxml_open_file($project->get_project_path($project_name) . '/publish/languages.xml');
			}
		}
		return $this->languages[$project_name];
	}
	// }}}
	// {{{ get_settings()
	/**
	 * gets project template settings from db
	 *
	 * @public
	 *
	 * @param	$project_name (string) project name
	 * @param	$type (string) template set
	 *
	 * @return	$settings (xmlobject) project settings
	 */
	function &get_settings($project_name, $type) {
		global $conf, $project, $log;
		
		if (!isset($this->settings[$project_name])) {
			$this->settings[$project_name] = array();
		}
		if (!isset($this->settings[$project_name][$type])) {
			if ($this->isPreview) {
				$this->settings[$project_name][$type] = $project->get_tpl_settings_xml($project_name, $type);
				
				return $this->settings[$project_name][$type];
			} else {
				$this->settings[$project_name][$type] = domxml_open_file($project->get_project_path($this->project) . "/publish/settings.xml");
			}
		}
		return $this->settings[$project_name][$type];
	}
	// }}}
	// {{{ get_path_by_id()
	/**
	 * gets path to page by id
	 *
	 * @public
	 *
	 * @param	$id (int) id of page
	 * @param	$lang (string) language
	 * @param	$project_name (string) project name
	 *
	 * @param	$path (string) path to page
	 */
	function get_path_by_id($id, $lang, $project_name) {
		global $project;
		
		if ($id == '') {
			return '';
		}
		
		if (!isset($this->page_refs[$project_name])) {
			$this->page_refs[$project_name] = array();
		}
		if (!isset($this->page_refs[$project_name][$lang])) {
			$this->page_refs[$project_name][$lang] = array();
		}
		if (!isset($this->page_refs[$project_name][$lang][$id])) {
			$path = '';
			
			$navigation = $this->get_navigation($project_name);
			$languages = $this->get_languages($project_name);
			
			$temp_node = $project->search_for_id($navigation->document_element(), $id);
			if ($temp_node == null) {
				return '';
			}
			while (nodeType::isFolderNode($temp_node) && $temp_node->first_child() != null) {
				$temp_node = $temp_node->first_child();
			}
			if ($temp_node == null) {
				return '';
			}
			$multilang = $temp_node->get_attribute('multilang') == 'true';
			$type = $temp_node->get_attribute('file_type') == '' ? 'html' : $temp_node->get_attribute('file_type');
			
			$noindex = false;
			$prev_node = $temp_node->previous_sibling();
			
			$doc_node = $temp_node->owner_document();
			$root_node = $doc_node->document_element();
			if ($root_node == $temp_node->parent_node()) {
				$noindex = true;
			}
			if (($prev_node == null || $prev_node->node_type() != XML_ELEMENT_NODE) && !$noindex && $type == 'html') {
				if ($this->isPreview && false) {
					$path = '';
				} else {
					$path = 'index.html';
				}
			} else {
				$path = $this->_glp_encode($temp_node->get_attribute('name')) . ".{$id}.{$type}";
			}
			
			while ($temp_node != null && $temp_node->parent_node() != null) {
				$temp_node = $temp_node->parent_node();	
				$parent_node = $temp_node->parent_node();
				if ($parent_node != null && $parent_node->node_type() == XML_ELEMENT_NODE) {
					$path = $this->_glp_encode($temp_node->get_attribute('name')) . "/{$path}";
				}
			}
			
			if ($multilang) {
				if ($lang == 'int') {
					$temp_node = $languages->document_element();
					$temp_node = $temp_node->first_child();
					$lang = $temp_node->get_attribute('shortname');;
				}
				$path = $lang . '/' . $path;
			} else {
				$path = 'int/' . $path;
			}
			
			if ($this->isPreview) {
				$this->page_refs[$project_name][$lang][$id] = '/dyn/' . $path;
			} else {
				$this->page_refs[$project_name][$lang][$id] = '/dyn_publish/' . $path;
			}
		}
		return $this->page_refs[$project_name][$lang][$id];
	}
	// }}}
	// {{{ get_id_by_path()
	/**
	 * gets id of page by its path
	 *
	 * @public
	 *
	 * @param	$path (string) path to page
	 * @param	$project (string) project name
	 *
	 * @return	$id (int) id of page
	 */
	function get_id_by_path($path, $project_name) {
		global $project;
		
		$id = null;
		
		$path = explode('/', $path);
		$filename = end($path);
		if ($filename == '' || $filename == 'index.html') {
			$navigation = $this->get_navigation($project_name);
			$temp_node = $navigation->document_element();
			$pos = 3;
			while ($path[$pos] != '' && $path[$pos] != 'index.html') {
				$child_nodes = $temp_node->child_nodes();
				for ($i = 0; $i < count($child_nodes); $i++) {
					if ($this->_glp_encode($child_nodes[$i]->get_attribute('name')) == $path[$pos]) {
						$temp_node = $child_nodes[$i];
					}
				}
				$pos++;
			}
			if ($pos > 3) {
				$id = $project->get_node_id($temp_node->first_child());
			} else {
				$id = $project->get_node_id($temp_node);
			}
		} else {
			$filename = pathinfo($filename);
			$filename = substr($filename['basename'], 0, strlen($filename['basename']) - strlen($filename['extension']) - 1);
			$id = substr($filename, strrpos($filename, '.') + 1);
		}
		
		return $id;
	}
	
	/**
	 * specific url encoding of name
	 *
	 * @private
	 *
	 * @param	$str (string) name to encode
	 *
	 * @return	$encoded (string) encoded string
	 */
	function _glp_encode($str) {
		$str = strtolower(utf8_decode($str));
		
		/*
		$search = array(
			"'ä'",
			"'ö'",
			"'ü'",
			"'ß'",
			"'[^a-z0-9äöüß_\.\-]'",
		);
		$replace = array(
			"ae",
			"oe",
			"ue",
			"ss",
			"_",
		);
		*/
		$search = array(
			"'[^a-z0-9_\.\-]'",
		);
		$replace = array(
			"",
		);
		$str = preg_replace($search, $replace, $str);
		
		return $str;
	}
	// }}}
	// {{{ add_extras_to_navigation
	/**
	 * adds information about active node and its parents to page hirarchy
	 *
	 * @public
	 *
	 * @param		$project_name (string) project name
	 * @param		$id (int) id of active page
	 * @type		$type (string) ??? really needed?
	 * @lang		$lang (string) ??? really needed?
	 * @is_preview	$is_preview (bool) ??? really needed?
	 */
	function add_extras_to_navigation($project_name, $id, $type, $lang, &$xml_navigation, $is_preview = true) {
		global $conf;
		
		$xpath_navigation = project::xpath_new_context($xml_navigation);
			
		$xfetch = xpath_eval($xpath_navigation, "//*/@status");
		for ($i = 0; $i < count($xfetch->nodeset); $i++) {
			$xfetch->nodeset[$i]->unlink_node();
		}
		
		$xfetch = xpath_eval($xpath_navigation, "//*[@{$conf->ns['database']['ns']}:id = '" . $id . "']");
		$xfetch->nodeset[0]->set_attribute('status', 'active');
		$temp_node = $xfetch->nodeset[0]->parent_node();
		while ($temp_node->node_type() == XML_ELEMENT_NODE ) {
			$temp_node->set_attribute('status', 'parent-of-active');
			$temp_node = $temp_node->parent_node();	
		}
	}
	// }}}
	// {{{ get_colors()
	/**
	 * gets colorschemes from db
	 *
	 * @public
	 *
	 * @param	$project_name (string) project name
	 *
	 * @return	$colors (xmlobject) colorschemes
	 */
	function &get_colors($project_name) {
		global $conf, $project;

		if ($this->isPreview) {
			return $project->get_colors($project_name);
		} else {
			$xslt_doc = domxml_open_file($project->get_project_path($project_name) . '/publish/colors.xml');
			
			return $xslt_doc;
		}
	}
	// }}}
	// {{{ get_relative_path_to
	/**
	 * gets relative path to path of active page
	 *
	 * @public
	 *
	 * @param	$target_path (string) path to target file
	 *
	 * @return	$path (string) relative path
	 */
	function get_relative_path_to($target_path) {
		global $log;

		$path = '';
		if ($target_path == '') {
			$path = '';
		} else {
			$log->add_entry("actual path: " . $this->actual_path);
			$log->add_entry("target path: " . $target_path);

			$actual_path = explode('/', $this->actual_path);
			$target_path = explode('/', $target_path);
			
			$i = 0;
			while ($actual_path[$i] == $target_path[$i] && $i < count($actual_path)) {
				$i++;
			}
			if (count($actual_path) - $i >= 1) {
				$path = str_repeat('../', count($actual_path) - $i - 1) . implode('/', array_slice($target_path, $i));
				if ($path == '') {
					$path = './';
				}
			} else {
				$path = '';
			}
		}
		return $path;
	}
	// }}}
	// {{{ change_inSource_ref
	/**
	 * changes all links to library in source property to real world paths
	 *
	 * @public
	 *
	 * @param	$source (string) source code
	 *
	 * @return	$source (string) source code with real world pathes
	 */
	function change_inSource_ref($source) {
		global $conf;
		
		$newSource = "";
		$posOffset = 0;
		while (($startPos = strpos($source, '"' . $conf->url_lib_scheme_intern . ':/', $posOffset)) !== false) {
			$newSource .= substr($source, $posOffset, $startPos - $posOffset) . '"';
			$posOffset = $startPos + strlen($conf->url_lib_scheme_intern) + 3;
			$endPos = strpos($source, "\"", $posOffset);
			$newSource .= $this->get_relative_path_to('/lib' . substr($source, $startPos + 8, $endPos - ($startPos + 8)));
			$posOffset = $endPos;
		}
		$newSource .= substr($source, $posOffset);
		
		return '<source>' . htmlentities($newSource) . '</source>';
	}
	// }}}
	// {{{ get_file_info()
	/**
	 * gets information about a file
	 *
	 * gets dirname, basename, extension, size, date (last modification). if 
	 * file is an image, it gives width and height, too.
	 *
	 * @public
	 *
	 * @param	$path (string) library path in "libref:" notation
	 *
	 * @return	$info (xmlobject) image info
	 */
	function get_file_info($path) {
		global $conf, $project;
		
		$value = '<file';
		if (substr($path, 0, strlen($conf->url_lib_scheme_intern) + 1) == $conf->url_lib_scheme_intern . ':') {
			$file_path = $project->get_project_path($this->project) . '/lib/' . substr($path, strlen($conf->url_lib_scheme_intern) + 1);
			if (file_exists($file_path)) {
				$fileinfo = pathinfo($file_path);
				$imageinfo = @getimagesize($file_path);
				
				$value .= ' exists="true"';
				$value .= ' dirname="' . $fileinfo['dirname'] . '"';
				$value .= ' basename="' . $fileinfo['basename'] . '"';
				$value .= ' extension="' . $fileinfo['extension'] . '"';
				if ($imageinfo[2] > 0) {
					$value .= ' width="' . $imageinfo[0] . '"';
					$value .= ' height="' . $imageinfo[1] . '"';
				}
				$fs_access = new fs_local();
				$value .= ' size="' . $fs_access->f_size_format($file_path) . '"';
				$value .= ' date="' . $conf->dateUTC($conf->date_format_UTC, filemtime($file_path)) . '"';
			} else {
				$value .= ' exists="false"';
			}
		} else {
			$value .= ' exists="false"';
		}
		$value .= ' />';
		
		return $value;
	}
	// }}}
	// {{{ get_doc_type()
	/**
	 * gets document type declarition
	 *
	 * @public
	 *
	 * @param	$type (string) html or xhtml
	 * @param	$version (string) version of document definition
	 * @param	$subtype (string) strict or transitional or frameset
	 *
	 * @return	$doctype (string) generated doctype declarition
	 */
	function get_doc_type($param) {
		list($type, $version, $subtype) = explode('/', $param);
		/* HTML */
		if ($type == "html" && $version == "4.01" && $subtype == "strict") {
			$value = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">";
		} else if ($type == "html" && $version == "4.01" && $subtype == "transitional") {
			$value = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
		} else if ($type == "html" && $version == "4.01" && $subtype == "frameset") {
			$value = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Frameset//EN\" \"http://www.w3.org/TR/html4/frameset.dtd\">";
		/* XHTML 1.0 */
		} else if ($type == "xhtml" && $version == "1.0" && $subtype == "strict") {
			$value = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">";
		} else if ($type == "xhtml" && $version == "1.0" && $subtype == "transitional") {
			$value = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
		} else if ($type == "xhtml" && $version == "1.0" && $subtype == "frameset") {
			$value = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd\">";
		/* XHTML 1.1 */
		} else if ($type == "xhtml" && $version == "1.1") {
			$value = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">";
		/* undefined */
		} else {
			$value = '';
		}
			
		return "<value>" . htmlspecialchars($value) . "</value>";
	}
	// }}}
}

// {{{ urlSchemeHandler()
/**
 * handles all specific document() calls in xslt template
 *
 * @relates	tpl_engine_xslt
 *
 * @param	$processor (object) actual xslt processor object
 * @param	$scheme (string) url scheme of document call (get://, call://, pageref://, libref://)
 *
 * @return	$xmlvalue (string) xml conform return of called scheme
 */
function urlSchemeHandler($processor, $scheme, $param) {
	global $conf, $xml_proc, $log;
	
	$value = "<null error=\"invalid function\" />";
	
	if ($scheme == 'get') {
		list($func, $id, $param) = explode('/', trim($param, '/'), 3);
		
		if ($func == 'page') {
			$xml_page_data = $xml_proc->get_page($id);
			$value = $xml_page_data->dump_mem(false);
		} else if ($func == 'redirect') {
			$xml_page_data = $xml_proc->get_page_redirect($id);
			$value = $xml_page_data->dump_mem(false);
		} else if ($func == 'template') {
			$xml_template = $xml_proc->get_template($xml_proc->project, $id, $param == "cached", $xml_proc->variables);
			$value = $xml_template->dump_mem(false);
		} else if ($func == 'navigation') {
			$xml_navigation = $xml_proc->get_navigation($xml_proc->project);
			if ($xml_proc->id != -1) {
				$xml_proc->add_extras_to_navigation($xml_proc->project, $xml_proc->id, $xml_proc->type, $xml_proc->lang, $xml_navigation);
			}
			$value = $xml_navigation->dump_mem(false);
		} else if ($func == 'languages') {
			$xml_languages = $xml_proc->get_languages($xml_proc->project);
			$value = $xml_languages->dump_mem(false);
		}
	} else if ($scheme == 'call') {
		list($func, $param) = explode('/', trim($param, '/'), 2);
		
		if ($func == 'changesrc') {
			$value = $xml_proc->change_inSource_ref($param);
		} else if ($func == 'fileinfo') {
			$value = $xml_proc->get_file_info($param);
		} else if ($func == 'doctype') {
			$value = $xml_proc->get_doc_type($param);
		}
	} else if ($scheme == $conf->url_page_scheme_intern) {
		list($id, $param) = explode('/', trim($param, '/'), 2);
		
		$target_path = $xml_proc->get_path_by_id($id, $param, $xml_proc->project);
		$value_path = $xml_proc->get_relative_path_to($target_path);
		
		$value = '<page_ref>' . htmlspecialchars($value_path) . '</page_ref>';
	} else if ($scheme == $conf->url_lib_scheme_intern) {
		$tmp_path = $xml_proc->get_relative_path_to('/lib/' . trim($param, '/'));
		$value = '<file_ref>' . htmlspecialchars($tmp_path) . '</file_ref>';
	} else {
		error_log("called unknown scheme: $scheme");
	}
	
	return $value;
}
// }}}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
