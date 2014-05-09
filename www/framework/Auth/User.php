<?php 
/**
 * @file    auth_user.php
 *
 *
 * copyright (c) 2002-2010 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace depage\Auth;

/**
 * contains functions for handling user authentication
 * and session handling.
 */
class User {
    // {{{ constructor()
    /**
     * constructor
     *
     * @public
     *
     * @param       PDO     $pdo        pdo object for database access
     *
     * @return      void
     */
    public function __construct(\depage\DB\PDO $pdo) {
        $this->pdo = $pdo;
    }
    // }}}
    // {{{ loadByUsername()
    /**
     * gets a user-object by username directly from database
     *
     * @public
     *
     * @param       PDO     $pdo        pdo object for database access
     * @param       string  $username   username of the user
     *
     * @return      User
     */
    static public function loadByUsername($pdo, $username) {
        $uid_query = $pdo->prepare(
            "SELECT 
                user.type,
                user.type AS type,
                user.id AS id,
                user.name as name,
                user.name_full as fullname,
                user.pass as passwordhash,
                user.email as email,
                user.settings as settings,
                user.level as level
            FROM
                {$pdo->prefix}_auth_user AS user
            WHERE
                name = :name"
        );
        
        $uid_query->execute(array(
            ':name' => $username,
        ));
        
        // pass pdo-instance to constructor
        $uid_query->setFetchMode(\PDO::FETCH_CLASS, "depage\\Auth\\User", array($pdo));
        $user = $uid_query->fetch(\PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE | \PDO::FETCH_PROPS_LATE);

        return $user;
    }
    // }}}
    // {{{ loadBySid()
    /**
     * gets a user-object by sid (session-id) directly from database
     *
     * @public
     *
     * @param       PDO     $pdo        pdo object for database access
     * @param       string  $sid        session id
     *
     * @return      auth_user
     */
    static public function loadBySid($pdo, $sid) {
        $uid_query = $pdo->prepare(
            "SELECT
                user.type,
                user.type AS type,
                user.id AS id,
                user.name as name,
                user.name_full as fullname,
                user.pass as passwordhash,
                user.email as email,
                user.settings as settings,
                user.level as level
            FROM
                {$pdo->prefix}_auth_user AS user, 
                {$pdo->prefix}_auth_sessions AS sessions
            WHERE
                sessions.sid = :sid AND
                sessions.userid = user.id"
        );
        $uid_query->execute(array(
            ':sid' => $sid,
        ));
        
        // pass pdo-instance to constructor
        $uid_query->setFetchMode(\PDO::FETCH_CLASS, "auth_user", array($pdo));
        $user = $uid_query->fetch(\PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE | \PDO::FETCH_PROPS_LATE);
        return $user;
    }
    // }}}
    // {{{ loadById()
    /**
     * gets a user-object by id directly from database
     *
     * @public
     *
     * @param       PDO     $pdo        pdo object for database access
     * @param       int     $id         id of the user
     *
     * @return      auth_user
     */
    static public function loadById($pdo, $id) {
        $uid_query = $pdo->prepare(
            "SELECT
                user.type,
                user.type AS type,
                user.id AS id,
                user.name as name,
                user.name_full as fullname,
                user.pass as passwordhash,
                user.email as email,
                user.settings as settings,
                user.level as level
            FROM
    {$pdo->prefix}_auth_user AS user
            WHERE
                id = :id"
        );
        $uid_query->execute(array(
            ':id' => $id,
        ));
        
        // pass pdo-instance to constructor
        $uid_query->setFetchMode(\PDO::FETCH_CLASS, "depage\\auth\\user", array($pdo));
        $user = $uid_query->fetch(\PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE | \PDO::FETCH_PROPS_LATE);
        return $user;
    }
    // }}} 
    // {{{ getAll()
    /**
     * gets an array of user-objects
     *
     * @public
     *
     * @param       PDO     $pdo        pdo object for database access
     * @param       int     $id         id of the user
     *
     * @return      auth_user
     */
    static public function getAll($pdo) {
        $users = array();
        $uid_query = $pdo->prepare(
            "SELECT
                user.type,
                user.type AS type,
                user.id AS id,
                user.name as name,
                user.name_full as fullname,
                user.pass as passwordhash,
                user.email as email,
                user.settings as settings,
                user.level as level
            FROM
                {$pdo->prefix}_auth_user AS user"
        );
        $uid_query->execute();
        
        // pass pdo-instance to constructor
        $uid_query->setFetchMode(\PDO::FETCH_CLASS, "auth_user", array($pdo));
        do {
            $user = $uid_query->fetch(\PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE | \PDO::FETCH_PROPS_LATE);
            if ($user) {
                array_push($users, $user);
            }
        } while ($user);

        return $users;
    }
    // }}} 
       
    // {{{ get_useragent()
    /**
     * gets a user-object by sid (session-id) directly from database
     *
     * @public
     *
     * @param       PDO     $pdo        pdo object for database access
     * @param       string  $sid        session id
     *
     * @return      auth_user
     */
    public function get_useragent() {
        $cachepath = DEPAGE_CACHE_PATH . "browscap/";
        if (!is_dir($cachepath)) {
            mkdir($cachepath, 0777, true);
        }
        
        if (ini_get("browscap")) {
            $info = get_browser($this->useragent);
        } else {
            $browscap = new browscap($cachepath);
            $browscap->silent = true;
            $browscap->doAutoUpdate = false; // don't update now
            $browscap->lowercase = true; 
            //$browscap->updateMethod = Browscap::UPDATE_CURL;
            $info = $browscap->getBrowser($this->useragent);
        }
        
        return "{$info->browser} {$info->version} on {$info->platform}";
    }
    // }}}
    // logout {{{
    /**
     * Logout
     * 
     * Called when the user is logged out.
     * 
     * Override in inheriting classes to provide session end functionality.
     * 
     * @param $session_id
     * 
     * @return void
     */
    public function logout($session_id) {
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
