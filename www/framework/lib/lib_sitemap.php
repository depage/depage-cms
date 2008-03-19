<?php
/**
 * @file    lib_sitemape.php
 *
 * Sitemap Generator Library
 *
 * This implements a class to generate "Google"-Sitemap
 *
 *
 * copyright (c) 2002-2008 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

require_once('lib_project.php');

class sitemap {
    var $project_name;

    /* {{{ constructor */
    function sitemap($project_name) {
        $this->project_name = $project_name;
    }
    /* }}} */
    /* {{{ generate */
    function generate($baseurl) {
        global $project;

        $xmlstr = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $xmlstr .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        $languages = $project->get_languages($this->project_name);

        $page_struct = $project->get_page_struct($this->project_name);
        $pages = $page_struct->get_elements_by_tagname("page");
        foreach($pages as $page) {
            $xmlstr .= "<url>\n";
            $xmlstr .= "\t<loc>" . htmlentities($baseurl . $page->get_attribute("url")) . "</loc>\n";
            $xmlstr .= "\t<lastmod>" . "</lastmod>\n";
            $xmlstr .= "</url>\n";
        }

        $xmlstr .= "</urlset>";

        return $xmlstr;
    }
    /* }}} */
    /* {{{ _generate_url */
    function _generate_url(&$xmlstr, $node, $priority = 0.5) {
        if ($node->node_name == "pg:page") {
            $xmlstr .= "<url>\n";
            $xmlstr .= "\t<loc>" . "</loc>\n";
            $xmlstr .= "\t<lastmod>" . "</lastmod>\n";
            $xmlstr .= "</url>\n";
        }
    }
    /* }}} */
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
