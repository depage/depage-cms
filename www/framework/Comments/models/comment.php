<?php
/**
 * @file    framework/Comments/Models/Comment.php
 *
 * comment class
 *
 *
 * copyright (c) 2012 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Comments\Models;

class Comment extends \Depage\Entity\Entity {
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
    static public function loadByPageId($pdo, $pageId, $showSpam = false) {
        return self::load($pdo, array(
            'page_id' => $pageId,
            'hidden' => false,
            'spam' => $showSpam,
        ), "date ASC");
    }
    // }}}

    // {{{ countByPageId()
    static public function countByPageId($pdo, $pageId, $showSpam = false) {
        return self::fetchCount($pdo, "SELECT COUNT(*) as count FROM {$pdo->prefix}_" . static::$table_name . " WHERE page_id = :page_id AND hidden = :hidden AND spam = :spam", array(
            'page_id' => $pageId,
            'hidden' => false,
            'spam' => $showSpam,
        ));
    }
    // }}}

    // {{{ getCommentHtml()
    public function getCommentHtml() {
        $h = "";
        $lines = explode("\n", $this->comment);
        foreach ($lines as $line) {
            $h .= "<p>";
            $h .= preg_replace(array('/((https?|ftp):[^\'"\s]+)/i'), array('<a href="$0" rel="nofollow" target="_blank">$0</a>'), htmlspecialchars($line));
            $h .= "</p>";
        }

        return $h;
    }
    // }}}

    // {{{ getProfileImage()
    public function getProfileImageUrl($size = null) {
        $hash = md5(strtolower(trim($this->author_email)));

        $settings = array(
            'default' => "mm",
            'rating' => "g",
        );
        if ($size !== null) $settings['size'] = $size;

        $param = http_build_query($settings);
        return "https://secure.gravatar.com/avatar/$hash?$param";
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
