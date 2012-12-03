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

    // {{{ loadByPageId()
    static public function loadByPageId($pdo, $pageId) {
        return self::load($pdo, array(
            'page_id' => $pageId,
        ));
    }
    // }}}
    
    // {{{ getCommentHtml()
    public function getCommentHtml() {
        $h = "";
        $lines = explode("\n", $this->comment);
        foreach ($lines as $line) {
            $h .= "<p>";
            $h .= preg_replace(array('/((https?|ftp):[^\'"\s]+)/i'), array('<a href="$0" rel="nofollow">$0</a>'), htmlspecialchars($line));
            $h .= "</p>";
        }

        return $h;
    }
    // }}}
    
    // {{{ getProfileImage()
    public function getProfileImageUrl() {
        $hash = md5(strtolower(trim($this->author_email)));

        $param = http_build_query(array(
            'default' => "mm",
            'rating' => "g",
        ), '', '&amp;');
        return "http://www.gravatar.com/avatar/$hash?$param";
    }
    // }}}
    
    // {{{ setAuthor_ip()
    public function setAuthor_ip($value) {
        $this->data['author_ip'] = inet_pton($value);
        $this->dirty['author_ip'] = true;
    }
    // }}}
    
    // {{{ getAuthor_ip()
    public function getAuthor_ip() {
         return inet_ntop($this->data['author_ip']);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
