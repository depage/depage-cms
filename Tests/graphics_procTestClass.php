<?php

/**
 * Override imagemagick class to access protected methods/attributes in
 * tests
 **/
class graphics_procTestClass extends \Depage\Graphics\Providers\Imagemagick
{
    public function getCommand()
    {
        return $this->command;
    }

    public function setCommand($command)
    {
        $this->command = $command;
    }

    public function execCommand()
    {
        return parent::execCommand();
    }
}
