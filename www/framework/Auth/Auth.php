<?php
/**
 * @file    auth.php
 *
 * User and Session Handling Library
 *
 * This file contains classes for session
 * handling.
 *
 *
 * copyright (c) 2002-2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 *
 * @todo look into http://www.openwall.com/articles/PHP-Users-Passwords
 */

namespace Depage\Auth;

/**
 * contains functions for handling user authentication
 * and session handling.
 */
abstract class Auth
{
    // {{{ variables
    public $realm = "depage::cms";
    public $digestCompat = false;
    public $sid, $uid;
    public $valid = false;
    public $sessionLifetime = 172801; // in seconds
    public $privateKey = "private Key";
    public $justLoggedOut = false;
    public $loginUrl = "login/";
    public $logoutUrl = "logout/";

    protected $domain = "";
    protected $user = null;
    protected $pdo;
    protected $log = null;
    // }}}

    // {{{ factory()
    /**
     * factory method
     *
     * @public
     *
     * @param       Depage\Db\Pdo  $pdo        depage\Db\PDO object for database access
     * @param       string  $realm      realm to use for http-basic and http-digest auth
     * @param       domain  $domain     domain to use for cookie and auth validity
     *
     * @return      void
     */
    public static function factory($pdo, $realm, $domain, $method, $digestCompat = false) {
        $method = str_replace("_", "-", $method);

        // @TODO add https option to enforce https with login attempts
        if ($method == "http-digest" && $digestCompat) {
            return new Methods\HttpDigest($pdo, $realm, $domain, $digestCompat);
        } elseif ($method == "http-basic") {
            return new Methods\HttpBasic($pdo, $realm, $domain, $digestCompat);
        } else {
            return new Methods\HttpCookie($pdo, $realm, $domain, $digestCompat);
        }
    }
    // }}}

    // {{{ constructor()
    /**
     * constructor
     *
     * @public
     *
     * @param       Depage\Db\Pdo  $pdo        depage\Db\Pdo object for database access
     * @param       string  $realm      realm to use for http-basic and http-digest auth
     * @param       domain  $domain     domain to use for cookie and auth validity
     *
     * @return      void
     */
    public function __construct($pdo, $realm, $domain, $digestCompat = false) {
        $this->pdo = $pdo;
        $this->realm = $realm;
        $this->domain = $domain;
        $this->digestCompat = $digestCompat;

        if (class_exists("\\Depage\\Log\\Log")) {
            $this->log = new \Depage\Log\Log();
        }
    }
    // }}}
    // {{{ enforce()
    /**
     * enforces authentication
     *
     * @public
     *
     * @param       string  $method     method to use for authentication. Can be http
     * @return      void
     */
    abstract public function enforce($testUserFunction = null);
    // }}}
    // {{{ enforceLazy()
    /**
     * enforces authentication
     *
     * @public
     *
     * @param       string  $method     method to use for authentication. Can be http
     * @return      void
     */
    abstract public function enforceLazy();
    // }}}
    // {{{ enforceLogout()
    /**
     * enforces authentication
     *
     * @public
     *
     * @param       string  $method     method to use for authentication. Can be http
     * @return      void
     */
    abstract public function enforceLogout();
    // }}}

