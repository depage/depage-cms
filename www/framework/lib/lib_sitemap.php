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

        $xmlstr = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xmlstr .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        $languages = array_keys($project->get_languages($this->project_name));

        $page_struct = $project->get_page_struct($this->project_name);
        $pages = $page_struct->get_elements_by_tagname("page");

        //@todo exclude pages, that are "hidden" or add an extra "no-publish"-tag
        foreach($pages as $page) {
            foreach ($languages as $lang) {
                $page_data = $project->get_page_data($this->project_name, $page->get_attribute("ref"));
                $meta = $page_data->get_elements_by_tagname("meta");
                //@todo change lastmod not based on lastchange_UTC but on the lastmod of sourcechange (sha1)
                $lastmod = $meta[0]->get_attribute("lastchange_UTC");
                $lastmod = substr($lastmod, 0, 4) . "-" . substr($lastmod, 5, 2) . "-" . substr($lastmod, 8, 2);

                $xmlstr .= "<url>\n";
                $xmlstr .= "\t<loc>" . htmlentities("{$baseurl}/{$lang}" . $page->get_attribute("url")) . "</loc>\n";
                $xmlstr .= "\t<lastmod>" . htmlentities($lastmod) . "</lastmod>\n";

                //@todo add priority (based on depth of navigation)
                //@todo add changefreq (based on some statistics?)
                $xmlstr .= "</url>\n";
            }
        }

        $xmlstr .= "</urlset>";

        return $xmlstr;
    }
    /* }}} */
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
