<?php

namespace Depage\Transformer;

class TransformCache
{
    // {{{ variables
    protected $pdo;
    protected $projectName;
    protected $templateName;
    protected $cache;
    protected $tableName;
    // }}}

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $
     * @return void
     **/
    public function __construct($pdo, $projectName, $templateName)
    {
        $this->pdo = $pdo;
        $this->projectName = $projectName;
        $this->templateName = $templateName;
        $this->cache = \Depage\Cache\Cache::factory("transform/{$this->projectName}/{$this->templateName}");
        $this->tableName = $this->pdo->prefix . "_proj_" . $this->projectName . "_transform_used_docs";
    }
    // }}}
    // {{{ exists()
    /**
     * @brief exists
     *
     * @param mixed $docId
     * @return void
     **/
    public function exist($docId, $subId = "default")
    {
        $query = $this->pdo->prepare("SELECT COUNT(transformId) FROM {$this->tableName} WHERE docId = ? AND template = ?;");
        $query->execute(array(
            $docId,
            $this->templateName,
        ));

        if ($query->fetchColumn() == 0) {
            return false;
        }

        $cachePath = $this->getCachePathFor($docId, $subId);

        return $this->cache->exist($cachePath);
    }
    // }}}
    // {{{ get()
    /**
     * @brief get
     *
     * @param mixed $docId
     * @return void
     **/
    public function get($docId, $subId = "default")
    {
        $cachePath = $this->getCachePathFor($docId, $subId);

        return $this->cache->getFile($cachePath);
    }
    // }}}
    // {{{ set()
    /**
     * @brief set
     *
     * @param mixed
     * @return void
     **/
    public function set($docId, $usedDocuments, $content, $subId = "default")
    {
        $usedDocuments[] = $docId;
        $cachePath = $this->getCachePathFor($docId, $subId);

        $this->cache->setFile($cachePath, $content);

        $query = $this->pdo->prepare("REPLACE INTO {$this->tableName} (transformId, docId, template) VALUES (?, ?, ?);");

        foreach ($usedDocuments as $id) {
            $query->execute(array(
                $docId,
                $id,
                $this->templateName,
            ));
        }
    }
    // }}}
    // {{{ delete()
    /**
     * @brief delete
     *
     * @param mixed $docId
     * @return void
     **/
    protected function delete($docId, $subId = "")
    {
        $cachePath = $this->getCachePathFor($docId, $subId);

        return $this->cache->delete($cachePath);
    }
    // }}}
    // {{{ clearFor()
    /**
     * @brief clearFor
     *
     * @param mixed $docId
     * @return void
     **/
    public function clearFor($docId, $subId = "")
    {
        $query = $this->pdo->prepare("SELECT DISTINCT transformId FROM {$this->tableName} WHERE docId = ? AND template = ?;");
        $query->execute(array(
            $docId,
            $this->templateName,
        ));
        $deleteQuery = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE transformId = ? AND template = ?;");

        while ($result = $query->fetchObject()) {
            $deleteQuery->execute(array(
                $result->transformId,
                $this->templateName,
            ));
            $this->delete($result->transformId, $subId);
        }
    }
    // }}}
    // {{{ clearAll()
    /**
     * @brief clearAll
     *
     * @param mixed $docId
     * @return void
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
     * @param mixed $
     * @return void
     **/
    protected function getCachePathFor($docId, $subId = "default")
    {
        return $docId . "/" . $subId;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
