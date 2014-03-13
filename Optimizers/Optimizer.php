<?php

namespace Depage\Graphics\Optimizers;

abstract class Optimizer
{
    protected $executable = '';
    protected $command = '';

    protected function execCommand()
    {
        exec($this->command . ' 2>&1', $commandOutput, $returnStatus);
        if ($returnStatus != 0) {
            throw new \Depage\Graphics\Exceptions\Exception(implode("\n", $commandOutput));
        }

        return true;
    }
}
