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
    public $status = "";

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
    protected $numberOfSubtasks = 100;


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
            case 'array':
                return "unserialize(" . var_export(serialize($param), true) . ")";
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

        $files = glob(__DIR__ . "/Sql/*.sql");
        sort($files);
        foreach ($files as $file) {
            $schema->loadFile($file);
            $schema->update();
        }
    }
    // }}}

    // public functions
    // {{{ remove()
    public function remove() {
        $query = $this->pdo->prepare(
            "DELETE FROM {$this->tableTasks}
            WHERE id = :id"
        );
        $query->execute(array(
            "id" => $this->taskId,
        ));
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
        foreach ($this->tmpvars as $_tmpindex => $_tmpvar) {
            $$_tmpindex = $_tmpvar;
        }

        // evaluate statement
        $value = eval($subtask->php);

        // unset internal variables
        unset($subtask, $_tmpindex, $_tmpvar);
        $this->tmpvars = get_defined_vars();

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

    // {{{ addSubtask()
    /* addSubtask only creates task in db.
     * the current instance is NOT modified.
     * reload task from the db if you want to execute subtasks.
     *
     * also see addSubtasks for more convenience.
     *
     * @return int return id of created subtask that can be used for depends_on
     */
    public function addSubtask($name, $php, $depends_on = NULL) {
        $query = $this->pdo->prepare(
            "INSERT INTO {$this->tableSubtasks}
                (task_id, name, php, depends_on) VALUES (:taskId, :name, :php, :depends_on)"
        );
        $query->execute(array(
            "taskId" => $this->taskId,
            "name" => $name,
            "php" => $php,
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

            $task["id"] = $this->addSubtask($task["name"], $task["php"], $depends_on);
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

        $progress['estimated'] = (int) (($result->time / $tasksDone) * $tasksPlanned);
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
                (name, projectname, time_added) VALUES (:name, :projectName,  NOW())"
        );
        $query->execute(array(
            "name" => $taskName,
            "projectName" => $projectName,
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
                $this->includeDependentSubtask($subtask, $id_to_subtask);
            }
        }

        ksort($this->subtasks);
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
    private function includeDependentSubtask($subtask, &$id_to_subtask) {
        while ($subtask->depends_on && !isset($this->subtasks[$subtask->depends_on])) {
            $subtask = $this->loadSubtaskById($subtask->depends_on);
            $this->subtasks[$subtask->id] = $subtask;
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
