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
require_once('lib_publish.php');

class sitemap {
    var $project_name;
    var $xmlstr;
    var $languages;

    /* {{{ constructor */
    function sitemap($project_name) {
        $this->project_name = $project_name;
    }
    /* }}} */
    /* {{{ generate */
    function generate($publish_id, $baseurl) {
        global $project;

        $this->baseurl = $baseurl;
        $this->pb = new publish($this->project_name, $publish_id);
        $this->languages = array_keys($project->get_languages($this->project_name));

        $this->xmlstr = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $this->xmlstr .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        $page_struct = $project->get_page_struct($this->project_name);
        $this->add_page_data($page_struct->document_element());

        $this->xmlstr .= "</urlset>";

        return $this->xmlstr;
    }
    /* }}} */
    /* {{{ add_page_data */
    function add_page_data($node, $depth = 0) {
        global $project;

        if ($node->tagname == "page" && $node->get_attribute("nav_hidden") != "true") {
            $pathinfo = pathinfo($node->get_attribute("url"));
            foreach ($this->languages as $lang) {
                $file = new publish_file("/{$lang}{$pathinfo['dirname']}", $pathinfo['basename']);
                $lastmod = date("Y-m-d", $this->pb->get_lastmod($file));
                
                // pages in top hierarchy are more important
                $priority = floor((1 / sqrt($depth) * 10)) / 10;

                $this->xmlstr .= "<url>\n";
                $this->xmlstr .= "\t<loc>" . htmlentities($this->baseurl . $file->get_fullname()) . "</loc>\n";
                $this->xmlstr .= "\t<lastmod>" . htmlentities($lastmod) . "</lastmod>\n";
                $this->xmlstr .= "\t<priority>" . htmlentities($priority) . "</priority>\n";
                //@todo add changefreq (based on some statistics?)

                $this->xmlstr .= "</url>\n";
            }
        }

        $children = $node->child_nodes();
        foreach ($children as $child) {
            if ($child->get_attribute("nav_hidden") != "true") {
                $this->add_page_data($child, $depth + 1);
            }
        }
    }
    /* }}} */
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
