<?php
use \Depage\Html\Html;

if (count($this->tasks) > 0) {
?>
    <dl class="tasks" data-ajax-update-timeout="1000">
<?php

$timeFormatter = new \Depage\Formatters\TimeNatural();

foreach($this->tasks as $task) {
    $progress = $task->getProgress();
    $name = "";
    $class = "";
    if (!empty($task->projectName)) {
        $name .= $task->projectName . " / ";
    }
    $name .= $task->taskName;
    if ($task->status == "failed") {
        $status = sprintf(_("\n'%s' failed:\n%s"), $progress->description, $progress->status);
        $class = "failed";
    } else if ($progress->percent < 2) {
        $status = sprintf(_("'%s' starting"), $progress->description);
        $class = "running";
    } else {
        $status = sprintf(_("'%s' will finish in %s"), $progress->description, $timeFormatter->format($progress->estimated));
        $class = "running";
    }

    ?>
        <dt class="<?php Html::t($class); ?>"><?php Html::t($progress->percent); ?>%</dt>
        <dd class="<?php Html::t($class); ?>">
            <progress value="<?php Html::e($progress->percent); ?>" max="100"></progress>
            <p><strong><?php Html::t($name); ?></strong> <?php Html::t($status, true); ?></p>
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