    // {{{ isValidSid()
    protected function isValidSid($sid) {
        // test for validity
        $session_query = $this->pdo->prepare(
            "SELECT
                sid, userid
            FROM
                {$this->pdo->prefix}_auth_sessions
            WHERE
                sid = :sid
            LIMIT 1"
        );
        $session_query->execute(array(
            ':sid' => $sid,
        ));
        $result = $session_query->fetchAll();

        if (count($result) > 0) {
            // set new timestamp
            $timestamp_query = $this->pdo->prepare(
                "UPDATE
                    {$this->pdo->prefix}_auth_sessions
                SET
                    dateLastUpdate = NOW()
                WHERE
                    sid = :sid AND
                    ip = :ip"
            );
            $timestamp_query->execute(array(
                ':sid' => $sid,
                ':ip' => \Depage\Http\Request::getRequestIp(),
            ));

            $this->uid = $result[0]['userid'];
            $this->valid = true;

            return $this->uid;
        } else {
            $this->valid = false;

            return false;
        }
    }
    // }}}
    // {{{ setSid()
    function setSid($sid) {
        $this->sid = $sid;

        return $sid;
    }
    // }}}
    // {{{ getSid()
    protected function getSid() {
        if (!$this->valid) {
            if (!$this->isValidSid($this->sid)) {
                $this->registerSession();
            }
        }
        return $this->sid;
    }
    // }}}
    // {{{ getNewSid()
    protected function getNewSid() {
        session_start();
        session_regenerate_id();

        $this->sid = session_id();

        return $this->sid;
    }
    // }}}
    // {{{ uniqid16()
    /**
     * generates a uniqid, used for sessions.
     *
     * @public
     *
     * @return    $id (string) new id
     */
    protected function uniqid16() {
        return uniqid(dechex(mt_rand(256, 4095)));
    }
    // }}}
    // {{{ registerSession()
    protected function registerSession($uid = null, $sid = null) {
        if (is_null($sid)) {
            $this->sid = $this->getNewSid();
        } else {
            $this->sid = $sid;
        }
        $ip = \Depage\Http\Request::getRequestIp();
        if (is_null($uid)) {
            $update_query = $this->pdo->prepare(
                "REPLACE INTO
                    {$this->pdo->prefix}_auth_sessions
                SET
                    sid = :sid,
                    dateLastUpdate = NOW(),
                    ip = :ip,
                    useragent = :useragent"
            )->execute(array(
                'sid' => $this->sid,
                'ip' => $ip,
                'useragent' => $_SERVER['HTTP_USER_AGENT'],
            ));
        } else {
            $this->uid = $uid;
            $update_query = $this->pdo->prepare(
                "REPLACE INTO
                    {$this->pdo->prefix}_auth_sessions
                SET
                    sid = :sid,
                    userid = :uid,
                    dateLogin = NOW(),
                    dateLastUpdate = NOW(),
                    ip = :ip,
                    useragent = :useragent"
            )->execute(array(
                'sid' => $this->sid,
                'uid' => $this->uid,
                'ip' => $ip,
                'useragent' => $_SERVER['HTTP_USER_AGENT'],
            ));

            // update time of last login in user-table
            $update_query = $this->pdo->prepare(
                "UPDATE
                    {$this->pdo->prefix}_auth_user
                SET
                    loginTimeout = 0,
                    dateLastlogin = NOW()
                WHERE
                    id = :uid"
            )->execute(array(
                ':uid' => $this->uid,
            ));

            // add login entry to auth log
            $query = $this->pdo->prepare(
                "INSERT INTO
                    {$this->pdo->prefix}_auth_log
                SET
                    userid = :uid,
                    dateLogin = NOW(),
                    ip = :ip,
                    useragent = :useragent"
            )->execute(array(
                'uid' => $this->uid,
                'ip' => $ip,
                'useragent' => $_SERVER['HTTP_USER_AGENT'],
            ));
        }

        $this->valid = true;

        return $sid;
    }
    // }}}
    // {{{ prolongLogin()
    protected function prolongLogin($user) {
        if ($user->loginTimeout == 0) {
            $user->loginTimeout = 100;
        } else if ($user->loginTimeout < 20000) {
            $user->loginTimeout *= 2;
        }
        $user->save();

        usleep($user->loginTimeout * 1000);
    }
    // }}}

    // {{{ updatePasswordHash()
    /**
     * @brief updated password hash if a new algorithm is chosen for password hashing
     *
     * @param mixed $username
     * @param mixed $password
     * @return bool
     **/
    protected function updatePasswordHash($user, $password)
    {
        $pass = new \Depage\Auth\Password($this->realm, $this->digestCompat);

        if ($pass->needsRehash($user->passwordhash)) {
            $user->passwordhash = $pass->hash($user->name, $password);

            $user->save();

            return true;
        } else {
            return false;
        }
    }
    // }}}

    // {{{ getActiveUsers()
    function getActiveUsers() {
        return User::loadActive($this->pdo);
    }
    // }}}

    // {{{ getSessionName()
    public static function getSessionName($realm, $domain): string
    {
        $url = parse_url($domain);

        $cookiePrefix = $realm . "-" . $url['host'];
        $cookiePrefix = preg_replace("/[^-_a-zA-Z0-9]/", "", $cookiePrefix);
        $cookiePrefix = trim($cookiePrefix, "-");
        if (!empty($cookiePrefix)) {
            $sessionName = "$cookiePrefix-sid";
        }

        return $sessionName;
    }
    // }}}

    // {{{ logout()
    /**
     * logs user out
     *
     * @public
     *
     * @param    $sid (string) session id
     */
    public function logout($sid = null) {
        if ($sid == null) {
            $sid = $this->sid;

            //remove session
            $this->destroySession();
        }

        // get user object for info
        $user = User::loadBySid($this->pdo, $sid);
        if ($user) {
            $user->onLogout($sid);
            if (!empty($this->log)) {
                $this->log->log("'{$user->name}' has logged out with $sid", "auth");
            }
        }

        // delete session data for sid
        $delete_query = $this->pdo->prepare(
            "DELETE FROM
                {$this->pdo->prefix}_auth_sessions
            WHERE
                sid = :sid"
        );
        $delete_query->execute(array(
            ':sid' => $sid,
        ));
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
        $schema = new \Depage\Db\Schema($pdo);

        $schema->setReplace(
            function ($tableName) use ($pdo) {
                return $pdo->prefix . $tableName;
            }
        );
        $schema->loadGlob(__DIR__ . "/Sql/*.sql");
        $schema->update();
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
