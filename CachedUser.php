<?php
/**
 * @file    CachedUser.php
 *
 * description
 *
 * copyright (c) 2019 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Auth;

/**
 * @brief CachedUser
 * Class CachedUser
 *
 * Allows to load Users but caches them in memory
 * to allow for faster access and with lesser database
 * queries
 */
class CachedUser
{
    /**
     * @brief usersByUsername
     **/
    static private $usersByUsername = [];

    /**
     * @brief usersByEmail
     **/
    static protected $usersByEmail = [];

    /**
     * @brief usersById
     **/
    static protected $usersById = [];

    // {{{ cacheUser()
    /**
     * @brief cacheUser
     *
     * @param mixed $user
     * @return void
     **/
    protected static function cacheUser($user)
    {
        self::$usersById[$user->id] = $user;
        self::$usersByUsername[strtolower($user->name)] = $user;
        self::$usersByEmail[strtolower($user->email)] = $user;
    }
    // }}}
    // {{{ clearCache()
    /**
     * @brief clearCache
     *
     * @return void
     **/
    public static function clearCache()
    {
        self::$usersById = [];
        self::$usersByUsername = [];
        self::$usersByEmail = [];
    }
    // }}}

    // {{{ loadByUsername()
    /**
     * gets a user-object by username directly from database
     *
     * @public
     *
     * @param       \Depage\Db\Pdo     $pdo        pdo object for database access
     * @param       string  $username   username of the user
     *
     * @return      User
     */
    static public function loadByUsername($pdo, $username) {
        if (!isset(self::$usersByUsername[strtolower($username)])) {
            self::cacheUser(User::loadByUsername($pdo, $username));
        }

        return self::$usersByUsername[strtolower($username)];
    }
    // }}}
    // {{{ loadByEmail()
    /**
     * gets a user-object by username directly from database
     *
     * @public
     *
     * @param       Depage\Db\Pdo     $pdo        pdo object for database access
     * @param       string  $email      email of the user
     *
     * @return      User
     */
    static public function loadByEmail($pdo, $email) {
        if (!isset(self::$usersByEmail[strtolower($email)])) {
            self::cacheUser(User::loadByEmail($pdo, $email));
        }

        return self::$usersByEmail[strtolower($email)];
    }
    // }}}
    // {{{ loadById()
    /**
     * gets a user-object by id directly from database
     *
     * @public
     *
     * @param       Depage\Db\Pdo     $pdo        pdo object for database access
     * @param       int     $id         id of the user
     *
     * @return      auth_user
     */
    static public function loadById($pdo, $id) {
        if (!isset(self::$usersByIs[$id])) {
            self::cacheUser(User::loadById($pdo, $id));
        }

        return self::$usersById[$id];
    }
    // }}}
}



// vim:set ft=php sw=4 sts=4 fdm=marker et :
