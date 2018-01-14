<?php

namespace Depage\Fs\Tests\TestClasses;

class FsSshTestClass extends \Depage\Fs\FsSsh
{
    public $privateKeyFile;
    public $publicKeyFile;
    public $privateKey;
    public $publicKey;
    public $tmp;

    public function isValidKeyCombination()
    {
        return parent::isValidKeyCombination();
    }
}
