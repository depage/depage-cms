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
    
    // {{{ load()
    static public function load($task_id, $table_prefix, $pdo) {
        $task = new task($table_prefix, $pdo);

        $task->task_id = $task_id;

        $task->lock_name = sys_get_temp_dir() . '/' . $table_prefix . "." . $task->task_id . '.lock';

        $task->loadTask();
        $task->loadSubtasks();

        return $task;
    }
    // }}}
    // {{{ create()
    static public function create($task_name, $table_prefix, $pdo) {
        $task = new task($table_prefix, $pdo);

        $task->task_id = $task->createTask($task_name);
        $task->lock_name = sys_get_temp_dir() . '/' . $table_prefix . "." . $task->task_id . '.lock';

        $task->loadTask();
        $task->loadSubtasks();

        return $task;
    }
    // }}}
    
    // {{{ setTaskStatus()
    public function setTaskStatus($status) {
        $query = $this->pdo->prepare("UPDATE {$this->task_table} SET status = :status WHERE id = :id");
        $query->execute(array(
            "status" => $status,
            "id" => $this->task_id,
        ));
    }
    // }}}
    // {{{ setSubtaskStatus()
    public function setSubtaskStatus($subtask, $status) {
        $query = $this->pdo->prepare("UPDATE {$this->subtask_table} SET status = :status WHERE id = :id");
        $query->execute(array(
            "status" => $status,
            "id" => $subtask->id,
        ));
    }
    // }}}
    // {{{ getNextSubtask();
    public function getNextSubtask() {
        $subtask = current($this->subtasks);
        next($this->subtasks);

        return $subtask;
    }
    // }}}
    // {{{ runSubtask()
    /* @return NULL|false returns NULL for no error, false for a parse error */
    public function runSubtask($subtask) {
        // readd local variables
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
        $this->lock_file = fopen($this->lock_name, 'w');
        return flock($this->lock_file, LOCK_EX | LOCK_NB);
    }
    // }}}
    // {{{ unlock()
    public function unlock() {
        flock($this->lock_file, LOCK_UN);
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
        $query = $this->pdo->prepare("INSERT INTO {$this->subtask_table} (task_id, name, php, depends_on) VALUES (:task_id, :name, :php, :depends_on)");
        $query->execute(array(
            "task_id" => $this->task_id,
            "name" => $name,
            "php" => $php,
            "depends_on" => $depends_on,
        ));

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
    
    // private functions
    // {{{ createTask()
    private function createTask($task_name) {
        $query = $this->pdo->prepare("INSERT INTO {$this->task_table} (name) VALUES (:name)");
        $query->execute(array(
            "name" => $task_name,
        ));

        return $this->pdo->lastInsertId();
    }
    // }}}
    // {{{ loadTask();
    private function loadTask() {
        $query = $this->pdo->prepare("SELECT name, status FROM {$this->task_table} WHERE id = :id");
        $query->execute(array(
            "id" => $this->task_id,
        ));

        $result = $query->fetchObject();
        if (empty($result)) {
            throw new \Exception("no such task");
        }
        if (!empty($result->status)) {
            throw new \Exception("task was already run.");
        }

        $this->task_name = $result->name;
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
