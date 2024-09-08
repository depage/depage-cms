<?php

namespace Depage\Session;

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

    /**
     * @brief sessionData
     **/
    protected $sessionData = "";

    /**
     * @brief pdo
     **/
    protected $pdo;

    /**
     * @brief lockWaitTime
     **/
    protected $lockWaitTime = 10;

    /**
     * @brief seqno
     **/
    protected $seqno = 0;

    // {{{ register()
    public static function register($pdo, $localWaitTime = 10)
    {
        $class = __CLASS__;

        $handler = new $class($pdo, $localWaitTime);

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
    protected function __construct($pdo, $localWaitTime = 10)
    {
        $this->pdo = $pdo;
        $this->lockWaitTime = $localWaitTime;

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
    public function open(string $save_path, string $name):bool
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
    public function close():bool
    {
        if ($this->lockWaitTime > 0) {
            // release session lock
            $result = $this->pdo->query("SELECT RELEASE_LOCK(\"$this->sessionLock\")");
        }

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
    public function read($sessionId):string|false
    {
        if ($this->lockWaitTime > 0) {
            // aquire session lock
            $this->sessionLock = $this->pdo->quote("session_$sessionId");
            $result = $this->pdo->query("SELECT GET_LOCK(\"$this->sessionLock\", $this->lockWaitTime)");

            if (!$result || $result->fetchColumn() != 1) {
                die("could not obtain session lock!");
            }
        }

        // get session data
        $query = $this->pdo->prepare(
            "SELECT
                sid, seqno, sessionData
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
            $this->sessionData = $result->sessionData;
            $this->seqno = $result->seqno;

            return $this->sessionData;
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
    public function write(string $sessionId, string $sessionData):bool
    {
        // only update timestamp when session data has not changed
        if ($this->sessionData === $sessionData) {
            $query = $this->pdo->prepare(
                "UPDATE
                    {$this->tableName}
                SET
                    dateLastUpdate = NOW()
                WHERE
                    sid = :sid
                    "
            )->execute([
                ':sid' => $sessionId,
            ]);

            return true;
        }

        // only update session data if seqno has not changed
        $query = $this->pdo->prepare(
            "INSERT INTO
                {$this->tableName}
            SET
                sid = :sid,
                ip = :ip,
                seqno = :seqno1 + 1,
                sessionData = :data1,
                dateLastUpdate = NOW(),
                useragent = :useragent
            ON DUPLICATE KEY UPDATE
                sessionData = IF(seqno = :seqno2, :data2, VALUES(sessionData)),
                dateLastUpdate = NOW(),
                seqno = IF(seqno = :seqno3, seqno + 1, VALUES(seqno))
                "
        );
        $query->execute(array(
            ':sid' => $sessionId,
            ':ip' => \Depage\Http\Request::getRequestIp(),
            ':useragent' => $_SERVER['HTTP_USER_AGENT'] ?? "",
            ':data1' => $sessionData,
            ':data2' => $sessionData,
            ':seqno1' => $this->seqno,
            ':seqno2' => $this->seqno,
            ':seqno3' => $this->seqno,
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
    public function destroy(string $sessionId):bool
    {
        // logout user -> load user first
        if (class_exists("\\Depage\\Auth\\User")) {
            $user = \Depage\Auth\User::loadBySid($this->pdo, $sessionId);
            if ($user) {
                if (class_exists('Depage\Log\Log')) {
                    $log = new \Depage\Log\Log();
                    $log->log("logging out $user->name ($user->fullname)");
                } else {
                    error_log("logging out $user->name ($user->fullname)");
                }

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
    public function gc(int $maxlifetime):int|false
    {
        $count = 0;

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
            $count++;
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
        $count += $result->rowCount();

        return $count;
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker : */
