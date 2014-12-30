<?php
use \Depage\Html\Html;

if (count($this->tasks) > 0) {
?>
    <dl class="tasks">
<?php

foreach($this->tasks as $task) {
    $progress = $task->getProgress();
    $name = "";
    if (!empty($task->projectName)) {
        $name .= $task->projectName . " / ";
    }
    $name .= $task->taskName;
    ?>
        <dt><?php Html::t($name); ?></dt>
        <dd>
            <progress value="<?php Html::e($progress->percent); ?>" max="100"></progress>
            <p><?php Html::t(sprintf(_("'%s' will finish in %s sec"), $progress->description, $progress->estimated)); ?></p>
        <dd>
    <?php
}
?>
    </dl>
<?php
} else {
?>
    <p><?php Html::t(_("No current tasks.")); ?></p>
<?php
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
