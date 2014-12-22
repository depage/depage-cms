<?php
/**
 * @file    framework/cms/cms_project.php
 *
 * depage cms project module
 *
 *
 * copyright (c) 2014 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace Depage\Cms;

class ProjectGroup extends \Depage\Entity\Entity
{
    // {{{ variables
    /**
     * @brief fields
     **/
    static protected $fields = array(
        "id" => null,
        "name" => "",
        "pos" => 1,
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
                projectgroup.name DESC

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
    static protected function fetch($pdo, $query, $parameters = array())
    {
        $projects = array();
        $query->execute($parameters);

        // pass pdo-instance to constructor
        $query->setFetchMode(\PDO::FETCH_CLASS, "Depage\\Cms\\ProjectGroup", array($pdo));
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
