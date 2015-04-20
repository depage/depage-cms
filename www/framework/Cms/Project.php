<?php
/**
 * @file    framework/cms/cms_project.php
 *
 * depage cms project module
 *
 *
 * copyright (c) 2002-2010 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms;

class Project extends \Depage\Entity\Entity
{
    // {{{ variables
    /**
     * @brief fields
     **/
    static protected $fields = array(
        "id" => null,
        "name" => "",
        "fullname" => "",
        "groupId" => 1,
    );

    /**
     * @brief primary
     **/
    static protected $primary = array("id");

    /**
     * @brief pdo object for database access
     **/
    protected $pdo = null;

    /**
     * @brief cache
     **/
    protected $cache = null;

    /**
     * @brief xmldb
     **/
    public $xmldb = null;
    // }}}

    /* {{{ constructor */
    /**
     * constructor
     *
     * @public
     *
     * @param       Depage\Db\Pdo $pdo pdo object for database access
     *
     * @return      void
     */
    public function __construct($pdo) {
        parent::__construct($pdo);

        $this->pdo = $pdo;
    }
    /* }}} */

    // {{{ loadAll()
    /**
     * gets an array of user-objects
     *
     * @public
     *
     * @param       Depage\Db\Pdo     $pdo        pdo object for database access
     *
     * @return      Array array of projects
     */
    static public function loadAll($pdo) {
        $fields = implode(", ", self::getFields("projects"));

        $query = $pdo->prepare(
            "SELECT $fields, projectgroup.name as groupName
            FROM
                {$pdo->prefix}_projects AS projects,
                {$pdo->prefix}_project_groups AS projectgroup
            WHERE
                projects.groupId = projectgroup.id
            ORDER BY
                projectgroup.pos ASC, fullname ASC

            "
        );

        $projects = self::fetch($pdo, $query);

        return $projects;
    }
    // }}}
    // {{{ loadByName()
    /**
     * gets an array of user-objects
     *
     * @public
     *
     * @param       Depage\Db\Pdo     $pdo        pdo object for database access
     *
     * @return      Array array of projects
     */
    static public function loadByName($pdo, $name) {
        $fields = implode(", ", self::getFields("projects"));

        $query = $pdo->prepare(
            "SELECT $fields, projectgroup.name as groupName
            FROM
                {$pdo->prefix}_projects AS projects,
                {$pdo->prefix}_project_groups AS projectgroup
            WHERE
                projects.name = :name AND projects.groupId = projectgroup.id
            ORDER BY
                projectgroup.pos ASC, fullname ASC
            "
        );
        $projects = self::fetch($pdo, $query, array(
            ":name" => $name,
        ));

        if (count($projects) == 0) {
            throw new Exceptions\Project("project '$name' does not exist.");
        }

        return $projects[0];
    }
    // }}}
    // {{{ fetch()
    /**
     * @brief fetch
     *
     * @param mixed $query
     * @return void
     **/
    static protected function fetch($pdo, $query, $params = array())
    {
        $projects = array();
        $query->execute($params);

        // pass pdo-instance to constructor
        $query->setFetchMode(\PDO::FETCH_CLASS, "Depage\\Cms\\Project", array($pdo));
        do {
            $project = $query->fetch(\PDO::FETCH_CLASS);
            if ($project) {
                array_push($projects, $project);
            }
        } while ($project);

        return $projects;
    }
    // }}}
    // {{{ save()
    /**
     * save a project object
     *
     * @public
     *
     * @return
     */
    public function save() {
        $fields = array();
        $primary = self::$primary[0];
        $isNew = $this->data[$primary] === null;

        $dirty = array_keys($this->dirty, true);

        if (count($dirty) > 0) {
            $this->initProject();

            if ($isNew) {
                $query = "INSERT INTO {$this->pdo->prefix}_projects";
            } else {
                $query = "UPDATE {$this->pdo->prefix}_projects";
            }
            foreach ($dirty as $key) {
                $fields[] = "$key=:$key";
            }
            $query .= " SET " . implode(",", $fields);

            if (!$isNew) {
                $query .= " WHERE $primary=:$primary";
                $dirty[] = $primary;
            }

            $params = array_intersect_key($this->data,  array_flip($dirty));

            $cmd = $this->pdo->prepare($query);
            $success = $cmd->execute($params);

            if ($isNew) {
                $this->$primary = $this->pdo->lastInsertId();
            }

            if ($success) {
                $this->dirty = array_fill_keys(array_keys(static::$fields), false);
            }

            $this->initProject();
        }
    }
    // }}}

    // {{{ updateSchema()
    /**
     * @brief updateSchema
     *
     * @return void
     **/
    public static function updateSchema($pdo)
    {
        $schema = new \Depage\DB\Schema($pdo);

        $schema->setReplace(
            function ($tableName) use ($pdo) {
                return $pdo->prefix . $tableName;
            }
        );

        $files = glob(__DIR__ . "/Sql/*.sql");
        sort($files);
        foreach ($files as $file) {
            $schema->loadFile($file);
            $schema->update();
        }
    }
    // }}}

    // {{{ initProject()
    /**
     * @brief initProject
     *
     * @param mixed
     * @return void
     **/
    public function initProject()
    {
        $this->updateProjectSchema();

        $projectPath = DEPAGE_PATH . "projects/{$this->name}/";

        $success = is_writable($projectPath) || mkdir($projectPath, 0777, true);
        mkdir($projectPath . "lib/", 0777, true);
        mkdir($projectPath . "import/", 0777, true);
        mkdir($projectPath . "xml/", 0777, true);
        mkdir($projectPath . "xslt/", 0777, true);

        if (!$success) {
            throw new Exceptions\Project("Could not create project directory '$projectPath'.");
        }
    }
    // }}}
    // {{{ updateProjectSchema()
    /**
     * @brief updateProjectSchema
     *
     * @return void
     **/
    public function updateProjectSchema()
    {
        $projectName = $this->name;

        $xmldb = new \Depage\XmlDb\XmlDb("{$this->pdo->prefix}_proj_{$projectName}", $this->pdo, $this->cache, array(
            'pathXMLtemplate' => $this->xmlPath,
        ));
        $xmldb->updateSchema();

        $schema = new \Depage\DB\Schema($this->pdo);

        $schema->setReplace(
            function ($tableName) use ($projectName) {
                return $this->pdo->prefix . str_replace("PROJECTNAME", $projectName, $tableName);
            }
        );

        // schema for comments
        $files = glob(__DIR__ . "/../Comments/Sql/*.sql");
        sort($files);
        foreach ($files as $file) {
            $schema->loadFile($file);
            $schema->update();
        }
    }
    // }}}

    // {{{ initXmlDb()
    /**
     * @brief initXmlDb
     *
     * @return xmldb
     **/
    protected function initXmlDb()
    {
        // get cache instance
        $this->cache = \Depage\Cache\Cache::factory("xmldb");

        $this->xmldb = new \Depage\XmlDb\XmlDb("{$this->pdo->prefix}_proj_{$this->name}", $this->pdo, $this->cache, array(
            'pathXMLtemplate' => $this->xmlPath,
        ));
    }
    // }}}
    // {{{ getRecentlyChangedPages
    /**
     * @brief getRecentlyChangedPages
     *
     * @param max
     * @return array
     **/
    public function getRecentlyChangedPages($max = null)
    {
        $pages = array();

        $this->initXmlDb();

        $xml = $this->xmldb->getDocXml("pages");

        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace("pg", "http://cms.depagecms.net/ns/page");
        $nodelist = $xpath->query("//pg:page");

        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $node = $nodelist->item($i);
            $docId = $node->getAttributeNS("http://cms.depagecms.net/ns/database", "docref");
            $docInfo = $this->xmldb->getDoc($docId)->getDocInfo();
            $docInfo->url = $node->getAttribute("url");

            $pages[] = $docInfo;
        }

        usort($pages, function($a, $b) {
            $aTi = $a->lastchange->getTimestamp();
            $bTi = $b->lastchange->getTimestamp();
            if ($aTi == $bTi) {
                return 0;
            }
            return ($aTi > $bTi) ? -1 : 1;
        });

        if ($max > 0) {
            $pages = array_splice($pages, 0, $max);
        }

        return $pages;
    }
    // }}}
    // {{{ getPreviewPath()
    /**
     * @brief getPreviewPath
     *
     * @return Path for preview url for this project
     **/
    public function getPreviewPath()
    {
        $languages = array_keys($this->getLanguages());

        // @todo check template path
        return "project/{$this->name}/preview/html/pre/{$languages[0]}";
    }
    // }}}
    // {{{ getLanguages()
    /**
     * @brief getLanguages
     *
     * @param mixed
     * @return array of languages
     **/
    public function getLanguages()
    {
        $languages = array();
        $this->initXmlDb();

        $settings = $this->xmldb->getDoc("settings");
        $nodes = $settings->getNodeIdsByXpath("//proj:language");
        foreach ($nodes as $nodeId) {
            $attr = $settings->getAttributes($nodeId);
            $languages[$attr['shortname']] = $attr['name'];
        }

        return $languages;
    }
    // }}}
    // {{{ getHomeUrl()
    /**
     * @brief getHomeUrl
     *
     * @return Get path to home page
     **/
    public function getHomeUrl()
    {
        $this->initXmlDb();

        $xml = $this->xmldb->getDocXml("pages");

        $nodelist = $xml->getElementsByTagNameNS("http://cms.depagecms.net/ns/page", "page");

        return $this->getPreviewPath() . $nodelist->item(0)->getAttribute("url");
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
