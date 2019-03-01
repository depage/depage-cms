<?php
/**
 * @file    framework/task/task_runner.php
 *
 * depage cms task runner module
 *
 *
 * copyright (c) 2011-2014 Frank Hellenkamp [jonas@depage.net]
 * copyright (c) 2011 Lion Vollnhals [lion.vollnhals@googlemail.com]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 * @author    Lion Vollnhals [lion.vollnhals@googlemail.com]
 */

namespace Depage\Tasks;

class Task {
    private $tmpvars = array();

    /**
     * @brief fp file pointer to file lock
     **/
    protected $lockFp = null;

    /**
     * @brief int Id of Task
     **/
    public $taskId = null;

    /**
     * @brief string name of task
     **/
    public $taskName = "";

    /**
     * @brief string name/filter for project name
     **/
    public $projectName = "";

    /**
     * @brief string current status of task
     **/
    public $status = "generating";

    /**
     * @brief string table name for tasks
     **/
    protected $tableTasks = "";

    /**
     * @brief string table name for subtasks
     **/
    protected $tableSubtasks = "";

    /**
     * @brief numberOfSubtasks number of subtasks to load at the same time
     **/
    protected $numberOfSubtasks = 2000;

    /**
     * @brief timeToCheckSubtasks seconds after which task runner will check for new subtask
     **/
    protected $timeToCheckSubtasks = 30;

    /**
     * @brief lastCheck time of last check for new subtasks
     **/
    protected $lastCheck = null;

    /**
     * @brief subTasksRun array of subtask ids that where already run
     **/
    protected $subTasksRun = array();


    // {{{ constructor
    private function __construct($pdo) {
        $this->pdo = $pdo;
        $this->tableTasks = $this->pdo->prefix . "_tasks";
        $this->tableSubtasks = $this->pdo->prefix . "_subtasks";

    }
    // }}}

    // static functions
    // {{{ load()
    static public function load($pdo, $taskId) {
        $task = new Task($pdo);

        $task->taskId = $taskId;

        $task->lockName = sys_get_temp_dir() . '/' . $pdo->prefix . "." . $task->taskId . '.lock';

        if ($task->loadTask()) {
            $task->loadSubtasks();

            return $task;
        } else {
            return false;
        }
    }
    // }}}
    // {{{ loadByName()
    static public function loadByName($pdo, $taskName, $condition = "") {
        $task = new Task($pdo);

        if ($condition != "") {
            $condition = " AND ($condition)";
        }

        $query = $pdo->prepare(
            "SELECT id
            FROM {$task->tableTasks}
            WHERE name = :name
                $condition"
        );
        $query->execute(array(
            "name" => $taskName,
        ));

        $tasks = array();

        while ($result = $query->fetchObject()) {
            $tasks[] = Task::load($pdo, $result->id);
        }

        if (count($tasks) == 0) {
            return false;
        } else {
            return $tasks;
        }
    }
    // }}}
    // {{{ loadAll()
    static public function loadAll($pdo) {
        $task = new Task($pdo);

        $query = $pdo->prepare(
            "SELECT id
            FROM {$task->tableTasks}"
        );
        $query->execute();

        $tasks = array();

        while ($result = $query->fetchObject()) {
            $tasks[] = Task::load($pdo, $result->id);
        }

        return $tasks;
    }
    // }}}
    // {{{ loadOrCreate()
    static public function loadOrCreate($pdo, $taskName, $projectName = "") {
        list($task) = self::loadByName($pdo, $taskName, "status IS NULL OR status != 'failed'");

        if (!$task) {
            $task = self::create($pdo, $taskName, $projectName);
        }

        return $task;
    }
    // }}}
    // {{{ create()
    static public function create($pdo, $taskName, $projectName = "") {
        $task = new Task($pdo);

        $task->taskId = $task->createTask($taskName, $projectName);
        $task->lockName = sys_get_temp_dir() . '/' . $pdo->prefix . "." . $task->taskId . '.lock';

        $task->loadTask();

        return $task;
    }
    // }}}

