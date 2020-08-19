<?php
/**
 * @file    Task.php
 *
 * description
 *
 * copyright (c) 2020 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Api;

/**
 * @brief Task
 * Class Task
 */
class Task extends Json
{
    protected $autoEnforceAuth = false;

    // {{{ delete()
    /**
     * @brief delete
     *
     * @return object
     **/
    public function delete()
    {
        $retVal = [
            'success' => false,
        ];
        $taskToDelete = filter_input(INPUT_POST, 'taskId', FILTER_SANITIZE_NUMBER_INT);

        if ($task = \Depage\Tasks\Task::load($this->pdo, $taskToDelete)) {
            $retVal['success'] = $task->remove();
        }

        return $retVal;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :

