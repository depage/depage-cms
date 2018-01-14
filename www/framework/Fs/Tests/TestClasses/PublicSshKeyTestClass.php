<?php

namespace Depage\Fs\Tests\TestClasses;

use Depage\Fs\PublicSshKey;

class PublicSshKeyTestClass extends PublicSshKey
{
    public function file_put_contents($filename, $data, $flags = 0, $context = null)
    {
        if ($data === 'writeFail!') {
            return false;
        } else {
            return parent::file_put_contents($filename, $data, $flags, $context);
        }
    }
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