    // {{{ escapeParam()
    static public function escapeParam($param) {
        switch (gettype($param)) {
            case 'object':
                return "unserialize(" . var_export(serialize($param), true) . ")";
            break;
            case 'array':
                $code = "";
                foreach ($param as $key => $val) {
                    $code .= self::escapeParam($key) . " => " . self::escapeParam($val) . ",";
                }
                $code = trim($code, ",");

                return "[$code]";
            break;
            default:
                return var_export($param, true);
        }

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
        $schema = new \Depage\DB\Schema($pdo);

        $schema->setReplace(
            function ($tableName) use ($pdo) {
                return $pdo->prefix . $tableName;
            }
        );

        $schema->loadGlob(__DIR__ . "/Sql/*.sql");
        $schema->update();
    }
    // }}}

    // public functions
    // {{{ remove()
    public function remove() {
        $query = $this->pdo->prepare(
            "DELETE FROM {$this->tableTasks}
            WHERE id = :id"
        );
        return $query->execute(array(
            "id" => $this->taskId,
        ));
    }
    // }}}

    // {{{ begin()
    /**
     * @brief begin
     *
     * @param mixed
     * @return void
     **/
    public function begin()
    {
        $this->setTaskStatus(null);
    }
    // }}}
    // {{{ setTaskStatus()
    public function setTaskStatus($status) {
        $query = $this->pdo->prepare(
            "UPDATE {$this->tableTasks}
            SET status = :status
            WHERE id = :id"
        );
        $query->execute(array(
            "status" => $status,
            "id" => $this->taskId,
        ));
    }
    // }}}
    // {{{ setSubtaskStatus()
    public function setSubtaskStatus($subtask, $status) {
        $query = $this->pdo->prepare(
            "UPDATE {$this->tableSubtasks}
            SET status = :status
            WHERE id = :id"
        );
        $query->execute(array(
            "status" => $status,
            "id" => $subtask->id,
        ));
    }
    // }}}
    // {{{ getNextSubtask();
    public function getNextSubtask() {
        if (time() - $this->timeToCheckSubtasks > $this->lastCheck) {
            // clear subtasks so that subtask have to be reloaded
            $this->subtasks = [];
        }
        $subtask = current($this->subtasks);

        if (!$subtask) {
            // check if there have been added new subtasks to the database
            $this->loadSubtasks();
            $subtask = current($this->subtasks);
        }
        next($this->subtasks);

        return $subtask;
    }
    // }}}
    // {{{ runSubtask()
    /* @return NULL|false returns NULL for no error, false for a parse error */
    public function runSubtask($subtask) {
        // re-add local variables
        foreach ($this->tmpvars as $_tmpindex => &$_tmpvar) {
            $$_tmpindex = &$_tmpvar;
        }

        // evaluate statement
        $value = eval($subtask->php);
        $this->subTasksRun[$subtask->id] = true;

        // unset internal variables
        unset($subtask, $_tmpindex, $_tmpvar);

        $_tmpnames = array_keys(get_defined_vars());
        foreach ($_tmpnames as $_tmpname)
        {
            $this->tmpvars[$_tmpname] = &$$_tmpname;
        }

        return $value;
    }
    // }}}

