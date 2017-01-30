<?php

namespace depage\Session;

class SessionHandler implements \SessionHandlerInterface
{
    /**
     * @brief tableName
     **/
    protected $tableName = "auth_sessions";

    /**
     * @brief sessionLock
     **/
    protected $sessionLock = null;

    // {{{ register()
    public static function register($pdo)
    {
        $class = __CLASS__;

        $handler = new $class($pdo);

        session_set_save_handler($handler, true);
    }
    // }}}
    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $pdo
     * @return void
     **/
    protected function __construct($pdo)
    {
        $this->pdo = $pdo;

        if (isset($pdo->prefix)) {
            $this->tableName = $pdo->prefix . "_" . $this->tableName;
        }
    }
    // }}}

    // {{{ open()
    /**
     * @brief open
     *
     * @param string $save_path
     * @param string $name
     * @return bool
     **/
    public function open($save_path, $name)
    {
        return true;
    }
    // }}}
    // {{{ close()
    /**
     * @brief close
     *
     * @return bool
     **/
    public function close()
    {
        // release session lock
        $result = $this->pdo->query("SELECT RELEASE_LOCK(\"$this->sessionLock\")");

        return true;
    }
    // }}}
    // {{{ read()
    /**
     * @brief read
     *
     * @param string $sessionId
     * @return string
     **/
    public function read($sessionId)
    {
        // aquire session lock
        $this->sessionLock = $this->pdo->quote("session_$sessionId");
        $result = $this->pdo->query("SELECT GET_LOCK(\"$this->sessionLock\", 60)");

        if (count($result) != 1) {
            die("could not obtain session lock!");
        }

        // get session data
        $query = $this->pdo->prepare(
            "SELECT
                sid, sessionData
            FROM
                {$this->tableName}
            WHERE
                sid = :sid
            LIMIT 1"
        );
        $query->execute(array(
            ':sid' => $sessionId,
        ));
        $result = $query->fetchObject();

        if ($result) {
            return $result->sessionData;
        } else {
            return "";
        }
    }
    // }}}
    // {{{ write()
    /**
     * @brief write
     *
     * @param string $sessionId
     * @param string $sessionData
     * @return bool
     **/
    public function write($sessionId, $sessionData)
    {
        $query = $this->pdo->prepare(
            "INSERT INTO
                {$this->tableName}
            SET
                sid = :sid,
                ip = :ip,
                sessionData = :data1,
                dateLastUpdate = NOW(),
                useragent = :useragent
            ON DUPLICATE KEY UPDATE
                sessionData = :data2,
                dateLastUpdate = NOW()
                "
        );
        $query->execute(array(
            ':sid' => $sessionId,
            ':ip' => \Depage\Http\Request::getRequestIp(),
            ':useragent' => $_SERVER['HTTP_USER_AGENT'],
            ':data1' => $sessionData,
            ':data2' => $sessionData,
        ));

        return true;
    }
    // }}}
    // {{{ destroy()
    /**
     * @brief destroy
     *
     * @param string $sessionId
     * @return bool
     **/
    public function destroy($sessionId)
    {
        // logout user -> load user first
        if (class_exists("\\Depage\\Auth\\User")) {
            $user = \Depage\Auth\User::loadBySid($this->pdo, $sessionId);
            if ($user) {
                $log = new \Depage\Log\Log();
                $log->log("logging out $user->name ($user->fullname)");

                $user->onLogout($sessionId);
            }

        }

        $query = $this->pdo->prepare(
            "DELETE FROM
                {$this->tableName}
            WHERE
                sid = :sid"
        );
        $query->execute(array(
            ':sid' => $sessionId,
        ));

        return true;
    }
    // }}}
    // {{{ gc()
    /**
     * @brief garbage collector
     *
     * @param mixed $maxlifetime
     * @return true
     **/
    public function gc($maxlifetime)
    {
        // destroy every session of loggedin users
        $query = $this->pdo->prepare(
            "SELECT
                sid
            FROM
                $this->tableName
            WHERE
                userid IS NOT NULL AND
                dateLastUpdate < DATE_SUB(NOW(), INTERVAL :maxlifetime SECOND)"
        );
        $query->execute(array(
            ':maxlifetime' => $maxlifetime,
        ));

        while ($result = $query->fetchObject()) {
            $this->destroy($result->sid);
        }

        // delete remaining sessions
        $result = $this->pdo->query(
            "DELETE FROM
                $this->tableName
            WHERE
                userid IS NULL AND
                dateLastUpdate < DATE_SUB(NOW(), INTERVAL $maxlifetime SECOND)"
        );

        return true;
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker : */
