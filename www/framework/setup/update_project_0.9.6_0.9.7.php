<?php
/**
 * @file	update_project_0.9.6_0.9.7.php
 *
 * Update Routine
 *
 * source version: >= 0.9.6
 * target version: 0.9.7
 *
 *
 * copyright (c) 2002-2007 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author	Frank Hellenkamp [jonas@depagecms.net]
 */

if (!function_exists('die_error')) require_once('../lib/lib_global.php');

class project_updater_0_9_6__0_9_7 {
	// {{{ constructor()
	function project_updater_0_9_6__0_9_7() {
		$this->from = "0.9.6";
		$this->to = "0.9.7";
	}
	// }}}
	// {{{ convert_xml_data()
	function convert_xml_data($project_xml) {
		global $conf, $project;

		// {{{ get important nodes
		$project_ctx = project::xpath_new_context($project_xml);
		ob_start();

		//get proj:project
		$node_project = $project_xml->document_element();

		//get proj:data node
		$xpresult = xpath_eval($project_ctx, '/proj:project/proj:data');
		$node_project_data = $xpresult->nodeset[0];
		//get proj:content node
		$xpresult = xpath_eval($project_ctx, '/proj:project/proj:data/proj:content');
		$node_project_content = $xpresult->nodeset[0];
		//get proj:colorschemes node
		$xpresult = xpath_eval($project_ctx, '/proj:project/proj:data/proj:colorschemes');
		$node_project_colorschemes = $xpresult->nodeset[0];
		//get proj:templates_publish node
		$xpresult = xpath_eval($project_ctx, '/proj:project/proj:data/proj:templates_publish');
		$node_project_templates_publish = $xpresult->nodeset[0];
		//get proj:templates_newnodes
		$xpresult = xpath_eval($project_ctx, '/proj:project/proj:data/proj:templates_newnodes');
		$node_project_templates_newnodes = $xpresult->nodeset[0];
		// }}}
		// {{{ change node structure and attributes
		//add proj:pages
		$node_project_pages = $project_xml->create_element_ns($conf->ns['project']['uri'], 'pages', $conf->ns['project']['ns']);
		$node_project_data->insert_before($node_project_pages, $node_project_colorschemes);

		//add proj:globals
		$node_project_globals = $project_xml->create_element_ns($conf->ns['project']['uri'], 'globals', $conf->ns['project']['ns']);
		$node_project_data->insert_before($node_project_globals, $node_project_colorschemes);

		//move pg:page_data to proj:pages
		$xpresult = xpath_eval($project_ctx, '//pg:page_data');
		foreach ($xpresult->nodeset as $node_temp) {
			$page_id = $node_temp->get_attribute("id");
			$node_parent = $node_temp->parent_node();
			list($node_meta) = $node_temp->get_elements_by_tagname('meta');

			//set db:ref
			$project->xmldb->set_attribute_ns($node_parent, $conf->ns['database']['uri'], $conf->ns['database']['ns'], 'ref', $page_id);

			//remove file_name
			$node_parent->remove_attribute('file_name');
			//move colorscheme attribute
			$node_meta->set_attribute('colorscheme', $node_parent->get_attribute('colorscheme'));
			$node_parent->remove_attribute('colorscheme');

			$node_project_pages->append_child($node_temp);
		}

		//move pg:folder_data to proj:pages
		$xpresult = xpath_eval($project_ctx, '//pg:folder_data');
		foreach ($xpresult->nodeset as $node_temp) {
			$page_id = $node_temp->get_attribute("id");
			$node_parent = $node_temp->parent_node();
			list($node_meta) = $node_temp->get_elements_by_tagname('meta');

			//set db:ref
			$project->xmldb->set_attribute_ns($node_parent, $conf->ns['database']['uri'], $conf->ns['database']['ns'], 'ref', $page_id);

			$node_project_pages->append_child($node_temp);
		}

		//add proj:tpl_templates
		$node_project_templates = $project_xml->create_element_ns($conf->ns['project']['uri'], 'tpl_templates', $conf->ns['project']['ns']);
		$node_project_data->insert_before($node_project_templates, $node_project_templates_newnodes);

		//move pg:template to proj:tpl_templates
		$xpresult = xpath_eval($project_ctx, '//edit:template');
		foreach ($xpresult->nodeset as $node_temp) {
			$node_parent = $node_temp->parent_node();
			
			$node_tpl_data = $project_xml->create_element_ns($conf->ns['page']['uri'], 'template_data', $conf->ns['page']['ns']);
			$node_tpl_data->set_attribute('active', $node_parent->get_attribute('active'));
			$node_tpl_data->set_attribute('type', $node_parent->get_attribute('type'));
			$node_id_attr = $node_parent->get_attribute_node('id');
			$node_id_attr->set_name('ref');
			$project->xmldb->set_attribute_ns($node_tpl_data, $conf->ns['database']['uri'], $conf->ns['database']['ns'], 'id', $node_id_attr->value());

			$node_parent->remove_attribute('active');
			$node_parent->remove_attribute('type');

			$node_project_templates->append_child($node_tpl_data);
			$node_tpl_data->append_child($node_temp);
		}

		//rename proj:content
		$node_project_content->set_name('pages_struct');

		//rename proj:templates_publish
		$node_project_templates_publish->set_name('tpl_templates_struct');
		$node_project_templates_publish->remove_attribute('type');

		//rename proj:templates_newnodes
		$node_project_templates_newnodes->set_name('tpl_newnodes');

		//move proj:colorschemes to proj:globals
		$node_project_globals->append_child($node_project_colorschemes);

		//move proj:tpl_* to proj:globals
		$node_project_globals->append_child($node_project_templates_publish);
		$node_project_globals->append_child($node_project_templates);
		$node_project_globals->append_child($node_project_templates_newnodes);

		// remove proj:dataObjects
		$xpresult = xpath_eval($project_ctx, '//proj:dataObjects');
		foreach ($xpresult->nodeset as $node_temp) {
			$node_temp->unlink_node();
		}

		//set version attribute
		$node_project->set_attribute('version', '0.9.7');

		// }}}
		return $project_xml;

	}
	// }}}
	// {{{ update_database_structure()
	function update_database_structure() {

	}
	// }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
