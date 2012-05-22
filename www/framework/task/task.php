<?php
/**
 * @file    framework/task/task_runner.php
 *
 * depage cms task runner module
 *
 *
 * copyright (c) 2011 Lion Vollnhals [lion.vollnhals@googlemail.com]
 * copyright (c) 2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 * @author    Lion Vollnhals [lion.vollnhals@googlemail.com]
 */

namespace depage\task;

class task {
    private $tmpvars = array();

    // {{{ constructor
    private function __construct($table_prefix, $pdo) {
        $this->task_table = $table_prefix . "_tasks";
        $this->subtask_table = $table_prefix . "_subtasks";
        $this->pdo = $pdo;

    }
    // }}}
    
    // static functions
    // {{{ load()
    static public function load($task_id, $table_prefix, $pdo) {
        $task = new task($table_prefix, $pdo);

        $task->task_id = $task_id;

        $task->lock_name = sys_get_temp_dir() . '/' . $table_prefix . "." . $task->task_id . '.lock';

        if ($task->loadTask()) {
            $task->loadSubtasks();

            return $task;
        } else {
            return false;
        }
    }
    // }}}
    // {{{ loadByName()
    static public function loadByName($task_name, $table_prefix, $pdo) {
        $task = new task($table_prefix, $pdo);

        $query = $pdo->prepare(
            "SELECT id 
            FROM {$task->task_table} 
            WHERE name = :name"
        );
        $query->execute(array(
            "name" => $task_name,
        ));

        $tasks = array();

        while ($result = $query->fetchObject()) {
            $tasks[] = task::load($result->id, $table_prefix, $pdo);
        }

        if (count($tasks) == 0) {
            return false;
        } else {
            return $tasks;
        }
    }
    // }}}
    // {{{ loadAll()
    static public function loadAll($table_prefix, $pdo) {
        $task = new task($table_prefix, $pdo);

        $query = $pdo->prepare(
            "SELECT id 
            FROM {$task->task_table}"
        );
        $query->execute();

        $tasks = array();

        while ($result = $query->fetchObject()) {
            $tasks[] = task::load($result->id, $table_prefix, $pdo);
        }

        if (count($tasks) == 0) {
            return false;
        } else {
            return $tasks;
        }
    }
    // }}}
    // {{{ loadOrCreate()
    static public function loadOrCreate($task_name, $table_prefix, $pdo) {
        list($task) = self::loadByName($task_name, $table_prefix, $pdo);

        if (!$task) {
            $task = self::create($task_name, $table_prefix, $pdo);
        }

        return $task;
    }
    // }}}
    // {{{ create()
    static public function create($task_name, $table_prefix, $pdo) {
        $task = new task($table_prefix, $pdo);

        $task->task_id = $task->createTask($task_name);
        $task->lock_name = sys_get_temp_dir() . '/' . $table_prefix . "." . $task->task_id . '.lock';

        $task->loadTask();

        return $task;
    }
    // }}}
    
    // {{{ escapeParam()
    static public function escapeParam($param) {
        return "unserialize('" . serialize($param) . "')";
    }
    // }}}
    
    // public functions
    // {{{ remove()
    public function remove() {
        $query = $this->pdo->prepare(
            "DELETE FROM {$this->task_table} 
            WHERE id = :id"
        );
        $query->execute(array(
            "id" => $this->task_id,
        ));
    }
    // }}}
    
