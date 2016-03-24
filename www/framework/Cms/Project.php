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
    public function __construct($pdo, $cache) {
        parent::__construct($pdo);

        $this->pdo = $pdo;
        $this->cache = $cache;
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
    static public function loadAll($pdo, $cache) {
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

        $projects = self::fetch($pdo, $cache, $query);

        return $projects;
    }
    // }}}
    // {{{ loadByUser()
    /**
     * gets an array of user-objects
     *
     * @public
     *
     * @param       Depage\Db\Pdo     $pdo        pdo object for database access
     *
     * @return      Array array of projects
     */
    static public function loadByUser($pdo, $cache, $user) {
        if ($user->canEditAllProjects()) {
            return self::loadAll($pdo, $cache);
        } else {
            $fields = implode(", ", self::getFields("projects"));

            $query = $pdo->prepare(
                "SELECT $fields, projectgroup.name as groupName
                FROM
                    {$pdo->prefix}_projects AS projects,
                    {$pdo->prefix}_project_groups AS projectgroup,
                    {$pdo->prefix}_project_auth AS projectauth
                WHERE
                    projects.groupId = projectgroup.id AND
                    (projects.id = projectauth.projectId AND projectauth.userId = :userid)
                ORDER BY
                    projectgroup.pos ASC, fullname ASC

                "
            );

            $projects = self::fetch($pdo, $cache, $query, [
                'userid' => $user->id,
            ]);

            return $projects;
        }
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
    static public function loadByName($pdo, $cache, $name) {
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
        $projects = self::fetch($pdo, $cache, $query, array(
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
    static protected function fetch($pdo, $cache, $query, $params = array())
    {
        $projects = array();
        $query->execute($params);

        // pass pdo-instance to constructor
        $query->setFetchMode(\PDO::FETCH_CLASS, "Depage\\Cms\\Project", array($pdo, $cache));
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
        $schema->loadGlob(__DIR__ . "/Sql/*.sql");
        $schema->update();
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

        $projectPath = $this->getProjectPath();

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

        $this->xmldb = $this->getXmlDb();
        $this->xmldb->updateSchema();

        $schema = new \Depage\DB\Schema($this->pdo);

        $schema->setReplace(
            function ($tableName) use ($projectName) {
                return $this->pdo->prefix . str_replace("PROJECTNAME", $projectName, $tableName);
            }
        );

        // schema for comments
        $files = array_merge(
            glob(__DIR__ . "/../Comments/Sql/*.sql"),
            glob(__DIR__ . "/../Publisher/Sql/*.sql"),
            glob(__DIR__ . "/../Transformer/Sql/*.sql")
        );
        sort($files);
        foreach ($files as $file) {
            $schema->loadFile($file);
            $schema->update();
        }
    }
    // }}}

    // {{{ getXmlDb()
    /**
     * @brief getXmlDb
     *
     * @return xmldb
     **/
    public function getXmlDb($userId = null)
    {
        if (is_null($this->xmldb)) {
            $prefix = $this->pdo->prefix . "_proj_" . $this->name;

            $xsltPath = "projects/" . $this->name . "/xslt/";
            $xmlPath = "projects/" . $this->name . "/xml/";
            $libPath = "projects/" . $this->name . "/lib/";

            $this->xmldb = new \Depage\XmlDb\XmlDb($prefix, $this->pdo, $this->cache, array(
                'pathXMLtemplate' => $xmlPath,
                'project' => $this,
                'userId' => $userId,
            ));
        }

        return $this->xmldb;
    }
    // }}}
    // {{{ getPdo()
    /**
     * @brief getPdo
     *
     * @return xmldb
     **/
    public function getPdo()
    {
        return $this->pdo;
    }
    // }}}

    // {{{ getSettingsDoc()
    /**
     * @brief getSettingsDoc
     *
     * @param mixed
     * @return void
     **/
    public function getSettingsDoc()
    {
        $this->xmldb = $this->getXmlDb();

        return $this->xmldb->getDoc("settings");
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

        $this->xmldb = $this->getXmlDb();

        $xml = $this->xmldb->getDocXml("pages");

        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace("pg", "http://cms.depagecms.net/ns/page");
        $nodelist = $xpath->query("//pg:page");

        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $node = $nodelist->item($i);
            $docId = $node->getAttributeNS("http://cms.depagecms.net/ns/database", "docref");

            if ($docId) {
                $docInfo = $this->xmldb->getDoc($docId)->getDocInfo();
                $docInfo->url = $node->getAttribute("url");

                $pages[] = $docInfo;
            }
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
    // {{{ getProjectPath()
    /**
     * @brief getProjectPath
     *
     * @return Path for project files
     **/
    public function getProjectPath()
    {
        return DEPAGE_PATH . "projects/{$this->name}/";
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
        if ($languages = $this->cache->get("dp_proj_{$this->name}_settings/languages.ser")) {
            return $languages;
        } else {
            $languages = array();
            $this->xmldb = $this->getXmlDb();

            $settings = $this->xmldb->getDoc("settings");
            $nodes = $settings->getNodeIdsByXpath("//proj:language");
            foreach ($nodes as $nodeId) {
                $attr = $settings->getAttributes($nodeId);
                $languages[$attr['shortname']] = $attr['name'];
            }

            $this->cache->set("dp_proj_{$this->name}_settings/languages.ser", $languages);
        }

        return $languages;
    }
    // }}}
    // {{{ getPublishingTargets()
    /**
     * @brief getPublishingTargets
     *
     * @param mixed
     * @return array of languages
     **/
    public function getPublishingTargets()
    {
        $targets = array();
        $this->xmldb = $this->getXmlDb();

        $settings = $this->getSettingsDoc();
        $nodes = $settings->getNodeIdsByXpath("//proj:publishTarget");
        foreach ($nodes as $nodeId) {
            $attr = $settings->getAttributes($nodeId);
            $targets[$nodeId] = $attr['name'];
        }

        return $targets;
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
        $this->xmldb = $this->getXmlDb();

        $xml = $this->xmldb->getDocXml("pages");

        $nodelist = $xml->getElementsByTagNameNS("http://cms.depagecms.net/ns/page", "page");

        return $this->getPreviewPath() . $nodelist->item(0)->getAttribute("url");
    }
    // }}}
    // {{{ getLastPublishDate()
    /**
     * @brief getLastPublishDate
     *
     * @param mixed
     * @return void
     **/
    public function getLastPublishDate()
    {
        $publishPdo = clone $this->pdo;
        $publishPdo->prefix = $this->pdo->prefix . "_proj_" . $this->name;

        $targets = $this->getPublishingTargets();
        list($publishId) = array_keys($targets);

        $fs = \Depage\Fs\Fs::factory("");
        $publisher = new \Depage\Publisher\Publisher($publishPdo, $fs, $publishId);

        $date = $publisher->getLastPublishDate();

        return $date;
    }
    // }}}

    // {{{ addPublishTask()
    /**
     * @brief addPublishTask
     *
     * @param mixed $param
     * @return void
     **/
    public function addPublishTask($taskName, $publishId)
    {
        $this->xmldb = $this->getXmlDb();

        $projectPath = $this->getProjectPath();
        $settings = $this->getSettingsDoc()->getAttributes($publishId);
        $fsLocal = \Depage\Fs\Fs::factory($projectPath);

        // getting als files in library
        $files = $fsLocal->lsFiles("lib/*");
        $dirs = $fsLocal->lsDir("lib/*");
        while (count($dirs) > 0) {
            $dir = array_pop($dirs);
            $files = array_merge($files, $fsLocal->lsFiles($dir . "/*"));
            $dirs = array_merge($dirs, $fsLocal->lsDir($dir . "/*"));
        }

        // get pdo object for publisher
        $publishPdo = clone $this->pdo;
        $publishPdo->prefix = $this->pdo->prefix . "_proj_" . $this->name;

        // get transformer
        $transformCache = new \Depage\Transformer\TransformCache($this->pdo, $this->name, $settings['template_set'] . "-live-" . $publishId);
        $transformer = \Depage\Transformer\Transformer::factory("live", $this->xmldb, $this->name, $settings['template_set'], $transformCache);
        $urls = $transformer->getUrlsByPageId();
        $languages = $this->getLanguages();

        $task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $taskName, $this->projectName);
        $initId = $task->addSubtask("init", "
            \$fs = \\Depage\\Fs\\Fs::factory(%s);
            \$project = %s;
            \$publisher = new \\Depage\\Publisher\\Publisher(%s, \$fs, %s);
            \$transformer = %s;
            \$transformCache = %s;
        ", array(
            $settings['output_folder'],
            $this,
            $publishPdo,
            $publishId,
            $transformer,
            $transformCache,
        ));

        $task->addSubtask("testing publish target", "\$publisher->testConnection();", array(), $initId);
        $task->addSubtask("resetting publishing state", "\$publisher->resetPublishedState();", array(), $initId);

        // publish file library
        foreach ($files as $file) {
            $task->addSubtask("publishing $file", "\$publisher->publishFile(%s, %s);", array(
                $projectPath . $file,
                $file,
            ), $initId);
        }

        // transform pages
        foreach ($urls as $pageId => $url) {
            foreach ($languages as $lang => $name) {
                $target = $lang . $url;
                $task->addSubtask("publishing $target", "
                    \$publisher->publishString(
                        \$transformer->transformUrl(%s, %s),
                        %s
                    );", array(
                        $url,
                        $lang,
                        $target
                ), $initId);
            }
        }

        // publish sitemap
        $task->addSubtask("publishing sitemap", "
            \$publisher->publishString(
                \$project->generateSitemap(),
                %s
            );", array(
                "sitemap.xml",
        ), $initId);

        foreach ($languages as $lang => $name) {
            $task->addSubtask("publishing atom feed", "
                \$publisher->publishString(
                    \$project->generateAtomFeed(%s),
                    %s
                );", array(
                    $lang,
                    "$lang/atom.xml",
            ), $initId);
        }

        /*
        // transform htaccess
        $task->addSubtask("transforming htaccess", "\$publisher->transformHtaccess();", $initId);

        // transform index page
        $task->addSubtask("transforming htaccess", "\$publisher->transformHtaccess();", $initId);

         */

        /*
        // publish htaccess
        $task->addSubtask("publishing htaccess", "\$publisher->publishHtaccess();", $initId);

        // publish index page
        $task->addSubtask("publishing htaccess", "\$publisher->publishHtaccess();", $initId);
         */

        // unpublish removed files
        $task->addSubtask("removing leftover files", "\$publisher->unpublishRemovedFiles();", array(), $initId);

        return $task;
    }
    // }}}

    // {{{ generateSitemap()
    /**
     * @brief generateSitemap
     *
     * @param mixed
     * @return void
     **/
    public function generateSitemap()
    {
        $this->xmldb = $this->getXmlDb();

        $transformer = \Depage\Transformer\Transformer::factory("pre", $this->xmldb, $this->name, "sitemap");
        $xml = $this->xmldb->getDocXml("pages");

        $parameters = array(
            "currentContentType" => "text/xml",
            "currentEncoding" => "UTF-8",
            "depageVersion" => \Depage\Depage\Runner::getVersion(),
            "depageIsLive" => true,
            "baseUrl" => "https://depage.net/",
        );

        $sitemap = $transformer->transform($xml, $parameters);
        return $sitemap;
    }
    // }}}
    // {{{ generateAtomFeed()
    /**
     * @brief generateAtomFeed
     *
     * @param mixed
     * @return void
     **/
    public function generateAtomFeed($lang)
    {
        $this->xmldb = $this->getXmlDb();

        $transformer = \Depage\Transformer\Transformer::factory("pre", $this->xmldb, $this->name, "atom");
        $xml = $this->xmldb->getDocXml("pages");

        $parameters = array(
            "currentLang" => $lang,
            "currentContentType" => "text/xml",
            "currentEncoding" => "UTF-8",
            "depageVersion" => \Depage\Depage\Runner::getVersion(),
            "depageIsLive" => true,
            "baseUrl" => "https://depage.net/",
        );

        $sitemap = $transformer->transform($xml, $parameters);
        return $sitemap;
    }
    // }}}
    // {{{ generateCss()
    /**
     * @brief generateCss
     *
     * @param mixed
     * @return void
     **/
    public function generateCss()
    {
        $this->removeGeneratedCss();

        $this->xmldb = $this->getXmlDb();

        $transformer = \Depage\Transformer\Transformer::factory("pre", $this->xmldb, $this->name, "css");
        $xml = $this->xmldb->getDocXml("colors");
        $xpath = new \DOMXPath($xml);
        $nodes = $xpath->query("//proj:colorscheme[@name != 'tree_name_color_global']/@name");
        $parameters = array(
            "currentContentType" => "text/css",
            "currentEncoding" => "UTF-8",
            "depageVersion" => \Depage\Depage\Runner::getVersion(),
            "depageIsLive" => true,
        );
        $cssPath = $this->getProjectPath() . "lib/global/css/";

        // generate global colorscheme
        $css = $transformer->transform($xml, $parameters);
        file_put_contents($cssPath . "colors-all.css", $css);

        // generate css files for colorschemes
        foreach ($nodes as $node) {
            $colorscheme = $node->value;
            $parameters['currentColorscheme'] = "$colorscheme";
            $css = $transformer->transform($xml, $parameters);
            file_put_contents($cssPath . "color_" . $colorscheme . ".css", $css);
        }
    }
    // }}}
    // {{{ removeGeneratedCss()
    /**
     * @brief removeGeneratedCss
     *
     * @param mixed
     * @return void
     **/
    public function removeGeneratedCss()
    {
        $cssPath = $this->getProjectPath() . "lib/global/css/";
        $files = glob($cssPath . "color_*.css");

        foreach ($files as $file) {
            unlink($file);
        }
    }
    // }}}

    // {{{ __sleep()
    /**
     * allows Depage\Db\Pdo-object to be serialized
     */
    public function __sleep()
    {
        return array_merge(parent::__sleep(), array(
            'pdo',
            'cache',
        ));
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
