<?php
/**
 * @file    framework/cms/cms_project.php
 *
 * depage cms project module
 *
 *
 * copyright (c) 2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms;

class ProjectGroup extends \Depage\Entity\Entity
{
    // {{{ variables
    /**
     * @brief fields
     **/
    static protected $fields = [
        "id" => null,
        "name" => "",
        "pos" => 1,
    ];

    /**
     * @brief primary
     **/
    static protected $primary = ["id"];

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

    // {{{ loadAll()
    /**
     * gets an array of project groups
     *
     * @public
     *
     * @param       Depage\Db\Pdo     $pdo        pdo object for database access
     *
     * @return      Array array of projects
     */
    static public function loadAll($pdo) {
        $fields = implode(", ", self::getFields());

        $query = $pdo->prepare(
            "SELECT $fields
            FROM
                {$pdo->prefix}_project_groups AS projectgroup
            ORDER BY
                projectgroup.pos ASC,
                projectgroup.name ASC
            "
        );

        $projects = self::fetch($pdo, $query);

        return $projects;
    }
    // }}}
    // {{{ fetch()
    /**
     * @brief fetch
     *
     * @param mixed $query
     * @return void
     **/
    static protected function fetch($pdo, $query, $parameters = [])
    {
        $projects = [];
        $query->execute($parameters);

        // pass pdo-instance to constructor
        $query->setFetchMode(\PDO::FETCH_CLASS, "Depage\\Cms\\ProjectGroup", [$pdo]);
        do {
            $project = $query->fetch(\PDO::FETCH_CLASS);
            if ($project) {
                array_push($projects, $project);
            }
        } while ($project);

        return $projects;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
