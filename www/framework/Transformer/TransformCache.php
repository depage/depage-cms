<?php

namespace Depage\Transformer;

class TransformCache
{
    // {{{ variables
    protected $pdo;
    protected $projectName;
    protected $cache;
    protected $tableName;
    // }}}

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $pdo
     * @param string $projectName
     * @return void
     **/
    public function __construct($pdo, $projectName)
    {
        $this->pdo = $pdo;
        $this->projectName = $projectName;
        $this->cache = \Depage\Cache\Cache::factory("transform/{$this->projectName}");
        $this->tableName = $this->pdo->prefix . "_proj_" . $this->projectName . "_transform_used_docs";
    }
    // }}}
    // {{{ exists()
    /**
     * @brief exists
     *
     * @param int $docId
     * @param string $templateName
     * @param string $subId
     * @return void
     **/
    public function exist($docId, $templateName, $subId = "default"):bool
    {
        $query = $this->pdo->prepare("SELECT COUNT(transformId) FROM {$this->tableName} WHERE docId = ? AND template = ?;");
        $query->execute([
            $docId,
            $templateName,
        ]);

        if ($query->fetchColumn() == 0) {
            return false;
        }

        $cachePath = $this->getCachePathFor($docId, $templateName, $subId);

        return $this->cache->exist($cachePath);
    }
    // }}}
    // {{{ get()
    /**
     * @brief get
     *
     * @param int $docId
     * @param string $templateName
     * @param string $subId
     * @return void
     **/
    public function get($docId, $templateName, $subId = "default"):mixed
    {
        $cachePath = $this->getCachePathFor($docId, $templateName, $subId);

        return $this->cache->getFile($cachePath);
    }
    // }}}
    // {{{ set()
    /**
     * @brief set
     *
     * @param int $docId
     * @param array $usedDocuments
     * @param string $content
     * @param string $templateName
     * @param string $subId
     * @return void
     **/
    public function set($docId, $usedDocuments, $content, $templateName, $subId = "default"):void
    {
        $usedDocuments[] = $docId;
        $cachePath = $this->getCachePathFor($docId, $templateName, $subId);

        $this->cache->setFile($cachePath, $content);

        $query = $this->pdo->prepare("INSERT IGNORE INTO {$this->tableName} (transformId, docId, template) VALUES (?, ?, ?);");

        foreach ($usedDocuments as $id) {
            $query->execute([
                $docId,
                $id,
                $templateName,
            ]);
        }
    }
    // }}}
    // {{{ delete()
    /**
     * @brief delete
     *
     * @param int $docId
     * @param string $templateName
     * @param string $subId
     * @return bool
     **/
    protected function delete($docId, $templateName, $subId = "")
    {
        $cachePath = $this->getCachePathFor($docId, $templateName, $subId);

        return $this->cache->delete($cachePath);
    }
    // }}}
    // {{{ clearFor()
    /**
     * @brief clearFor
     *
     * @param int $docId
     * @param string $templateName
     * @param string $subId
     * @return void
     **/
    public function clearFor($docId, $templateName, $subId = ""):void
    {
        $query = $this->pdo->prepare("SELECT DISTINCT transformId, template FROM {$this->tableName} WHERE docId = ? AND template LIKE ?");
        $query->execute([
            $docId,
            $templateName . "%",
        ]);
        $deleteQuery = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE transformId = ? AND template = ?");

        while ($result = $query->fetchObject()) {
            $deleteQuery->execute([
                $result->transformId,
                $result->template,
            ]);
            $this->delete($result->transformId, $result->template, $subId);
        }
    }
    // }}}
    // {{{ clearAll()
    /**
     * @brief clearAll
     *
     * @return bool
     **/
    public function clearAll()
    {
        $deleteQuery = $this->pdo->prepare("TRUNCATE {$this->tableName};");
        $deleteQuery->execute();

        return $this->cache->clear();
    }
    // }}}
    // {{{ getCachePathFor()
    /**
     * @brief getCachePathFor
     *
     * @param int $docId
     * @param string $templateName
     * @param string $subId
     * @return string
     **/
    protected function getCachePathFor($docId, $templateName, $subId = "default"):string
    {
        return "$templateName/$docId/$subId";
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
