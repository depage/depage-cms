<?php
/**
 * @file    lib_spider.php
 *
 * Spider Library
 *
 * defines class to search a domain for cleartext
 *
 *
 * copyright (c) 2008-2008 Frank Hellenkamp [jonas@depagecms.net]
 * with colde elements from sphider
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

class crawler {
    /* {{{ class variables */
    var $to_crawl = array();
    var $crawled = array();
    var $max_crawl = 10;
    var $print_log = false;
    /* }}} */

    /* {{{ constructor */
    function crawler() {
    }
    /* }}} */
    /* {{{ crawl */
    function crawl($url, $base_url = "", $encoding = null) {
        $this->to_crawl = array($url);
        $this->crawled[$url] = false;
        $j = 0;

        for ($i = 0; $i < count($this->to_crawl) && $j < $this->max_crawl; $i++) {
            if (!$this->crawled[$this->to_crawl[$i]]) {
                $sp = new crawler_file_indexer();
                $sp->num = $j;
                $sp->parse_url($this->to_crawl[$i], $encoding);

                if ($this->print_log) {
                    $this->print_status($sp);
                }

                $this->to_crawl = array_merge($this->to_crawl, array_keys($this->rebase_links($sp->url, $sp->links)));
                $this->crawled[$this->to_crawl[$i]] = true;
                $j++;
            }
        }
    }
    /* }}} */
    /* {{{ rebase_links */
    function rebase_links($base_url, $links, $stay_in_domain = true) {
        $absolute_links = array();

        $url_parts = parse_url($base_url);
        $url_host = "{$url_parts["scheme"]}://{$url_parts["host"]}";
        if (isset($url_parts["port"])) {
            $url_host .= ":{$url_parts["port"]}";
        }
        $url_path_parts = explode("/", $url_parts["path"]);

        foreach ($links as $link => $num) {
            /* test if url starts with a scheme */
            if (!preg_match("/^[a-z]+:/", $link)) {
                if (substr($link, 0, 1) == "/") {
                    /* url points up from root of host */
                    $link = $url_host . $link;
                    //$absolute_links[$url_host . $link] += $num;
                } else {
                    /* url is relative */
                    $up = 1;
                    $link_parts = explode("/", $link);
                    while ($link_parts[0] == "..") {
                        array_shift($link_parts);
                        $up++;
                    }
                    $link = $url_host;
                    for ($i = 0; $i < count($url_path_parts) - $up; $i++) {
                        $link .= "{$url_path_parts[$i]}/";
                    }
                    $link .= implode("/", $link_parts);
                }
            }
            if (!$stay_in_domain || strpos($link, $url_host) === 0) {
                $absolute_links[$link] += $num;
            }
        }

        return $absolute_links;
    }
    /* }}} */
    /* {{{ print_status */
    function print_status($result) {
        echo("<div class=\"result\">\n");
        if (!$result->index) {
            echo("<h4>--</h4>\n");
            echo("<p class=\"url\">{$result->num}: <a href=\"{$result->url}\">{$result->url}</a></p>\n");
            echo("<p class=\"error\">");
            if ($result->status != "") {
                echo("{$result->status}");
            }
            echo("</p>\n");
        } else {
            if ($result->head->title != "") {
                echo("<h4>{$result->head->title}</h4>\n");
            } else {
                echo("<h4>Untitled Document</h4>\n");
            }
            echo("<p class=\"url\">{$result->num}: <a href=\"{$result->url}\">{$result->url}</a></p>\n");
            echo("<p>");
            echo(htmlspecialchars(substr($result->fulltext, 0, 250)));
            echo("</p>\n");
        }
        echo("</div>\n");
    }
    /* }}} */
}

class crawler_file_indexer {
    /* {{{ class variables */
    var $encoding;
    var $content_type;
    var $url;
    var $base_url;
    var $sha1;
    var $head;
    var $meta;
    var $fulltext = "";
    var $keywords = array();
    var $links = array();
    var $index = true;
    var $status = "";

    var $indexable_content_types = array(
        "text/html",
    );
    /* }}} */

