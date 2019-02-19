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
    public function addUrl($pageId, $url)
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
            SET canonical=1
            WHERE publishId = :publishId AND pageId = :pageId AND url = :url;
        ");
        $query->execute(array(
            'publishId' => $this->publishId,
            'pageId' => $pageId,
            'url' => $url,
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

    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
