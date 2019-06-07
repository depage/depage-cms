<?php
/**
 * @file    auth_user.php
 *
 *
 * copyright (c) 2002-2010 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Auth;

/**
 * contains functions for handling user authentication
 * and session handling.
 */
class User extends \Depage\Entity\Entity
{
    // {{{ variables
    /**
     * @brief fields
     **/
    static protected $fields = array(
        "type" => __CLASS__,
        "id" => null,
        "name" => "",
        "fullname" => "",
        "sortname" => "",
        "passwordhash" => "",
        "email" => "",
        "settings" => "",
        "dateRegistered" => null,
        "dateLastlogin" => null,
        "dateUpdated" => null,
        "dateResetPassword" => null,
        "confirmId" => null,
        "resetPasswordId" => null,
        "loginTimeout" => 0,
    );

    /**
     * @brief primary
     **/
    static protected $primary = array("id");

    /**
     * @brief pdo object for database access
     **/
    protected $pdo = null;

    /**
     * @brief useragent
     **/
     protected $useragent = "";

    /**
     * @brief string sid of user when load from loadActive()
     **/
    public $sid = null;
    // }}}

    // {{{ constructor()
    /**
     * constructor
     *
     * @public
     *
     * @param       Depage\Db\Pdo     $pdo        pdo object for database access
     *
     * @return      void
     */
    public function __construct(\Depage\Db\Pdo $pdo) {
        parent::__construct($pdo);

        $this->pdo = $pdo;

        // set class to called class (for subclasses of Depage\Auth\User)
        $this->data["type"] = get_class($this);
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
        $fields = "type, " . implode(", ", self::getFields());

        $uid_query = $pdo->prepare(
            "SELECT $fields
            FROM
                {$pdo->prefix}_auth_user AS user
            WHERE
                name = :name"
        );

        $uid_query->execute(array(
            ':name' => $username,
        ));

        // pass pdo-instance to constructor
        $uid_query->setFetchMode(\PDO::FETCH_CLASS, "Depage\\Auth\\User", array($pdo));
        $user = $uid_query->fetch(\PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE);

        if (!$user) {
            throw new Exceptions\User("user '$username' does not exist.");
        }
        $user->onLoad();

        return $user;
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
        $fields = "type, " . implode(", ", self::getFields());

        $uid_query = $pdo->prepare(
            "SELECT $fields
            FROM
                {$pdo->prefix}_auth_user AS user
            WHERE
                email = :email"
        );

        $uid_query->execute(array(
            ':email' => $email,
        ));

        // pass pdo-instance to constructor
        $uid_query->setFetchMode(\PDO::FETCH_CLASS, "Depage\\Auth\\User", array($pdo));
        $user = $uid_query->fetch(\PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE);

        if (!$user) {
            throw new Exceptions\User("user with email '$email' does not exist.");
        }
        $user->onLoad();

        return $user;
    }
    // }}}
    // {{{ loadBySid()
    /**
     * gets a user-object by sid (session-id) directly from database
     *
     * @public
     *
     * @param       Depage\Db\Pdo     $pdo        pdo object for database access
     * @param       string  $sid        session id
     *
     * @return      auth_user
     */
    static public function loadBySid($pdo, $sid) {
        $fields = "type, " . implode(", ", self::getFields());

        $uid_query = $pdo->prepare(
            "SELECT $fields, sessions.sid as sid
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
        $uid_query->setFetchMode(\PDO::FETCH_CLASS, "Depage\\Auth\\User", array($pdo));
        $user = $uid_query->fetch(\PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE);

        if ($user) {
            $user->onLoad();
        }

        return $user;
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
        $fields = "type, " . implode(", ", self::getFields());

        $uid_query = $pdo->prepare(
            "SELECT $fields
            FROM
                {$pdo->prefix}_auth_user AS user
            WHERE
                id = :id"
        );
        $uid_query->execute(array(
            ':id' => $id,
        ));

        // pass pdo-instance to constructor
        $uid_query->setFetchMode(\PDO::FETCH_CLASS, "Depage\\Auth\\User", array($pdo));
        $user = $uid_query->fetch(\PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE);

        if ($user) {
            $user->onLoad();
        }

        return $user;
    }
    // }}}
    // {{{ loadByConfirmId()
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
    static public function loadByConfirmId($pdo, $confirmId) {
        $fields = "type, " . implode(", ", array_keys(self::$fields));

        $uid_query = $pdo->prepare(
            "SELECT $fields
            FROM
                {$pdo->prefix}_auth_user AS user
            WHERE
                confirmId = :confirmId"
        );
        $uid_query->execute(array(
            ':confirmId' => $confirmId,
        ));

        // pass pdo-instance to constructor
        $uid_query->setFetchMode(\PDO::FETCH_CLASS, "Depage\\Auth\\User", array($pdo));
        $user = $uid_query->fetch(\PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE);

        if ($user) {
            $user->onLoad();
        }

        return $user;
    }
    // }}}
    // {{{ loadByResetPasswordId()
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
    static public function loadByResetPasswordId($pdo, $resetPasswordId) {
        $fields = "type, " . implode(", ", array_keys(self::$fields));

        $uid_query = $pdo->prepare(
            "SELECT $fields
            FROM
                {$pdo->prefix}_auth_user AS user
            WHERE
                resetPasswordId = :resetPasswordId"
        );
        $uid_query->execute(array(
            ':resetPasswordId' => $resetPasswordId,
        ));

        // pass pdo-instance to constructor
        $uid_query->setFetchMode(\PDO::FETCH_CLASS, "Depage\\Auth\\User", array($pdo));
        $user = $uid_query->fetch(\PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE);

        if ($user) {
            $user->onLoad();
        }

        return $user;
    }
    // }}}
    // {{{ loadActive()
    /**
     * gets an array of user-objects
     *
     * @public
     *
     * @param       Depage\Db\Pdo     $pdo        pdo object for database access
     * @param       int     $id         id of the user
     *
     * @return      auth_user
     */
    static public function loadActive($pdo) {
        $users = array();
        $fields = "type, " . implode(", ", self::getFields());

        $uid_query = $pdo->prepare(
            "SELECT $fields,
                sessions.project AS project,
                sessions.ip AS ip,
                sessions.sid AS sid,
                sessions.dateLastUpdate AS dateLastUpdate,
                sessions.useragent AS useragent
            FROM
                {$pdo->prefix}_auth_user AS user,
                {$pdo->prefix}_auth_sessions AS sessions
            WHERE
                user.id=sessions.userid and
                sessions.dateLastUpdate > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY user.sortname"
        );
        $uid_query->execute();

        // pass pdo-instance to constructor
        $uid_query->setFetchMode(\PDO::FETCH_CLASS, "Depage\\Auth\\User", array($pdo));
        do {
            $user = $uid_query->fetch(\PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE);
            if ($user) {
                $user->onLoad();
                $users[] = $user;
            }
        } while ($user);

        return $users;
    }
    // }}}
    // {{{ loadAll()
    /**
     * gets an array of user-objects
     *
     * @public
     *
     * @param       Depage\Db\Pdo     $pdo        pdo object for database access
     * @param       int     $id         id of the user
     *
     * @return      auth_user
     */
    static public function loadAll($pdo) {
        $users = array();
        $fields = "type, " . implode(", ", self::getFields());

        $uid_query = $pdo->prepare(
            "SELECT $fields
            FROM
                {$pdo->prefix}_auth_user AS user
            ORDER BY user.sortname"
        );
        $uid_query->execute();

        // pass pdo-instance to constructor
        $uid_query->setFetchMode(\PDO::FETCH_CLASS, "Depage\\Auth\\User", array($pdo));
        do {
            $user = $uid_query->fetch(\PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE);
            if ($user) {
                $user->onLoad();
                $users[$user->id] = $user;
            }
        } while ($user);

        return $users;
    }
    // }}}

    // {{{ setFullname()
    /**
     * @brief setFullname
     *
     * @param mixed $value
     * @return void
     **/
    protected function setFullname($value)
    {
        $this->data['fullname'] = $value;
        $this->dirty['fullname'] = true;

        $nameparts = explode(" ", trim($value));
        $this->data['sortname'] = end($nameparts);
        $this->dirty['sortname'] = true;

        return $this;
    }
    // }}}
    // {{{ setSortname()
    /**
     * @brief setSortname
     *
     * @param mixed $value
     * @return void
     **/
    protected function setSortname($value)
    {

    }
    // }}}
    // {{{ setPassword()
    /**
     * @brief setPassword
     *
     * @param mixed $
     * @return void
     **/
    public function setPassword($newPassword, $authDomain = "")
    {
        $pass = new \Depage\Auth\Password($authDomain);
        $this->passwordhash = $pass->hash($this->name, $newPassword);

        return $this;
    }
    // }}}

    // {{{ save()
    /**
     * save a user object
     *
     * @public
     *
     * @return      auth_user
     */
    public function save() {
        $fields = [];
        $params = [];
        $primary = self::$primary[0];
        $isNew = $this->data[$primary] === null;

        if ($isNew) {
            $this->dateRegistered = date("Y-m-d H:i:s");
            $this->loginTimeout = 0;
        }

        $dirty = array_keys(array_intersect_key($this->dirty, self::$fields), true);
        if (count($dirty) > 0) {
            if ($isNew) {
                $query = "INSERT INTO {$this->pdo->prefix}_auth_user";
            } else {
                $query = "UPDATE {$this->pdo->prefix}_auth_user";
            }
            foreach ($dirty as $key) {
                $fields[] = "$key=:$key";
                $params[$key] = $this->data[$key];
            }
            $query .= " SET " . implode(",", $fields);

            if (!$isNew) {
                $query .= " WHERE $primary=:$primary";
                $params[$primary] = $this->data[$primary];
            }

            $cmd = $this->pdo->prepare($query);
            $success = $cmd->execute($params);

            if ($isNew) {
                $this->data[$primary] = $this->pdo->lastInsertId();
            }

            if ($success) {
                foreach (static::$fields as $key => $default) {
                    $this->dirty[$key] = false;
                }
            }
        }
    }
    // }}}

    // {{{ getUseragent()
    /**
     * gets a user-object by sid (session-id) directly from database
     *
     * @public
     *
     * @param       Depage\Db\Pdo     $pdo        pdo object for database access
     * @param       string  $sid        session id
     *
     * @return      auth_user
     */
    public function getUseragent() {
        $parser = \UAParser\Parser::create();
        $result = $parser->parse($this->useragent);

        return $result->toString();
    }
    // }}}

    // {{{ onLogout
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
    public function onLogout($sid) {
    }
    // }}}
    // {{{ onLoad()
    /**
     * @brief onLoad
     *
     * @param mixed
     * @return void
     **/
    protected function onLoad()
    {
        // can be overridden by child class
    }
    // }}}

}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
