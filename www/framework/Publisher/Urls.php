<?php
/**
 * @file    Urls.php
 *
 * description
 *
 * copyright (c) 2017 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Publisher;

/**
 * @brief Urls
 * Class Urls
 */
class Urls
{
    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $pdo
     * @return void
     **/
    public function __construct($pdo, $publishId)
    {
        $this->pdo = $pdo;
        $this->tableUrls = $this->pdo->prefix . "_published_urls";
        $this->publishId = $publishId;
    }
    // }}}

    // {{{ addUrl()
    /**
     * @brief addUrl
     *
     * @param mixed $
     * @return void
     **/
    public function addUrl($pageId, $url, $pos)
    {
        // reset canonical entries
        $query = $this->pdo->prepare("UPDATE {$this->tableUrls}
            SET canonical=0
            WHERE publishId = :publishId AND pageId = :pageId;
        ");
        $query->execute(array(
            'publishId' => $this->publishId,
            'pageId' => $pageId,
        ));

        // add url
        $query = $this->pdo->prepare("INSERT INTO {$this->tableUrls} (publishId, pageId, url)
            SELECT * FROM (SELECT :publishId1 as publishId, :pageId1 as pageId, :url1 as url) AS tmp
            WHERE NOT EXISTS (
                SELECT id FROM {$this->tableUrls} WHERE publishId = :publishId2 AND pageId = :pageId2 AND url = :url2
            ) LIMIT 1;
        ");
        $query->execute(array(
            'publishId1' => $this->publishId,
            'publishId2' => $this->publishId,
            'pageId1' => $pageId,
            'pageId2' => $pageId,
            'url1' => $url,
            'url2' => $url,
        ));

        // update canonical to current url
        $query = $this->pdo->prepare("UPDATE {$this->tableUrls}
            SET canonical = 1, pos = :pos
            WHERE publishId = :publishId AND pageId = :pageId AND url = :url;
        ");
        $query->execute(array(
            'publishId' => $this->publishId,
            'pageId' => $pageId,
            'url' => $url,
            'pos' => $pos,
        ));
    }
    // }}}
    // {{{ getAllUrls()
    /**
     * @brief getAllUrls
     *
     * @param mixed
     * @return void
     **/
    public function getAllUrls()
    {
        $urls = [];
        $query = $this->pdo->prepare("SELECT pageId, url, canonical FROM {$this->tableUrls} WHERE publishId = :publishId ORDER BY pageId ASC, canonical ASC");
        $query->execute(array(
            'publishId' => $this->publishId,
        ));

        $data = $query->fetchObject();
        while ($data) {
            $data->canonical = (bool) $data->canonical;
            if (!isset($urls[$data->pageId])) {
                $urls[$data->pageId] = [];
            }
            $urls[$data->pageId][] = $data;

            $data = $query->fetchObject();
        };

        return $urls;
    }
    // }}}
    // {{{ getCanonicalUrls()
    /**
     * @brief getCanonicalUrls
     *
     * @param mixed
     * @return void
     **/
    public function getCanonicalUrls()
    {
        // @todo fix order of canonical urls to keep order of pages in tree
        $urls = [];
        $query = $this->pdo->prepare("SELECT pageId, url FROM {$this->tableUrls} WHERE publishId = :publishId AND canonical = 1 ORDER BY pos ASC");
        $query->execute(array(
            'publishId' => $this->publishId,
        ));

        $data = $query->fetchObject();
        while ($data) {
            $urls[$data->pageId] = $data->url;

            $data = $query->fetchObject();
        };

        return $urls;
    }
    // }}}
    // {{{ getAlternateUrls()
    /**
     * @brief getAlternateUrls
     *
     * @param mixed
     * @return void
     **/
    public function getAlternateUrls()
    {
        $urls = [];
        $query = $this->pdo->prepare(
            "SELECT
                u1.pageId, u1.url as alternate, u2.url as url
            FROM
                {$this->tableUrls} AS u1,
                {$this->tableUrls} AS u2
            WHERE
                u1.publishId = :publishId1 AND
                u2.publishId = :publishId2 AND
                u1.canonical = 0 AND
                u1.pageId = u2.pageId AND
                u2.canonical = 1
            ORDER BY u1.url ASC");
        $query->execute(array(
            'publishId1' => $this->publishId,
            'publishId2' => $this->publishId,
        ));

        $data = $query->fetchObject();
        while ($data) {
            $urls[$data->alternate] = $data->url;

            $data = $query->fetchObject();
        };

        return $urls;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