    // {{{ lock()
    public function lock() {
        $this->lockFp = fopen($this->lockName, 'w');

        $locked = flock($this->lockFp, LOCK_EX | LOCK_NB);

        if ($locked) {
            $query = $this->pdo->prepare(
                "UPDATE {$this->tableTasks}
                SET time_started = NOW()
                WHERE
                    id = :id AND
                    time_started IS NULL"
            );
            $query->execute(array(
                "id" => $this->taskId,
            ));
        }

        return $locked;
    }
    // }}}
    // {{{ unlock()
    public function unlock() {
        if (isset($this->lockFp)) {
            flock($this->lockFp, LOCK_UN);

            unlink($this->lockName);
        }
    }
    // }}}
    // {{{ isRunning()
    /**
     * @brief isLocked
     *
     * @param mixed
     * @return void
     **/
    public function isRunning()
    {
        $this->lockFp = fopen($this->lockName, 'w');

        $locked = flock($this->lockFp, LOCK_EX | LOCK_NB);

        if (!$locked) {
            return true;
        }

        flock($this->lockFp, LOCK_UN);
        fclose($this->lockFp);

        $this->lockFp = null;

        return false;
    }
    // }}}

    // {{{ addSubtask()
    /* addSubtask only creates task in db.
     * the current instance is NOT modified.
     * reload task from the db if you want to execute subtasks.
     *
     * also see addSubtasks for more convenience.
     *
     * @return int return id of created subtask that can be used for depends_on
     */
    public function addSubtask($name, $php, $params = array(), $depends_on = NULL) {
        if (!is_array($params)) {
            $params = array();
        }
        foreach ($params as &$param) {
            $param = $this->escapeParam($param);
        }
        $phpCode = trim(vsprintf($php, $params));
        $query = $this->pdo->prepare(
            "INSERT INTO {$this->tableSubtasks}
                (task_id, name, php, depends_on) VALUES (:taskId, :name, :php, :depends_on)"
        );
        $query->execute(array(
            "taskId" => $this->taskId,
            "name" => $name,
            "php" => $phpCode,
            "depends_on" => $depends_on,
        ));

        if ($this->status == "done") {
            // reset done status when adding new subtasks
            $this->setTaskStatus(null);
        }

        return $this->pdo->lastInsertId();
    }
    // }}}
    // {{{ addSubtasks()
    /* addSubtasks creates multiple subtasks.
     * specify tasks as an array of arrays containing name, php and depends_on keys.
     * depends_on references another task in this array by index.
     */
    public function addSubtasks($tasks) {
        foreach ($tasks as &$task) {
            if (!is_array($task)) {
                throw new \Exception ("malformed task array");
            }

            if (isset($tasks[$task["depends_on"]])) {
                $depends_on = $tasks[$task["depends_on"]]["id"];
            } else {
                $depends_on = NULL;
            }

            $task["id"] = $this->addSubtask($task["name"], $task["php"], array(), $depends_on);
        }
    }
    // }}}

    // {{{ getProgress()
    public function getProgress() {
        $progress = array();

        // {{{ get progress
        $query = $this->pdo->prepare(
            "SELECT
                (SELECT COUNT(*) FROM {$this->tableSubtasks} WHERE task_id = :taskId1) AS num,
                (SELECT COUNT(*) FROM {$this->tableSubtasks} WHERE task_id = :taskId2 AND status = 'done') AS done"
        );
        $query->execute(array(
            "taskId1" => $this->taskId,
            "taskId2" => $this->taskId,
        ));
        $result = $query->fetchObject();

        $tasksSum = $result->num;
        $tasksDone = $result->done > 0 ? $result->done : 0.0001;
        $tasksPlanned = $tasksSum - $tasksDone;

        $progress['percent'] = (int) ($tasksDone / $tasksSum * 100);
        // }}}
        // {{{ get estimated times
        $query = $this->pdo->prepare(
            "SELECT UNIX_TIMESTAMP(time_started) AS time_started, TIMESTAMPDIFF(SECOND, time_started, NOW()) AS time
            FROM {$this->tableTasks}
            WHERE id = :taskId"
        );
        $query->execute(array(
            "taskId" => $this->taskId,
        ));
        $result = $query->fetchObject();

        $progress['estimated'] = (int) (($result->time / $tasksDone) * $tasksPlanned) * 1.1 + 1;
        $progress['time_started'] = (int) $result->time_started;
        // }}}
        // {{{ get name and status of running subtask
        $query = $this->pdo->prepare(
            "SELECT name, status
            FROM {$this->tableSubtasks}
            WHERE
                task_id = :taskId AND
                (status IS NULL OR status != 'done')
            ORDER BY id ASC
            LIMIT 1"
        );
        $query->execute(array(
            "taskId" => $this->taskId,
        ));
        $result = $query->fetchObject();

        if ($result) {
            $progress['description'] = $result->name;
            $progress['status'] = $result->status;
        } else {
            $progress['description'] = "";
            $progress['status'] = "";
        }
        // }}}

        return (object) $progress;
    }
    // }}}

