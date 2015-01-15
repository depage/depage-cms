<?php
use \Depage\Html\Html;

if (count($this->tasks) > 0) {
?>
    <dl class="tasks" data-ajax-update-timeout="1000">
<?php

foreach($this->tasks as $task) {
    $progress = $task->getProgress();
    $name = "";
    if (!empty($task->projectName)) {
        $name .= $task->projectName . " / ";
    }
    $name .= $task->taskName;
    if ($task->status == "failed") {
        $status = sprintf(_("%s%% / '%s' failed with error '%s'"), $progress->percent, $progress->description, $progress->status);
    } else {
        $status = sprintf(_("%s%% / '%s' will finish in %s sec"), $progress->percent, $progress->description, $progress->estimated);
    }

    ?>
        <dt><?php Html::t($name); ?></dt>
        <dd>
            <progress value="<?php Html::e($progress->percent); ?>" max="100"></progress>
            <p><?php Html::t($status); ?></p>
            <?php
                $element = $this->taskForm->getElement("taskId");
                $element->setDefaultValue($task->taskId);

                Html::e($this->taskForm);
            ?>
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
