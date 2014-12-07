<?php
/**
 * @file    framework/cms/cms_project.php
 *
 * depage cms project module
 *
 *
 * copyright (c) 2002-2010 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
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
        $projects = array();
        $fields = implode(", ", array_keys(self::$fields));

        $query = $pdo->prepare(
            "SELECT $fields
            FROM
                {$pdo->prefix}_projects AS projects"
        );
        $query->execute();

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

    // {{{ updateSchema()
    /**
     * @brief updateSchema
     *
     * @return void
     **/
    public function updateSchema()
    {
        $schema = new \Depage\DB\Schema($this->pdo);

        $schema->setReplace(
            function ($tableName) {
                return $this->pdo->prefix . $tableName;
            }
        );
        $schema->loadGlob(__DIR__ . "/Sql/*.sql");
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