    // private functions
    // {{{ createTask()
    private function createTask($taskName, $projectName = "") {
        $query = $this->pdo->prepare(
            "INSERT INTO {$this->tableTasks}
                (name, projectname, status, time_added) VALUES (:name, :projectName, :status, NOW())"
        );
        $query->execute(array(
            "name" => $taskName,
            "projectName" => $projectName,
            "status" => "generating",
        ));

        return $this->pdo->lastInsertId();
    }
    // }}}
    // {{{ loadTask();
    private function loadTask() {
        $query = $this->pdo->prepare(
            "SELECT name, projectname, status
            FROM {$this->tableTasks}
            WHERE id = :id"
        );
        $query->execute(array(
            "id" => $this->taskId,
        ));

        $result = $query->fetchObject();
        if (empty($result)) {
            return false;
        }

        $this->taskName = $result->name;
        $this->projectName = $result->projectname;
        $this->status = $result->status;

        return $this;
    }
    // }}}
    // {{{ loadSubtasks()
    private function loadSubtasks() {
        $this->lastCheck = time();

        $query = $this->pdo->prepare(
            "SELECT *
            FROM {$this->tableSubtasks}
            WHERE
                task_id = :taskId AND
                status IS NULL
            ORDER BY id ASC
            LIMIT $this->numberOfSubtasks"
        );
        $query->execute(array(
            "taskId" => $this->taskId,
        ));

        $subtasks = $query->fetchAll(\PDO::FETCH_OBJ);
        $this->subtasks = array();

        foreach ($subtasks as $subtask) {
            if (empty($subtask->status)) {
                $this->subtasks[$subtask->id] = $subtask;
                $this->includeDependentSubtask($subtask);
            }
        }

        ksort($this->subtasks);

        //$this->generateTaskRunFile();
    }
    // }}}
    // {{{ loadSubtaskById()
    private function loadSubtaskById($id) {
        $query = $this->pdo->prepare(
            "SELECT *
            FROM {$this->tableSubtasks}
            WHERE
                id = :id AND
                task_id = :taskId
            LIMIT 1"
        );
        $query->execute(array(
            "id" => $id,
            "taskId" => $this->taskId,
        ));

        $subtask = $query->fetchObject();

        return $subtask;
    }
    // }}}
    // {{{ includeDependentSubtask()
    private function includeDependentSubtask($subtask) {
        while ($subtask->depends_on && !isset($this->subtasks[$subtask->depends_on])) {
            $subtask = $this->loadSubtaskById($subtask->depends_on);
            if (!isset($this->subTasksRun[$subtask->id])) {
                $this->subtasks[$subtask->id] = $subtask;
            }
        }
    }
    // }}}

    // {{{ generateTaskRunFile()
    /**
     * @brief generateTaskRunFile
     *
     * @param mixed
     * @return void
     **/
    public function generateTaskRunFile()
    {
        if (count($this->subtasks) == 0) {
            return;
        }

        $file = "logs/task-" . uniqid() . ".php";

        $fp = fopen($file, "w");

        fwrite($fp, "<?php\n");
        foreach ($this->subtasks as $t) {
            fwrite($fp, $t->php . "\n");
        }

        fclose($fp);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
