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

    // {{{ getProjects()
    /**
     * gets available projects from database.
     *
     * @public
     *
     * @return    $projects (array) available projects
     */
    function getProjects($all = true) {
        $projects = array();

        //return array( "depage" => 1, );

        //@todo implement this correctly
        $sid = $this->user->sid;
        if ($all || $this->user->get_level_by_sid() == 1) {
            // get all projects for admins
            $query = $this->pdo->prepare(
                "SELECT projects.*
                FROM
                    $this->table_projects AS projects
                ORDER BY name"
            );
        } else {
            // get only allowed projects for normal users
            $query = $this->pdo->prepare(
                "SELECT projects.*
                FROM
                    $this->table_projects AS projects,
                    $this->table_sessions AS sessions,
                    $this->table_user_projects AS user_projects
                WHERE
                    sessions.sid = '$sid' AND
                    sessions.userid = user_projects.uid AND
                    user_projects.pid = projects.id
                ORDER BY name"
            );
        }
        $success = $query->execute();
        if ($success) {

            var_dump($query->fetchAll());
            die();
            while ($row = mysql_fetch_assoc($result)) {
                $projects[$row['name']] = $row['id_doc'];
            }
        }

        return $projects;
    }
    // }}}

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

        $success = mkdir($projectPath, 0777, true) || is_writable($projectPath);
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
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
