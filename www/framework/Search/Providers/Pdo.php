<?php

namespace Depage\Search\Providers;

/**
 * brief Pdo
 * Class Pdo
 */
class Pdo
{
    /*
     CREATE TABLE `dp_search` (
        `url` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
        `title` text NOT NULL,
        `description` text NOT NULL,
        `headlines` text NOT NULL,
        `content` longtext NOT NULL,
        PRIMARY KEY (`url`),
        FULLTEXT KEY content (title, description, headlines, content)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
     */
    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $
     * @return void
     **/
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->table = $pdo->prefix . "_search";
    }
    // }}}

    // {{{ add()
    /**
     * @brief add
     *
     * @param mixed $param
     * @return void
     **/
    public function add($url, $title, $description, $headlines, $content)
    {
        $query = $this->pdo->prepare(
            "INSERT {$this->table}
            SET
                url = :url,
                title = :title,
                description = :description,
                headlines = :headlines,
                content = :content
            ON DUPLICATE KEY UPDATE title=VALUES(title), headlines=VALUES(headlines), description=VALUES(content), description=VALUES(content)"
        );
        $query->execute([
            'url' => $url,
            'title' => $title,
            'description' => $description,
            'headlines' => $headlines,
            'content' => $content,
        ]);
    }
    // }}}
    // {{{ remove()
    /**
     * @brief remove
     *
     * @param mixed $param
     * @return void
     **/
    public function remove($url)
    {
        $query = $this->pdo->prepare(
            "DELETE FROM {$this->table}
            WHERE url = :url"
        );
        $query->execute([
            'url' => $url,
        ]);

    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