    /* {{{ parse_url */
    function parse_url($url, $encoding = null) {
        $this->encoding = $encoding;
        $this->url = $url;

        if ($fp = @fopen($this->url, "rb")) {
            $this->get_metadata(stream_get_meta_data($fp));

            if ($this->index === true) {
                $file_content = '';
                while (!feof($fp)) {
                    $file_content .= fread($fp, 8192);
                }
                fclose($fp);

                $this->parse_data($file_content);
            }
        } else {
            $this->index = false;
        }
    }
    /* }}} */
    /* {{{ get_metadata */
    function get_metadata($stream_metadata) {
        $data = new StdClass();

        if (is_array($stream_metadata['wrapper_data'])) {
            foreach($stream_metadata['wrapper_data'] as $response) {
                if ($this->status == "" && preg_match("/^HTTP\/[^ ]* (.*)/i", $response, $matches)) {
                    $this->status = $matches[1];
                    if (substr($this->status, 0, 1) == "4") {
                        // don't index, because there have been an error
                        $this->index = false;
                    }
                } else if (preg_match("/^location: (.*)/i", $response, $matches)) {
                    // redirected so add the redirect url to the links and don't index
                    $this->links[$matches[1]]++;
                    $this->index = false;
                    $this->status = "redirected";
                } elseif (preg_match("/^content-type: ([^;]*)(; charset=(.*))?/i", $response, $matches)) {
                    $this->content_type = $matches[1];
                    $this->encoding = $matches[3];
                    if (!in_array($this->content_type, $this->indexable_content_types)) {
                        $this->index = false;
                        $this->status = "not an indexable content-type";
                    }
                }
            } 
        }

        return $data;
    }
    /* }}} */
    /* {{{ parse_data */
    function parse_data($file_content) {
        mb_internal_encoding("UTF-8");
        mb_http_output("UTF-8");

        $this->sha1 = sha1($file_content);

        if ($this->encoding == null) {
            //get encoding from meta-tag
            if (preg_match("/<meta\\s+http-equiv\\s*=[\"']?Content-type[\"']?\\s+content\\s*=[\"']?([^>\"';]*);\\scharset=([^>\"']*)[\"']?[^>]*>/si", $file_content, $matches)) {
                $this->content_type = $matches[1];
                $this->encoding = $matches[2];
            } else {
                $this->encoding = "ISO-8859-1";
            }
        }

        // convert to UTF-8
        $file_content = mb_convert_encoding($file_content, "UTF-8", $this->encoding);
        
        // strip unnecessary whitespace
        $file_content = preg_replace("/\\s{1,}/", " ", $file_content);

        $this->parse_head($file_content);

        $this->get_links($file_content);
        $this->fulltext = $this->strip_tags($file_content);

        $this->get_keywords($this->head->title);
        $this->get_keywords($this->meta['keywords']);
        $this->get_keywords($this->meta['description']);
        $this->get_keywords($this->fulltext);
    }
    /* }}} */
    /* {{{ parse_head */
    function parse_head($file_content) {
        $this->head = new StdClass();

	preg_match("/<head[^>]*>(.*?)<\\/head[^>]*>/si", $file_content, $matches);	
        $header = $matches[1];

        if ($header != "") {
            // get title
            if (preg_match("/<title[^>]*>(.*?)<\\/title[^>]*>/si", $header, $matches)) {
                $this->head->title = trim($matches[1]);
            } else {
                $this->head->title = null;
            }

            // get meta-data
            preg_match_all("/<meta\\s+([a-z]+)\\s*=[\"']?([^<>\"']*)[\"']?\\s*content=[\"']?([^<>'\"]*)[\"']?/i", $header, $matches, PREG_SET_ORDER);
            $this->meta = array();

            for ($i = 0; $i < count($matches); $i++) {
                if (mb_strtolower($matches[$i][1]) == "name") {
                    $this->meta[mb_strtolower($matches[$i][2])] = $matches[$i][3];
                }
            }
        }
    }
    /* }}} */
    /* {{{ strip_tags */
    function strip_tags($file_content) {
        // remove head
	$file_content = preg_replace("/<head[^>]*>(.*?)<\\/head[^>]*>/si", "", $file_content);

        // remove comments
	$file_content = preg_replace("/<!--.*?-->/si", " ", $file_content);	

        // remove scripts
	$file_content = preg_replace("/<script[^>]*?>.*?<\\/script>/si", " ", $file_content);
        
        // replace non breakable space
	$file_content = preg_replace("/&nbsp;/", " ", $file_content);

        // replace images with alt text
	$file_content = preg_replace("/<img[^>]*alt\\s*=[\"']?([^\"'>]*)[\"']?[^>]*>/i", "\\1 ", $file_content);

        // add space after tags
	$file_content = preg_replace("/<[\w ]+>/", "\\0 ", $file_content);
	$file_content = preg_replace("/<\/[\w ]+>/", "\\0 ", $file_content);

        // strip tags
        $file_content = strip_tags($file_content);

        // replace html entities
        $file_content = html_entity_decode($file_content);
	$file_content = preg_replace('/&#x([0-9a-f]+);/ei', 'chr(hexdec("\\1"))', $file_content);
        $file_content = preg_replace('/&#([0-9]+);/e', 'chr("\\1")', $file_content);

        // strip unnecessary whitespace
        $file_content = preg_replace("/\\s{1,}/", " ", $file_content);

        return $file_content;
    }
    /* }}} */
    /* {{{ get_keywords */
    function get_keywords($file_content) {
        // convert to lowercase
        $file_content = mb_strtolower($file_content);
        
        // replace everything with an accent with normal character
        $file_content = mb_ereg_replace("[áàâ]", "a", $file_content);
        $file_content = mb_ereg_replace("[éèê]", "e", $file_content);
        $file_content = mb_ereg_replace("[íìî]", "i", $file_content);
        $file_content = mb_ereg_replace("[óòô]", "o", $file_content);
        $file_content = mb_ereg_replace("[úùû]", "u", $file_content);
        $file_content = mb_ereg_replace("[ß]", "ss", $file_content);
        
        // replace everything that is not a word and keep underscore for keywords in programming languages
        $file_content = mb_ereg_replace("[^a-z0-9äöü_]+", " ", $file_content);
        
        // strip unnecessary whitespace
        $file_content = preg_replace("/\\s{1,}/", " ", $file_content);

        $words = explode(" ", $file_content);
        foreach ($words as $word) {
            if ($word != "" && mb_strlen($word) > 2) {
                $this->keywords[$word]++;
            }
        }

        ksort($this->keywords);
    }
    /* }}} */
    /* {{{ get_links */
    function get_links($file_content) {
        $link_regs = array(
            "/href\\s*=\\s*[\'\"]?([+:%\/\?~=&;\\\(\),._a-zA-Z0-9-]*)(#[.a-zA-Z0-9-]*)?[\'\" ]?(\\s*rel\\s*=\\s*[\'\"]?(nofollow)[\'\"]?)?/i", /* normal links */
            "/(frame[^>]*src\\s*)=\\s*[\'\"]?(([[a-z]{3,5}:\/\/(([.a-zA-Z0-9-])+(:[0-9]+)*))*([+:%\/?=&;\\\(\),._ a-zA-Z0-9-]*))(#[.a-zA-Z0-9-]*)?[\'\" ]?/i", /* frames */
            "/(window[.]location)\\s*=\\s*[\'\"]?(([[a-z]{3,5}:\/\/(([.a-zA-Z0-9-])+(:[0-9]+)*))*([+:%\/?=&;\\\(\),._ a-zA-Z0-9-]*))(#[.a-zA-Z0-9-]*)?[\'\" ]?/i", /* javascript window */
            "/(http-equiv=['\"]refresh['\"]\\s*content=['\"][0-9]+;url)\\s*=\\s*[\'\"]?(([[a-z]{3,5}:\/\/(([.a-zA-Z0-9-])+(:[0-9]+)*))*([+:%\/?=&;\\\(\),._ a-zA-Z0-9-]*))(#[.a-zA-Z0-9-]*)?[\'\" ]?/i", /* meta refresh */
        );
        foreach ($link_regs as $reg) {
            preg_match_all($reg, $file_content, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                if ($match[1] != "" && !isset($match[4])) { // if url is not empty and nofollow is not set
                    $this->links[$match[1]]++;
                }
            }
        }
    }
    /* }}} */
}

/* vim:set ft=php sw=4 sts=4 fdm=marker fenc=UTF-8 : */
?>
