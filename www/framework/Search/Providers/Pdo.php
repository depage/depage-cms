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
            `metaphone` longtext NOT NULL,
            PRIMARY KEY (`url`),
            FULLTEXT KEY `content` (`title`,`description`,`headlines`,`content`)
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
                content = :content,
                metaphone = :metaphone
            ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), headlines=VALUES(headlines), content=VALUES(content), metaphone=VALUES(metaphone)"
        );
        $query->execute([
            'url' => $url,
            'title' => $title,
            'description' => $description,
            'headlines' => $headlines,
            'content' => $content,
            'metaphone' => $this->metaphone("$title $description $headlines $content"),
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
    // {{{ query()
    /**
     * @brief query
     *
     * @param mixed $
     * @return void
     **/
    public function query($search, $start = 0, $count = 20)
    {
        $query = $this->pdo->prepare(
            "SELECT *,
                MATCH (title, description, headlines, content) AGAINST (:search1 IN NATURAL LANGUAGE MODE) as score
            FROM {$this->table}
                WHERE MATCH (title, description, headlines, content) AGAINST (:search2 IN NATURAL LANGUAGE MODE)
                OR metaphone LIKE :metaphone
            ORDER by score DESC
            LIMIT :start, :count"
        );
        $query->execute([
            'search1' => $search,
            'search2' => $search,
            'metaphone' => "%" . $this->metaphone($search) . "%",
            'start' => $start,
            'count' => $count,
        ]);

        return $query->fetchAll(\PDO::FETCH_OBJ);
    }
    // }}}

    // {{{ metaphone()
    /**
     * @brief metaphone
     *
     * @param mixed $
     * @return void
     **/
    protected function metaphone($text)
    {
        $words = explode(" ", $text);

        foreach ($words as $key => &$word) {
            $word = metaphone($word);
        }
        return implode(" ", $words);

    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