    // {{{ setTaskStatus()
    public function setTaskStatus($status) {
        $query = $this->pdo->prepare(
            "UPDATE {$this->task_table} 
            SET status = :status 
            WHERE id = :id"
        );
        $query->execute(array(
            "status" => $status,
            "id" => $this->task_id,
        ));
    }
    // }}}
    // {{{ setSubtaskStatus()
    public function setSubtaskStatus($subtask, $status) {
        $query = $this->pdo->prepare(
            "UPDATE {$this->subtask_table} 
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
        $this->lock_fp = fopen($this->lock_name, 'w');

        $locked = flock($this->lock_fp, LOCK_EX | LOCK_NB);

        if ($locked) {
            $query = $this->pdo->prepare(
                "UPDATE {$this->task_table} 
                SET time_started = NOW() 
                WHERE 
                    id = :id AND 
                    time_started IS NULL"
            );
            $query->execute(array(
                "id" => $this->task_id,
            ));
        }

        return $locked;
    }
    // }}}
    // {{{ unlock()
    public function unlock() {
        if (isset($this->lock_fp)) {
            flock($this->lock_fp, LOCK_UN);

            unlink($this->lock_name);
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
            "INSERT INTO {$this->subtask_table} 
                (task_id, name, php, depends_on) VALUES (:task_id, :name, :php, :depends_on)"
        );
        $query->execute(array(
            "task_id" => $this->task_id,
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
            "SELECT COUNT(*) AS count, status
            FROM {$this->subtask_table}
            WHERE task_id = :task_id
            GROUP BY status"
        );
        $query->execute(array(
            "task_id" => $this->task_id,
        ));
        $result = $query->fetchALL(\PDO::FETCH_COLUMN);

        $tasksPlanned = $result[0];
        $tasksDone = $result[1];
        $tasksSum = $tasksPlanned + $tasksDone;

        $progress['percent'] = (int) ($tasksDone / $tasksSum * 100);
        // }}}
        // {{{ get estimated times
        $query = $this->pdo->prepare(
            "SELECT UNIX_TIMESTAMP(time_started) AS time_started, TIMESTAMPDIFF(SECOND, time_started, NOW()) AS time
            FROM {$this->task_table}
            WHERE id = :task_id"
        );
        $query->execute(array(
            "task_id" => $this->task_id,
        ));
        $result = $query->fetchObject();

        $progress['estimated'] = (int) (($result->time / $tasksDone) * $tasksPlanned);
        $progress['time_started'] = (int) $result->time_started;
        // }}}
        // {{{ get name of running subtask
        $query = $this->pdo->prepare(
            "SELECT name
            FROM {$this->subtask_table}
            WHERE 
                task_id = :task_id AND
                status IS NULL
            ORDER BY id ASC
            LIMIT 1"
        );
        $query->execute(array(
            "task_id" => $this->task_id,
        ));
        $result = $query->fetchObject();

        $progress['description'] = $result->name;
        // }}}

        return (object) $progress;
    }
    // }}}
    
    // private functions
    // {{{ createTask()
    private function createTask($task_name) {
        $query = $this->pdo->prepare(
            "INSERT INTO {$this->task_table} 
                (name, time_added) VALUES (:name, NOW())"
        );
        $query->execute(array(
            "name" => $task_name,
        ));

        return $this->pdo->lastInsertId();
    }
    // }}}
    // {{{ loadTask();
    private function loadTask() {
        $query = $this->pdo->prepare(
            "SELECT name, status 
            FROM {$this->task_table} 
            WHERE id = :id"
        );
        $query->execute(array(
            "id" => $this->task_id,
        ));

        $result = $query->fetchObject();
        if (empty($result)) {
            return false;
        }

        $this->task_name = $result->name;
        $this->status = $result->status;

        return $this;
    }
    // }}}
    // {{{ loadSubtasks()
    private function loadSubtasks() {
        $query = $this->pdo->prepare(
            "SELECT *
            FROM {$this->subtask_table}
            WHERE task_id = :task_id
            ORDER BY id ASC"
        );
        $query->execute(array(
            "task_id" => $this->task_id,
        ));

        $subtasks = $query->fetchAll(\PDO::FETCH_OBJ);
        $id_to_subtask = array();
        $this->subtasks = array();

        foreach ($subtasks as $subtask) {
            $id_to_subtask[$subtask->id] = $subtask;

            if (empty($subtask->status)) {
                $this->subtasks[$subtask->id] = $subtask;
                $this->includeDependentSubtask($subtask, $id_to_subtask);
            }
        }

        ksort($this->subtasks);
    }
    // }}}
    // {{{ includeDependentSubtask()
    private function includeDependentSubtask($subtask, &$id_to_subtask) {
        while ($subtask->depends_on) {
            $subtask = $id_to_subtask[$subtask->depends_on];
            $this->subtasks[$subtask->id] = $subtask;
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
