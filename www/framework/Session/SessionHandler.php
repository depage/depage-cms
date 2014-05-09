<?php 

namespace depage\Session;

class SessionHandler implements \SessionHandlerInterface
{
    /**
     * @brief tableName
     **/
    protected $tableName = null;

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
            $this->tableName = $pdo->prefix . "_auth_sessions";
        } else {
            $this->tableName = "auth_sessions";
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
        return true;
    }
    // }}}
    // {{{ read()
    /**
     * @brief read
     *
     * @param string $session_id
     * @return string
     **/
    public function read($session_id)
    {
        $query = $this->pdo->prepare(
            "SELECT 
                sid, data
            FROM 
                {$this->tableName}
            WHERE
                sid = :sid AND
                ip = :ip
            LIMIT 1"
        );
        $query->execute(array(
            ':sid' => $session_id,
            ':ip' => $_SERVER['REMOTE_ADDR'],
        ));
        $result = $query->fetchObject();

        if ($result) {
            return $result->data;
        } else {
            return "";
        }
    }
    // }}}
    // {{{ write()
    /**
     * @brief write
     *
     * @param string $session_id
     * @param string $session_data
     * @return bool
     **/
    public function write($session_id, $session_data)
    {
        $query = $this->pdo->prepare(
            "INSERT INTO
                {$this->tableName}
            SET
                sid = :sid,
                ip = :ip,
                data = :data1,
                last_update = NOW(),
                useragent = :useragent
            ON DUPLICATE KEY UPDATE
                data = :data2,
                last_update = NOW()
                "
        );
        $query->execute(array(
            ':sid' => $session_id,
            ':ip' => $_SERVER['REMOTE_ADDR'],
            ':useragent' => $_SERVER['HTTP_USER_AGENT'],
            ':data1' => $session_data,
            ':data2' => $session_data,
        ));

        return true;
    }
    // }}}
    // {{{ destroy()
    /**
     * @brief destroy
     *
     * @param string $session_id
     * @return bool
     **/
    public function destroy($session_id)
    {
        $query = $this->pdo->prepare(
            "DELETE FROM
                {$this->tableName}
            WHERE
                sid = :sid"
        );
        $query->execute(array(
            ':sid' => $session_id,
        ));
        
        return true;
    }
    // }}}
    // {{{ gc()
    /**
     * @brief gc
     *
     * @param mixed $maxlifetime
     * @return true
     **/
    public function gc($maxlifetime)
    {
        
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker : */
