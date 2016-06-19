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
    static protected $fields = [
        "id" => null,
        "name" => "",
        "fullname" => "",
        "groupId" => 1,
    ];

    /**
     * @brief primary
     **/
    static protected $primary = ["id"];

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
        $projects = self::fetch($pdo, $cache, $query, [
            ":name" => $name,
        ]);

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
    static protected function fetch($pdo, $cache, $query, $params = [])
    {
        $projects = [];
        $query->execute($params);

        // pass pdo-instance to constructor
        $query->setFetchMode(\PDO::FETCH_CLASS, "Depage\\Cms\\Project", [$pdo, $cache]);
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
        $fields = [];
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

            $this->xmldb = new \Depage\XmlDb\XmlDb($prefix, $this->pdo, $this->cache, [
                'pathXMLtemplate' => $xmlPath,
                'project' => $this,
                'userId' => $userId,
            ]);
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
        $pages = [];

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
                $docInfo->released = $node->getAttribute("db:released") == "true";

                $pages[] = $docInfo;
            }
        }

        usort($pages, function($a, $b) {
            if (!$a->released && $b->released) {
                return -1;
            } else if ($a->released && !$b->released) {
                return 1;
            }
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
    // {{{ getUnreleasedPages()
    /**
     * @brief getUnreleasedPages
     *
     * @param mixed
     * @return void
     **/
    public function getUnreleasedPages()
    {
        $pages = $this->getRecentlyChangedPages();

        $pages = array_filter($pages, function($page) {
            return $page->released == false;
        });

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

        return $this->getBaseUrl() . "/" . $languages[0];
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
            $languages = [];
            $this->xmldb = $this->getXmlDb();

            $settings = $this->getSettingsDoc();
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
        $targets = [];
        $this->xmldb = $this->getXmlDb();

        $settings = $this->getSettingsDoc();
        $nodes = $settings->getNodeIdsByXpath("//proj:publishTarget");
        foreach ($nodes as $nodeId) {
            $attr = $settings->getAttributes($nodeId);
            $targets[$nodeId] = (object) $attr;
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
    public function getHomeUrl($publishId = null)
    {
        $this->xmldb = $this->getXmlDb();
        $xml = $this->xmldb->getDocXml("pages");

        $languages = array_keys($this->getLanguages());

        $nodelist = $xml->getElementsByTagNameNS("http://cms.depagecms.net/ns/page", "page");

        return $this->getBaseUrl($publishId) . "/" . $languages[0] . $nodelist->item(0)->getAttribute("url");
    }
    // }}}
    // {{{ getBaseUrl()
    /**
     * @brief getBaseUrl
     *
     * @param mixed $
     * @return void
     **/
    public function getBaseUrl($publishId = null)
    {
        if (is_null($publishId)) {
            // @todo check template path
            return "project/{$this->name}/preview/html/pre";
        } else {
            $targets = $this->getPublishingTargets();
            $conf = $targets[$publishId];

            // get base-url
            $baseurl = parse_url(rtrim($conf->baseurl, "/"));
            $baseurl = $baseurl['scheme'] . "://" . $baseurl['host'] . $baseurl['path'];

            return $baseurl;
        }
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

    // {{{ releasePage()
    /**
     * @brief releasePage
     *
     * @param mixed $
     * @return void
     **/
    public function releasePage($pageId, $userId = null)
    {
        // @todo set userId correctly
        $doc = $this->xmldb->getDoc($pageId);
        $doc->getHistory()->save($userId, true);

        $doc->clearCache();
        $this->xmlDb->getDoc("pages")->clearCache();

        return $doc->getDocInfo()->rootid;
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
        $targets = $this->getPublishingTargets();
        $conf = $targets[$publishId];
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
        $transformCache = new \Depage\Transformer\TransformCache($this->pdo, $this->name, $conf->template_set . "-live-" . $publishId);
        $transformer = \Depage\Transformer\Transformer::factory("live", $this->xmldb, $this->name, $conf->template_set, $transformCache);
        $urls = $transformer->getUrlsByPageId();
        $languages = $this->getLanguages();

        $task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $taskName, $this->projectName);
        $initId = $task->addSubtask("init", "
            \$fs = \\Depage\\Fs\\Fs::factory(%s, array(
                'user' => %s,
                'pass' => %s,
            ));
            \$project = %s;
            \$publisher = new \\Depage\\Publisher\\Publisher(%s, \$fs, %s);
            \$transformer = %s;
            \$transformCache = %s;
        ", [
            $conf->output_folder,
            $conf->output_user,
            $conf->output_pass,
            $this,
            $publishPdo,
            $publishId,
            $transformer,
            $transformCache,
        ]);

        $task->addSubtask("testing publish target", "\$publisher->testConnection();", [], $initId);
        $task->addSubtask("resetting publishing state", "\$publisher->resetPublishedState();", [], $initId);

        // publish file library
        foreach ($files as $file) {
            $task->addSubtask("publishing $file", "\$publisher->publishFile(%s, %s);", [
                $projectPath . $file,
                $file,
            ], $initId);
        }

        // transform pages
        foreach ($urls as $pageId => $url) {
            foreach ($languages as $lang => $name) {
                $target = $lang . $url;
                $task->addSubtask("publishing $target", "
                    \$publisher->publishString(
                        \$transformer->transformUrl(%s, %s),
                        %s
                    );", [
                        $url,
                        $lang,
                        $target
                ], $initId);
            }
        }

        // @todo add files that should be generated automatically (e.g. through graohics)

        // publish sitemap
        $task->addSubtask("publishing sitemap", "
            \$publisher->publishString(
                \$project->generateSitemap(%s),
                %s
            );", [
                $publishId,
                "sitemap.xml",
        ], $initId);

        // publish feeds
        foreach ($languages as $lang => $name) {
            $task->addSubtask("publishing atom feed ($lang)", "
                \$publisher->publishString(
                    \$project->generateAtomFeed(%s, %s),
                    %s
                );", [
                    $publishId,
                    $lang,
                    "$lang/atom.xml",
            ], $initId);
        }

        $task->addSubtask("publishing htaccess", "
            \$publisher->publishString(
                \$project->generateHtaccess(%s),
                %s
            );", [
                $publishId,
                ".htaccess",
        ], $initId);

        $task->addSubtask("publishing index", "
            \$publisher->publishString(
                \$project->generateIndex(%s),
                %s
            );", [
                $publishId,
                "index.php",
        ], $initId);

        // unpublish removed files
        $task->addSubtask("removing leftover files", "\$publisher->unpublishRemovedFiles();", [], $initId);

        $task->begin();

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
    public function generateSitemap($publishId)
    {
        $this->xmldb = $this->getXmlDb();

        $transformer = \Depage\Transformer\Transformer::factory("pre", $this->xmldb, $this->name, "sitemap");
        $xml = $this->xmldb->getDocXml("pages");

        $parameters = [
            "currentContentType" => "text/xml",
            "currentEncoding" => "UTF-8",
            "depageVersion" => \Depage\Depage\Runner::getVersion(),
            "depageIsLive" => true,
            "baseUrl" => $this->getBaseUrl($publishId),
        ];

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
    public function generateAtomFeed($publishId, $lang)
    {
        $this->xmldb = $this->getXmlDb();

        $transformer = \Depage\Transformer\Transformer::factory("pre", $this->xmldb, $this->name, "atom");
        $xml = $this->xmldb->getDocXml("pages");

        $parameters = [
            "currentLang" => $lang,
            "currentContentType" => "text/xml",
            "currentEncoding" => "UTF-8",
            "depageVersion" => \Depage\Depage\Runner::getVersion(),
            "depageIsLive" => true,
            "baseUrl" => $this->getBaseUrl($publishId),
        ];

        $sitemap = $transformer->transform($xml, $parameters);
        return $sitemap;
    }
    // }}}
    // {{{ generateHtaccess()
    /**
     * @brief generateHtaccess
     *
     * @param mixed
     * @return void
     *
     * @todo refactor htaccess generator out when it is getting more complicated than the simple basics
     **/
    public function generateHtaccess($publishId)
    {
        $htaccess = "";
        $targets = $this->getPublishingTargets();
        $languages = array_keys($this->getLanguages());
        $defaultLanguage = current($languages);
        $projectPath = $this->getProjectPath();
        $conf = $targets[$publishId];
        $baseurl = $this->getBaseUrl($publishId);
        $baseurlParts = parse_url(rtrim($baseurl, "/"));
        $rewritebase = $baseurlParts['path'];
        if ($rewritebase == "") {
            $rewritebase = "/";
        }

        $htaccess .= "AddCharset UTF-8 .html\n\n";

        if ($conf->mod_rewrite == "true") {
            $htaccess .= "RewriteEngine       on\n";
            $htaccess .= "RewriteBase         $rewritebase\n\n";

            if (count($languages) > 0) {
                // load autolangchooser
                $htaccess .= "RewriteRule         ^/?$                     index.php [L]\n\n";
            } else {
                // redirect to first page
                $htaccess .= "RewriteRule         ^/?$                     {$baseurl}/{$defaultLanguage}{$baseLink} [L,R]\n\n";
            }
        } else {
            if (count($languages) > 0) {
                // load autolangchooser
                $htaccess .= "RedirectMatch       ^/$                      {$baseurl}/index.php\n";
            } else {
                // redirect to first page
                $htaccess .= "RedirectMatch       ^/$                      {$baseurl}/{$defaultLanguage}{$baseLink}\n\n";
            }
        }

        if (file_exists("$projectPath/lib/htaccess")) {
            $htaccess .= file_get_contents("$projectPath/lib/htaccess");
        }

        if ($conf->mod_rewrite == "true") {
            // redirect non-existing multipage-html to php-page
            $htaccess .= "RewriteCond         %{REQUEST_FILENAME}      !-s\n";
            $htaccess .= "RewriteRule         ^(.*)\.([0-9]+)\.html    \$1.php [L]\n\n";

            // redirect non-existing html to php-page
            $htaccess .= "RewriteCond         %{REQUEST_FILENAME}      !-s\n";
            $htaccess .= "RewriteRule         ^(.*)\.html              \$1.php [L]\n\n";

            $folders = implode("|", $languages) . "|lib";
            // redirect all pages that are not found to index-page
            // this has to be the last rules for custom rewrite-rules to work
            $htaccess .= "RewriteCond         %{REQUEST_FILENAME}      !-s\n";
            $htaccess .= "RewriteRule         ^($folders)/(.*)$       index.php?notfound=true [L]\n\n";
        }

        return $htaccess;
    }
    // }}}
    // {{{ generateIndex()
    /**
     * @brief generateIndex
     *
     * @param mixed
     * @return void
     **/
    public function generateIndex($publishId)
    {
        $this->xmldb = $this->getXmlDb();
        $xml = $this->xmldb->getDocXml("pages");

        $targets = $this->getPublishingTargets();
        $languages = array_keys($this->getLanguages());
        $defaultLanguage = current($languages);
        $projectPath = $this->getProjectPath();
        $conf = $targets[$publishId];
        $baseurl = $this->getBaseUrl($publishId);

        $transformCache = new \Depage\Transformer\TransformCache($this->pdo, $this->name, $conf->template_set . "-live-" . $publishId);
        $transformer = \Depage\Transformer\Transformer::factory("live", $this->xmldb, $this->name, $conf->template_set, $transformCache);
        $urls = $transformer->getUrlsByPageId();

        $index = "";
        $index .= file_get_contents(__DIR__ . "/../Redirector/Redirector.php");
        $index .= "?>";
        $index .= file_get_contents(__DIR__ . "/../Redirector/Result.php");

        $index .= "namespace {\n";

        $index .= "\$redirector = new \\Depage\\Redirector\\Redirector(" . var_export($baseurl, true) . ");\n";

        $index .= "\$redirector->setLanguages(" . var_export($languages, true) . ");\n";
        $index .= "\$redirector->setPages(" . var_export($urls, true) . ");\n";

        if (file_exists("$projectPath/lib/shortcuts")) {
            $index .= file_get_contents("$projectPath/lib/shortcuts");
            $index .= "if (isset(\$shortcuts)) {\n";
                $index .= "    \$redirector->setAliases(\$shortcuts);\n";
            $index .= "}\n";
        }

        $index .= "\n";

        $index .= "if (isset(\$_GET['notfound'])) {\n";
            $index .= "    \$redirector->redirectToAlternativePage(\$_SERVER['REQUEST_URI'], \$_SERVER['HTTP_ACCEPT_LANGUAGE']);\n";
        $index .= "} else {\n";
            $index .= "    \$redirector->redirectToIndex(\$_SERVER['REQUEST_URI'], \$_SERVER['HTTP_ACCEPT_LANGUAGE']);\n";
        $index .= "}\n\n";

        $index .= "}";


        return $index;
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
        $parameters = [
            "currentContentType" => "text/css",
            "currentEncoding" => "UTF-8",
            "depageVersion" => \Depage\Depage\Runner::getVersion(),
            "depageIsLive" => true,
        ];
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
        return array_merge(parent::__sleep(), [
            'pdo',
            'cache',
        ]);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
