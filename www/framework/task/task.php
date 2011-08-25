<?php

namespace depage\task;

class task {
    public function __construct($task_id, $table_prefix, $pdo) {
        $this->task_id = $task_id;
        $this->task_table = $table_prefix . "_tasks";
        $this->subtask_table = $table_prefix . "_subtasks";
        $this->pdo = $pdo;
        $this->lock_name = sys_get_temp_dir() . '/' . $table_prefix . "." . $task_id . '.lock';

        $this->load_task();
        $this->load_subtasks();
    }

    public function set_task_status($status) {
        $query = $this->pdo->prepare("UPDATE {$this->task_table} SET status = :status WHERE id = :id");
        $query->execute(array(
            "status" => $status,
            "id" => $this->task_id,
        ));
    }

    public function set_subtask_status($subtask, $status) {
        $query = $this->pdo->prepare("UPDATE {$this->subtask_table} SET status = :status WHERE id = :id");
        $query->execute(array(
            "status" => $status,
            "id" => $subtask->id,
        ));
    }

    public function get_next_subtask() {
        $subtask = current($this->subtasks);
        next($this->subtasks);

        return $subtask;
    }
    
    public function run_subtask($subtask) {
        eval($subtask->php);
    }
    
    public function lock() {
        $this->lock_file = fopen($this->lock_name, 'w');
        return flock($this->lock_file, LOCK_EX | LOCK_NB);
    }

    public function unlock() {
        flock($this->lock_file, LOCK_UN);
    }

    private function load_task() {
        $query = $this->pdo->prepare("SELECT name FROM {$this->task_table} WHERE id = :id");
        $query->execute(array(
            "id" => $this->task_id,
        ));

        $result = $query->fetchObject();
        if (empty($result))
            throw new \Exception("no such task");
        
        $this->task_name = $result->name; 
    }
    
    private function load_subtasks() {
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
                $this->include_dependent_subtasks($subtask, $id_to_subtask);
            }
        }
        
        ksort($this->subtasks);
    }

    private function include_dependent_subtasks($subtask, &$id_to_subtask) {
        while ($subtask->depends_on) {
            $subtask = $id_to_subtask[$subtask->depends_on];
            $this->subtasks[$subtask->id] = $subtask; 
        }
    }
}
    
    
/* vim:set ft=php sw=4 sts=4 fdm=marker : */
