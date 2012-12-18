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

namespace depage\cms;

class project {
    /* {{{ constructor */
    /**
     * constructor
     *
     * @public
     *
     * @param       $pdo (PDO) pdo object for database access
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

        return array(
            "depage" => 1,
            "test" => 2,
        );

        $sid = $this->user->sid;
        if ($all || $this->user->get_level_by_sid() == 1) {
            // get all projects for admins
            $result = db_query(
                "SELECT projects.id, projects.name, projects.id_doc 
                FROM 
                    $conf->db_table_projects AS projects
                ORDER BY name"
            );
        } else {
            // get only allowed projects for normal users
            $result = db_query(
                "SELECT projects.id, projects.name, projects.id_doc 
                FROM 
                    $conf->db_table_projects AS projects,
                    $conf->db_table_sessions AS sessions,
                    $conf->db_table_user_projects AS user_projects
                WHERE
                    sessions.sid = '$sid' AND
                    sessions.userid = user_projects.uid AND
                    user_projects.pid = projects.id
                ORDER BY name"
            );
        }
        if ($result) {
            while ($row = mysql_fetch_assoc($result)) {
                $projects[$row['name']] = $row['id_doc'];
            }
        }

        return $projects;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
