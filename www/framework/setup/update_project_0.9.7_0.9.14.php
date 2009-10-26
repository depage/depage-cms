<?php
/**
 * @file	update_project_0.9.7_0.9.14.php
 *
 * Update Routine
 *
 * source version: 0.9.7
 * target version: 0.9.14
 *
 *
 * copyright (c) 2002-2007 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author	Frank Hellenkamp [jonas@depagecms.net]
 */

if (!function_exists('die_error')) require_once('../lib/lib_global.php');

class project_updater_0_9_7__0_9_14 {
	// {{{ constructor()
	function project_updater_0_9_7__0_9_14() {
		$this->from = "0.9.7";
		$this->to = "0.9.14";
	}
	// }}}
	// {{{ convert_xml_data()
	function convert_xml_data($project_xml) {
		// {{{ get important nodes
		$project_ctx = project::xpath_new_context($project_xml);
		ob_start();

		//get proj:project
		$node_project = $project_xml->document_element();

		//get proj:pages_struct node
		$xpresult = xpath_eval($project_ctx, '/proj:project/proj:data/proj:pages_struct');
		$node_project_pages_struct = $xpresult->nodeset[0];
		//get proj:pages_data node
		$xpresult = xpath_eval($project_ctx, '/proj:project/proj:data/proj:pages');
		$node_project_pages_data = $xpresult->nodeset[0];
		//get proj:colorschemes node
		$xpresult = xpath_eval($project_ctx, '/proj:project/proj:data/proj:globals/proj:colorschemes');
		$node_project_colorschemes = $xpresult->nodeset[0];
		//get proj:tpl_templates_struct node
		$xpresult = xpath_eval($project_ctx, '/proj:project/proj:data/proj:globals/proj:tpl_templates_struct');
		$node_project_tpl_templates_struct = $xpresult->nodeset[0];
		//get proj:tpl_templates_data node
		$xpresult = xpath_eval($project_ctx, '/proj:project/proj:data/proj:globals/proj:tpl_templates');
		$node_project_tpl_templates_data = $xpresult->nodeset[0];
		//get proj:tpl_newnodes node
		$xpresult = xpath_eval($project_ctx, '/proj:project/proj:data/proj:globals/proj:tpl_newnodes');
		$node_project_tpl_newnodes = $xpresult->nodeset[0];
		//get proj:data node
		$xpresult = xpath_eval($project_ctx, '/proj:project/proj:data');
		$node_project_data = $xpresult->nodeset[0];
		// }}}
		// {{{ change node structure and attributes
		//move pages_struct
		$node_project->append_child($node_project_pages_struct);
		$node_project_pages_struct->set_name('pages_tree');

		//move pages_data
		$node_project->append_child($node_project_pages_data);
		$node_project_pages_data->set_name('pages_data');

		//move colorschemes
		$node_project->append_child($node_project_colorschemes);

		//move templates_struct
		$node_project->append_child($node_project_tpl_templates_struct);
		$node_project_tpl_templates_struct->set_name('tpl_templates_tree');

		//move templates_data
		$node_project->append_child($node_project_tpl_templates_data);
		$node_project_tpl_templates_data->set_name('tpl_templates_data');

		//move templates_newnodes
		$node_project->append_child($node_project_tpl_newnodes);
		$node_project_tpl_newnodes->set_name('tpl_newnodes_data');

		// remove proj:data node
		$node_project_data->unlink_node();

		//remove db:name attributes where not nessecary
		$xpresult = xpath_eval($project_ctx, '//*[not(starts-with(name(), \'edit\'))]/@db:name');
		foreach ($xpresult->nodeset as $node_temp) {
			$node_temp->unlink_node();
		}

		//remove name attribute from proj:settings
		$xpresult = xpath_eval($project_ctx, '/proj:project/proj:settings/@name');
		foreach ($xpresult->nodeset as $node_temp) {
			$node_temp->unlink_node();
		}
		//set version attribute
		$node_project->set_attribute('version', '0.9.14');

		// }}}
		return $project_xml;
	}
	// }}}
	// {{{ update_database_structure
	function update_database_structure() {
		global $conf; 

		// {{{ alter user tables
		db_query("ALTER TABLE {$conf->db_praefix}_auth_user, COMMENT='depage 0.9.14'");
		db_query("ALTER TABLE {$conf->db_praefix}_auth_sessions, COMMENT='depage 0.9.14'");
		db_query("ALTER TABLE {$conf->db_praefix}_auth_sessions_win, COMMENT='depage 0.9.14'");
		// }}}
		// {{{ alter other tables
		db_query("ALTER TABLE {$conf->db_praefix}_env, COMMENT='depage 0.9.14'");
		db_query("ALTER TABLE {$conf->db_praefix}_interface_text, COMMENT='depage 0.9.14'");
		db_query("ALTER TABLE {$conf->db_praefix}_mediathumbs, COMMENT='depage 0.9.14'");
		db_query("ALTER TABLE {$conf->db_praefix}_log, COMMENT='depage 0.9.14'");
		// }}}
		// {{{ alter project tables
		db_query("ALTER TABLE {$conf->db_praefix}_transform_cache, COMMENT='depage 0.9.14'");
		db_query("ALTER TABLE {$conf->db_praefix}_xmldata_elements, COMMENT='depage 0.9.14'");
		db_query("ALTER TABLE {$conf->db_praefix}_xmldata_cache, COMMENT='depage 0.9.14'");
		// }}}
		// {{{ alter task tables
		db_query("ALTER TABLE {$conf->db_praefix}_tasks , COMMENT='depage 0.9.14'");
		db_query("ALTER TABLE {$conf->db_praefix}_tasks_threads, COMMENT='depage 0.9.14'");
		// }}}
	}
	// }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
