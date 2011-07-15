<?php

namespace depage\task;

class task {
    public function __construct($task_name, $table_prefix, $pdo) {
        $this->task_name = $task_name;
        $this->task_table = $table_prefix . "_tasks";
        $this->subtask_table = $table_prefix . "_subtasks";
        $this->pdo = $pdo;
        $this->lock_name = sys_get_temp_dir() . '/' . $table_prefix . "." . $task_name . '.lock';

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

    public function lock() {
        $this->lock_file = fopen($this->lock_name, 'w');
        return flock($this->lock_file, LOCK_EX | LOCK_NB);
    }

    public function unlock() {
        flock($this->lock_file, LOCK_UN);
    }

    private function load_task() {
        $query = $this->pdo->prepare("SELECT id FROM {$this->task_table} WHERE $task_name = :task_name");
        $query->execute(array(
            "task_name" => $this->task_name,
        ));

        $result = $query->fetchObject();
        $this->task_id = $result->id; 
    }
    
    private function load_subtasks() {
        $query = $this->pdo->prepare(
            "SELECT id, php, depends_on, status
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

            if (!$subtask->status) {
                $this->subtasks[$subtask->id] = $subtask;
                $this->include_dependent_subtasks($subtask, $id_to_subtask);
            }
        }
    }

    private function include_dependent_subtasks($subtask, &$id_to_subtask) {
        while ($subtask->depends_on) {
            $subtask = $id_to_subtask[$subtask->depends_on];
            $this->subtasks[$subtask->id] = $subtask; 
        }
    }
}
    
    
/* vim:set ft=php sw=4 sts=4 fdm=marker : */
