<?php
/**
 * @file    lib_atom.php
 *
 * defines class to generate atom feeds
 *
 *
 * copyright (c) 2008-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

class atom_entry {
    /* {{{ constructor */
    function atom_entry($feed, $title, $text, $link, $date = null, $author = null) {
        $this->feed = $feed;

        $this->title = $title;
        $this->text = $text;
        $this->link = $link;
        if ($date !== null) {
            $this->date = strtotime($date);
        } else {
            $this->date = time();
        }
        $this->author = $author;
    }
    /* }}} */
    /* {{{ generate() */
    function generate() {
        $output = "";

        if (strpos($this->link, "http://") !== 0 || strpos($this->link, "https://") !== 0) {
            $link = $this->feed->baseurl . $this->link;
        } else {
            $link = $this->link;
        }

        $output .= "<entry>";
            $output .= "<title>" . htmlspecialchars($this->title) . "</title>";

            $output .= "<link rel=\"alternate\" href=\"" . htmlspecialchars($link) . "\" />";
            $output .= "<content src=\"" . htmlspecialchars($link) . "\" type=\"text/html\" />";
            $output .= "<id>tag:" . htmlspecialchars($this->feed->host) . "," . date("Y-m-d", $this->date) . ":/" . htmlspecialchars($this->link) . "</id>";

            $output .= "<updated>" . htmlspecialchars(atom::rfc_date($this->date)) . "</updated>";
            
            $output .= "<summary type=\"html\" mode=\"escaped\">" . htmlspecialchars($this->text) . "</summary>";

            if ($this->author !== null) {
                $output .= "<author><name>" . htmlspecialchars($this->author) . "</name></author>\n";
            }
        $output .= "</entry>\n";

        return $output;
    }
    /* }}} */
}

class atom {
    var $entries = array();
    var $rights = null;
    var $author = null;
    var $icon = null;
    var $logo = null;

    /* {{{ constructor() */
    function atom($baseurl, $title, $updated = null) {
        $this->entries = array();

        $this->title = $title;

        $this->baseurl = $baseurl;

        $urlinfo = parse_url($this->baseurl);
        $this->host = $urlinfo["host"];
        $this->path = $urlinfo["host"];

        if (substr($this->baseurl, -1) != "/") {
            $this->baseurl .= "/";
        }

        if ($updated !== null) {
            $this->date = strtotime($updated);
        } else {
            $this->date = time();
        }
    }
    /* }}} */

    /* {{{ cmp_entries() */
    function cmp_entries($e1, $e2) {
        if ($e1->date == $e2->date) {
            return 0;
        }
        return ($e1->date < $e2->date) ? +1 : -1;
    }
    /* }}} */
    /* {{{ rfc_date() */
    function rfc_date($date) {
        $offset = date("O", $date);

        return date("Y-m-d\TH:i:s",$date) . substr($offset,0,3) . ":" . substr($offset,-2); 
    }
    /* }}} */


    /* {{{ add_author() */
    function add_author($author) {
        $this->author = $author;
    }
    /* }}} */
    /* {{{ add_icon() */
    function add_icon($icon) {
        $this->icon = $icon;
    }
    /* }}} */
    /* {{{ add_logo() */
    function add_logo($logo) {
        $this->logo = $logo;
    }
    /* }}} */
    /* {{{ add_rights() */
    function add_rights($rights) {
        $this->rights = $rights;
    }
    /* }}} */
    /* {{{ add_entry() */
    function add_entry($title, $text, $link, $date = null, $author = null) {
        $this->entries[] = new atom_entry($this, $title, $text, $link, $date, $author);
    }
    /* }}} */

    /* {{{ header() */
    function header() {
        //header("Content-type: application/atom+xml");
        header("Content-type: application/xml");
    }
    /* }}} */
    /* {{{ generate() */
    function generate($maxnum = 10) {
        $output = "";

        usort($this->entries, array($this, "cmp_entries"));

        $output .= "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        $output .= "<feed xmlns=\"http://www.w3.org/2005/Atom\">\n";

            $output .= "<title>" . htmlspecialchars($this->title) . "</title>";
            $output .= "<link href=\"" . htmlspecialchars($this->baseurl) . "\" />";
            $output .= "<link rel=\"self\" href=\"" . htmlspecialchars($this->baseurl) . "feed.php\" />";
            $output .= "<updated>" . htmlspecialchars(atom::rfc_date($this->date)) . "</updated>";
            $output .= "<id>" . htmlspecialchars($this->baseurl) . "</id>\n";

            if ($this->author !== null) {
                $output .= "<author><name>" . htmlspecialchars($this->author) . "</name></author>\n";
            }
            if ($this->rights !== null) {
                $output .= "<rights>" . htmlspecialchars($this->rights) . "</rights>\n";
            }
            if ($this->icon !== null) {
                $output .= "<icon>" . htmlspecialchars($this->baseurl . $this->icon) . "</icon>\n";
            }
            if ($this->logo !== null) {
                $output .= "<logo>" . htmlspecialchars($this->baseurl . $this->logo) . "</logo>\n";
            }
        
            for ($i = 0; $i < count($this->entries); $i++) {
                if ($i < $maxnum) {
                    $output .= $this->entries[$i]->generate();
                }
            }

        $output .= "</feed>";

        return $output;
    }
    /* }}} */
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
