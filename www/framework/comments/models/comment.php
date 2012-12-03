<?php
/**
 * @file    framework/comments/models/comment.php
 *
 * comment class
 *
 *
 * copyright (c) 2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace depage\comments\models;

class comment extends \depage\entity\entity {
    // variables {{{
    /**
     * Table Name
     * 
     * @see entity::$table_name
     * @var string
     */
    protected static $table_name = 'comments';
    
    /**
     * Cols
     *
     * @see entity::$cols
     * @var array
     */
    protected static $cols = array (
        'id'              => \PDO::PARAM_INT,
        'page_id'         => \PDO::PARAM_INT,
        'comment'         => \PDO::PARAM_STR,
        'date'            => \PDO::PARAM_STR,
        'author_name'     => \PDO::PARAM_STR,
        'author_email'    => \PDO::PARAM_STR,
        'author_url'      => \PDO::PARAM_STR,
        'author_ip'       => \PDO::PARAM_STR,
        'author_user_id'  => \PDO::PARAM_INT,
        'type'            => \PDO::PARAM_STR,
        'hidden'          => \PDO::PARAM_INT,
        'spam'            => \PDO::PARAM_INT,
    );
    
    /**
     * Primary
     *
     * @see entity::$primary
     * @var array
     */
    protected static $primary = array('id');
    // }}}

    // {{{ getCommentHtml()
    public function getCommentHtml() {
        // escape message input
        $content = htmlspecialchars($this->comment, ENT_NOQUOTES);

        // embed links and line breaks from plain text content
        $content = nl2br(preg_replace(array('/((https?|ftp):[^\'"\s]+)/i'), array('<a href="$0" rel="nofollow">$0</a>'), $content));
        return $content;
        
        $h = "";
        $lines = explode("\n", $this->comment);
        foreach ($lines as $line) {
            $h .= htmlspecialchars($line);
            $h .= "<br>";
        }

        return $h;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
