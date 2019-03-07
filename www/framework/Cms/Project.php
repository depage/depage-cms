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

use \Depage\Notifications\Notification;

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

    /**
     * @brief previewType
     **/
    protected $previewType = "pre";

    /**
     * @brief graphicsOptions
     **/
    protected $graphicsOptions = [];
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
    static public function loadByUser($pdo, $cache, $user, $name = null) {
        if ($user->canEditAllProjects()) {
            if (!empty($name)) {
                return [self::loadByName($pdo, $cache, $name)];
            } else {
                return self::loadAll($pdo, $cache);
            }
        } else {
            $fields = implode(", ", self::getFields("projects"));

            $nameQuery = "";
            if (!empty($name)) {
                $nameQuery = "projects.name = :name AND";
            }

            $query = $pdo->prepare(
                "SELECT $fields, projectgroup.name as groupName
                FROM
                    {$pdo->prefix}_projects AS projects,
                    {$pdo->prefix}_project_groups AS projectgroup,
                    {$pdo->prefix}_project_auth AS projectauth
                WHERE
                    $nameQuery
                    projects.groupId = projectgroup.id AND
                    (projects.id = projectauth.projectId AND projectauth.userId = :userid)
                ORDER BY
                    projectgroup.pos ASC, fullname ASC

                "
            );

            $params = [
                'userid' => $user->id,
            ];
            if (!empty($name)) {
                $params['name'] = $name;
            }

            $projects = self::fetch($pdo, $cache, $query, $params);

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

        $this->createProjectDir($projectPath);
        $this->createProjectDir($projectPath . "lib/");
        $this->createProjectDir($projectPath . "import/");
        $this->createProjectDir($projectPath . "xml/");
        $this->createProjectDir($projectPath . "xslt/");
        $this->createProjectDir($projectPath . "backups/");
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

        $xmldb = $this->getXmlDb();
        $xmldb->updateSchema();

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

        // update schema for newsletter
        $files = array_merge(
            glob(__DIR__ . "/Sql/Newsletter/*.sql")
        );
        sort($files);
        foreach ($files as $file) {
            $schema->loadFile($file);
            $schema->update();
        }
    }
    // }}}
    // {{{ createProjectDir()
    /**
     * @brief createProjectDir
     *
     * @param mixed $path
     * @return void
     **/
    private function createProjectDir($path)
    {
        $success = is_writable($path) || mkdir($path, 0777, true);

        if (!$success) {
            throw new Exceptions\Project("Could not create project directory '$path'.");
        }

        return true;
    }
    // }}}

    // {{{ setPreviewType()
    /**
     * @brief setPreviewType
     *
     * @param mixed $
     * @return void
     **/
    public function setPreviewType($type)
    {
        $this->previewType = $type;
    }
    // }}}
    // {{{ setGraphicsOptions()
    /**
     * @brief setGraphicsOptions
     *
     * @param mixed $options
     * @return void
     **/
    public function setGraphicsOptions($options)
    {
        $this->graphicsOptions = $options;
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

            $projectPath = $this->getProjectPath();

            $xsltPath = $projectPath . "xslt/";
            $xmlPath = $projectPath . "xml/";
            $libPath = $projectPath . "lib/";

            $this->xmldb = new \Depage\XmlDb\XmlDb($prefix, $this->pdo, $this->cache, [
                'pathXMLtemplate' => $xmlPath,
                'project' => $this,
                'userId' => $userId,
            ]);
        }

        return $this->xmldb;
    }
    // }}}
    // {{{ getXmlGetter()
    /**
     * @brief getXmlDb
     *
     * @return xmldb
     **/
    public function getXmlGetter($userId = null)
    {
        if ($this->previewType == "live") {
            $prefix = $this->pdo->prefix . "_proj_" . $this->name;

            $projectPath = $this->getProjectPath();

            $xsltPath = $projectPath . "xslt/";
            $xmlPath = $projectPath . "xml/";
            $libPath = $projectPath . "lib/";

            $xmldbHistory = new \Depage\XmlDb\XmlDbHistory($prefix, $this->pdo, $this->cache, [
                'pathXMLtemplate' => $xmlPath,
                'project' => $this,
                'userId' => $userId,
            ]);

            return $xmldbHistory;
        } else {
            return $this->getXmlDb($userId);
        }
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
    // {{{ getPages()
    /**
     * @brief getPages
     *
     * @param mixed
     * @return void
     **/
    public function getPages($docId = null)
    {
        $pages = [];

        $this->xmldb = $this->getXmlDb();

        $xml = $this->xmldb->getDocXml("pages");

        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace("pg", "http://cms.depagecms.net/ns/page");

        if (!is_null($docId)) {
            $nodelist = $xpath->query("//pg:*[@db:docref='$docId']");
        } else {
            $nodelist = $xpath->query("//pg:page");
        }

        for ($i = 0; $i < $nodelist->length; $i++) {
            $node = $nodelist->item($i);
            $currentDocId = $node->getAttributeNS("http://cms.depagecms.net/ns/database", "docref");

            if ($currentDocId) {
                $docInfo = $this->xmldb->getDoc($currentDocId)->getDocInfo();
                $docInfo->pageId = $node->getAttribute("db:id");
                $docInfo->url = $node->getAttribute("url");
                $docInfo->fileType = $node->getAttribute("file_type");
                $docInfo->published = $node->getAttribute("db:published") == "true";
                $docInfo->released = $node->getAttribute("db:released") == "true";

                $docInfo->nav = [];
                $docInfo->tags = [];
                foreach ($node->attributes as $name => $attrNode) {
                    if (substr($name, 0, 4) == 'nav_') {
                        $docInfo->nav[substr($name, 4)] = $attrNode->value;
                    } else if (substr($name, 0, 4) == 'tag_') {
                        $docInfo->tags[substr($name, 4)] = $attrNode->value;
                    }
                }

                $pages[] = $docInfo;
            }
        }

        return $pages;
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
        $pages = $this->getPages();

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
    // {{{ getXslTemplates()
    /**
     * @brief getXslTemplates
     *
     * @param mixed
     * @return void
     **/
    public function getXslTemplates()
    {
        $templates = [];

        $files = glob($this->getProjectPath() . "xslt/*");

        foreach ($files as $file) {
            if (is_dir($file)) {
                if (count(glob($file . "/*.xsl")) > 0) {
                    $templates[] = basename($file);
                }
            }
        }

        return $templates;
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
        $languages = [];
        $this->xmldb = $this->getXmlDb();

        $settings = $this->getSettingsDoc();
        $nodes = $settings->getNodeIdsByXpath("//proj:language");
        foreach ($nodes as $nodeId) {
            $attr = $settings->getAttributes($nodeId);
            $languages[$attr['shortname']] = $attr['name'];
        }

        return $languages;
    }
    // }}}
    // {{{ getColorschemes()
    /**
     * @brief getLanggetColorschemesuages
     *
     * @param mixed
     * @return array of colorschemes
     **/
    public function getColorschemes()
    {
        $colors = [];
        $xmldb = $this->getXmlDb();

        $doc = $xmldb->getDoc("colors");
        $nodes = $doc->getNodeIdsByXpath("//proj:colorscheme[@name != 'tree_name_color_global']");
        foreach ($nodes as $nodeId) {
            $attr = $doc->getAttributes($nodeId);
            $colors[$attr['name']] = $attr['name'];
        }

        return $colors;
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
    // {{{ getTags()
    /**
     * @brief getTags
     *
     * @param mixed
     * @return array of tags
     **/
    public function getTags()
    {
        $tags = [];
        $this->xmldb = $this->getXmlDb();

        $settings = $this->getSettingsDoc();
        $nodes = $settings->getNodeIdsByXpath("//proj:tag");
        foreach ($nodes as $nodeId) {
            $attr = $settings->getAttributes($nodeId);
            $tags[$attr['name']] = $attr['name'];
            // add support for localized names
        }

        return $tags;
    }
    // }}}
    // {{{ getNavigations()
    /**
     * @brief getNavigations
     *
     * @param mixed
     * @return array of navigations
     **/
    public function getNavigations()
    {
        $navs = [];
        $this->xmldb = $this->getXmlDb();

        $settings = $this->getSettingsDoc();
        $nodes = $settings->getNodeIdsByXpath("//proj:navigation");
        foreach ($nodes as $nodeId) {
            $attr = $settings->getAttributes($nodeId);
            $navs[$attr['shortname']] = $attr['name'];
            // add support for localized names
        }

        return $navs;
    }
    // }}}
    // {{{ getVariables()
    /**
     * @brief getVariables
     *
     * @param mixed
     * @return array of variables
     **/
    public function getVariables()
    {
        $variables = [];
        $this->xmldb = $this->getXmlDb();

        $settings = $this->getSettingsDoc();
        $nodes = $settings->getNodeIdsByXpath("//proj:variable");
        foreach ($nodes as $nodeId) {
            $attr = $settings->getAttributes($nodeId);
            $variables[$attr['name']] = $attr['value'];
        }

        return $variables;
    }
    // }}}
    // {{{ getColorPalette()
    /**
     * @brief getColorPalette
     *
     * @param mixed
     * @return void
     **/
    public function getColorPalette()
    {
        $colors = [];
        $doc = $this->xmldb->getDoc("colors");
        $xml = $doc->getXML();

        $xpath = new \DOMXPath($xml);
        $nodelist = $xpath->query("//color");

        foreach ($nodelist as $node) {
            $colors[$node->getAttribute("value")] = true;
        }

        return array_keys($colors);
    }
    // }}}
    // {{{ getDefaultTargetUrl()
    /**
     * @brief getDefaultTargetUrl
     *
     * @param mixed
     * @return void
     **/
    public function getDefaultTargetUrl()
    {
        $target = rtrim(reset($this->getPublishingTargets())->baseurl, "/");

        return $target;

    }
    // }}}
    // {{{ getDefaultLanguage()
    /**
     * @brief getDefaultLanguage
     *
     * @param mixed
     * @return void
     **/
    public function getDefaultLanguage()
    {
        $lang = reset(array_keys($this->getLanguages()));

        return $lang;
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
        $xmldb = $this->getXmlGetter();
        $xml = $xmldb->getDocXml("pages");

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
            return DEPAGE_BASE . "project/{$this->name}/preview/html/{$this->previewType}";
        } else {
            $targets = $this->getPublishingTargets();
            $conf = $targets[$publishId];

            // get base-url
            $parts = parse_url(rtrim($conf->baseurl, "/"));
            if (!isset($parts['path'])) {
                $parts['path'] = "/";
            } else {
                $parts['path'] .= "/";
            }
            $baseurl = $parts['scheme'] . "://" . $parts['host'] . $parts['path'];

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
    // {{{ getLastPublishDateOf()
    /**
     * @brief getLastPublishDateOf
     *
     * @param mixed
     * @return void
     **/
    public function getLastPublishDateOf($filename)
    {
        $publishPdo = clone $this->pdo;
        $publishPdo->prefix = $this->pdo->prefix . "_proj_" . $this->name;

        list($publishId) = array_keys($this->getPublishingTargets());

        $fs = \Depage\Fs\Fs::factory("");
        $publisher = new \Depage\Publisher\Publisher($publishPdo, $fs, $publishId);

        $info = $publisher->getFileInfo($filename);
        if ($info) {
            return $info->lastmod;
        }

        return false;
    }
    // }}}

    // {{{ hasPageShortcuts()
    /**
     * @brief hasPageShortcuts
     *
     * @param mixed
     * @return void
     **/
    public function hasPageShortcuts()
    {
        $this->xmldb = $this->getXmlDb();

        $pages = $this->xmldb->getDoc("pages");
        $nodeIds = $pages->getNodeIdsByXpath("//pg:*[@nav_blog = 'true' or @nav_news = 'true']");

        return count($nodeIds) > 0 && $this->getPostTemplate();
    }
    // }}}
    // {{{ addNewPost()
    /**
     * @brief addNewPost
     *
     * @param mixed
     * @return void
     **/
    public function addNewPost()
    {
        $this->xmldb = $this->getXmlDb();

        $year = date("Y");
        $month = date("m");

        $pages = $this->xmldb->getDoc("pages");
        $docId = $pages->getDocId();
        $prefix = $this->pdo->prefix . "_proj_" . $this->name;

        $deltaUpdates = new \Depage\WebSocket\JsTree\DeltaUpdates($prefix, $this->pdo, $this->xmldb, $docId, $this->name, 0);
        $nodeIdNews = reset($pages->getNodeIdsByXpath("//pg:*[@nav_blog = 'true' or @nav_news = 'true']"));
        $nodeIdYear = reset($pages->getNodeIdsByXpath("//pg:*[@nav_blog = 'true' or @nav_news = 'true']/pg:folder[@name = '$year']"));
        $nodeIdMonth = reset($pages->getNodeIdsByXpath("//pg:*[@nav_blog = 'true' or @nav_news = 'true']/pg:folder[@name = '$year']/pg:folder[@name = '$month']"));

        if (!$nodeIdYear) {
            $nodeIdYear = $pages->addNodeByName("pg:folder", $nodeIdNews, 0);
            $pages->setAttribute($nodeIdYear, "name", $year);

            $deltaUpdates->recordChange($nodeIdNews);
        }
        if (!$nodeIdMonth) {
            $nodeIdMonth = $pages->addNodeByName("pg:folder", $nodeIdYear, 0);
            $pages->setAttribute($nodeIdMonth, "name", $month);

            $deltaUpdates->recordChange($nodeIdYear);
        }
        $doc = new \DOMDocument();
        $doc->load($this->getPostTemplate());

        $pageId = $pages->addNodeByName("pg:page", $nodeIdMonth, 0, ['dataNodes' => $doc->documentElement->childNodes]);
        $deltaUpdates->recordChange($nodeIdMonth);

        return $pageId;
    }
    // }}}
    // {{{ getPostTemplate()
    /**
     * @brief getPostTemplate
     *
     * @return void
     **/
    protected function getPostTemplate()
    {
        $templatePath = $this->getProjectPath() . "xml/";
        $names = [
            'Post.xml',
            'Blogentry.xml',
            'News.xml',
        ];

        foreach ($names as $name) {
            $file = $templatePath . $name;
            if (file_exists($file)) {
                return $file;
            }
        }

        return false;
    }
    // }}}
    // {{{ hasNewsletter()
    /**
     * @brief hasNewsletter
     *
     * @param mixed
     * @return void
     **/
    public function hasNewsletter()
    {
        return in_array("newsletter", $this->getXslTemplates());
    }
    // }}}

    // {{{ releaseDocument()
    /**
     * @brief releaseDocument
     *
     * @param mixed $
     * @return void
     **/
    public function releaseDocument($docId, $userId)
    {
        // @todo set userId correctly
        $doc = $this->xmldb->getDoc($docId);
        $doc->getHistory()->save($userId, true);

        $doc->clearCache();

        return $doc->getDocInfo()->rootid;
    }
    // }}}
    // {{{ requestDocumentRelease()
    /**
     * @brief requestDocumentRelease
     *
     * @param mixed $docId, $userId
     * @return void
     **/
    public function requestDocumentRelease($docId, $userId)
    {
        $users = \Depage\Auth\User::loadAll($this->pdo);

        $requestingUser = $users[$userId];
        $users = array_filter($users, function($u) {
            if ($u->canPublishProject()) {
                $userProjects = \Depage\Cms\Project::loadByUser($this->pdo, $this->xmldbCache, $u);

                return in_array($this->name, $userProjects);
            }
            return false;
        });

        $pages = $this->getPages($docId);
        $title = _("Document Release Request");
        $text = sprintf(_("%s is requesting a document release for '%s' on project '%s'."), $requestingUser->fullname, $pages[0]->url, $this->name);

        foreach ($users as $u) {
            $newN = new Notification($this->pdo);
            $newN->setData([
                'uid' => $u->id,
                'tag' => "depage." . $this->name,
                'title' => $title,
                'message' => $text,
                'options' => [
                    'link' => DEPAGE_BASE . "project/{$this->name}/release-pages/$docId/",
                ],
            ])
            ->save();

            $newN = new Notification($this->pdo);
            $newN->setData([
                'uid' => $u->id,
                'tag' => "mail." . $this->name,
                'title' => $title,
                'message' => $text,
                'options' => [
                    'link' => DEPAGE_BASE . "project/{$this->name}/release-pages/$docId/",
                ],
            ])
            ->save();
        }

        return true;
    }
    // }}}
    // {{{ addPublishTask()
    /**
     * @brief addPublishTask
     *
     * @param mixed $param
     * @return void
     **/
    public function addPublishTask($taskName, $publishId, $userId)
    {
        $task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $taskName, $this->name);
        $task->addSubtask("init publishing subtasks", "
            \$project = %s;
            \$project->initPublishTask(%s, %s, %s);
        ", [
            $this,
            $taskName,
            $publishId,
            $userId,
        ]);

        $task->begin();

        return $task;
    }
    // }}}
    // {{{ initPublishTask()
    /**
     * @brief initPublishTask
     *
     * @param mixed $param
     * @return void
     **/
    public function initPublishTask($taskName, $publishId, $userId)
    {
        $this->setPreviewType("live");
        $xmlgetter = $this->getXmlGetter();

        $projectPath = $this->getProjectPath();
        $targets = $this->getPublishingTargets();
        $conf = $targets[$publishId];
        $fsLocal = \Depage\Fs\Fs::factory($projectPath);

        // save folders in history
        $xmldb = $this->getXmlDb($userId);
        $docs = $xmldb->getDocuments();
        foreach ($docs as $doc) {
            $info = $doc->getDocInfo();
            if ($info->type == "Depage\\Cms\\XmlDocTypes\\Folder") {
                $this->releaseDocument($info->name, $userId);
            }
        }

        // save current general documents in history
        $this->releaseDocument("pages", $userId);
        $this->releaseDocument("settings", $userId);
        $this->releaseDocument("colors", $userId);

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
        //$transformCache = new \Depage\Transformer\TransformCache($this->pdo, $this->name, $conf->template_set . "-live-" . $publishId);
        $transformCache = null;
        $transformer = \Depage\Transformer\Transformer::factory("live", $xmlgetter, $this->name, $conf->template_set, $transformCache);
        $baseUrl = $this->getBaseUrl($publishId);
        $transformer->setBaseUrl($baseUrl);
        $transformer->routeHtmlThroughPhp = $conf->mod_rewrite == "true";
        $urls = $transformer->getUrlsByPageId();
        $languages = $this->getLanguages();

        $task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $taskName, $this->name);
        $initId = $task->addSubtask("init", "
            clearstatcache();
            \$fs = \\Depage\\Fs\\Fs::factory(%s, array(
                'user' => %s,
                'pass' => %s,
            ));
            \$project = %s;
            \$publisher = new \\Depage\\Publisher\\Publisher(%s, \$fs, %s);
            \$urls = new \\Depage\\Publisher\\Urls(%s, %s);
            \$transformer = %s;
            \$transformCache = %s;
            \$indexer = new \\Depage\\Search\\Indexer();
        ", [
            $conf->output_folder,
            $conf->output_user,
            $conf->output_pass,
            $this,
            $publishPdo,
            $publishId,
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
        $apiAvailable = file_exists($projectPath . 'lib/global/api.php');

        if ($apiAvailable) {
            // call updateSchema API
            $task->addSubtask("updating schema", "
                \$request = new \\Depage\\Http\\Request(%s);
                \$request->allowUnsafeSSL = true;
                \$response = \$request->execute();
                \$result = \$response->getJson();

                if (!\$result['success']) {
                    throw new \\Exception(\$result['error']);
                }
            ", [
                $baseUrl . "api/updateschema/",
            ], $initId);
        }

        $graphicsOptions = [
            'extension' => $this->graphicsOptions->extension,
            'executable' => $this->graphicsOptions->executable,
            'optimize' => $this->graphicsOptions->optimize,
            'baseUrl' => $baseUrl,
            'cachePath' => $projectPath . "lib/cache/graphics/",
            'relativePath' => $projectPath,
        ];

        $i = 0;
        foreach ($urls as $pageId => $url) {
            foreach ($languages as $lang => $name) {
                $target = $lang . $url;

                $pubId = $task->addSubtask(
                    "transforming $target",
                    "\$html = \$transformer->transformUrl(%s, %s);",
                    [$url, $lang],
                    $initId
                );
                $task->addSubtask(
                    "generating images for $target",
                    "\$images = \$indexer->loadXml(\$html, %s)->getImages();
                    \$imgUrl = new \\Depage\\Graphics\\Imgurl(%s);
                    foreach (\$images as \$i) {
                        \$imgUrl->render(\$i);
                        if (\$imgUrl->rendered) {
                            \$publisher->publishFile(%s . 'lib/cache/graphics/' . \$imgUrl->id, 'lib/cache/graphics/' . \$imgUrl->id);
                        }
                    }
                    ",
                    [$baseUrl . $target, $graphicsOptions, $projectPath],
                    $pubId
                );
                $task->addSubtask(
                    "publishing $target",
                    "\$publisher->publishString(\$html, %s, \$updated);",
                    [$target],
                    $pubId
                );
                if ($apiAvailable) {
                    $task->addSubtask(
                        "indexing $target",
                        "if (\$updated) {
                            \$request = new \\Depage\\Http\\Request(%s);
                            \$request->allowUnsafeSSL = true;
                            \$response = \$request->setPostData(%s)->execute();
                        }",
                        [$baseUrl . "api/search/index/", ["url" => $baseUrl . $target]],
                        $pubId
                    );
                }
            }
            $task->addSubtask(
                "adding canonical url for $target",
                "\$urls->addUrl(%s, %s, %s);",
                [$pageId, $url, $i],
                $pubId
            );
            $i++;
        }

        // @todo add files that should be generated automatically (e.g. through graphics)

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

        // @todo updated with humans.txt
        // http://humanstxt.org/Standard.html
        $version = \Depage\Depage\Runner::getName() . " " . \Depage\Depage\Runner::getVersion() . "\npublished at ";
        $task->addSubtask("publishing info", "
            \$publisher->publishString(
                %s . date('r'),
                %s
            );", [
                $version,
                "publishInfo.txt",
        ], $initId);

        // publish newsletters if available
        if ($this->hasNewsletter()) {
            $newsletters = \Depage\Cms\Newsletter::loadReleased($this->pdo, $this);

            foreach ($newsletters as $newsletter) {
                // @todo check if newsletter has been published
                foreach ($languages as $lang => $name) {
                    $task->addSubtask("publishing newsletter {$newsletter->name}", "
                        \$publisher->publishString(
                            %s->transform(\"live\", \"$lang\"),
                            %s
                        );", [
                            $newsletter,
                            "$lang/newsletter/{$newsletter->name}.html",
                    ], $initId);
                }
            }
        }

        // unpublish removed files
        $task->addSubtask("removing leftover files", "
            \$files = \$publisher->unpublishRemovedFiles();
            if (%s) {
                \$request = new \\Depage\\Http\\Request(%s);
                \$request->allowUnsafeSSL = true;
                foreach (\$files as \$file) {
                    \$response = \$request->setPostData(array('url' => %s . '/' . \$file))->execute();
                }
            }
        ", [
            $apiAvailable,
            $baseUrl . "/api/search/remove/",
            $baseUrl,
        ], $initId);

        $task->begin();

        return $task;
    }
    // }}}

    // {{{ getXsltProcessor()
    /**
     * @brief getXsltProcessor
     *
     * @param mixed $xslFile
     * @return void
     **/
    public function getXsltProcessor($xslFile)
    {
        libxml_disable_entity_loader(false);
        libxml_use_internal_errors(true);

        $xsltProc = new \XSLTProcessor();

        $xsl = new \DOMDocument();
        $xsl->load($xslFile);
        $xsltProc->importStylesheet($xsl);

        return $xsltProc;
    }
    // }}}
    // {{{ updateXmlForNodeId()
    /**
    * @brief updateXmlForNodeId
    *
    * @param mixed $param
    * @return void
    **/
    public function updateXmlForNodeId($xsltProc, $id)
    {
        $xmldb = $this->getXmlDb();

        $doc = $xmldb->getDocByNodeId($id);
        if (!$doc) {
            return;
        }
        $node = $doc->getSubdocByNodeId($id);

        if (!$newNode = $xsltProc->transformToDoc($node)) {
            // @todo add better error handling
            $messages = "";
            $errors = libxml_get_errors();
            foreach($errors as $error) {
                $messages .= $error->message . "\n";
            }

            $error = "Could not transform the XML document:\n" . $messages;

            throw new \Exception($error);
        }
        if ($node->saveXml() != $newNode->saveXml()) {
            $doc->replaceNode($newNode, $id);
        }
    }
    // }}}
    // {{{ addProjectUpdateTask()
    /**
     * @brief addProjectUpdateTask
     *
     * @return void
     **/
    public function addProjectUpdateTask()
    {
        $updateSrc = $this->getProjectPath() . "xslt/update.php";
        if (file_exists($updateSrc)) {
            $xslSrc = $this->getProjectPath() . "xslt/update/update.xsl";
            $xmldb = $this->getXmlDb();

            $task = \Depage\Tasks\Task::loadOrCreate($this->pdo, "update project", $this->name);
            $initId = $task->addSubtask("init", "
                \$project = %s;
                \$xsltProc = \$project->getXsltProcessor(%s);
            ", [
                $this,
                $xslSrc,
            ]);

            include($updateSrc);

            $task->addSubtask("clearing transform cache", "\$project->clearTransformCache();");

            $task->begin();
        } else {
            return false;
        }
    }
    // }}}

    // {{{ getProjectConfig()
    /**
     * @brief getProjectConfig
     *
     * @param mixed
     * @return void
     **/
    public function getProjectConfig()
    {
        $projectPath = $this->getProjectPath();
        $config = new \Depage\Config\Config([
            'aliases' => [],
            'rootAliases' => [],
            'routeHtmlThroughPhp' => false,
            'version' => 1,
        ]);

        if (file_exists("$projectPath/lib/global/config.php")) {
            $config->readConfig("$projectPath/lib/global/config.php");
        }

        return $config;
    }
    // }}}
    // {{{ clearTransformCache()
    /**
     * @brief clearTransformCache
     *
     * @return void
     **/
    public function clearTransformCache()
    {
        $templates = $this->getXslTemplates();
        $previewTypes = ["dev", "pre", "live"];

        foreach ($templates as $template) {
            foreach ($previewTypes as $type) {
                $transformCache = new \Depage\Transformer\TransformCache($this->pdo, $this->name, "$template-$type");
                $transformCache->clearAll();

                $xslFile = "cache/xslt/$this->name/$template/$type.xsl";
                if (file_exists($xslFile)) {
                    unlink($xslFile);
                }
            }
        }

        return true;
    }
    // }}}

    // {{{ generateSitemap()
    /**
     * @brief generateSitemap
     *
     * @param mixed
     * @return void
     **/
    public function generateSitemap($publishId = null)
    {
        $xmlgetter = $this->getXmlGetter();
        $baseUrl = $this->getBaseUrl($publishId);

        $transformer = \Depage\Transformer\Transformer::factory($this->previewType, $xmlgetter, $this->name, "sitemap");
        $transformer->setBaseUrl($baseUrl);
        $xml = $xmlgetter->getDocXml("pages");

        $parameters = [
            "currentContentType" => "text/xml",
            "currentEncoding" => "UTF-8",
            "depageVersion" => \Depage\Depage\Runner::getVersion(),
            "depageIsLive" => true,
            "baseUrl" => $baseUrl,
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
        $xmlgetter = $this->getXmlGetter();

        $transformer = \Depage\Transformer\Transformer::factory($this->previewType, $xmlgetter, $this->name, "atom");
        $xml = $xmlgetter->getDocXml("pages");

        $parameters = [
            "currentLang" => $lang,
            "currentContentType" => "text/xml",
            "currentEncoding" => "UTF-8",
            "depageVersion" => \Depage\Depage\Runner::getVersion(),
            "depageIsLive" => true,
            "baseUrl" => $this->getBaseUrl($publishId) . "/",
        ];

        $atom = $transformer->transform($xml, $parameters);

        return $atom;
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
        $rewritebase = parse_url(rtrim($baseurl, "/"), PHP_URL_PATH);
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

            $folders = implode("|", $languages) . "|lib|api";
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
        $xmlgetter = $this->getXmlGetter();
        $xml = $xmlgetter->getDocXml("pages");

        $publishPdo = clone $this->pdo;
        $publishPdo->prefix = $this->pdo->prefix . "_proj_" . $this->name;

        $targets = $this->getPublishingTargets();
        $languages = array_keys($this->getLanguages());
        $defaultLanguage = current($languages);
        $projectPath = $this->getProjectPath();
        $conf = $targets[$publishId];
        $baseurl = $this->getBaseUrl($publishId);

        $transformer = \Depage\Transformer\Transformer::factory("live", $xmlgetter, $this->name, $conf->template_set);

        $urls = new \Depage\Publisher\Urls($publishPdo, $publishId);

        $index = [];
        $index[] = "<?php";
        $index[] = substr(file_get_contents(__DIR__ . "/../Redirector/Redirector.php"), 5);
        $index[] = substr(file_get_contents(__DIR__ . "/../Redirector/Result.php"), 5);

        $index[] = "namespace {";

        $index[] = "\$redirector = new \\Depage\\Redirector\\Redirector(" . var_export($baseurl, true) . ");";

        $index[] = "\$redirector->setLanguages(" . var_export($languages, true) . ");";
        $index[] = "\$redirector->setPages(" . var_export($urls->getCanonicalUrls(), true) . ");";
        $index[] = "\$redirector->setAlternatePages(" . var_export($urls->getAlternateUrls(), true) . ");";

        $projectConf = $this->getProjectConfig();
        if (isset($projectConf->aliases)) {
            $index[] = "\$redirector->setAliases(" . var_export($projectConf->aliases->toArray(), true) . ");";
        }
        if (isset($projectConf->rootAliases)) {
            $index[] = "\$redirector->setRootAliases(" . var_export($projectConf->rootAliases->toArray(), true) . ");";
        }

        $index[] = "\$acceptLanguage = isset(\$_SERVER['HTTP_ACCEPT_LANGUAGE']) ? \$_SERVER['HTTP_ACCEPT_LANGUAGE'] : \"\";";
        $index[] = "\$replacementScript = \$redirector->testAliases(\$_SERVER['REQUEST_URI'], \$acceptLanguage);";
        $index[] = "if (!empty(\$replacementScript)) {";
            $index[] = "    chdir(dirname(\$replacementScript));";
            $index[] = "    include(basename(\$replacementScript));";
            $index[] = "    die();";
        $index[] = "}";

        $index[] = "if (isset(\$_GET['notfound'])) {";
            $index[] = "    \$redirector->redirectToAlternativePage(\$_SERVER['REQUEST_URI'], \$acceptLanguage);";
        $index[] = "} else {";
            $index[] = "    \$redirector->redirectToIndex(\$_SERVER['REQUEST_URI'], \$acceptLanguage);";
        $index[] = "}";

        $index[] = "}";


        return implode($index, "\n");
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

        $xmlgetter = $this->getXmlGetter();

        $transformer = \Depage\Transformer\Transformer::factory("pre", $xmlgetter, $this->name, "css");
        $xml = $xmlgetter->getDocXml("colors");
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
    // {{{ __toString()
    /**
     * @brief __toString
     *
     * @return void
     **/
    public function __toString()
    {
        return $this->name;
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
            'previewType',
            'graphicsOptions',
        ]);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
